<?php

final class ControladorVacunas
{
    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevaVacuna'])) {
            self::ctrCrearVacuna();
            return;
        }
        if (isset($_POST['editarVacuna'])) {
            self::ctrEditarVacuna();
            return;
        }
        if (isset($_POST['IDvacuna'], $_POST['estadoVacuna'])) {
            self::ctrActualizarEstadoVacuna();
        }
    }

    public static function ctrMostrarVacunas($item = null, $valor = null): mixed
    {
        return ModeloVacunas::mdlMostrarVacunas($item, $valor);
    }

    public static function ctrListarEspecies(): array
    {
        return ModeloVacunas::mdlListarEspecies();
    }

    public static function ctrCrearVacuna(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nueva');
        if ($datos === null) {
            self::redirigir('danger', 'Revise los datos de la vacuna e intente nuevamente.');
        }
        self::responder(ModeloVacunas::mdlCrearVacuna($datos), 'Vacuna registrada correctamente.');
    }

    public static function ctrEditarVacuna(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $idVacuna = filter_var($_POST['IDvacuna'] ?? null, FILTER_VALIDATE_INT);
        $datos = self::datosFormulario('editar');
        $actual = $idVacuna === false ? null : ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacuna);
        if ($datos === null || !is_array($actual)) {
            self::redirigir('danger', 'Los datos enviados no son válidos.');
        }
        $datos['IDvacuna'] = (int) $idVacuna;
        self::responder(ModeloVacunas::mdlEditarVacuna($datos), 'Vacuna actualizada correctamente.');
    }

    public static function ctrActualizarEstadoVacuna(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();
        $idVacuna = filter_var($_POST['IDvacuna'] ?? null, FILTER_VALIDATE_INT);
        $estado = filter_var($_POST['estadoVacuna'] ?? null, FILTER_VALIDATE_INT);
        if ($idVacuna === false || !in_array($estado, [0, 1], true)) {
            self::redirigir('danger', 'La operación solicitada no es válida.');
        }
        $respuesta = ModeloVacunas::mdlActualizarEstadoVacuna((int) $idVacuna, (int) $estado);
        if ($respuesta === 'no_encontrada') {
            self::redirigir('danger', 'La vacuna solicitada no existe.');
        }
        if (!in_array($respuesta, ['ok', 'sin_cambios'], true)) {
            self::redirigir('danger', 'No se pudo actualizar el estado de la vacuna.');
        }
        self::redirigir(
            'success',
            $estado === 1 ? 'Vacuna habilitada correctamente.' : 'Vacuna deshabilitada correctamente.'
        );
    }

    private static function datosFormulario(string $prefijo): ?array
    {
        $campos = $prefijo === 'nueva'
            ? ['nuevaVacuna', 'nuevaDescripcionVacuna', 'nuevoPrecioVacuna', 'nuevoStockVacuna', 'nuevaEspecieVacuna']
            : ['editarVacuna', 'editarDescripcionVacuna', 'editarPrecioVacuna', 'editarStockVacuna', 'editarEspecieVacuna'];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $nombre = trim($_POST[$campos[0]]);
        $descripcion = trim($_POST[$campos[1]]);
        $precioTexto = str_replace(',', '.', trim($_POST[$campos[2]]));
        $stock = filter_var($_POST[$campos[3]], FILTER_VALIDATE_INT);
        $idEspecie = filter_var($_POST[$campos[4]], FILTER_VALIDATE_INT);
        if ($nombre === '' || mb_strlen($nombre) > 50
            || preg_match("/^[\\p{L}\\p{M}0-9 .()\\/,+\\'-]+$/u", $nombre) !== 1
            || $descripcion === '' || mb_strlen($descripcion) > 255
            || !is_numeric($precioTexto) || (float) $precioTexto < 0 || (float) $precioTexto > 99999999.99
            || $stock === false || (int) $stock < 0
            || $idEspecie === false || !ModeloVacunas::mdlEspecieExiste((int) $idEspecie)) {
            return null;
        }
        return [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => number_format((float) $precioTexto, 2, '.', ''),
            'stock' => (int) $stock,
            'IDespecie' => (int) $idEspecie,
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
                ? 'Ya existe una vacuna con ese nombre para la especie seleccionada.'
                : 'No se pudo guardar la vacuna.'
        );
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['vacuna_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Vacunas');
        exit;
    }
}
