<?php
// Coordina la configuración de dosis y esquemas de vacunación.

final class ControladorSeguimientoVacunas
{
    public static function ctrProcesarSolicitud(): void
    {
        Seguridad::exigirRol([1, 2]);
        Seguridad::exigirCsrf();
        $accion = $_POST['accionEsquema'] ?? '';
        if ($accion === 'eliminar') {
            $id = filter_var($_POST['IDesquemaVac'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || (int) $id < 1) {
                self::redirigir('danger', 'El esquema solicitado no es válido.');
            }
            self::responderEliminar(ModeloSeguimientoVacunas::mdlEliminarEsquema((int) $id));
        }
        if (!in_array($accion, ['crear', 'editar'], true)) {
            self::redirigir('danger', 'La operación solicitada no es válida.');
        }
        $datos = self::datosFormulario();
        if ($datos === null || !ModeloSeguimientoVacunas::mdlVacunaExiste($datos['IDvacuna'])) {
            self::redirigir('danger', 'Revise la vacuna, dosis, edad e intervalo ingresados.');
        }
        if ($accion === 'editar') {
            $id = filter_var($_POST['IDesquemaVac'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || (int) $id < 1) {
                self::redirigir('danger', 'El esquema solicitado no es válido.');
            }
            $datos['IDesquemaVac'] = (int) $id;
            self::responderGuardar(ModeloSeguimientoVacunas::mdlEditarEsquema($datos), true);
        }
        self::responderGuardar(ModeloSeguimientoVacunas::mdlCrearEsquema($datos), false);
    }

    public static function ctrObtenerSeguimiento(): array
    {
        return ModeloSeguimientoVacunas::mdlObtenerSeguimiento();
    }

    public static function ctrListarEsquemas(): array { return ModeloSeguimientoVacunas::mdlListarEsquemas(); }
    public static function ctrMostrarEsquema(int $id): ?array { return ModeloSeguimientoVacunas::mdlMostrarEsquema($id); }
    public static function ctrListarEspecies(): array { return ModeloSeguimientoVacunas::mdlListarEspecies(); }
    public static function ctrListarVacunas(): array { return ModeloSeguimientoVacunas::mdlListarVacunas(); }

    private static function datosFormulario(): ?array
    {
        $vacuna = filter_var($_POST['IDvacunaEsquema'] ?? null, FILTER_VALIDATE_INT);
        $dosis = filter_var($_POST['numeroDosis'] ?? null, FILTER_VALIDATE_INT);
        $edad = filter_var($_POST['edadAplicacion'] ?? null, FILTER_VALIDATE_INT);
        $unidad = $_POST['unidadEdad'] ?? '';
        $intervaloTexto = trim((string) ($_POST['intervaloSiguienteDias'] ?? ''));
        $intervalo = $intervaloTexto === '' ? null : filter_var($intervaloTexto, FILTER_VALIDATE_INT);
        if ($vacuna === false || (int) $vacuna < 1
            || $dosis === false || (int) $dosis < 1 || (int) $dosis > 99
            || $edad === false || (int) $edad < 1 || (int) $edad > 3650
            || !in_array($unidad, ['Días', 'Semanas', 'Meses', 'Años'], true)
            || ($intervaloTexto !== '' && ($intervalo === false || (int) $intervalo < 1 || (int) $intervalo > 3650))) {
            return null;
        }
        return ['IDvacuna' => (int) $vacuna, 'numeroDosis' => (int) $dosis,
            'edadAplicacion' => (int) $edad, 'unidadEdad' => $unidad,
            'intervaloSiguienteDias' => $intervalo === null ? null : (int) $intervalo];
    }

    private static function responderGuardar(string $respuesta, bool $edicion): void
    {
        if ($respuesta === 'ok') {
            self::redirigir('success', $edicion ? 'Esquema actualizado correctamente.' : 'Dosis agregada al esquema correctamente.');
        }
        $mensajes = [
            'duplicado' => 'Esa vacuna ya tiene registrado el mismo número de dosis.',
            'clave_en_uso' => 'Esta dosis ya fue aplicada. Puede ajustar su edad e intervalo, pero no cambiar la vacuna ni el número de dosis.',
            'no_encontrado' => 'El esquema ya no existe.',
        ];
        self::redirigir($respuesta === 'duplicado' || $respuesta === 'clave_en_uso' ? 'warning' : 'danger',
            $mensajes[$respuesta] ?? 'No se pudo guardar el esquema.');
    }

    private static function responderEliminar(string $respuesta): void
    {
        if ($respuesta === 'ok') {
            self::redirigir('success', 'Dosis eliminada del esquema.');
        }
        self::redirigir($respuesta === 'en_uso' ? 'warning' : 'danger',
            $respuesta === 'en_uso'
                ? 'No se puede eliminar porque esta dosis ya figura en un historial de vacunación.'
                : 'No se pudo eliminar la dosis del esquema.');
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['seguimiento_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: SeguimientoVac');
        exit;
    }
}
