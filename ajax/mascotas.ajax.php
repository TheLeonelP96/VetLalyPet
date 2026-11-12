<?php
// Endpoint AJAX para cargar clientes, especies y razas de las mascotas.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Mascotas.controlador.php';
require_once __DIR__ . '/../modelos/Mascotas.modelos.php';
require_once __DIR__ . '/../modelos/Clientes.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirAutenticacion(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

if (isset($_POST['IDmascota'])) {
    $idMascota = filter_var($_POST['IDmascota'], FILTER_VALIDATE_INT);
    if ($idMascota === false) {
        http_response_code(422);
        echo json_encode(['error' => 'La mascota solicitada no es válida.']);
        exit;
    }
    $mascota = ControladorMascotas::crtMostrarMascotas('IDmascota', (int) $idMascota);
    if (!is_array($mascota)) {
        http_response_code(404);
        echo json_encode(['error' => 'Mascota no encontrada.']);
        exit;
    }
    echo json_encode($mascota, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (isset($_POST['IDcliente'])) {
    $idCliente = filter_var($_POST['IDcliente'], FILTER_VALIDATE_INT);
    if ($idCliente === false) {
        http_response_code(422);
        echo json_encode(['error' => 'El cliente solicitado no es válido.']);
        exit;
    }
    echo json_encode(
        ModeloMascotas::mdlListarMascotasPorCliente((int) $idCliente),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

http_response_code(422);
echo json_encode(['error' => 'No se indicó una operación válida.']);
