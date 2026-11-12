<?php
// Coordina el registro, modificación, cancelación y validación de horarios de las citas.

final class ControladorCitas
{
    private const ESTADO_PENDIENTE = 'Pendiente';
    private const ESTADO_CANCELADA = 'Cancelada';
    private const ESTADO_COMPLETADA = 'Completada';
    private const HORA_APERTURA = 7;
    private const HORA_CIERRE = 19;
    private const DURACION_BLOQUE_MINUTOS = 15;

    // Dirige cada solicitud del módulo hacia la operación de citas correspondiente.
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

    // Consulta citas para la agenda, listados o edición.
    public static function ctrMostrarCitas($item = null, $valor = null): mixed
    {
        return ModeloCitas::mdlMostrarCitas($item, $valor);
    }

    // Devuelve los servicios que pueden seleccionarse al reservar.
    public static function ctrListarServiciosActivos(): array
    {
        return ModeloCitas::mdlListarServiciosActivos();
    }

    // Verifica turno, duración y posibles cruces antes de reservar.
    public static function ctrEvaluarDisponibilidad(
        $idUsuario,
        $fecha,
        $hora,
        $idServicios,
        $idCitaExcluir = null
    ): array {
        $idUsuario = filter_var($idUsuario, FILTER_VALIDATE_INT);
        $idsServicios = self::normalizarIdsServicios($idServicios);
        $idExcluir = ($idCitaExcluir === null || $idCitaExcluir === '')
            ? null
            : filter_var($idCitaExcluir, FILTER_VALIDATE_INT);
        $fechaPasada = self::fechaCalendarioPasada($fecha);
        $fechaCompleta = self::normalizarFechaHorario($fecha, $hora);
        $servicios = ModeloCitas::mdlObtenerServiciosActivos($idsServicios);
        $serviciosValidos = $idsServicios !== [] && count($servicios) === count($idsServicios);
        $duracion = self::DURACION_BLOQUE_MINUTOS;
        $veterinario = $idUsuario === false ? null : self::obtenerUsuarioAsignable((int) $idUsuario);
        $veterinarioValido = is_array($veterinario);
        $turnoValido = $fechaCompleta !== null && $serviciosValidos && $veterinarioValido
            && self::dentroDelTurno($fechaCompleta, $duracion, $veterinario['turno_U']);
        $solicitudValida = $fechaCompleta !== null
            && $veterinarioValido
            && $serviciosValidos
            && self::terminaEnHorario($fechaCompleta, $duracion)
            && $turnoValido
            && ($idExcluir === null || $idExcluir !== false);
        $disponible = $solicitudValida && ModeloCitas::mdlHorarioDisponible(
            (int) $idUsuario,
            $fechaCompleta,
            $duracion,
            $idExcluir === null ? null : (int) $idExcluir
        );

        if ($fechaPasada) {
            $mensaje = 'No se puede programar una cita en una fecha pasada.';
        } elseif ($fechaCompleta === null) {
            $mensaje = 'Seleccione una fecha y una hora válida dentro del horario de atención.';
        } elseif (!$veterinarioValido) {
            $mensaje = 'El veterinario seleccionado no está disponible.';
        } elseif (!$serviciosValidos) {
            $mensaje = 'Seleccione uno o más servicios disponibles.';
        } elseif (!self::terminaEnHorario($fechaCompleta, $duracion)) {
            $mensaje = 'La cita terminaría después de las 19:00.';
        } elseif (!$turnoValido) {
            $mensaje = 'El veterinario trabaja en turno ' . $veterinario['turno_U']
                . ' (' . self::textoHorarioTurno($veterinario['turno_U'])
                . '). La cita debe estar dentro de ese horario.';
        } elseif (!$disponible) {
            $mensaje = 'El veterinario ya tiene una cita en ese bloque.';
        } else {
            $mensaje = 'Veterinario disponible para este bloque de 15 minutos.';
        }
        return [
            'horarioValido' => $fechaCompleta !== null,
            'veterinarioValido' => $veterinarioValido,
            'turnoValido' => $turnoValido,
            'turnoVeterinario' => $veterinario['turno_U'] ?? null,
            'disponible' => $disponible,
            'duracionMinutos' => $serviciosValidos ? $duracion : null,
            'mensaje' => $mensaje,
        ];
    }

    // Valida y registra una nueva cita con sus servicios asociados.
    public static function ctrCrearCita(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        if (self::fechaCalendarioPasada($_POST['fechaCitaCrear'] ?? null)) {
            self::redirigir('danger', 'No se puede programar una cita en una fecha pasada.');
        }
        $datos = self::datosFormulario('crear');
        if ($datos === null) {
            self::redirigir('danger', 'Los datos de la cita no son válidos o están fuera del horario de atención.');
        }
        $datos['estadoCita'] = self::ESTADO_PENDIENTE;

        $respuesta = ModeloCitas::mdlIngresarCita($datos);
        self::responderEscritura($respuesta, 'La cita fue programada correctamente.');
    }

