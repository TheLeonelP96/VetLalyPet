<?php
// Endpoint AJAX para cargar información de usuarios y validar operaciones del módulo.

require_once __DIR__ . '/../core/Seguridad.php';
require_once __DIR__ . '/../controlador/Usuarios.controlador.php';
require_once __DIR__ . '/../modelos/Usuarios.modelos.php';

header('Content-Type: application/json; charset=utf-8');
Seguridad::exigirRol([1], true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Seguridad::validarCsrf()) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud inválida o vencida.']);
    exit;
}

$idUsuario = filter_var($_POST['IDusuario'] ?? null, FILTER_VALIDATE_INT);
if ($idUsuario === false) {
    http_response_code(422);
    echo json_encode(['error' => 'El usuario solicitado no es válido.']);
    exit;
}

$usuario = ControladorUsuarios::ctrMostrarUsuarios('IDusuario', (int) $idUsuario);
if (!is_array($usuario)) {
    http_response_code(404);
    echo json_encode(['error' => 'Usuario no encontrado.']);
    exit;
}

unset($usuario['password']);
echo json_encode($usuario, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
