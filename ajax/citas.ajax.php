<?php
// Endpoint AJAX para consultar datos de mascotas, horarios y citas sin recargar la vista.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Citas.controlador.php';
require_once __DIR__ . '/../modelos/Citas.modelos.php';
require_once __DIR__ . '/../modelos/Mascotas.modelos.php';
require_once __DIR__ . '/../modelos/Usuarios.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirAutenticacion(true);

function responderCitasJson(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    responderCitasJson(['error' => 'Solicitud inválida o vencida.'], 403);
}

$accion = $_POST['accion'] ?? '';
if ($accion === 'mascotas_cliente') {
    $idCliente = filter_var($_POST['IDcliente'] ?? null, FILTER_VALIDATE_INT);
    if ($idCliente === false) {
        responderCitasJson(['error' => 'El cliente seleccionado no es válido.'], 422);
    }
    responderCitasJson([
        'mascotas' => ModeloMascotas::mdlListarMascotasPorCliente((int) $idCliente),
    ]);
}

if ($accion === 'disponibilidad') {
    responderCitasJson(ControladorCitas::ctrEvaluarDisponibilidad(
        $_POST['IDusuario'] ?? null,
        $_POST['fechaCita'] ?? null,
        $_POST['horaCita'] ?? null,
        $_POST['IDservicios'] ?? ($_POST['IDservicio'] ?? null),
        $_POST['IDcitaExcluir'] ?? null
    ));
}

responderCitasJson(['error' => 'No se indicó una operación válida.'], 422);
