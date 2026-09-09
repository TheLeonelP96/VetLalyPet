<?php

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/HistorialVacunas.controlador.php';
require_once __DIR__ . '/../modelos/HistorialVacunas.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirAutenticacion(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$accion = $_POST['accion'] ?? '';
if ($accion === 'opciones_mascota') {
    $idMascota = filter_var($_POST['IDmascota'] ?? null, FILTER_VALIDATE_INT);
    if ($idMascota === false || (int) $idMascota < 1) {
        http_response_code(422);
        echo json_encode(['error' => 'La mascota seleccionada no es válida.']);
        exit;
    }
    echo json_encode(
        ControladorHistorialVacunas::ctrListarOpcionesMascota((int) $idMascota),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($accion === 'historial') {
    $id = filter_var($_POST['IDhistorialVac'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || (int) $id < 1) {
        http_response_code(422);
        echo json_encode(['error' => 'El registro solicitado no es válido.']);
        exit;
    }
    $historial = ControladorHistorialVacunas::ctrMostrarHistorial('IDhistorialVac', (int) $id);
    if (!is_array($historial)) {
        http_response_code(404);
        echo json_encode(['error' => 'Registro de vacunación no encontrado.']);
        exit;
    }
    echo json_encode($historial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(422);
echo json_encode(['error' => 'Acción no válida.']);