    // Valida cambios de una cita y vuelve a comprobar su disponibilidad.
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
        if ((int) ($_SESSION['IDrol'] ?? 0) === 2
            && (int) $actual['IDusuario'] !== (int) ($_SESSION['IDusuario'] ?? 0)) {
            self::redirigir('warning', 'Solo puede modificar sus propias citas.');
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
            Seguridad::exigirRol([1, 2]);
            if ((int) ($_SESSION['IDrol'] ?? 0) === 2
                && (int) $actual['IDusuario'] !== (int) ($_SESSION['IDusuario'] ?? 0)) {
                self::redirigir('warning', 'Solo puede completar las citas que están asignadas a usted.');
            }
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

        if (self::fechaCalendarioPasada($_POST['fechaCitaEditar'] ?? null)) {
            self::redirigir('danger', 'No se puede reprogramar una cita en una fecha pasada.');
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

    // Normaliza los datos enviados desde el formulario de alta o edición.
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
        $idsServicios = self::normalizarIdsServicios(
            $_POST[$crear ? 'nuevoServicio' : 'editarServicio'] ?? null
        );
        $servicios = ModeloCitas::mdlObtenerServiciosActivos($idsServicios);
        $serviciosValidos = $idsServicios !== [] && count($servicios) === count($idsServicios);
        $duracion = self::DURACION_BLOQUE_MINUTOS;

        $veterinario = $idUsuario === false ? null : self::obtenerUsuarioAsignable((int) $idUsuario);
        $asignacionPermitida = (int) ($_SESSION['IDrol'] ?? 0) !== 2
            || (int) $idUsuario === (int) ($_SESSION['IDusuario'] ?? 0);
        if ($motivo === null || $fechaCompleta === null
            || $idMascota === false || !self::mascotaValida((int) $idMascota)
            || !is_array($veterinario)
            || !$asignacionPermitida
            || !$serviciosValidos
            || !self::terminaEnHorario($fechaCompleta, $duracion)
            || !self::dentroDelTurno(
                $fechaCompleta,
                $duracion,
                $veterinario['turno_U']
            )) {
            return null;
        }
        return [
            'motivoCita' => $motivo,
            'fechaAtencion' => $fechaCompleta,
            'IDmascota' => (int) $idMascota,
            'IDusuario' => (int) $idUsuario,
            'servicios' => $servicios,
            'duracionMinutos' => $duracion,
        ];
    }

    // Convierte la selección múltiple de servicios en identificadores válidos.
    private static function normalizarIdsServicios($valor): array
    {
        $valores = is_array($valor) ? $valor : ($valor === null || $valor === '' ? [] : [$valor]);
        $ids = [];
        foreach ($valores as $item) {
            $id = filter_var($item, FILTER_VALIDATE_INT);
            if ($id === false || (int) $id < 1) return [];
            $ids[] = (int) $id;
        }
        return array_values(array_unique($ids));
    }

    // Combina y valida la fecha y hora recibidas desde el formulario.
    private static function normalizarFechaHorario($fecha, $hora): ?string
    {
        if (!is_string($fecha) || !is_string($hora)
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1
            || preg_match('/^(\d{2}):(\d{2})$/', $hora, $partes) !== 1
            || (int) $partes[2] > 59 || (int) $partes[2] % self::DURACION_BLOQUE_MINUTOS !== 0) {
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

    // Indica si la fecha solicitada ya quedó en el pasado.
    private static function fechaCalendarioPasada($fecha): bool
    {
        return is_string($fecha)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1
            && $fecha < date('Y-m-d');
    }

    // Comprueba que la duración del servicio no salga del horario permitido.
    private static function terminaEnHorario(string $fechaAtencion, int $duracionMinutos): bool
    {
        $inicio = new DateTimeImmutable($fechaAtencion);
        $cierre = $inicio->setTime(self::HORA_CIERRE, 0);
        return $inicio->modify('+' . $duracionMinutos . ' minutes') <= $cierre;
    }

    private static function obtenerUsuarioAsignable(int $idUsuario): ?array
    {
        $usuario = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
        if (!is_array($usuario) || (int) $usuario['estado_U'] !== 1) {
            return null;
        }
        $rolSesion = (int) ($_SESSION['IDrol'] ?? 0);
        if ($rolSesion === 1) {
            return $usuario;
        }
        if ((int) $usuario['IDrol'] !== 2) {
            return null;
        }
        if ($rolSesion === 2 && (int) $usuario['IDusuario'] !== (int) ($_SESSION['IDusuario'] ?? 0)) {
            return null;
        }
        return $usuario;
    }

    // Comprueba que el veterinario trabaje durante la hora elegida.
    private static function dentroDelTurno(
        string $fechaAtencion,
        int $duracionMinutos,
        string $turno
    ): bool {
        [$inicioTurno, $finTurno] = match ($turno) {
            'Mañana' => [7, 13],
            'Tarde' => [13, 19],
            'Horario continuo' => [7, 19],
            default => [-1, -1],
        };
        if ($inicioTurno < 0) {
            return false;
        }
        $inicio = new DateTimeImmutable($fechaAtencion);
        $desde = $inicio->setTime($inicioTurno, 0);
        $hasta = $inicio->setTime($finTurno, 0);
        return $inicio >= $desde && $inicio->modify('+' . $duracionMinutos . ' minutes') <= $hasta;
    }

    private static function textoHorarioTurno(string $turno): string
    {
        return match ($turno) {
            'Mañana' => '07:00–13:00',
            'Tarde' => '13:00–19:00',
            'Horario continuo' => '07:00–19:00',
            default => 'sin horario válido',
        };
    }

    // Verifica que la mascota exista y esté habilitada para una cita.
    private static function mascotaValida(int $idMascota): bool
    {
        $mascota = ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota);
        return is_array($mascota)
            && (int) $mascota['habilitadoM'] === 1
            && (int) $mascota['habilitadoC'] === 1;
    }

    // Limpia y valida el motivo opcional de la cita.
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
        if (substr((string) ($cita['fechaAtencion'] ?? ''), 0, 10) > date('Y-m-d')) {
            self::redirigir('warning', 'No se puede completar una cita antes de la fecha programada.');
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
            'ocupado' => 'El veterinario ya tiene una cita en ese bloque.',
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
