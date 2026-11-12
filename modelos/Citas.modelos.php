<?php
// Contiene las consultas de citas, disponibilidad, servicios asociados y estados.

require_once __DIR__ . '/conexion.php';

final class ModeloCitas
{
    private const DURACION_BLOQUE_MINUTOS = 15;
    private const FILTROS = [
        'IDcita' => 'c.IDcita', 'IDusuario' => 'c.IDusuario',
        'IDmascota' => 'c.IDmascota', 'estadoCita' => 'c.estadoCita',
    ];

    private static function seleccionBase(): string
    {
        return "SELECT c.*,
                       DATE_FORMAT(c.fechaAtencion, '%Y-%m-%dT%H:%i:%s') AS fechaAtencionIso,
                       m.nombrem, m.IDcliente, cli.nombrec, cli.apellidoc,
                       CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS nombreUsuario,
                       (SELECT GROUP_CONCAT(cs.IDservicio ORDER BY cs.IDcitaServicio)
                          FROM citaServicio cs WHERE cs.IDcita = c.IDcita) AS IDservicios,
                       (SELECT GROUP_CONCAT(s.tipoServicio ORDER BY cs.IDcitaServicio SEPARATOR ', ')
                          FROM citaServicio cs INNER JOIN tiposervicios s ON s.IDtiposervicio = cs.IDservicio
                         WHERE cs.IDcita = c.IDcita) AS tipoServicio,
                       EXISTS(SELECT 1 FROM citaServicio cs INNER JOIN tiposervicios s ON s.IDtiposervicio = cs.IDservicio
                              WHERE cs.IDcita = c.IDcita AND s.tipoServicio = 'Vacunación') AS esVacunacion,
                       (SELECT COALESCE(SUM(cs.duracionAplicada), 0) FROM citaServicio cs
                         WHERE cs.IDcita = c.IDcita) AS duracionMinutos,
                       DATE_FORMAT(DATE_ADD(c.fechaAtencion, INTERVAL 15 MINUTE),
                           '%Y-%m-%dT%H:%i:%s') AS fechaFinIso,
                       (SELECT COUNT(*) FROM historialvac h WHERE h.IDcita = c.IDcita) AS vacunasAplicadas,
                       (SELECT COUNT(DISTINCT dp.IDpago) FROM citaServicio cs
                          INNER JOIN pagoservicio dp ON dp.IDcitaServicio = cs.IDcitaServicio
                         WHERE cs.IDcita = c.IDcita) AS pagosRegistrados
                FROM citas c
                INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
                INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
                INNER JOIN usuarios u ON u.IDusuario = c.IDusuario";
    }

    public static function mdlMostrarCitas($item = null, $valor = null): mixed
    {
        $sql = self::seleccionBase();
        $parametros = [];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de cita no permitido.');
            }
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        $sql .= ' ORDER BY c.fechaAtencion';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlListarServiciosActivos(): array
    {
        return Conexion::conectar()->query(
            'SELECT IDtiposervicio AS IDservicio, tipoServicio, descripcion, precio, duracionMinutos
             FROM tiposervicios WHERE estadoServicio = 1 ORDER BY tipoServicio'
        )->fetchAll();
    }

