<?php
// Endpoint AJAX para cargar información del catálogo de servicios.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Servicios.controlador.php';
require_once __DIR__ . '/../modelos/Servicios.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirRol([1], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$idServicio = filter_var($_POST['IDservicio'] ?? null, FILTER_VALIDATE_INT);
if ($idServicio === false || (int) $idServicio < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'El servicio solicitado no es válido.']);
    exit;
}

$servicio = ControladorServicios::ctrMostrarServicios('IDservicio', (int) $idServicio);
if (!is_array($servicio)) {
    http_response_code(404);
    echo json_encode(['error' => 'Servicio no encontrado.']);
    exit;
}

echo json_encode($servicio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
