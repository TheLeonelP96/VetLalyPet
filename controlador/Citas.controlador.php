<?php

class ControladorCitas
{
    private const ESTADO_PENDIENTE = 'Pendiente';
    private const ESTADO_CANCELADA = 'Cancelada';
    private const ESTADO_COMPLETADA = 'Completada';
    private const CLAVE_CONFIRMAR_REACTIVACION = 'cita_confirmar_reactivacion';
    private const VIGENCIA_CONFIRMACION = 300;

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

    private static function redirigirCalendario(string $tipo, string $mensaje): void
    {
        $_SESSION['cita_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Calendar');
        exit;
    }

    private static function normalizarFechaHorario($fecha, $hora, bool $permitirPasado = false): ?string
    {
        if (!is_string($fecha) || !is_string($hora)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
            || !preg_match('/^(\d{2}):(00|30)$/', $hora, $partes)) {
            return null;
        }

        $horaNumero = (int) $partes[1];
        if ($horaNumero < 7 || $horaNumero >= 19) {
            return null;
        }

        $fechaCita = DateTime::createFromFormat('!Y-m-d H:i', "$fecha $hora");
        $errores = DateTime::getLastErrors();
        if (!$fechaCita || (is_array($errores) && ($errores['warning_count'] || $errores['error_count']))) {
            return null;
        }
        if (!$permitirPasado && $fechaCita < new DateTime()) {
            return null;
        }
        return $fechaCita->format('Y-m-d H:i:s');
    }

    private static function veterinarioValido(int $idUsuario): bool
    {
        $usuario = ModeloUsuarios::MdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
        return is_array($usuario)
            && (int) $usuario['IDrol'] === 2
            && (int) $usuario['habilitado'] === 1;
    }

    private static function mascotaValida(int $idMascota): bool
    {
        $mascota = ModeloMascotas::MdlMostrarMascotas('mascotas', 'IDmascota', $idMascota);
        return is_array($mascota) && (int) $mascota['habilitadoM'] === 1;
    }

    private static function motivoValido($motivo): ?string
    {
        if (!is_string($motivo)) {
            return null;
        }
        $motivo = trim($motivo);
        return $motivo !== '' && strlen($motivo) <= 75 ? $motivo : null;
    }

    private static function confirmarReactivacion(int $idCita): bool
    {
        $confirmacion = $_SESSION[self::CLAVE_CONFIRMAR_REACTIVACION] ?? null;
        unset($_SESSION[self::CLAVE_CONFIRMAR_REACTIVACION]);

        if (is_array($confirmacion)
            && (int) ($confirmacion['IDcitas'] ?? 0) === $idCita
            && (int) ($confirmacion['expira'] ?? 0) >= time()) {
            return true;
        }

        $_SESSION[self::CLAVE_CONFIRMAR_REACTIVACION] = [
            'IDcitas' => $idCita,
            'expira' => time() + self::VIGENCIA_CONFIRMACION,
        ];
        return false;
    }

    public static function ctrMostrarCitas($item = null, $valor = null)
    {
        return ModeloCitas::mdlMostrarCitas($item, $valor);
    }

    public static function ctrCrearCita(): void
    {
        if (!isset($_POST['nuevoMotivo'])) {
            return;
        }

        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();

        $motivo = self::motivoValido($_POST['nuevoMotivo']);
        $fechaCompleta = self::normalizarFechaHorario(
            $_POST['fechaCitaCrear'] ?? null,
            $_POST['seleccionarHora'] ?? null
        );
        $idMascota = filter_var($_POST['seleccionarMascota'] ?? null, FILTER_VALIDATE_INT);
        $idUsuario = filter_var($_POST['nuevoUsuario'] ?? null, FILTER_VALIDATE_INT);

        if ($motivo === null || $fechaCompleta === null
            || $idMascota === false || !self::mascotaValida($idMascota)
            || $idUsuario === false || !self::veterinarioValido($idUsuario)) {
            self::redirigirCalendario('danger', 'Los datos de la cita no son válidos o el horario está fuera de atención.');
        }

        $respuesta = ModeloCitas::mdlIngresarCita([
            'motivoCita' => $motivo,
            'fechaAtencion' => $fechaCompleta,
            'estadoCita' => self::ESTADO_PENDIENTE,
            'colorCita' => '#3c8dbc',
            'IDmascota' => $idMascota,
            'IDusuario' => $idUsuario,
        ]);

        if ($respuesta === 'ocupado') {
            self::redirigirCalendario('warning', 'El veterinario ya tiene una cita activa en esa fecha y hora.');
        }
        self::redirigirCalendario(
            $respuesta === 'ok' ? 'success' : 'danger',
            $respuesta === 'ok' ? 'La cita fue programada correctamente.' : 'No se pudo registrar la cita.'
        );
    }

