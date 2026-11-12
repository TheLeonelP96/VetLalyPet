<?php
// Consulta alertas operativas como stock bajo, citas y próximas vacunaciones.

require_once __DIR__ . '/conexion.php';

final class ModeloAlertas
{
    public static function mdlObtenerAlertas(int $idUsuario, int $idRol): array
    {
        $conexion = Conexion::conectar();
        $alertas = [];
        $sqlCitas = "SELECT c.IDcita, c.fechaAtencion, m.nombrem,
                            (SELECT GROUP_CONCAT(s.tipoServicio ORDER BY cs.IDcitaServicio SEPARATOR ', ')
                             FROM citaServicio cs INNER JOIN tiposervicios s ON s.IDtiposervicio=cs.IDservicio
                             WHERE cs.IDcita=c.IDcita) AS tipoServicio,
                            CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
                     FROM citas c
                     INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
                     INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
                     WHERE c.estadoCita = 'Pendiente'
                       AND c.fechaAtencion BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)";
        $parametros = [];
        if ($idRol === 2) {
            $sqlCitas .= ' AND c.IDusuario = :IDusuario';
            $parametros[':IDusuario'] = $idUsuario;
        }
        $sqlCitas .= ' ORDER BY c.fechaAtencion LIMIT 5';
        $stmt = $conexion->prepare($sqlCitas);
        $stmt->execute($parametros);
        foreach ($stmt->fetchAll() as $cita) {
            $alertas[] = [
                'icono' => 'fa-calendar', 'clase' => 'text-primary',
                'titulo' => 'Cita próxima: ' . $cita['nombrem'],
                'detalle' => date('d/m/Y H:i', strtotime($cita['fechaAtencion']))
                    . ' · ' . $cita['tipoServicio'] . ' · ' . $cita['veterinario'],
                'url' => 'Calendar',
            ];
        }

        $vencidas = (int) $conexion->query(
            "SELECT COUNT(*) FROM historialvac h
             WHERE h.fechaProximaDosis IS NOT NULL AND h.fechaProximaDosis < CURDATE()"
        )->fetchColumn();
        if ($vencidas > 0) {
            $alertas[] = [
                'icono' => 'fa-warning', 'clase' => 'text-danger',
                'titulo' => "$vencidas dosis de vacunación atrasadas",
                'detalle' => 'Revise el seguimiento y confirme las próximas aplicaciones.',
                'url' => 'SeguimientoVac',
            ];
        }

        $sinStock = (int) $conexion->query(
            'SELECT COUNT(*) FROM vacuna WHERE estadoVac = 1 AND stock = 0'
        )->fetchColumn();
        if ($sinStock > 0) {
            $alertas[] = [
                'icono' => 'fa-exclamation-circle', 'clase' => 'text-danger',
                'titulo' => "$sinStock vacunas agotadas",
                'detalle' => 'No podrán registrarse nuevas aplicaciones hasta reponer existencias.',
                'url' => 'Vacunas',
            ];
        }

        $stockBajo = (int) $conexion->query(
            'SELECT COUNT(*) FROM vacuna WHERE estadoVac = 1 AND stock > 0 AND stock <= stockMinimo'
        )->fetchColumn();
        if ($stockBajo > 0) {
            $alertas[] = [
                'icono' => 'fa-medkit', 'clase' => 'text-warning',
                'titulo' => "$stockBajo vacunas con stock bajo",
                'detalle' => 'Las existencias alcanzaron el mínimo definido para cada vacuna.',
                'url' => 'Vacunas',
            ];
        }

        if ($idRol !== 2) {
            $pendientesPago = (int) $conexion->query(
                "SELECT COUNT(*) FROM citas c
                 WHERE c.estadoCita='Completada' AND EXISTS (
                    SELECT 1 FROM citaServicio cs
                    LEFT JOIN pagoservicio dp ON dp.IDcitaServicio=cs.IDcitaServicio
                    WHERE cs.IDcita=c.IDcita AND dp.IDpagoServicio IS NULL)"
            )->fetchColumn();
            if ($pendientesPago > 0) {
                $alertas[] = [
                    'icono' => 'fa-money', 'clase' => 'text-success',
                    'titulo' => "$pendientesPago citas pendientes de cobro",
                    'detalle' => 'Hay atenciones completadas que todavía no tienen pago.',
                    'url' => 'Pagos',
                ];
            }
        }

        return $alertas;
    }
}
