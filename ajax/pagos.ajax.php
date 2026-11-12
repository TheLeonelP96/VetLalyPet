<?php
// Endpoint AJAX para obtener pagos y calcular el detalle cobrable de una cita.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Pagos.controlador.php';
require_once __DIR__ . '/../modelos/Pagos.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirAutenticacion(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$accion = $_POST['accion'] ?? 'pago';
if ($accion === 'detalle_cita') {
    $idCita = filter_var($_POST['IDcita'] ?? null, FILTER_VALIDATE_INT);
    if ($idCita === false || (int) $idCita < 1) {
        http_response_code(422);
        echo json_encode(['error' => 'La cita seleccionada no es válida.']);
        exit;
    }
    $detalle = ControladorPagos::ctrDetalleCobroCita((int) $idCita);
    if (!is_array($detalle)) {
        http_response_code(404);
        echo json_encode(['error' => 'Cita no encontrada.']);
        exit;
    }
    echo json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$idPago = filter_var($_POST['IDpago'] ?? null, FILTER_VALIDATE_INT);
if ($idPago === false || (int) $idPago < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'El pago solicitado no es válido.']);
    exit;
}

$pago = ControladorPagos::ctrMostrarPagos('IDpago', (int) $idPago);
if (!is_array($pago)) {
    http_response_code(404);
    echo json_encode(['error' => 'Pago no encontrado.']);
    exit;
}

echo json_encode($pago, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
