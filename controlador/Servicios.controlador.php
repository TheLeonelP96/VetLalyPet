<?php
// Valida y coordina las operaciones del catálogo de tipos de servicio.

final class ControladorServicios
{
    private const SERVICIO_VACUNACION = 'Vacunación';

    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevoServicio'])) {
            self::ctrCrearServicio();
            return;
        }
        if (isset($_POST['editarServicio'], $_POST['IDservicio'])) {
            self::ctrEditarServicio();
            return;
        }
        if (isset($_POST['IDservicio'], $_POST['estadoServicio'])) {
            self::ctrActualizarEstadoServicio();
        }
    }

    public static function ctrMostrarServicios($item = null, $valor = null): mixed
    {
        return ModeloServicios::mdlMostrarServicios($item, $valor);
    }

    private static function ctrCrearServicio(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigir('danger', 'Revise el nombre, la descripción y el precio del servicio.');
        }
        self::responder(ModeloServicios::mdlCrearServicio($datos), 'Servicio registrado correctamente.');
    }

    private static function ctrEditarServicio(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $idServicio = filter_var($_POST['IDservicio'] ?? null, FILTER_VALIDATE_INT);
        $actual = $idServicio === false
            ? null : ModeloServicios::mdlMostrarServicios('IDservicio', (int) $idServicio);
        $datos = self::datosFormulario('editar');
        if (!is_array($actual) || $datos === null) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }
        if ($actual['tipoServicio'] === self::SERVICIO_VACUNACION) {
            $datos['tipo'] = self::SERVICIO_VACUNACION;
            $datos['precio'] = '0.00';
        }
        $datos['IDservicio'] = (int) $idServicio;
        self::responder(ModeloServicios::mdlEditarServicio($datos), 'Servicio actualizado correctamente.');
    }

    private static function ctrActualizarEstadoServicio(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $idServicio = filter_var($_POST['IDservicio'] ?? null, FILTER_VALIDATE_INT);
        $estado = filter_var($_POST['estadoServicio'] ?? null, FILTER_VALIDATE_INT);
        if ($idServicio === false || !in_array($estado, [0, 1], true)) {
            self::redirigir('danger', 'La operación solicitada no es válida.');
        }
        $respuesta = ModeloServicios::mdlActualizarEstadoServicio((int) $idServicio, (int) $estado);
        if (!in_array($respuesta, ['ok', 'sin_cambios'], true)) {
            self::redirigir('danger', 'No se pudo actualizar el estado del servicio.');
        }
        self::redirigir('success', $estado === 1
            ? 'Servicio habilitado correctamente.'
            : 'Servicio deshabilitado. Sus citas e historial se conservaron.');
    }

    private static function datosFormulario(string $prefijo): ?array
    {
        $nuevo = $prefijo === 'nuevo';
        $campoTipo = $nuevo ? 'nuevoServicio' : 'editarServicio';
        $campoDescripcion = $nuevo ? 'nuevaDescripcionServicio' : 'editarDescripcionServicio';
        $campoPrecio = $nuevo ? 'nuevoPrecioServicio' : 'editarPrecioServicio';
        $campoDuracion = $nuevo ? 'nuevaDuracionServicio' : 'editarDuracionServicio';
        if (!isset($_POST[$campoTipo], $_POST[$campoDescripcion], $_POST[$campoPrecio], $_POST[$campoDuracion])
            || !is_string($_POST[$campoTipo])
            || !is_string($_POST[$campoDescripcion])
            || !is_string($_POST[$campoPrecio]) || !is_string($_POST[$campoDuracion])) {
            return null;
        }
        $tipo = trim($_POST[$campoTipo]);
        $descripcion = trim($_POST[$campoDescripcion]);
        $precioTexto = str_replace(',', '.', trim($_POST[$campoPrecio]));
        $duracion = filter_var($_POST[$campoDuracion], FILTER_VALIDATE_INT);
        if ($tipo === '' || mb_strlen($tipo) > 50
            || preg_match("/^[\\p{L}\\p{M}0-9 .()\\/,+&'-]+$/u", $tipo) !== 1
            || $descripcion === '' || mb_strlen($descripcion) > 255
            || !is_numeric($precioTexto)
            || (float) $precioTexto < 0 || (float) $precioTexto > 99999999.99
            || $duracion === false || (int) $duracion < 5 || (int) $duracion > 480) {
            return null;
        }
        return [
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'precio' => number_format((float) $precioTexto, 2, '.', ''),
            'duracion' => (int) $duracion,
        ];
    }

    private static function responder(string $respuesta, string $mensajeOk): void
    {
        if ($respuesta === 'ok') {
            self::redirigir('success', $mensajeOk);
        }
        self::redirigir(
            $respuesta === 'duplicado' ? 'warning' : 'danger',
            $respuesta === 'duplicado'
                ? 'Ya existe un servicio con ese nombre.'
                : 'No se pudo guardar el servicio.'
        );
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['servicio_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Servicios');
        exit;
    }
}
