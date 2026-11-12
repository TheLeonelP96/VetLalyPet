<?php
// Construye las consultas de los reportes de citas, pagos, clientes y mascotas.

require_once __DIR__ . '/conexion.php';

final class ModeloReportes
{
    public static function mdlObtenerReporte(string $desde, string $hasta): array
    {
        $conexion = Conexion::conectar();
        $limites = [':desde' => "$desde 00:00:00", ':hasta' => (new DateTimeImmutable($hasta))->modify('+1 day')->format('Y-m-d 00:00:00')];

        $detalleCitas = self::filas($conexion,
            "SELECT c.IDcita, c.fechaAtencion, c.estadoCita, c.motivoCita,
                    m.nombrem AS mascota,
                    CONCAT_WS(' ', cl.nombrec, cl.apellidoc) AS cliente,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS responsable,
                    GROUP_CONCAT(s.tipoServicio ORDER BY cs.IDcitaServicio SEPARATOR ', ') AS servicios
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cl ON cl.IDcliente = m.IDcliente
             INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
             INNER JOIN citaServicio cs ON cs.IDcita = c.IDcita
             INNER JOIN tiposervicios s ON s.IDtiposervicio = cs.IDservicio
             WHERE c.fechaAtencion >= :desde AND c.fechaAtencion < :hasta
             GROUP BY c.IDcita, c.fechaAtencion, c.estadoCita, c.motivoCita,
                      m.nombrem, cl.nombrec, cl.apellidoc, u.nombre_U, u.apellido_U
             ORDER BY c.fechaAtencion DESC", $limites);

        $mascotas = self::filas($conexion,
            "SELECT m.IDmascota, m.nombrem AS mascota, m.sexo,
                    TIMESTAMPDIFF(YEAR, m.fechaNac_m, CURDATE()) AS edad,
                    m.habilitadoM,
                    e.nombre_Especie AS especie, r.nombre_Raza AS raza,
                    CONCAT_WS(' ', cl.nombrec, cl.apellidoc) AS propietario
             FROM mascotas m
             INNER JOIN clientes cl ON cl.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             INNER JOIN especie e ON e.IDespecie = r.IDespecie
             ORDER BY m.nombrem, propietario", []);

        $clientesFrecuentes = self::filas($conexion,
            "SELECT cl.IDcliente,
                    CONCAT_WS(' ', cl.nombrec, cl.apellidoc) AS cliente,
                    cl.telefono_c AS telefono,
                    COUNT(c.IDcita) AS cantidadCitas,
                    COUNT(DISTINCT m.IDmascota) AS cantidadMascotas,
                    MAX(c.fechaAtencion) AS ultimaCita
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cl ON cl.IDcliente = m.IDcliente
             WHERE c.fechaAtencion >= :desde AND c.fechaAtencion < :hasta
               AND c.estadoCita <> 'Cancelada'
             GROUP BY cl.IDcliente, cl.nombrec, cl.apellidoc, cl.telefono_c
             ORDER BY cantidadCitas DESC, cliente
             LIMIT 10", $limites);

        $pagos = self::filas($conexion,
            "SELECT p.IDpago, p.fechapago, p.metodopago, p.montopago,
                    CONCAT_WS(' ', cl.nombrec, cl.apellidoc) AS cliente,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS recibidoPor,
                    GROUP_CONCAT(dp.concepto ORDER BY dp.IDpagoServicio SEPARATOR ', ') AS conceptos
             FROM pago p
             INNER JOIN clientes cl ON cl.IDcliente = p.IDcliente
             INNER JOIN usuarios u ON u.IDusuario = p.IDusuario
             INNER JOIN pagoservicio dp ON dp.IDpago = p.IDpago
             WHERE p.fechapago >= :desde AND p.fechapago < :hasta
             GROUP BY p.IDpago, p.fechapago, p.metodopago, p.montopago,
                      cl.nombrec, cl.apellidoc, u.nombre_U, u.apellido_U
             ORDER BY p.fechapago DESC, p.IDpago DESC", $limites);

        return compact('detalleCitas', 'mascotas', 'clientesFrecuentes', 'pagos');
    }

    private static function filas(PDO $conexion, string $sql, array $parametros): array
    {
        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

}
