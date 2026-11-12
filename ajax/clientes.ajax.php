<?php
// Endpoint AJAX para consultas y validaciones del módulo de clientes.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Clientes.controlador.php';
require_once __DIR__ . '/../modelos/Clientes.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirAutenticacion(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$idCliente = filter_var($_POST['IDcliente'] ?? null, FILTER_VALIDATE_INT);
if ($idCliente === false) {
    http_response_code(422);
    echo json_encode(['error' => 'El cliente solicitado no es válido.']);
    exit;
}

$cliente = ControladorClientes::crtMostrarClientes('IDcliente', (int) $idCliente);
if (!is_array($cliente)) {
    http_response_code(404);
    echo json_encode(['error' => 'Cliente no encontrado.']);
    exit;
}

echo json_encode($cliente, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
