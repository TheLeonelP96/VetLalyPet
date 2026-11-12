<?php
// Solicita al modelo los datos consolidados de los reportes del sistema.

final class ControladorReportes
{
    public static function ctrObtenerReporte($desde, $hasta): array
    {
        Seguridad::exigirRol([1]);
        $desde = self::fechaValida($desde) ?? date('Y-m-01');
        $hasta = self::fechaValida($hasta) ?? date('Y-m-d');
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        return ModeloReportes::mdlObtenerReporte($desde, $hasta) + [
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    private static function fechaValida($fecha): ?string
    {
        if (!is_string($fecha)) {
            return null;
        }
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto && $objeto->format('Y-m-d') === $fecha ? $fecha : null;
    }
}
