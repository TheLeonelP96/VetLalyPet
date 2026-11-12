<?php
// Valida los datos del cobro y delega en el modelo el registro o edición del pago.

final class ControladorPagos
{
    private const METODOS = ['Efectivo', 'QR'];

    // Identifica la operación solicitada y dirige la creación o edición del pago.
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

    // Obtiene pagos para listados o para consultar un registro específico.
    public static function ctrMostrarPagos($item = null, $valor = null): mixed
    {
        return ModeloPagos::mdlMostrarPagos($item, $valor);
    }

    // Lista citas completadas que todavía pueden cobrarse.
    public static function ctrListarCitasPendientesPago(): array
    {
        return ModeloPagos::mdlListarCitasPendientesPago();
    }

    // Calcula los servicios y el total cobrable de una cita.
    public static function ctrDetalleCobroCita(int $idCita): ?array
    {
        return ModeloPagos::mdlDetalleCobroCita($idCita);
    }

    // Recupera los datos necesarios para mostrar un comprobante.
    public static function ctrObtenerComprobante($idPago): ?array
    {
        Seguridad::exigirAutenticacion();
        $idPago = filter_var($idPago, FILTER_VALIDATE_INT);
        return $idPago === false || (int) $idPago < 1
            ? null : ModeloPagos::mdlObtenerComprobante((int) $idPago);
    }

    // Devuelve los métodos de pago habilitados por el sistema.
    public static function ctrMetodosPago(): array
    {
        return self::METODOS;
    }

    // Valida la sesión y registra un nuevo pago mediante el modelo.
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

    // Autoriza y actualiza los datos permitidos de un pago existente.
    private static function ctrEditarPago(): void
    {
        Seguridad::exigirRol([1]);
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

    // Normaliza y valida los campos recibidos desde los formularios de pago.
    private static function datosFormulario(string $prefijo, ?int $idCitaExistente = null): ?array
    {
        $nuevo = $prefijo === 'nuevo';
        $campos = $nuevo
            ? ['nuevoMetodoPago', 'nuevaFechaPago', 'nuevaCitaPago', 'nuevoUsuarioPago']
            : ['editarMetodoPago', 'editarFechaPago', 'editarUsuarioPago'];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $metodo = trim($_POST[$campos[0]]);
        $fecha = self::fechaHoraValida(trim($_POST[$campos[1]]));
        $idCita = $nuevo ? filter_var($_POST[$campos[2]], FILTER_VALIDATE_INT) : $idCitaExistente;
        $idUsuario = filter_var($_POST[$nuevo ? $campos[3] : $campos[2]], FILTER_VALIDATE_INT);
        $usuario = $idUsuario === false
            ? null : ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', (int) $idUsuario);

        if (!in_array($metodo, self::METODOS, true)
            || $fecha === null || $fecha > date('Y-m-d H:i:s')
            || $idCita === false || $idCita === null || (int) $idCita < 1
            || !is_array($usuario) || (int) $usuario['estado_U'] !== 1) {
            return null;
        }
        return [
            'metodo' => $metodo,
            'fecha' => $fecha,
            'IDcita' => (int) $idCita,
            'IDusuario' => (int) $idUsuario,
        ];
    }

    // Convierte una fecha HTML válida al formato utilizado por MySQL.
    private static function fechaHoraValida(string $fecha): ?string
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $fecha);
        return $objeto && $objeto->format('Y-m-d\TH:i') === $fecha
            ? $objeto->format('Y-m-d H:i:s') : null;
    }

    // Traduce los códigos del modelo a mensajes para el usuario.
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

    // Redirige al módulo mostrando el resultado de la operación.
    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['pago_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Pagos');
        exit;
    }
}
