<?php

require_once __DIR__ . '/../modelos/Usuarios.modelos.php';
require_once __DIR__ . '/../modelos/Clientes.modelos.php';
require_once __DIR__ . '/../modelos/Mascotas.modelos.php';

$baseUrl = 'http://localhost/LalyPets/';
$sufijo = bin2hex(random_bytes(4));
$correoAdmin = "prueba.cm.$sufijo@example.test";
$passwordAdmin = 'TemporalCM_2026!';
$idsUsuarios = [];
$idsClientes = [];
$idsMascotas = [];

function verificarCM(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK - $mensaje\n";
}

function clienteHttpCM(): CurlHandle
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

function solicitarCM(CurlHandle $curl, string $url, ?array $datos = null): array
{
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, $datos !== null);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $datos ?? '');
    $respuesta = curl_exec($curl);
    if ($respuesta === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $tamanoCabecera = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    return [
        'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'headers' => substr($respuesta, 0, $tamanoCabecera),
        'body' => substr($respuesta, $tamanoCabecera),
    ];
}

function csrfCM(string $html): string
{
    if (!preg_match('/name=["\']csrf-token["\'] content=["\']([^"\']+)["\']/', $html, $resultado)
        && !preg_match('/name=["\']csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $resultado)) {
        throw new RuntimeException('No se encontró el token CSRF.');
    }
    return html_entity_decode($resultado[1], ENT_QUOTES, 'UTF-8');
}

try {
    $anonimo = clienteHttpCM();
    verificarCM(solicitarCM($anonimo, $baseUrl . 'ajax/clientes.ajax.php', ['IDcliente' => '1'])['status'] === 401,
        'Clientes AJAX exige una sesión');
    verificarCM(solicitarCM($anonimo, $baseUrl . 'ajax/mascotas.ajax.php', ['IDmascota' => '1'])['status'] === 401,
        'Mascotas AJAX exige una sesión');
    curl_close($anonimo);

    verificarCM(ModeloUsuarios::mdlCrearUsuario([
        'nombre' => 'Admin', 'apellido' => 'Temporal CM', 'telefono' => '70000101',
        'correo' => $correoAdmin, 'password' => password_hash($passwordAdmin, PASSWORD_DEFAULT),
        'turno' => 'Mañana', 'foto' => '', 'IDrol' => 1,
    ]) === 'ok', 'Se preparó el administrador temporal');
    $admin = ModeloUsuarios::mdlBuscarPorCorreo($correoAdmin);
    $idsUsuarios[] = (int) $admin['IDusuario'];

    $navegador = clienteHttpCM();
    $inicio = solicitarCM($navegador, $baseUrl);
    $login = solicitarCM($navegador, $baseUrl, [
        'csrf_token' => csrfCM($inicio['body']), 'imgEmail' => $correoAdmin,
        'imgPassword' => $passwordAdmin,
    ]);
    verificarCM($login['status'] === 302, 'Se inició una sesión de prueba');

    $paginaClientes = solicitarCM($navegador, $baseUrl . 'Clientes');
    $token = csrfCM($paginaClientes['body']);
    verificarCM($paginaClientes['status'] === 200 && str_contains($paginaClientes['body'], 'Clientes registrados'),
        'Clientes se muestra como módulo independiente');

    $nombreCliente = 'Cliente Temporal';
    $altaCliente = solicitarCM($navegador, $baseUrl . 'Clientes', [
        'csrf_token' => $token, 'nuevoCliente' => $nombreCliente, 'nuevoApellidoc' => 'Temporal',
        'nuevoTelefono' => '70000102', 'nuevoRegistro' => '2026-08-30',
    ]);
    verificarCM($altaCliente['status'] === 302, 'El alta de cliente usa POST/Redirect/GET');
    $stmt = Conexion::conectar()->prepare('SELECT * FROM clientes WHERE telefono_c = :telefono ORDER BY IDcliente DESC LIMIT 1');
    $stmt->execute([':telefono' => '70000102']);
    $cliente = $stmt->fetch();
    verificarCM(is_array($cliente) && $cliente['telefono_c'] === '70000102',
        'El cliente se guardó con las columnas del esquema nuevo');
    $idCliente = (int) $cliente['IDcliente'];
    $idsClientes[] = $idCliente;

    $ajaxCliente = solicitarCM($navegador, $baseUrl . 'ajax/clientes.ajax.php', [
        'csrf_token' => $token, 'IDcliente' => (string) $idCliente,
    ]);
    $jsonCliente = json_decode($ajaxCliente['body'], true);
    verificarCM($ajaxCliente['status'] === 200 && $jsonCliente['IDcliente'] === $idCliente,
        'El AJAX de Clientes devuelve el registro correcto');

    solicitarCM($navegador, $baseUrl . 'Clientes', [
        'csrf_token' => $token, 'IDcliente' => (string) $idCliente,
        'editarCliente' => $nombreCliente, 'editarApellidoc' => 'Editado',
        'editarTelefono' => '70000103', 'editarRegistro' => '2026-08-29',
    ]);
    $clienteEditado = ModeloClientes::mdlMostrarClientes('IDcliente', $idCliente);
    verificarCM($clienteEditado['apellidoc'] === 'Editado' && $clienteEditado['telefono_c'] === '70000103',
        'La edición de Cliente actualiza los campos correctos');

    $paginaMascotas = solicitarCM($navegador, $baseUrl . 'Mascotas');
    verificarCM($paginaMascotas['status'] === 200
        && str_contains($paginaMascotas['body'], 'Mascotas registradas')
        && !str_contains($paginaMascotas['body'], 'name="nuevaFoto"'),
        'Mascotas se muestra por separado y ya no solicita fotografía');

    $razas = ModeloMascotas::mdlListarRazas();
    verificarCM(count($razas) >= 2, 'Existen razas relacionadas para realizar la prueba');
    $idRazaInicial = (int) $razas[0]['IDraza'];
    $idRazaEditada = (int) $razas[1]['IDraza'];
    $nombreMascota = "Mascota$sufijo";
    $altaMascota = solicitarCM($navegador, $baseUrl . 'Mascotas', [
        'csrf_token' => $token, 'nuevoMascota' => $nombreMascota,
        'nuevaFechaRegistro' => '2026-08-30', 'nuevaFechaNacimiento' => '2024-01-15',
        'nuevoPeso' => '4.75', 'nuevoSexo' => 'Hembra', 'nuevaDescripcion' => 'Prueba temporal',
        'nuevoColor' => 'Negro', 'nuevoCliente' => (string) $idCliente,
        'nuevaRaza' => (string) $idRazaInicial,
    ]);
    verificarCM($altaMascota['status'] === 302, 'El alta de Mascota usa POST/Redirect/GET');
    $stmt = Conexion::conectar()->prepare('SELECT IDmascota FROM mascotas WHERE nombrem = :nombre');
    $stmt->execute([':nombre' => $nombreMascota]);
    $idMascota = (int) $stmt->fetchColumn();
    verificarCM($idMascota > 0, 'La mascota quedó vinculada a su dueño y raza');
    $idsMascotas[] = $idMascota;

    $mascota = ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota);
    verificarCM((int) $mascota['IDcliente'] === $idCliente
        && (int) $mascota['IDraza'] === $idRazaInicial
        && $mascota['nombre_Especie'] !== '', 'La consulta relaciona mascota, cliente, raza y especie');

    $ajaxMascota = solicitarCM($navegador, $baseUrl . 'ajax/mascotas.ajax.php', [
        'csrf_token' => $token, 'IDmascota' => (string) $idMascota,
    ]);
    $jsonMascota = json_decode($ajaxMascota['body'], true);
    verificarCM($ajaxMascota['status'] === 200 && (int) $jsonMascota['IDraza'] === $idRazaInicial
        && !array_key_exists('fotoM', $jsonMascota),
        'El AJAX de Mascotas devuelve sus relaciones sin campos de fotografía');

    $porCliente = solicitarCM($navegador, $baseUrl . 'ajax/mascotas.ajax.php', [
        'csrf_token' => $token, 'IDcliente' => (string) $idCliente,
    ]);
    $lista = json_decode($porCliente['body'], true);
    verificarCM($porCliente['status'] === 200 && count($lista) === 1
        && (int) $lista[0]['IDmascota'] === $idMascota, 'Se listan mascotas habilitadas por cliente');

    $selectorCitas = solicitarCM($navegador, $baseUrl . 'ajax/citas.ajax.php', [
        'accion' => 'mascotas_cliente', 'IDcliente' => (string) $idCliente,
        'csrf_token' => $token,
    ]);
    $jsonCitas = json_decode($selectorCitas['body'], true);
    verificarCM($selectorCitas['status'] === 200 && count($jsonCitas['mascotas'] ?? []) === 1,
        'El selector de Citas conserva la consulta de mascotas por cliente');

    solicitarCM($navegador, $baseUrl . 'Mascotas', [
        'csrf_token' => $token, 'IDmascota' => (string) $idMascota,
        'editarMascota' => $nombreMascota, 'editarFechaRegistro' => '2026-08-29',
        'editarFechaNacimiento' => '2023-02-10', 'editarPeso' => '5.20',
        'editarSexo' => 'Hembra', 'editarDescripcion' => 'Descripción editada',
        'editarColor' => 'Negro azabache', 'editarCliente' => (string) $idCliente,
        'editarRaza' => (string) $idRazaEditada,
    ]);
    $mascotaEditada = ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota);
    verificarCM((int) $mascotaEditada['IDraza'] === $idRazaEditada
        && $mascotaEditada['peso'] === '5.20', 'La edición de Mascota actualiza datos y relaciones');

    solicitarCM($navegador, $baseUrl . 'Mascotas', [
        'csrf_token' => $token, 'IDmascota' => (string) $idMascota, 'estadoMascota' => '0',
    ]);
    verificarCM((int) ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota)['habilitadoM'] === 0,
        'La mascota se deshabilita sin borrarse');
    $listaDeshabilitada = ModeloMascotas::mdlListarMascotasPorCliente($idCliente);
    verificarCM($listaDeshabilitada === [], 'Una mascota deshabilitada no aparece en selectores operativos');
    solicitarCM($navegador, $baseUrl . 'Mascotas', [
        'csrf_token' => $token, 'IDmascota' => (string) $idMascota, 'estadoMascota' => '1',
    ]);
    verificarCM((int) ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota)['habilitadoM'] === 1,
        'La mascota puede habilitarse nuevamente');

    solicitarCM($navegador, $baseUrl . 'Clientes', [
        'csrf_token' => $token, 'IDcliente' => (string) $idCliente, 'estadoCliente' => '0',
    ]);
    verificarCM((int) ModeloClientes::mdlMostrarClientes('IDcliente', $idCliente)['habilitadoC'] === 0,
        'El cliente se deshabilita sin perder sus mascotas');
    $cantidadAntes = Conexion::conectar()->query('SELECT COUNT(*) FROM mascotas')->fetchColumn();
    solicitarCM($navegador, $baseUrl . 'Mascotas', [
        'csrf_token' => $token, 'nuevoMascota' => "Bloqueada$sufijo",
        'nuevaFechaRegistro' => '2026-08-30', 'nuevaFechaNacimiento' => '2025-01-01',
        'nuevoPeso' => '2.00', 'nuevoSexo' => 'Macho', 'nuevaDescripcion' => '',
        'nuevoColor' => '', 'nuevoCliente' => (string) $idCliente, 'nuevaRaza' => (string) $idRazaInicial,
    ]);
    $cantidadDespues = Conexion::conectar()->query('SELECT COUNT(*) FROM mascotas')->fetchColumn();
    verificarCM($cantidadAntes === $cantidadDespues,
        'No se asignan nuevas mascotas a un cliente deshabilitado');
    solicitarCM($navegador, $baseUrl . 'Clientes', [
        'csrf_token' => $token, 'IDcliente' => (string) $idCliente, 'estadoCliente' => '1',
    ]);
    verificarCM((int) ModeloClientes::mdlMostrarClientes('IDcliente', $idCliente)['habilitadoC'] === 1,
        'El cliente puede habilitarse nuevamente');
    curl_close($navegador);
} finally {
    if ($idsMascotas !== []) {
        $marcadores = implode(',', array_fill(0, count($idsMascotas), '?'));
        Conexion::conectar()->prepare("DELETE FROM mascotas WHERE IDmascota IN ($marcadores)")->execute($idsMascotas);
    }
    if ($idsClientes !== []) {
        $marcadores = implode(',', array_fill(0, count($idsClientes), '?'));
        Conexion::conectar()->prepare("DELETE FROM clientes WHERE IDcliente IN ($marcadores)")->execute($idsClientes);
    }
    if ($idsUsuarios !== []) {
        $marcadores = implode(',', array_fill(0, count($idsUsuarios), '?'));
        Conexion::conectar()->prepare("DELETE FROM usuarios WHERE IDusuario IN ($marcadores)")->execute($idsUsuarios);
    }
}

echo "PRUEBAS_CLIENTES_MASCOTAS_COMPLETADAS\n";
