<?php

final class ControladorHistorialVacunas
{
    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevaFechaAplicacion'])) {
            self::ctrCrearHistorial();
            return;
        }
        if (isset($_POST['editarFechaAplicacion'])) {
            self::ctrEditarHistorial();
        }
    }

    public static function ctrMostrarHistorial($item = null, $valor = null): mixed
    {
        return ModeloHistorialVacunas::mdlMostrarHistorial($item, $valor);
    }

    public static function ctrListarMascotasActivas(): array
    {
        return ModeloHistorialVacunas::mdlListarMascotasActivas();
    }

    public static function ctrListarOpcionesMascota(int $idMascota): array
    {
        return ModeloHistorialVacunas::mdlListarOpcionesMascota($idMascota);
    }

    private static function ctrCrearHistorial(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigir('danger', 'Revise los datos de la vacunación e intente nuevamente.');
        }
        self::responder(ModeloHistorialVacunas::mdlCrearHistorial($datos), 'Vacunación registrada correctamente.');
    }

    private static function ctrEditarHistorial(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $id = filter_var($_POST['IDhistorialVac'] ?? null, FILTER_VALIDATE_INT);
        $datos = self::datosFormulario('editar');
        if ($id === false || (int) $id < 1 || $datos === null) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }
        $datos['IDhistorialVac'] = (int) $id;
        self::responder(ModeloHistorialVacunas::mdlEditarHistorial($datos), 'Historial actualizado correctamente.');
    }

    private static function datosFormulario(string $prefijo): ?array
    {
        $esNuevo = $prefijo === 'nuevo';
        $campoFecha = $esNuevo ? 'nuevaFechaAplicacion' : 'editarFechaAplicacion';
        $campoProxima = $esNuevo ? 'nuevaFechaProximaDosis' : 'editarFechaProximaDosis';
        $campoObservacion = $esNuevo ? 'nuevaObservacionVacuna' : 'editarObservacionVacuna';
        $campoVacuna = $esNuevo ? 'nuevaVacunaHistorial' : 'editarVacunaHistorial';
        $campoCita = $esNuevo ? 'nuevaCitaHistorial' : 'editarCitaHistorial';
        $campoMascota = $esNuevo ? 'nuevaMascotaHistorial' : 'editarMascotaHistorial';

        foreach ([$campoFecha, $campoProxima, $campoObservacion, $campoVacuna, $campoCita, $campoMascota] as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $fecha = self::fechaValida(trim($_POST[$campoFecha]));
        $proximaTexto = trim($_POST[$campoProxima]);
        $proxima = $proximaTexto === '' ? null : self::fechaValida($proximaTexto);
        $observaciones = trim($_POST[$campoObservacion]);
        $idVacuna = filter_var($_POST[$campoVacuna], FILTER_VALIDATE_INT);
        $idMascota = filter_var($_POST[$campoMascota], FILTER_VALIDATE_INT);
        $citaTexto = trim($_POST[$campoCita]);
        $idCita = $citaTexto === '' ? null : filter_var($citaTexto, FILTER_VALIDATE_INT);

        if ($fecha === null || $fecha > date('Y-m-d')
            || ($proximaTexto !== '' && $proxima === null)
            || ($proxima !== null && $proxima < $fecha)
            || mb_strlen($observaciones) > 255
            || $idVacuna === false || (int) $idVacuna < 1
            || $idMascota === false || (int) $idMascota < 1
            || ($citaTexto !== '' && ($idCita === false || (int) $idCita < 1))) {
            return null;
        }

        return [
            'fechaAplicacion' => $fecha,
            'fechaProximaDosis' => $proxima,
            'observaciones' => $observaciones,
            'IDvacuna' => (int) $idVacuna,
            'IDcita' => $idCita === null ? null : (int) $idCita,
            'IDmascota' => (int) $idMascota,
        ];
    }

    private static function fechaValida(string $fecha): ?string
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto && $objeto->format('Y-m-d') === $fecha ? $fecha : null;
    }

    private static function responder(string $respuesta, string $mensajeOk): void
    {
        $mensajes = [
            'duplicado' => 'Esa vacuna ya fue registrada para la mascota en la fecha seleccionada.',
            'mascota_invalida' => 'La mascota o su propietario no están habilitados.',
            'incompatible' => 'La vacuna no es compatible con la especie de la mascota.',
            'sin_stock' => 'La vacuna seleccionada no está disponible o no tiene stock.',
            'cita_invalida' => 'La cita no corresponde a la mascota o a la fecha de aplicación.',
            'cita_pagada' => 'No se puede modificar la vacunación porque la cita ya fue cobrada.',
            'fecha_invalida' => 'La aplicación no puede ser anterior al nacimiento de la mascota.',
            'no_encontrado' => 'El registro del historial no existe.',
        ];
        if ($respuesta === 'ok') {
            self::redirigir('success', $mensajeOk);
        }
        self::redirigir(isset($mensajes[$respuesta]) ? 'warning' : 'danger',
            $mensajes[$respuesta] ?? 'No se pudo guardar el historial de vacunación.');
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['historial_vac_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: HistorialVac');
        exit;
    }
}
