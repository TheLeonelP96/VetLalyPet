<?php
// Endpoint AJAX para consultar y editar los esquemas de vacunación.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/SeguimientoVacunas.controlador.php';
require_once __DIR__ . '/../modelos/SeguimientoVacunas.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirRol([1, 2], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$id = filter_var($_POST['IDesquemaVac'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || (int) $id < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'El esquema solicitado no es válido.']);
    exit;
}

$esquema = ControladorSeguimientoVacunas::ctrMostrarEsquema((int) $id);
if ($esquema === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Esquema no encontrado.']);
    exit;
}
echo json_encode($esquema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