    public static function mdlObtenerServiciosActivos(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Conexion::conectar()->prepare(
            "SELECT IDtiposervicio AS IDservicio, tipoServicio, precio, duracionMinutos
             FROM tiposervicios WHERE estadoServicio = 1 AND IDtiposervicio IN ($marcadores)
             ORDER BY IDtiposervicio"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public static function mdlIngresarCita(array $datos): string
    {
        $conexion = Conexion::conectar();
        $bloqueo = self::nombreBloqueo($datos['IDusuario'], $datos['fechaAtencion']);
        $adquirido = false;
        try {
            $adquirido = self::adquirirBloqueo($conexion, $bloqueo);
            if (!$adquirido) return 'ocupado';
            $conexion->beginTransaction();
            if (!self::mdlHorarioDisponible($datos['IDusuario'], $datos['fechaAtencion'],
                $datos['duracionMinutos'], null, $conexion)) {
                $conexion->rollBack();
                return 'ocupado';
            }
            $stmt = $conexion->prepare(
                'INSERT INTO citas (motivoCita, estadoCita, fechaAtencion, IDusuario, IDmascota)
                 VALUES (:motivoCita, :estadoCita, :fechaAtencion, :IDusuario, :IDmascota)'
            );
            $stmt->execute(self::parametrosCita($datos));
            self::guardarServicios($conexion, (int) $conexion->lastInsertId(), $datos['servicios'], $datos['estadoCita']);
            $conexion->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            return $e->getCode() === '23000' ? 'relacion_invalida' : 'error';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            return 'error';
        } finally {
            if ($adquirido) self::liberarBloqueo($conexion, $bloqueo);
        }
    }

    public static function mdlEditarCita(array $datos): string
    {
        $conexion = Conexion::conectar();
        $bloqueo = self::nombreBloqueo($datos['IDusuario'], $datos['fechaAtencion']);
        $adquirido = false;
        try {
            $adquirido = self::adquirirBloqueo($conexion, $bloqueo);
            if (!$adquirido) return 'ocupado';
            $conexion->beginTransaction();
            $stmtActual = $conexion->prepare('SELECT estadoCita FROM citas WHERE IDcita = :IDcita FOR UPDATE');
            $stmtActual->execute([':IDcita' => $datos['IDcita']]);
            $actual = $stmtActual->fetch();
            if (!$actual) { $conexion->rollBack(); return 'no_encontrada'; }
            if ($actual['estadoCita'] !== $datos['estadoEsperado']) { $conexion->rollBack(); return 'estado_invalido'; }
            if (!self::mdlHorarioDisponible($datos['IDusuario'], $datos['fechaAtencion'],
                $datos['duracionMinutos'], $datos['IDcita'], $conexion)) {
                $conexion->rollBack(); return 'ocupado';
            }
            $stmt = $conexion->prepare(
                'UPDATE citas SET motivoCita=:motivoCita, estadoCita=:estadoCita,
                 fechaAtencion=:fechaAtencion, IDusuario=:IDusuario, IDmascota=:IDmascota
                 WHERE IDcita=:IDcita'
            );
            $parametros = self::parametrosCita($datos);
            $parametros[':IDcita'] = $datos['IDcita'];
            $stmt->execute($parametros);
            $conexion->prepare('DELETE FROM citaServicio WHERE IDcita = :IDcita')
                ->execute([':IDcita' => $datos['IDcita']]);
            self::guardarServicios($conexion, $datos['IDcita'], $datos['servicios'], $datos['estadoCita']);
            $conexion->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            return $e->getCode() === '23000' ? 'relacion_invalida' : 'error';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            return 'error';
        } finally {
            if ($adquirido) self::liberarBloqueo($conexion, $bloqueo);
        }
    }

    public static function mdlHorarioDisponible(int $idUsuario, string $fechaAtencion,
        int $duracionMinutos, ?int $idCitaExcluir = null, ?PDO $conexion = null): bool
    {
        $duracionMinutos = self::DURACION_BLOQUE_MINUTOS;
        $conexion ??= Conexion::conectar();
        $sql = "SELECT COUNT(*) FROM citas c
                WHERE c.IDusuario = :IDusuario AND c.estadoCita <> 'Cancelada'
                  AND c.fechaAtencion < DATE_ADD(:inicioFin, INTERVAL :duracion MINUTE)
                  AND DATE_ADD(c.fechaAtencion, INTERVAL 15 MINUTE) > :inicioComparacion";
        $parametros = [':IDusuario'=>$idUsuario, ':inicioFin'=>$fechaAtencion,
            ':inicioComparacion'=>$fechaAtencion, ':duracion'=>$duracionMinutos];
        if ($idCitaExcluir !== null) {
            $sql .= ' AND c.IDcita <> :IDcita';
            $parametros[':IDcita'] = $idCitaExcluir;
        }
        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn() === 0;
    }

    public static function mdlActualizarEstadoCita(int $idCita, string $estadoActual, string $estadoNuevo): string
    {
        $conexion = Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $stmt = $conexion->prepare('UPDATE citas SET estadoCita=:nuevo WHERE IDcita=:id AND estadoCita=:actual');
            $stmt->execute([':nuevo'=>$estadoNuevo, ':id'=>$idCita, ':actual'=>$estadoActual]);
            if ($stmt->rowCount() !== 1) { $conexion->rollBack(); return 'estado_invalido'; }
            $estadoDetalle = match ($estadoNuevo) {
                'Completada' => 'Realizado', 'Cancelada' => 'Cancelado', default => 'Programado'
            };
            $conexion->prepare('UPDATE citaServicio SET estadoDetalle=:estado WHERE IDcita=:id')
                ->execute([':estado'=>$estadoDetalle, ':id'=>$idCita]);
            $conexion->commit();
            return 'ok';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            return 'error';
        }
    }

    private static function guardarServicios(PDO $conexion, int $idCita, array $servicios, string $estado): void
    {
        $estadoDetalle = match ($estado) {
            'Completada' => 'Realizado', 'Cancelada' => 'Cancelado', default => 'Programado'
        };
        $stmt = $conexion->prepare(
            'INSERT INTO citaServicio
                (IDcita, IDservicio, duracionAplicada, precioAplicado, estadoDetalle)
             VALUES (:cita, :servicio, :duracion, :precio, :estado)'
        );
        foreach ($servicios as $servicio) {
            $stmt->execute([':cita'=>$idCita, ':servicio'=>$servicio['IDservicio'],
                ':duracion'=>$servicio['duracionMinutos'], ':precio'=>$servicio['precio'], ':estado'=>$estadoDetalle]);
        }
    }

    private static function parametrosCita(array $datos): array
    {
        return [':motivoCita'=>$datos['motivoCita'], ':estadoCita'=>$datos['estadoCita'],
            ':fechaAtencion'=>$datos['fechaAtencion'], ':IDusuario'=>$datos['IDusuario'],
            ':IDmascota'=>$datos['IDmascota']];
    }

    private static function nombreBloqueo(int $idUsuario, string $fecha): string
    { return 'vlp_c_' . $idUsuario . '_' . substr($fecha, 0, 10); }

    private static function adquirirBloqueo(PDO $conexion, string $nombre): bool
    { $s=$conexion->prepare('SELECT GET_LOCK(:n,5)'); $s->execute([':n'=>$nombre]); return (int)$s->fetchColumn()===1; }

    private static function liberarBloqueo(PDO $conexion, string $nombre): void
    { try { $s=$conexion->prepare('SELECT RELEASE_LOCK(:n)'); $s->execute([':n'=>$nombre]); } catch (Throwable $e) {} }
}
