<?php
// Coordina las altas, modificaciones, consultas e inhabilitaciones de clientes.

final class ControladorClientes
{
    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevoCliente'])) {
            self::ctrCrearCliente();
            return;
        }
        if (isset($_POST['editarCliente'])) {
            self::ctrModificarClientes();
            return;
        }
        if (isset($_POST['IDcliente'], $_POST['estadoCliente'])) {
            self::ctrActualizarEstadoCliente();
        }
    }

    public static function crtMostrarClientes($item = null, $valor = null): mixed
    {
        return ModeloClientes::mdlMostrarClientes($item, $valor);
    }

    public static function ctrListarClientesHabilitados(): array
    {
        return ModeloClientes::mdlListarClientesHabilitados();
    }

    public static function ctrCrearCliente(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigir('danger', 'Revise los datos del cliente e intente nuevamente.');
        }

        $respuesta = ModeloClientes::mdlCrearCliente($datos);
        self::redirigir(
            $respuesta === 'ok' ? 'success' : 'danger',
            $respuesta === 'ok' ? 'Cliente registrado correctamente.' : 'No se pudo registrar el cliente.'
        );
    }

    public static function ctrModificarClientes(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $idCliente = filter_var($_POST['IDcliente'] ?? null, FILTER_VALIDATE_INT);
        $datos = self::datosFormulario('editar');
        $actual = $idCliente === false ? null : ModeloClientes::mdlMostrarClientes('IDcliente', $idCliente);
        if ($datos === null || !is_array($actual)) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }

        $datos['IDcliente'] = (int) $idCliente;
        $respuesta = ModeloClientes::mdlEditarCliente($datos);
        self::redirigir(
            $respuesta === 'ok' ? 'success' : 'danger',
            $respuesta === 'ok' ? 'Cliente actualizado correctamente.' : 'No se pudo actualizar el cliente.'
        );
    }

    public static function ctrActualizarEstadoCliente(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $idCliente = filter_var($_POST['IDcliente'] ?? null, FILTER_VALIDATE_INT);
        $estado = filter_var($_POST['estadoCliente'] ?? null, FILTER_VALIDATE_INT);
        if ($idCliente === false || !in_array($estado, [0, 1], true)) {
            self::redirigir('danger', 'La operación solicitada no es válida.');
        }

        $respuesta = ModeloClientes::mdlActualizarEstadoCliente((int) $idCliente, (int) $estado);
        if ($respuesta === 'no_encontrado') {
            self::redirigir('danger', 'El cliente solicitado no existe.');
        }
        self::redirigir(
            'success',
            $estado === 1 ? 'Cliente habilitado correctamente.' : 'Cliente deshabilitado correctamente.'
        );
    }

    // Compatibilidad con nombres anteriores.
    public static function crtCrearCliente(): void
    {
        if (isset($_POST['nuevoCliente'])) {
            self::ctrCrearCliente();
        }
    }

    public static function crtEliminarClientes(): void
    {
        if (isset($_POST['IDcliente'])) {
            $_POST['estadoCliente'] = 0;
            self::ctrActualizarEstadoCliente();
        }
    }

    private static function datosFormulario(string $prefijo): ?array
    {
        $campos = $prefijo === 'nuevo'
            ? ['nuevoCliente', 'nuevoApellidoc', 'nuevoTelefono', 'nuevoRegistro']
            : ['editarCliente', 'editarApellidoc', 'editarTelefono', 'editarRegistro'];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $nombre = trim($_POST[$campos[0]]);
        $apellido = trim($_POST[$campos[1]]);
        $telefono = trim($_POST[$campos[2]]);
        $registro = trim($_POST[$campos[3]]);
        if (!self::nombreValido($nombre) || !self::nombreValido($apellido)
            || preg_match('/^[0-9+() -]{7,20}$/', $telefono) !== 1
            || !self::fechaValida($registro, false)) {
            return null;
        }
        return compact('nombre', 'apellido', 'telefono', 'registro');
    }

    private static function nombreValido(string $valor): bool
    {
        return mb_strlen($valor) >= 2 && mb_strlen($valor) <= 50
            && preg_match("/^[\p{L}\p{M} .'-]+$/u", $valor) === 1;
    }

    private static function fechaValida(string $fecha, bool $permiteFutura): bool
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if (!$objeto || $objeto->format('Y-m-d') !== $fecha) {
            return false;
        }
        return $permiteFutura || $fecha <= date('Y-m-d');
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['cliente_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Clientes');
        exit;
    }
}
