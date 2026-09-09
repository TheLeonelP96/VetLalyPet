<?php

final class ControladorCitas
{
    private const ESTADO_PENDIENTE = 'Pendiente';
    private const ESTADO_CANCELADA = 'Cancelada';
    private const ESTADO_COMPLETADA = 'Completada';
    private const HORA_APERTURA = 7;
    private const HORA_CIERRE = 19;

    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevoMotivo'])) {
            self::ctrCrearCita();
            return;
        }
        if (isset($_POST['idCitaEditar'])) {
            self::ctrEditarCita();
        }
    }

    public static function ctrMostrarCitas($item = null, $valor = null): mixed
    {
        return ModeloCitas::mdlMostrarCitas($item, $valor);
    }

    public static function ctrListarServiciosActivos(): array
    {
        return ModeloCitas::mdlListarServiciosActivos();
    }

    public static function ctrEvaluarDisponibilidad(
        $idUsuario,
        $fecha,
        $hora,
        $idCitaExcluir = null
    ): array {
        $idUsuario = filter_var($idUsuario, FILTER_VALIDATE_INT);
        $idExcluir = ($idCitaExcluir === null || $idCitaExcluir === '')
            ? null
            : filter_var($idCitaExcluir, FILTER_VALIDATE_INT);
        $fechaCompleta = self::normalizarFechaHorario($fecha, $hora);
        $veterinarioValido = $idUsuario !== false && self::veterinarioValido((int) $idUsuario);
        $solicitudValida = $fechaCompleta !== null
            && $veterinarioValido
            && ($idExcluir === null || $idExcluir !== false);
        $disponible = $solicitudValida && ModeloCitas::mdlHorarioDisponible(
            (int) $idUsuario,
            $fechaCompleta,
            $idExcluir === null ? null : (int) $idExcluir
        );

        if ($fechaCompleta === null) {
            $mensaje = 'Seleccione una fecha futura y un bloque de 30 minutos entre 07:00 y 18:30.';
        } elseif (!$veterinarioValido) {
            $mensaje = 'El veterinario seleccionado no está disponible.';
        } elseif (!$disponible) {
            $mensaje = 'El veterinario ya tiene una cita activa en esa fecha y hora.';
        } else {
            $mensaje = 'Veterinario disponible en este horario.';
        }
        return [
            'horarioValido' => $fechaCompleta !== null,
            'veterinarioValido' => $veterinarioValido,
            'disponible' => $disponible,
            'mensaje' => $mensaje,
        ];
    }

    public static function ctrCrearCita(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('crear');
        if ($datos === null) {
            self::redirigir('danger', 'Los datos de la cita no son válidos o están fuera del horario de atención.');
        }
        $datos['estadoCita'] = self::ESTADO_PENDIENTE;

        $respuesta = ModeloCitas::mdlIngresarCita($datos);
        self::responderEscritura($respuesta, 'La cita fue programada correctamente.');
    }

    public static function ctrEditarCita(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $idCita = filter_var($_POST['idCitaEditar'] ?? null, FILTER_VALIDATE_INT);
        $accion = $_POST['btnAccionCita'] ?? 'guardar';
        if ($idCita === false || !in_array($accion, ['cancelar', 'reactivar', 'completar', 'guardar'], true)) {
            self::redirigir('danger', 'La operación solicitada no es válida.');
        }

        $actual = ModeloCitas::mdlMostrarCitas('IDcita', (int) $idCita);
        if (!is_array($actual)) {
            self::redirigir('danger', 'La cita solicitada ya no existe.');
        }

        if ($accion === 'cancelar') {
            self::cambiarEstado(
                (int) $idCita,
                $actual['estadoCita'],
                self::ESTADO_PENDIENTE,
                self::ESTADO_CANCELADA,
                'La cita fue cancelada. Sus datos se conservaron y puede reactivarse.'
            );
        }
        if ($accion === 'completar') {
            self::completarYContinuar((int) $idCita, $actual);
        }

        if ($actual['estadoCita'] === self::ESTADO_COMPLETADA) {
            self::redirigir('warning', 'Una cita completada ya no puede modificarse.');
        }
        $estadoEsperado = $accion === 'reactivar'
            ? self::ESTADO_CANCELADA
            : self::ESTADO_PENDIENTE;
        if ($actual['estadoCita'] !== $estadoEsperado) {
            self::redirigir(
                'warning',
                $accion === 'reactivar'
                    ? 'Solo se pueden reactivar citas canceladas.'
                    : 'Solo se pueden modificar citas pendientes.'
            );
        }

        $datos = self::datosFormulario('editar');
        if ($datos === null) {
            self::redirigir('danger', 'Los datos de la cita no son válidos o están fuera del horario de atención.');
        }
        $datos['IDcita'] = (int) $idCita;
        $datos['estadoCita'] = self::ESTADO_PENDIENTE;
        $datos['estadoEsperado'] = $estadoEsperado;

        $respuesta = ModeloCitas::mdlEditarCita($datos);
        self::responderEscritura(
            $respuesta,
            $accion === 'reactivar'
                ? 'La cita fue reactivada correctamente.'
                : 'La cita fue actualizada correctamente.'
        );
    }

    private static function datosFormulario(string $modo): ?array
    {
        $crear = $modo === 'crear';
        $motivo = self::motivoValido($_POST[$crear ? 'nuevoMotivo' : 'editarMotivo'] ?? null);
        $fechaCompleta = self::normalizarFechaHorario(
            $_POST[$crear ? 'fechaCitaCrear' : 'fechaCitaEditar'] ?? null,
            $_POST[$crear ? 'seleccionarHora' : 'editarHora'] ?? null
        );
        $idMascota = filter_var(
            $_POST[$crear ? 'seleccionarMascota' : 'editarMascota'] ?? null,
            FILTER_VALIDATE_INT
        );
        $idUsuario = filter_var(
            $_POST[$crear ? 'nuevoUsuario' : 'editarUsuario'] ?? null,
            FILTER_VALIDATE_INT
        );
        $idServicio = filter_var(
            $_POST[$crear ? 'nuevoServicio' : 'editarServicio'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($motivo === null || $fechaCompleta === null
            || $idMascota === false || !self::mascotaValida((int) $idMascota)
            || $idUsuario === false || !self::veterinarioValido((int) $idUsuario)
            || $idServicio === false || !ModeloCitas::mdlServicioActivo((int) $idServicio)) {
            return null;
        }
        return [
            'motivoCita' => $motivo,
            'fechaAtencion' => $fechaCompleta,
            'IDmascota' => (int) $idMascota,
            'IDusuario' => (int) $idUsuario,
            'IDservicio' => (int) $idServicio,
        ];
    }

    private static function normalizarFechaHorario($fecha, $hora): ?string
    {
        if (!is_string($fecha) || !is_string($hora)
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1
            || preg_match('/^(\d{2}):(00|30)$/', $hora, $partes) !== 1) {
            return null;
        }
        $horaNumero = (int) $partes[1];
        if ($horaNumero < self::HORA_APERTURA || $horaNumero >= self::HORA_CIERRE) {
            return null;
        }

        $fechaCita = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$fecha $hora");
        $errores = DateTimeImmutable::getLastErrors();
        if (!$fechaCita || (is_array($errores) && ($errores['warning_count'] || $errores['error_count']))
            || $fechaCita < new DateTimeImmutable()) {
            return null;
        }
        return $fechaCita->format('Y-m-d H:i:s');
    }

    private static function veterinarioValido(int $idUsuario): bool
    {
        $usuario = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
        return is_array($usuario)
            && (int) $usuario['IDrol'] === 2
            && (int) $usuario['estado_U'] === 1;
    }

    private static function mascotaValida(int $idMascota): bool
    {
        $mascota = ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota);
        return is_array($mascota)
            && (int) $mascota['habilitadoM'] === 1
            && (int) $mascota['habilitadoC'] === 1;
    }

    private static function motivoValido($motivo): ?string
    {
        if (!is_string($motivo)) {
            return null;
        }
        $motivo = trim($motivo);
        return $motivo !== '' && mb_strlen($motivo) <= 75 ? $motivo : null;
    }

    private static function cambiarEstado(
        int $idCita,
        string $estadoActual,
        string $estadoRequerido,
        string $estadoNuevo,
        string $mensaje
    ): void {
        if ($estadoActual !== $estadoRequerido) {
            self::redirigir('warning', 'La cita ya no se encuentra pendiente.');
        }
        $respuesta = ModeloCitas::mdlActualizarEstadoCita($idCita, $estadoRequerido, $estadoNuevo);
        self::redirigir(
            $respuesta === 'ok' ? 'success' : 'danger',
            $respuesta === 'ok' ? $mensaje : 'No se pudo actualizar el estado de la cita.'
        );
    }

    private static function completarYContinuar(int $idCita, array $cita): void
    {
        if (($cita['estadoCita'] ?? '') !== self::ESTADO_PENDIENTE) {
            self::redirigir('warning', 'La cita ya no se encuentra pendiente.');
        }

        $respuesta = ModeloCitas::mdlActualizarEstadoCita(
            $idCita,
            self::ESTADO_PENDIENTE,
            self::ESTADO_COMPLETADA
        );
        if ($respuesta !== 'ok') {
            self::redirigir('danger', 'No se pudo completar la cita.');
        }

        if ((int) ($cita['esVacunacion'] ?? 0) === 1) {
            $_SESSION['historial_vac_flash'] = [
                'tipo' => 'success',
                'mensaje' => 'Cita completada. Registre ahora la vacuna aplicada para habilitar el cobro.',
            ];
            header('Location: HistorialVac?cita=' . $idCita);
            exit;
        }

        $_SESSION['pago_flash'] = [
            'tipo' => 'success',
            'mensaje' => 'Cita completada. Ya puede registrar el pago del servicio.',
        ];
        header('Location: Pagos?cita=' . $idCita);
        exit;
    }

    private static function responderEscritura(string $respuesta, string $mensajeOk): void
    {
        if ($respuesta === 'ok') {
            self::redirigir('success', $mensajeOk);
        }
        $mensajes = [
            'ocupado' => 'El veterinario ya tiene una cita activa en esa fecha y hora.',
            'relacion_invalida' => 'La mascota, el veterinario o el servicio ya no está disponible.',
            'no_encontrada' => 'La cita solicitada ya no existe.',
            'estado_invalido' => 'El estado de la cita cambió. Recargue el calendario e intente nuevamente.',
        ];
        self::redirigir('danger', $mensajes[$respuesta] ?? 'No se pudo guardar la cita.');
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['cita_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Calendar');
        exit;
    }
}
