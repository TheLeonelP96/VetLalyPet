<?php
// Recupera y actualiza los datos del perfil del usuario autenticado.

require_once __DIR__ . '/conexion.php';

final class ModeloPerfil
{
    public static function mdlObtenerCredenciales(int $idUsuario): ?array
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT IDusuario, password FROM usuarios WHERE IDusuario = :IDusuario LIMIT 1'
        );
        $stmt->execute([':IDusuario' => $idUsuario]);
        return $stmt->fetch() ?: null;
    }

    public static function mdlActualizarDatosPersonales(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE usuarios SET nombre_U = :nombre, apellido_U = :apellido,
                    telefono_U = :telefono, email_U = :correo, password = :password
                 WHERE IDusuario = :IDusuario AND estado_U = 1'
            );
            $stmt->execute([
                ':nombre' => $datos['nombre'], ':apellido' => $datos['apellido'],
                ':telefono' => $datos['telefono'], ':correo' => $datos['correo'],
                ':password' => $datos['password'], ':IDusuario' => (int) $datos['IDusuario'],
            ]);
            return $stmt->rowCount() <= 1 ? 'ok' : 'error';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlObtenerDashboard(int $idUsuario): ?array
    {
        $conexion = Conexion::conectar();
        $stmtUsuario = $conexion->prepare(
            "SELECT u.IDusuario, u.nombre_U, u.apellido_U, u.telefono_U, u.email_U,
                    u.turno_U, u.foto, u.estado_U, r.nombre_Rol
             FROM usuarios u INNER JOIN rol r ON r.IDrol = u.IDrol
             WHERE u.IDusuario = :IDusuario"
        );
        $stmtUsuario->execute([':IDusuario' => $idUsuario]);
        $usuario = $stmtUsuario->fetch();
        if (!$usuario) {
            return null;
        }

        $stmtResumen = $conexion->prepare(
            "SELECT
                SUM(DATE(c.fechaAtencion) = CURDATE() AND c.estadoCita <> 'Cancelada') AS citasHoy,
                SUM(c.fechaAtencion > NOW() AND c.estadoCita = 'Pendiente') AS proximas,
                SUM(c.estadoCita = 'Completada') AS completadas,
                SUM(c.estadoCita = 'Cancelada') AS canceladas
             FROM citas c WHERE c.IDusuario = :IDusuario"
        );
        $stmtResumen->execute([':IDusuario' => $idUsuario]);
        $resumen = $stmtResumen->fetch() ?: [];

        $stmtCitas = $conexion->prepare(
            "SELECT c.IDcita, c.fechaAtencion, c.estadoCita, c.motivoCita,
                    m.nombrem, CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    (SELECT GROUP_CONCAT(s.tipoServicio ORDER BY cs.IDcitaServicio SEPARATOR ', ')
                     FROM citaServicio cs INNER JOIN tiposervicios s ON s.IDtiposervicio=cs.IDservicio
                     WHERE cs.IDcita=c.IDcita) AS tipoServicio
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             WHERE c.IDusuario = :IDusuario
             ORDER BY (c.fechaAtencion >= NOW()) DESC,
                      CASE WHEN c.fechaAtencion >= NOW() THEN c.fechaAtencion END ASC,
                      c.fechaAtencion DESC LIMIT 10"
        );
        $stmtCitas->execute([':IDusuario' => $idUsuario]);
        $citas = $stmtCitas->fetchAll();

        $stmtCaja = $conexion->prepare(
            "SELECT COUNT(*) AS cantidadPagos,
                    COALESCE(SUM(CASE WHEN DATE(p.fechapago) = CURDATE() THEN p.montopago ELSE 0 END), 0) AS cobradoHoy,
                    COALESCE(SUM(CASE WHEN YEAR(p.fechapago) = YEAR(CURDATE())
                                      AND MONTH(p.fechapago) = MONTH(CURDATE())
                                 THEN p.montopago ELSE 0 END), 0) AS cobradoMes
             FROM pago p WHERE p.IDusuario = :IDusuario"
        );
        $stmtCaja->execute([':IDusuario' => $idUsuario]);
        $caja = $stmtCaja->fetch() ?: [];

        $stmtPagos = $conexion->prepare(
            "SELECT p.IDpago, p.fechapago, p.montopago, p.metodopago,
                    GROUP_CONCAT(DISTINCT m.nombrem ORDER BY m.nombrem SEPARATOR ', ') AS nombrem,
                    CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    GROUP_CONCAT(DISTINCT s.tipoServicio ORDER BY s.tipoServicio SEPARATOR ', ') AS tipoServicio
             FROM pago p
             INNER JOIN pagoservicio dp ON dp.IDpago=p.IDpago
             INNER JOIN citaServicio cs ON cs.IDcitaServicio=dp.IDcitaServicio
             INNER JOIN citas c ON c.IDcita=cs.IDcita
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente=p.IDcliente
             INNER JOIN tiposervicios s ON s.IDtiposervicio=cs.IDservicio
             WHERE p.IDusuario = :IDusuario
             GROUP BY p.IDpago ORDER BY p.fechapago DESC LIMIT 8"
        );
        $stmtPagos->execute([':IDusuario' => $idUsuario]);
        $pagos = $stmtPagos->fetchAll();

        return [
            'usuario' => $usuario,
            'resumen' => [
                'citasHoy' => (int) ($resumen['citasHoy'] ?? 0),
                'proximas' => (int) ($resumen['proximas'] ?? 0),
                'completadas' => (int) ($resumen['completadas'] ?? 0),
                'canceladas' => (int) ($resumen['canceladas'] ?? 0),
            ],
            'citas' => $citas,
            'caja' => [
                'cantidadPagos' => (int) ($caja['cantidadPagos'] ?? 0),
                'cobradoHoy' => (float) ($caja['cobradoHoy'] ?? 0),
                'cobradoMes' => (float) ($caja['cobradoMes'] ?? 0),
            ],
            'pagos' => $pagos,
        ];
    }
}
