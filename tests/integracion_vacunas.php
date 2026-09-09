<?php

require_once __DIR__ . '/../modelos/Usuarios.modelos.php';
require_once __DIR__ . '/../modelos/Vacunas.modelos.php';

$baseUrl = 'http://localhost/LalyPets/';
$sufijo = bin2hex(random_bytes(4));
$correoAdmin = "prueba.vacunas.$sufijo@example.test";
$passwordAdmin = 'TemporalVac_2026!';
$idsUsuarios = [];
$idsVacunas = [];

function verificarVac(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK - $mensaje\n";
}

function clienteHttpVac(): CurlHandle
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

function solicitarVac(CurlHandle $curl, string $url, ?array $datos = null): array
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

function csrfVac(string $html): string
{
    if (!preg_match('/name=["\']csrf-token["\'] content=["\']([^"\']+)["\']/', $html, $resultado)
        && !preg_match('/name=["\']csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $resultado)) {
        throw new RuntimeException('No se encontró el token CSRF.');
    }
    return html_entity_decode($resultado[1], ENT_QUOTES, 'UTF-8');
}

try {
    $anonimo = clienteHttpVac();
    verificarVac(
        solicitarVac($anonimo, $baseUrl . 'ajax/vacunas.ajax.php', ['IDvacuna' => '1'])['status'] === 401,
        'El AJAX de Vacunas exige una sesión'
    );
    curl_close($anonimo);

    verificarVac(ModeloUsuarios::mdlCrearUsuario([
        'nombre' => 'Admin', 'apellido' => 'Temporal Vacunas', 'telefono' => '70000201',
        'correo' => $correoAdmin, 'password' => password_hash($passwordAdmin, PASSWORD_DEFAULT),
        'turno' => 'Mañana', 'foto' => '', 'IDrol' => 1,
    ]) === 'ok', 'Se preparó el administrador temporal');
    $admin = ModeloUsuarios::mdlBuscarPorCorreo($correoAdmin);
    $idsUsuarios[] = (int) $admin['IDusuario'];

    $navegador = clienteHttpVac();
    $inicio = solicitarVac($navegador, $baseUrl);
    $login = solicitarVac($navegador, $baseUrl, [
        'csrf_token' => csrfVac($inicio['body']),
        'imgEmail' => $correoAdmin,
        'imgPassword' => $passwordAdmin,
    ]);
    verificarVac($login['status'] === 302, 'Se inició una sesión de prueba');

    $pagina = solicitarVac($navegador, $baseUrl . 'Vacunas');
    $token = csrfVac($pagina['body']);
    verificarVac($pagina['status'] === 200 && str_contains($pagina['body'], 'Vacunas registradas'),
        'El módulo de Vacunas responde correctamente');
    verificarVac(!preg_match('/<th>\s*ID\s*<\/th>/i', $pagina['body']),
        'La tabla no muestra el identificador interno');

    $especies = ModeloVacunas::mdlListarEspecies();
    verificarVac(count($especies) >= 2, 'Hay especies suficientes para probar compatibilidad');
    $idEspecieA = (int) $especies[0]['IDespecie'];
    $idEspecieB = (int) $especies[1]['IDespecie'];
    $nombre = "Vacuna temporal $sufijo";

    $csrfInvalido = solicitarVac($navegador, $baseUrl . 'ajax/vacunas.ajax.php', [
        'csrf_token' => 'invalido', 'IDvacuna' => '1',
    ]);
    verificarVac($csrfInvalido['status'] === 403, 'El AJAX rechaza solicitudes sin CSRF válido');

    $alta = solicitarVac($navegador, $baseUrl . 'Vacunas', [
        'csrf_token' => $token,
        'nuevaVacuna' => $nombre,
        'nuevaDescripcionVacuna' => 'Primera dosis de prueba',
        'nuevoPrecioVacuna' => '45.50',
        'nuevoStockVacuna' => '12',
        'nuevaEspecieVacuna' => (string) $idEspecieA,
    ]);
    verificarVac($alta['status'] === 302, 'El alta usa POST/Redirect/GET');
    $stmt = Conexion::conectar()->prepare(
        'SELECT IDvacuna FROM vacuna WHERE nombreVac = :nombre AND IDespecie = :especie'
    );
    $stmt->execute([':nombre' => $nombre, ':especie' => $idEspecieA]);
    $idVacunaA = (int) $stmt->fetchColumn();
    verificarVac($idVacunaA > 0, 'La vacuna quedó enlazada con su especie compatible');
    $idsVacunas[] = $idVacunaA;

    solicitarVac($navegador, $baseUrl . 'Vacunas', [
        'csrf_token' => $token,
        'nuevaVacuna' => $nombre,
        'nuevaDescripcionVacuna' => 'Duplicada',
        'nuevoPrecioVacuna' => '10',
        'nuevoStockVacuna' => '1',
        'nuevaEspecieVacuna' => (string) $idEspecieA,
    ]);
    $stmt = Conexion::conectar()->prepare(
        'SELECT COUNT(*) FROM vacuna WHERE nombreVac = :nombre AND IDespecie = :especie'
    );
    $stmt->execute([':nombre' => $nombre, ':especie' => $idEspecieA]);
    verificarVac((int) $stmt->fetchColumn() === 1,
        'No se duplica la misma vacuna dentro de una especie');

    solicitarVac($navegador, $baseUrl . 'Vacunas', [
        'csrf_token' => $token,
        'nuevaVacuna' => $nombre,
        'nuevaDescripcionVacuna' => 'Versión para otra especie',
        'nuevoPrecioVacuna' => '48',
        'nuevoStockVacuna' => '8',
        'nuevaEspecieVacuna' => (string) $idEspecieB,
    ]);
    $stmt = Conexion::conectar()->prepare(
        'SELECT IDvacuna FROM vacuna WHERE nombreVac = :nombre AND IDespecie = :especie'
    );
    $stmt->execute([':nombre' => $nombre, ':especie' => $idEspecieB]);
    $idVacunaB = (int) $stmt->fetchColumn();
    verificarVac($idVacunaB > 0, 'Una vacuna puede registrarse para otra especie compatible');
    $idsVacunas[] = $idVacunaB;

    $ajax = solicitarVac($navegador, $baseUrl . 'ajax/vacunas.ajax.php', [
        'csrf_token' => $token, 'IDvacuna' => (string) $idVacunaA,
    ]);
    $json = json_decode($ajax['body'], true);
    verificarVac($ajax['status'] === 200
        && (int) $json['IDespecie'] === $idEspecieA
        && $json['nombre_Especie'] === $especies[0]['nombre_Especie'],
        'El AJAX devuelve la vacuna y su especie para editar');

    solicitarVac($navegador, $baseUrl . 'Vacunas', [
        'csrf_token' => $token,
        'IDvacuna' => (string) $idVacunaA,
        'editarVacuna' => $nombre . ' editada',
        'editarDescripcionVacuna' => 'Descripción actualizada',
        'editarPrecioVacuna' => '52.25',
        'editarStockVacuna' => '4',
        'editarEspecieVacuna' => (string) $idEspecieA,
    ]);
    $editada = ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaA);
    verificarVac($editada['Descripcion'] === 'Descripción actualizada'
        && $editada['preciovac'] === '52.25'
        && (int) $editada['stock'] === 4,
        'La edición actualiza nombre, descripción, precio y stock');

    solicitarVac($navegador, $baseUrl . 'Vacunas', [
        'csrf_token' => $token, 'IDvacuna' => (string) $idVacunaA, 'estadoVacuna' => '0',
    ]);
    verificarVac((int) ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaA)['estadoVac'] === 0,
        'La vacuna se deshabilita sin borrarse');
    solicitarVac($navegador, $baseUrl . 'Vacunas', [
        'csrf_token' => $token, 'IDvacuna' => (string) $idVacunaA, 'estadoVacuna' => '1',
    ]);
    verificarVac((int) ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaA)['estadoVac'] === 1,
        'La vacuna se habilita nuevamente con una sola acción');
    curl_close($navegador);
} finally {
    if ($idsVacunas !== []) {
        $marcadores = implode(',', array_fill(0, count($idsVacunas), '?'));
        Conexion::conectar()->prepare("DELETE FROM vacuna WHERE IDvacuna IN ($marcadores)")->execute($idsVacunas);
    }
    if ($idsUsuarios !== []) {
        $marcadores = implode(',', array_fill(0, count($idsUsuarios), '?'));
        Conexion::conectar()->prepare("DELETE FROM usuarios WHERE IDusuario IN ($marcadores)")->execute($idsUsuarios);
    }
}

echo "PRUEBAS_VACUNAS_COMPLETADAS\n";
