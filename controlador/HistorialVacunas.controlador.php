<?php
// Gestiona las aplicaciones de vacunas y las validaciones del historial preventivo.

final class ControladorHistorialVacunas
{
    // Dirige las solicitudes de registro y edición del historial de vacunas.
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

    // Consulta aplicaciones registradas para mostrarlas en el historial.
    public static function ctrMostrarHistorial($item = null, $valor = null): mixed
    {
        return ModeloHistorialVacunas::mdlMostrarHistorial($item, $valor);
    }

    // Lista citas de vacunación disponibles para registrar aplicaciones.
    public static function ctrListarCitasVacunacion(): array
    {
        return ModeloHistorialVacunas::mdlListarCitasVacunacion();
    }

    // Obtiene vacunas y dosis compatibles con la cita seleccionada.
    public static function ctrListarOpcionesCita(int $idCita): array
    {
        return ModeloHistorialVacunas::mdlListarOpcionesCita($idCita);
    }

    // Valida y registra una o varias vacunas aplicadas en una cita.
    private static function ctrCrearHistorial(): void
    {
        Seguridad::exigirRol([1, 2]);
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigir('danger', 'Revise los datos de la vacunación e intente nuevamente.');
        }
        $cantidad = count($datos['IDvacunas']);
        self::responder(
            ModeloHistorialVacunas::mdlCrearHistorialMultiple($datos),
            $cantidad === 1 ? 'Vacunación registrada correctamente.' : "$cantidad vacunas registradas correctamente."
        );
    }

    // Actualiza una aplicación existente respetando las reglas del historial.
    private static function ctrEditarHistorial(): void
    {
        Seguridad::exigirRol([1, 2]);
        Seguridad::exigirCsrf();
        $id = filter_var($_POST['IDhistorialVac'] ?? null, FILTER_VALIDATE_INT);
        $datos = self::datosFormulario('editar');
        if ($id === false || (int) $id < 1 || $datos === null) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }
        $datos['IDhistorialVac'] = (int) $id;
        self::responder(ModeloHistorialVacunas::mdlEditarHistorial($datos), 'Historial actualizado correctamente.');
    }

    // Normaliza las fechas, observaciones y dosis recibidas del formulario.
    private static function datosFormulario(string $prefijo): ?array
    {
        $esNuevo = $prefijo === 'nuevo';
        $campoFecha = $esNuevo ? 'nuevaFechaAplicacion' : 'editarFechaAplicacion';
        $campoProxima = $esNuevo ? 'nuevaFechaProximaDosis' : 'editarFechaProximaDosis';
        $campoObservacion = $esNuevo ? 'nuevaObservacionVacuna' : 'editarObservacionVacuna';
        $campoVacuna = $esNuevo ? 'nuevaVacunaHistorial' : 'editarVacunaHistorial';
        $campoCita = $esNuevo ? 'nuevaCitaHistorial' : 'editarCitaHistorial';

        foreach ([$campoFecha, $campoProxima, $campoObservacion, $campoCita] as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }
        if (!isset($_POST[$campoVacuna])
            || (!$esNuevo && !is_string($_POST[$campoVacuna]))
            || ($esNuevo && !is_array($_POST[$campoVacuna]) && !is_string($_POST[$campoVacuna]))) {
            return null;
        }

        $fecha = self::fechaValida(trim($_POST[$campoFecha]));
        $proximaTexto = trim($_POST[$campoProxima]);
        $proxima = $proximaTexto === '' ? null : self::fechaValida($proximaTexto);
        $observaciones = trim($_POST[$campoObservacion]);
        $vacunasEnviadas = $esNuevo && is_array($_POST[$campoVacuna])
            ? $_POST[$campoVacuna] : [$_POST[$campoVacuna]];
        $idVacunas = [];
        foreach ($vacunasEnviadas as $vacunaEnviada) {
            if (!is_string($vacunaEnviada)) {
                return null;
            }
            $idVacuna = filter_var($vacunaEnviada, FILTER_VALIDATE_INT);
            if ($idVacuna === false || (int) $idVacuna < 1) {
                return null;
            }
            $idVacunas[] = (int) $idVacuna;
        }
        $idVacunas = array_values(array_unique($idVacunas));
        $citaTexto = trim($_POST[$campoCita]);
        $idCita = filter_var($citaTexto, FILTER_VALIDATE_INT);

        if ($fecha === null || $fecha > date('Y-m-d')
            || ($proximaTexto !== '' && $proxima === null)
            || ($proxima !== null && $proxima < $fecha)
            || mb_strlen($observaciones) > 255
            || $idVacunas === []
            || $idCita === false || (int) $idCita < 1) {
            return null;
        }

        return [
            'fechaAplicacion' => $fecha,
            'fechaProximaDosis' => $proxima,
            'observaciones' => $observaciones,
            'IDvacuna' => $idVacunas[0],
            'IDvacunas' => $idVacunas,
            'IDcita' => (int) $idCita,
        ];
    }

    // Valida y convierte una fecha de aplicación al formato de la base de datos.
    private static function fechaValida(string $fecha): ?string
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto && $objeto->format('Y-m-d') === $fecha ? $fecha : null;
    }

    // Convierte la respuesta del modelo en un mensaje para la interfaz.
    private static function responder(string $respuesta, string $mensajeOk): void
    {
        $mensajes = [
            'duplicado' => 'Esa vacuna ya fue registrada para la mascota en la fecha seleccionada.',
            'incompatible' => 'La vacuna no es compatible con la especie de la mascota.',
            'sin_stock' => 'La vacuna seleccionada no está disponible o no tiene stock.',
            'cita_invalida' => 'La cita no es una vacunación completada válida para la fecha de aplicación.',
            'cita_pagada' => 'No se puede modificar la vacunación porque la cita ya fue cobrada.',
            'sin_esquema' => 'La vacuna seleccionada todavía no tiene un esquema de vacunación configurado.',
            'fecha_invalida' => 'La aplicación no puede ser anterior al nacimiento de la mascota.',
            'no_encontrado' => 'El registro del historial no existe.',
        ];
        if ($respuesta === 'ok') {
            self::redirigir('success', $mensajeOk);
        }
        self::redirigir(isset($mensajes[$respuesta]) ? 'warning' : 'danger',
            $mensajes[$respuesta] ?? 'No se pudo guardar el historial de vacunación.');
    }

    // Regresa al módulo mostrando el resultado de la operación.
    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['historial_vac_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: HistorialVac');
        exit;
    }
}
