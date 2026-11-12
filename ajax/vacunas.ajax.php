<?php
// Endpoint AJAX para consultar especies y datos del catálogo de vacunas.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Vacunas.controlador.php';
require_once __DIR__ . '/../modelos/Vacunas.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirRol([1], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$idVacuna = filter_var($_POST['IDvacuna'] ?? null, FILTER_VALIDATE_INT);
if ($idVacuna === false || (int) $idVacuna < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'La vacuna solicitada no es válida.']);
    exit;
}

$vacuna = ControladorVacunas::ctrMostrarVacunas('IDvacuna', (int) $idVacuna);
if (!is_array($vacuna)) {
    http_response_code(404);
    echo json_encode(['error' => 'Vacuna no encontrada.']);
    exit;
}

echo json_encode($vacuna, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
