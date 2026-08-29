<?php

require_once "../core/Seguridad.php";
Seguridad::exigirAutenticacion(true);

require_once "../modelos/Mascotas.modelos.php";
require_once "../modelos/Usuarios.modelos.php";
require_once "../modelos/Citas.modelos.php";

header('Content-Type: application/json; charset=utf-8');

function responderJson(array $datos, int $estado = 200): void
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$accion = $_POST['accion'] ?? '';

if ($accion === 'mascotas_cliente') {
    $idCliente = filter_var($_POST['IDcliente'] ?? null, FILTER_VALIDATE_INT);
    if ($idCliente === false) {
        responderJson(['error' => 'Cliente inválido'], 422);
    }

    $mascotas = ModeloMascotas::mdlMostrarMascotasPorCliente('mascotas', 'IDcliente', $idCliente);
    $mascotasActivas = array_values(array_filter($mascotas, static function ($mascota) {
        return (int) ($mascota['habilitadoM'] ?? 0) === 1;
    }));
    responderJson(['mascotas' => $mascotasActivas]);
}

if ($accion === 'disponibilidad') {
    $idUsuario = filter_var($_POST['IDusuario'] ?? null, FILTER_VALIDATE_INT);
    $idExcluir = ($_POST['IDcitasExcluir'] ?? '') !== ''
        ? filter_var($_POST['IDcitasExcluir'], FILTER_VALIDATE_INT)
        : null;
    $fecha = $_POST['fechaCita'] ?? '';
    $hora = $_POST['horaCita'] ?? '';
    $fechaHorario = null;

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
        && preg_match('/^(0[7-9]|1[0-8]):(00|30)$/', $hora)) {
        $fechaHorario = DateTime::createFromFormat('!Y-m-d H:i', "$fecha $hora") ?: null;
    }

    $usuario = $idUsuario === false
        ? null
        : ModeloUsuarios::MdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
    $veterinarioValido = is_array($usuario)
        && (int) $usuario['IDrol'] === 2
        && (int) $usuario['habilitado'] === 1;
    $formatoValido = $veterinarioValido
        && $fechaHorario instanceof DateTime
        && $fechaHorario >= new DateTime()
        && ($idExcluir === null || $idExcluir !== false);

    responderJson([
        'disponible' => $formatoValido
            ? ModeloCitas::mdlHorarioDisponible($idUsuario, "$fecha $hora:00", $idExcluir)
            : false,
        'horarioValido' => (bool) $formatoValido,
    ]);
}

responderJson(['error' => 'Acción no reconocida'], 400);