    public static function ctrEditarCita(): void
    {
        if (!isset($_POST['idCitaEditar'])) {
            return;
        }

        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();

        $idCita = filter_var($_POST['idCitaEditar'], FILTER_VALIDATE_INT);
        $accion = $_POST['btnAccionCita'] ?? 'guardar';
        if ($idCita === false || !in_array($accion, ['cancelar', 'reactivar', 'completar', 'guardar'], true)) {
            self::redirigirCalendario('danger', 'La operación solicitada no es válida.');
        }

        $actual = ModeloCitas::mdlMostrarCitas('IDcitas', $idCita);
        if (!is_array($actual)) {
            self::redirigirCalendario('danger', 'La cita ya no existe.');
        }

        if ($accion === 'cancelar') {
            unset($_SESSION[self::CLAVE_CONFIRMAR_REACTIVACION]);
            if ($actual['estadoCita'] !== self::ESTADO_PENDIENTE) {
                self::redirigirCalendario('warning', 'Solo se pueden cancelar citas pendientes.');
            }
            $respuesta = ModeloCitas::mdlActualizarEstadoCita([
                'IDcitas' => $idCita,
                'estadoCita' => self::ESTADO_CANCELADA,
                'estadoActual' => self::ESTADO_PENDIENTE,
                'colorCita' => '#dd4b39',
            ]);
            self::redirigirCalendario(
                $respuesta === 'ok' ? 'success' : 'danger',
                $respuesta === 'ok'
                    ? 'La cita fue cancelada. Se conservó su información y puede reactivarse.'
                    : 'No se pudo cancelar la cita.'
            );
        }

        if ($accion === 'completar') {
            unset($_SESSION[self::CLAVE_CONFIRMAR_REACTIVACION]);
            if ($actual['estadoCita'] !== self::ESTADO_PENDIENTE) {
                self::redirigirCalendario('warning', 'Solo se pueden completar citas pendientes.');
            }
            $respuesta = ModeloCitas::mdlActualizarEstadoCita([
                'IDcitas' => $idCita,
                'estadoCita' => self::ESTADO_COMPLETADA,
                'estadoActual' => self::ESTADO_PENDIENTE,
                'colorCita' => '#00a65a',
            ]);
            self::redirigirCalendario(
                $respuesta === 'ok' ? 'success' : 'danger',
                $respuesta === 'ok'
                    ? 'La cita fue completada correctamente.'
                    : 'No se pudo completar la cita.'
            );
        }

        if ($actual['estadoCita'] === self::ESTADO_COMPLETADA) {
            self::redirigirCalendario('warning', 'Una cita completada ya no puede modificarse.');
        }
        if ($accion === 'reactivar' && $actual['estadoCita'] !== self::ESTADO_CANCELADA) {
            self::redirigirCalendario('warning', 'Solo se pueden reactivar citas canceladas.');
        }
        if ($accion === 'guardar' && $actual['estadoCita'] !== self::ESTADO_PENDIENTE) {
            self::redirigirCalendario('warning', 'La cita debe estar pendiente para realizar esa acción.');
        }

        if ($accion === 'reactivar' && !self::confirmarReactivacion($idCita)) {
            self::redirigirCalendario(
                'warning',
                'Primer paso registrado. Abra la cita y presione Reactivar Cita nuevamente para confirmar.'
            );
        }
        if ($accion !== 'reactivar') {
            unset($_SESSION[self::CLAVE_CONFIRMAR_REACTIVACION]);
        }

        $motivo = self::motivoValido($_POST['editarMotivo'] ?? null);
        $fechaCompleta = self::normalizarFechaHorario(
            $_POST['fechaCitaEditar'] ?? null,
            $_POST['editarHora'] ?? null,
            false
        );
        $idMascota = filter_var($_POST['editarMascota'] ?? null, FILTER_VALIDATE_INT);
        $idUsuario = filter_var($_POST['editarUsuario'] ?? null, FILTER_VALIDATE_INT);

        if ($motivo === null || $fechaCompleta === null
            || $idMascota === false || !self::mascotaValida($idMascota)
            || $idUsuario === false || !self::veterinarioValido($idUsuario)) {
            self::redirigirCalendario('danger', 'Los datos de la cita no son válidos o el horario está fuera de atención.');
        }

        $respuesta = ModeloCitas::mdlEditarCita([
            'IDcitas' => $idCita,
            'motivoCita' => $motivo,
            'fechaAtencion' => $fechaCompleta,
            'estadoCita' => self::ESTADO_PENDIENTE,
            'colorCita' => '#3c8dbc',
            'IDmascota' => $idMascota,
            'IDusuario' => $idUsuario,
        ]);

        if ($respuesta === 'ocupado') {
            self::redirigirCalendario('warning', 'El veterinario ya tiene otra cita en esa fecha y hora.');
        }
        if ($respuesta !== 'ok') {
            self::redirigirCalendario('danger', 'No se pudo procesar la cita.');
        }

        $mensajes = [
            'reactivar' => 'La cita fue reactivada correctamente.',
            'guardar' => 'La cita fue actualizada correctamente.',
        ];
        self::redirigirCalendario('success', $mensajes[$accion]);
    }
}
