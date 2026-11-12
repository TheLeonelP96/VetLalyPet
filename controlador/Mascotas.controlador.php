<?php
// Coordina el registro y mantenimiento de mascotas y sus relaciones.

final class ControladorMascotas
{
    private const SEXOS = ['Macho', 'Hembra'];

    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['crearClienteMascota'])) {
            self::ctrCrearClienteConMascota();
            return;
        }
        if (isset($_POST['crearEspecieRaza'])) {
            self::ctrCrearEspecieConRaza();
            return;
        }
        if (isset($_POST['nuevoMascota'])) {
            self::ctrCrearMascotas();
            return;
        }
        if (isset($_POST['editarMascota'])) {
            self::ctrModificarMascotas();
            return;
        }
        if (isset($_POST['IDmascota'], $_POST['estadoMascota'])) {
            self::ctrActualizarEstadoMascota();
        }
    }

    public static function crtMostrarMascotas($item = null, $valor = null): mixed
    {
        return ModeloMascotas::mdlMostrarMascotas($item, $valor);
    }

    public static function ctrListarEspecies(): array
    {
        return ModeloMascotas::mdlListarEspecies();
    }

    public static function ctrListarRazas(?int $idEspecie = null): array
    {
        return ModeloMascotas::mdlListarRazas($idEspecie);
    }

    public static function ctrCrearMascotas(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigir('danger', 'Revise los datos de la mascota e intente nuevamente.');
        }

        $respuesta = ModeloMascotas::mdlCrearMascota($datos);
        self::responderEscritura($respuesta, 'Mascota registrada correctamente.');
    }

    public static function ctrCrearClienteConMascota(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $cliente = self::datosClienteConjunto();
        $mascota = self::datosMascotaConjunto();
        if ($cliente === null || $mascota === null) {
            self::redirigir('danger', 'Revise los datos del cliente y la mascota e intente nuevamente.');
        }

        $respuesta = ModeloMascotas::mdlCrearClienteConMascota($cliente, $mascota);
        self::responderEscritura($respuesta, 'Cliente y mascota registrados correctamente.');
    }

    public static function ctrCrearEspecieConRaza(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $especie = trim((string) ($_POST['nombreEspecie'] ?? ''));
        $raza = trim((string) ($_POST['nombreRaza'] ?? ''));
        if (!self::nombreClasificacionValido($especie) || !self::nombreClasificacionValido($raza)) {
            self::redirigir('danger', 'Revise los nombres de la especie y la raza.');
        }

        $respuesta = ModeloMascotas::mdlCrearEspecieConRaza($especie, $raza);
        if ($respuesta === 'duplicado') {
            self::redirigir('warning', 'La especie indicada ya está registrada.');
        }
        self::redirigir(
            $respuesta === 'ok' ? 'success' : 'danger',
            $respuesta === 'ok' ? 'Especie y raza registradas correctamente.' : 'No se pudo registrar la especie y la raza.'
        );
    }

    public static function ctrModificarMascotas(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $idMascota = filter_var($_POST['IDmascota'] ?? null, FILTER_VALIDATE_INT);
        $datos = self::datosFormulario('editar');
        $actual = $idMascota === false ? null : ModeloMascotas::mdlMostrarMascotas('IDmascota', $idMascota);
        if ($datos === null || !is_array($actual)) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }

        $datos['IDmascota'] = (int) $idMascota;
        $respuesta = ModeloMascotas::mdlEditarMascota($datos);
        self::responderEscritura($respuesta, 'Mascota actualizada correctamente.');
    }

    public static function ctrActualizarEstadoMascota(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $idMascota = filter_var($_POST['IDmascota'] ?? null, FILTER_VALIDATE_INT);
        $estado = filter_var($_POST['estadoMascota'] ?? null, FILTER_VALIDATE_INT);
        if ($idMascota === false || !in_array($estado, [0, 1], true)) {
            self::redirigir('danger', 'La operación solicitada no es válida.');
        }

        $respuesta = ModeloMascotas::mdlActualizarEstadoMascota((int) $idMascota, (int) $estado);
        if ($respuesta === 'no_encontrado') {
            self::redirigir('danger', 'La mascota solicitada no existe.');
        }
        self::redirigir(
            'success',
            $estado === 1 ? 'Mascota habilitada correctamente.' : 'Mascota deshabilitada correctamente.'
        );
    }

    // Compatibilidad con nombres anteriores.
    public static function crtDesHabilitarMascotas(): void
    {
        if (isset($_POST['IDmascota'], $_POST['estadoMascota'])) {
            self::ctrActualizarEstadoMascota();
        }
    }

    public static function crtEliminarMascotas(): void
    {
        if (isset($_POST['IDmascota'])) {
            $_POST['estadoMascota'] = 0;
            self::ctrActualizarEstadoMascota();
        }
    }

    private static function datosFormulario(string $prefijo): ?array
    {
        $campos = $prefijo === 'nuevo'
            ? [
                'nuevoMascota', 'nuevaFechaRegistro', 'nuevaFechaNacimiento', 'nuevoPeso',
                'nuevoSexo', 'nuevoColor', 'nuevoCliente', 'nuevaRaza',
            ]
            : [
                'editarMascota', 'editarFechaRegistro', 'editarFechaNacimiento', 'editarPeso',
                'editarSexo', 'editarColor', 'editarCliente', 'editarRaza',
            ];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $nombre = trim($_POST[$campos[0]]);
        $registro = trim($_POST[$campos[1]]);
        $nacimiento = trim($_POST[$campos[2]]);
        $pesoTexto = str_replace(',', '.', trim($_POST[$campos[3]]));
        $sexo = trim($_POST[$campos[4]]);
        $descripcion = '';
        $color = trim($_POST[$campos[5]]);
        $idCliente = filter_var($_POST[$campos[6]], FILTER_VALIDATE_INT);
        $idRaza = filter_var($_POST[$campos[7]], FILTER_VALIDATE_INT);

        $cliente = $idCliente === false
            ? null
            : ModeloClientes::mdlMostrarClientes('IDcliente', $idCliente);
        if (!self::nombreMascotaValido($nombre)
            || !self::fechaValida($registro) || !self::fechaValida($nacimiento)
            || $nacimiento > $registro
            || !is_numeric($pesoTexto) || (float) $pesoTexto <= 0 || (float) $pesoTexto > 9999.99
            || !in_array($sexo, self::SEXOS, true)
            || mb_strlen($color) > 50
            || ($color !== '' && preg_match("/^[\p{L}\p{M} 0-9.,'-]+$/u", $color) !== 1)
            || !is_array($cliente) || (int) $cliente['habilitadoC'] !== 1
            || $idRaza === false || !ModeloMascotas::mdlExisteRaza((int) $idRaza)) {
            return null;
        }

        return [
            'nombre' => $nombre,
            'registro' => $registro,
            'nacimiento' => $nacimiento,
            'peso' => number_format((float) $pesoTexto, 2, '.', ''),
            'sexo' => $sexo,
            'descripcion' => $descripcion,
            'color' => $color,
            'IDcliente' => (int) $idCliente,
            'IDraza' => (int) $idRaza,
        ];
    }

    private static function datosClienteConjunto(): ?array
    {
        $nombre = trim((string) ($_POST['conjuntoNombreCliente'] ?? ''));
        $apellido = trim((string) ($_POST['conjuntoApellidoCliente'] ?? ''));
        $telefono = trim((string) ($_POST['conjuntoTelefonoCliente'] ?? ''));
        $registro = trim((string) ($_POST['conjuntoRegistroCliente'] ?? ''));
        if (!self::nombrePersonaValido($nombre) || !self::nombrePersonaValido($apellido)
            || preg_match('/^[0-9+() -]{7,20}$/', $telefono) !== 1
            || !self::fechaValida($registro)) {
            return null;
        }
        return compact('nombre', 'apellido', 'telefono', 'registro');
    }

    private static function datosMascotaConjunto(): ?array
    {
        $nombre = trim((string) ($_POST['conjuntoNombreMascota'] ?? ''));
        $registro = trim((string) ($_POST['conjuntoRegistroMascota'] ?? ''));
        $nacimiento = trim((string) ($_POST['conjuntoNacimientoMascota'] ?? ''));
        $pesoTexto = str_replace(',', '.', trim((string) ($_POST['conjuntoPesoMascota'] ?? '')));
        $sexo = trim((string) ($_POST['conjuntoSexoMascota'] ?? ''));
        $descripcion = '';
        $color = trim((string) ($_POST['conjuntoColorMascota'] ?? ''));
        $idRaza = filter_var($_POST['conjuntoRazaMascota'] ?? null, FILTER_VALIDATE_INT);
        if (!self::nombreMascotaValido($nombre)
            || !self::fechaValida($registro) || !self::fechaValida($nacimiento) || $nacimiento > $registro
            || !is_numeric($pesoTexto) || (float) $pesoTexto <= 0 || (float) $pesoTexto > 9999.99
            || !in_array($sexo, self::SEXOS, true)
            || mb_strlen($color) > 50
            || ($color !== '' && preg_match("/^[\p{L}\p{M} 0-9.,'-]+$/u", $color) !== 1)
            || $idRaza === false || !ModeloMascotas::mdlExisteRaza((int) $idRaza)) {
            return null;
        }
        return [
            'nombre' => $nombre, 'registro' => $registro, 'nacimiento' => $nacimiento,
            'peso' => number_format((float) $pesoTexto, 2, '.', ''), 'sexo' => $sexo,
            'descripcion' => $descripcion, 'color' => $color, 'IDraza' => (int) $idRaza,
        ];
    }

    private static function nombrePersonaValido(string $valor): bool
    {
        return mb_strlen($valor) >= 2 && mb_strlen($valor) <= 50
            && preg_match("/^[\p{L}\p{M} .'-]+$/u", $valor) === 1;
    }

    private static function nombreClasificacionValido(string $valor): bool
    {
        return mb_strlen($valor) >= 2 && mb_strlen($valor) <= 50
            && preg_match("/^[\p{L}\p{M} .'-]+$/u", $valor) === 1;
    }

    private static function nombreMascotaValido(string $nombre): bool
    {
        return mb_strlen($nombre) >= 1 && mb_strlen($nombre) <= 50
            && preg_match("/^[\p{L}\p{M}0-9 .'-]+$/u", $nombre) === 1;
    }

    private static function fechaValida(string $fecha): bool
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto !== false && $objeto->format('Y-m-d') === $fecha
            && $fecha <= date('Y-m-d');
    }

    private static function responderEscritura(string $respuesta, string $mensajeOk): void
    {
        if ($respuesta === 'ok') {
            self::redirigir('success', $mensajeOk);
        }
        self::redirigir(
            'danger',
            $respuesta === 'relacion_invalida'
                ? 'El dueño o la raza seleccionada ya no está disponible.'
                : 'No se pudo guardar la mascota.'
        );
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['mascota_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Mascotas');
        exit;
    }
}
