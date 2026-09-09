<?php

require_once __DIR__ . '/../modelos/Usuarios.modelos.php';

$baseUrl = 'http://localhost/LalyPets/';
$sufijo = bin2hex(random_bytes(4));
$correoAdmin = "prueba.admin.$sufijo@example.test";
$correoUsuario = "prueba.usuario.$sufijo@example.test";
$passwordAdmin = 'TemporalAdmin_2026!';
$passwordUsuario = 'TemporalUser_2026!';
$idsTemporales = [];

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK - $mensaje\n";
}

function cliente(): CurlHandle
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => '',
        CURLOPT_TIMEOUT => 15,
    ]);
    return $curl;
}

function solicitar(CurlHandle $curl, string $url, ?array $datos = null): array
{
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, $datos !== null);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $datos ?? '');
    $respuesta = curl_exec($curl);
    if ($respuesta === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $cabeceraTamano = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    return [
        'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'headers' => substr($respuesta, 0, $cabeceraTamano),
        'body' => substr($respuesta, $cabeceraTamano),
    ];
}

function csrf(string $html): string
{
    if (!preg_match('/name=["\']csrf-token["\'] content=["\']([^"\']+)["\']/', $html, $coincidencia)
        && !preg_match('/name=["\']csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $coincidencia)) {
        throw new RuntimeException('No se encontró el token CSRF.');
    }
    return html_entity_decode($coincidencia[1], ENT_QUOTES, 'UTF-8');
}

try {
    $anonimo = cliente();
    $sinSesion = solicitar($anonimo, $baseUrl . 'ajax/usuarios.ajax.php', ['IDusuario' => '1']);
    comprobar($sinSesion['status'] === 401, 'El AJAX rechaza solicitudes sin sesión');
    curl_close($anonimo);

    $resultado = ModeloUsuarios::mdlCrearUsuario([
        'nombre' => 'Admin', 'apellido' => 'Temporal', 'telefono' => '70000001',
        'correo' => $correoAdmin, 'password' => password_hash($passwordAdmin, PASSWORD_DEFAULT),
        'turno' => 'Mañana', 'foto' => '', 'IDrol' => 1,
    ]);
    comprobar($resultado === 'ok', 'Se preparó el administrador temporal');
    $admin = ModeloUsuarios::mdlBuscarPorCorreo($correoAdmin);
    $idsTemporales[] = (int) $admin['IDusuario'];

    $navegador = cliente();
    $inicio = solicitar($navegador, $baseUrl);
    $token = csrf($inicio['body']);
    $login = solicitar($navegador, $baseUrl, [
        'csrf_token' => $token, 'imgEmail' => $correoAdmin, 'imgPassword' => $passwordAdmin,
    ]);
    comprobar($login['status'] === 302 && stripos($login['headers'], 'Location: Inicio') !== false,
        'El inicio de sesión por correo redirige correctamente');

    $paginaUsuarios = solicitar($navegador, $baseUrl . 'Usuarios');
    comprobar($paginaUsuarios['status'] === 200 && str_contains($paginaUsuarios['body'], $correoAdmin),
        'La sesión administrativa abre el módulo Usuarios');
    $token = csrf($paginaUsuarios['body']);
    $csrfInvalido = solicitar($navegador, $baseUrl . 'ajax/usuarios.ajax.php', [
        'csrf_token' => 'token-invalido', 'IDusuario' => (string) $idsTemporales[0],
    ]);
    comprobar($csrfInvalido['status'] === 403, 'El AJAX rechaza un token CSRF inválido');

    $alta = solicitar($navegador, $baseUrl . 'Usuarios', [
        'csrf_token' => $token, 'nuevoNombre' => 'Usuario', 'nuevoApellido' => 'Temporal',
        'nuevoTelefono' => '70000002', 'nuevoEmail' => $correoUsuario,
        'nuevoPassword' => $passwordUsuario, 'nuevoTurno' => 'Tarde', 'nuevoRol' => '2',
    ]);
    comprobar($alta['status'] === 302, 'El alta de usuario usa POST/Redirect/GET');
    $usuario = ModeloUsuarios::mdlBuscarPorCorreo($correoUsuario);
    comprobar(is_array($usuario) && password_verify($passwordUsuario, $usuario['password']),
        'El usuario se guardó y su contraseña quedó cifrada');
    $idUsuario = (int) $usuario['IDusuario'];
    $idsTemporales[] = $idUsuario;

    $ajax = solicitar($navegador, $baseUrl . 'ajax/usuarios.ajax.php', [
        'csrf_token' => $token, 'IDusuario' => (string) $idUsuario,
    ]);
    $json = json_decode($ajax['body'], true);
    comprobar($ajax['status'] === 200 && is_array($json) && !array_key_exists('password', $json),
        'El AJAX consulta usuarios sin exponer la contraseña');

    $edicion = solicitar($navegador, $baseUrl . 'Usuarios', [
        'csrf_token' => $token, 'IDusuario' => (string) $idUsuario,
        'editarNombre' => 'Usuario Editado', 'editarApellido' => 'Temporal',
        'editarTelefono' => '70000003', 'editarEmail' => $correoUsuario,
        'editarPassword' => '', 'editarTurno' => 'Horario continuo', 'editarRol' => '3',
    ]);
    $editado = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
    comprobar($edicion['status'] === 302 && $editado['nombre_U'] === 'Usuario Editado'
        && (int) $editado['IDrol'] === 3, 'La edición actualiza los campos del esquema nuevo');

    $baja = solicitar($navegador, $baseUrl . 'Usuarios', [
        'csrf_token' => $token, 'IDusuario' => (string) $idUsuario, 'estadoUsuario' => '0',
    ]);
    $deshabilitado = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
    comprobar($baja['status'] === 302 && (int) $deshabilitado['estado_U'] === 0,
        'La cuenta se deshabilita sin eliminarse');

    $otroNavegador = cliente();
    $otroInicio = solicitar($otroNavegador, $baseUrl);
    $loginBloqueado = solicitar($otroNavegador, $baseUrl, [
        'csrf_token' => csrf($otroInicio['body']), 'imgEmail' => $correoUsuario,
        'imgPassword' => $passwordUsuario,
    ]);
    comprobar($loginBloqueado['status'] === 302 && stripos($loginBloqueado['headers'], 'Location: ./') !== false,
        'Una cuenta deshabilitada no puede iniciar sesión');
    curl_close($otroNavegador);

    $altaEstado = solicitar($navegador, $baseUrl . 'Usuarios', [
        'csrf_token' => $token, 'IDusuario' => (string) $idUsuario, 'estadoUsuario' => '1',
    ]);
    $rehabilitado = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
    comprobar($altaEstado['status'] === 302 && (int) $rehabilitado['estado_U'] === 1,
        'La cuenta puede habilitarse nuevamente');

    $salida = solicitar($navegador, $baseUrl . 'salir');
    $adminFuera = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idsTemporales[0]);
    comprobar($salida['status'] === 302 && (int) $adminFuera['sesion_U'] === 0,
        'El cierre de sesión actualiza el estado en la base');
    curl_close($navegador);
} finally {
    if ($idsTemporales !== []) {
        $marcadores = implode(',', array_fill(0, count($idsTemporales), '?'));
        $stmt = Conexion::conectar()->prepare("DELETE FROM usuarios WHERE IDusuario IN ($marcadores)");
        $stmt->execute($idsTemporales);
    }
}

echo "PRUEBAS_USUARIOS_COMPLETADAS\n";
