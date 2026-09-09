<?php

require_once __DIR__ . '/../../modelos/Citas.modelos.php';

if ($argc !== 6) {
    exit(2);
}

$inicio = (float) $argv[5];
while (microtime(true) < $inicio) {
    usleep(1000);
}

echo ModeloCitas::mdlIngresarCita([
    'motivoCita' => 'Prueba concurrente',
    'estadoCita' => 'Pendiente',
    'fechaAtencion' => $argv[1],
    'IDusuario' => (int) $argv[2],
    'IDmascota' => (int) $argv[3],
    'IDservicio' => (int) $argv[4],
]);
