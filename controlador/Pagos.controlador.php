<?php

final class ControladorPagos
{
    private const METODOS = ['Efectivo', 'QR', 'Transferencia', 'Tarjeta'];

    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevaCitaPago'], $_POST['nuevoMetodoPago'])) {
            self::ctrCrearPago();
            return;
        }
        if (isset($_POST['IDpago'], $_POST['editarMetodoPago'])) {
            self::ctrEditarPago();
        }
    }

    public static function ctrMostrarPagos($item = null, $valor = null): mixed
    {
        return ModeloPagos::mdlMostrarPagos($item, $valor);
    }

    public static function ctrListarCitasPendientesPago(): array
    {
        return ModeloPagos::mdlListarCitasPendientesPago();
    }

    public static function ctrDetalleCobroCita(int $idCita): ?array
    {
        return ModeloPagos::mdlDetalleCobroCita($idCita);
    }

    public static function ctrMetodosPago(): array
    {
        return self::METODOS;
    }

    private static function ctrCrearPago(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigir('danger', 'Revise los datos del pago e intente nuevamente.');
        }
        self::responder(ModeloPagos::mdlCrearPago($datos), 'Pago registrado correctamente.');
    }

    private static function ctrEditarPago(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $idPago = filter_var($_POST['IDpago'] ?? null, FILTER_VALIDATE_INT);
        $actual = $idPago === false ? null : ModeloPagos::mdlMostrarPagos('IDpago', (int) $idPago);
        $datos = self::datosFormulario('editar', is_array($actual) ? (int) $actual['IDcita'] : null);
        if ($idPago === false || (int) $idPago < 1 || !is_array($actual) || $datos === null) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }
        $datos['IDpago'] = (int) $idPago;
        self::responder(ModeloPagos::mdlEditarPago($datos), 'Pago actualizado correctamente.');
    }

    private static function datosFormulario(string $prefijo, ?int $idCitaExistente = null): ?array
    {
        $nuevo = $prefijo === 'nuevo';
        $campos = $nuevo
            ? ['nuevoMetodoPago', 'nuevaReferenciaPago', 'nuevaFechaPago', 'nuevaCitaPago']
            : ['editarMetodoPago', 'editarReferenciaPago', 'editarFechaPago'];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $metodo = trim($_POST[$campos[0]]);
        $referencia = trim($_POST[$campos[1]]);
        $fecha = self::fechaHoraValida(trim($_POST[$campos[2]]));
        $idCita = $nuevo ? filter_var($_POST[$campos[3]], FILTER_VALIDATE_INT) : $idCitaExistente;

        if (!in_array($metodo, self::METODOS, true)
            || mb_strlen($referencia) > 100
            || ($metodo !== 'Efectivo' && $referencia === '')
            || $fecha === null || $fecha > date('Y-m-d H:i:s')
            || $idCita === false || $idCita === null || (int) $idCita < 1) {
            return null;
        }
        return [
            'metodo' => $metodo,
            'referencia' => $metodo === 'Efectivo' ? '' : $referencia,
            'fecha' => $fecha,
            'IDcita' => (int) $idCita,
        ];
    }

    private static function fechaHoraValida(string $fecha): ?string
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fecha);
        return $objeto && $objeto->format('Y-m-d\TH:i') === $fecha
            ? $objeto->format('Y-m-d H:i:s') : null;
    }

    private static function responder(string $respuesta, string $mensajeOk): void
    {
        $mensajes = [
            'duplicado' => 'La cita seleccionada ya tiene un pago registrado.',
            'cita_invalida' => 'Solo se pueden cobrar citas completadas.',
            'sin_vacunas' => 'Registre primero las vacunas aplicadas en esta cita.',
            'monto_invalido' => 'El servicio no tiene un monto válido para cobrar.',
            'no_encontrado' => 'El pago solicitado no existe.',
        ];
        if ($respuesta === 'ok') {
            self::redirigir('success', $mensajeOk);
        }
        self::redirigir(isset($mensajes[$respuesta]) ? 'warning' : 'danger',
            $mensajes[$respuesta] ?? 'No se pudo guardar el pago.');
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['pago_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Pagos');
        exit;
    }
}
