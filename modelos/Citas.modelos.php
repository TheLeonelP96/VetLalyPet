<?php

require_once __DIR__ . '/conexion.php';

final class ModeloCitas
{
    private const ESTADO_CANCELADA = 'Cancelada';
    private const FILTROS = [
        'IDcita' => 'c.IDcita',
        'IDusuario' => 'c.IDusuario',
        'IDmascota' => 'c.IDmascota',
        'estadoCita' => 'c.estadoCita',
    ];

    private static function seleccionBase(): string
    {
        return "SELECT c.*,
                       DATE_FORMAT(c.fechaAtencion, '%Y-%m-%dT%H:%i:%s') AS fechaAtencionIso,
                       m.nombrem,
                       m.IDcliente,
                       cli.nombrec,
                       cli.apellidoc,
                       CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS nombreUsuario,
                       s.tipoServicio,
                       (s.tipoServicio = 'Vacunación') AS esVacunacion,
                       s.descripcion AS descripcionServicio,
                       s.precio,
                       (SELECT COUNT(*) FROM historialvac h WHERE h.IDcita = c.IDcita) AS vacunasAplicadas,
                       (SELECT COUNT(*) FROM pago p WHERE p.IDcita = c.IDcita) AS pagosRegistrados
                FROM citas c
                INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
                INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
                INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
                INNER JOIN servicios s ON s.IDservicio = c.IDservicio";
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
            'SELECT IDservicio, tipoServicio, descripcion, precio
             FROM servicios WHERE estadoServicio = 1 ORDER BY tipoServicio'
        )->fetchAll();
    }

    public static function mdlServicioActivo(int $idServicio): bool
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT 1 FROM servicios WHERE IDservicio = :IDservicio AND estadoServicio = 1'
        );
        $stmt->execute([':IDservicio' => $idServicio]);
        return (bool) $stmt->fetchColumn();
    }

    public static function mdlIngresarCita(array $datos): string
    {
        $conexion = Conexion::conectar();
        $bloqueo = self::nombreBloqueo($datos['IDusuario'], $datos['fechaAtencion']);
        $adquirido = false;
        try {
            $adquirido = self::adquirirBloqueo($conexion, $bloqueo);
            if (!$adquirido) {
                return 'ocupado';
            }
            $conexion->beginTransaction();
            if (!self::mdlHorarioDisponible(
                $datos['IDusuario'],
                $datos['fechaAtencion'],
                null,
                $conexion
            )) {
                $conexion->rollBack();
                return 'ocupado';
            }

            $stmt = $conexion->prepare(
                'INSERT INTO citas
                    (motivoCita, estadoCita, fechaAtencion, IDusuario, IDmascota, IDservicio)
                 VALUES
                    (:motivoCita, :estadoCita, :fechaAtencion, :IDusuario, :IDmascota, :IDservicio)'
            );
            $stmt->execute(self::parametrosEscritura($datos));
            $conexion->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return $e->getCode() === '23000' ? 'relacion_invalida' : 'error';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        } finally {
            if ($adquirido) {
                self::liberarBloqueo($conexion, $bloqueo);
            }
        }
    }

    public static function mdlEditarCita(array $datos): string
    {
        $conexion = Conexion::conectar();
        $bloqueo = self::nombreBloqueo($datos['IDusuario'], $datos['fechaAtencion']);
        $adquirido = false;
        try {
            $adquirido = self::adquirirBloqueo($conexion, $bloqueo);
            if (!$adquirido) {
                return 'ocupado';
            }
            $conexion->beginTransaction();
            $stmtActual = $conexion->prepare(
                'SELECT IDcita, estadoCita FROM citas WHERE IDcita = :IDcita FOR UPDATE'
            );
            $stmtActual->execute([':IDcita' => (int) $datos['IDcita']]);
            $actual = $stmtActual->fetch();
            if (!$actual) {
                $conexion->rollBack();
                return 'no_encontrada';
            }
            if ($actual['estadoCita'] !== $datos['estadoEsperado']) {
                $conexion->rollBack();
                return 'estado_invalido';
            }
            if (!self::mdlHorarioDisponible(
                $datos['IDusuario'],
                $datos['fechaAtencion'],
                $datos['IDcita'],
                $conexion
            )) {
                $conexion->rollBack();
                return 'ocupado';
            }

            $stmt = $conexion->prepare(
                'UPDATE citas SET
                    motivoCita = :motivoCita,
                    estadoCita = :estadoCita,
                    fechaAtencion = :fechaAtencion,
                    IDusuario = :IDusuario,
                    IDmascota = :IDmascota,
                    IDservicio = :IDservicio
                 WHERE IDcita = :IDcita'
            );
            $parametros = self::parametrosEscritura($datos);
            $parametros[':IDcita'] = (int) $datos['IDcita'];
            $stmt->execute($parametros);
            $conexion->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return $e->getCode() === '23000' ? 'relacion_invalida' : 'error';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        } finally {
            if ($adquirido) {
                self::liberarBloqueo($conexion, $bloqueo);
            }
        }
    }

    public static function mdlHorarioDisponible(
        int $idUsuario,
        string $fechaAtencion,
        ?int $idCitaExcluir = null,
        ?PDO $conexion = null
    ): bool {
        $conexion = $conexion ?? Conexion::conectar();
        $sql = "SELECT COUNT(*) FROM citas
                WHERE IDusuario = :IDusuario
                  AND fechaAtencion = :fechaAtencion
                  AND estadoCita <> 'Cancelada'";
        $parametros = [
            ':IDusuario' => $idUsuario,
            ':fechaAtencion' => $fechaAtencion,
        ];
        if ($idCitaExcluir !== null) {
            $sql .= ' AND IDcita <> :IDcita';
            $parametros[':IDcita'] = $idCitaExcluir;
        }
        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn() === 0;
    }

    public static function mdlActualizarEstadoCita(
        int $idCita,
        string $estadoActual,
        string $estadoNuevo
    ): string {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE citas SET estadoCita = :estadoNuevo
                 WHERE IDcita = :IDcita AND estadoCita = :estadoActual'
            );
            $stmt->execute([
                ':estadoNuevo' => $estadoNuevo,
                ':IDcita' => $idCita,
                ':estadoActual' => $estadoActual,
            ]);
            return $stmt->rowCount() === 1 ? 'ok' : 'estado_invalido';
        } catch (Throwable $e) {
            return 'error';
        }
    }

    private static function parametrosEscritura(array $datos): array
    {
        return [
            ':motivoCita' => $datos['motivoCita'],
            ':estadoCita' => $datos['estadoCita'],
            ':fechaAtencion' => $datos['fechaAtencion'],
            ':IDusuario' => (int) $datos['IDusuario'],
            ':IDmascota' => (int) $datos['IDmascota'],
            ':IDservicio' => (int) $datos['IDservicio'],
        ];
    }

    private static function nombreBloqueo(int $idUsuario, string $fechaAtencion): string
    {
        // GET_LOCK limita el nombre; este formato sigue siendo único y cabe aun con IDs grandes.
        return 'vlp_c_' . $idUsuario . '_' . sha1($fechaAtencion);
    }

    private static function adquirirBloqueo(PDO $conexion, string $nombre): bool
    {
        $stmt = $conexion->prepare('SELECT GET_LOCK(:nombre, 5)');
        $stmt->execute([':nombre' => $nombre]);
        return (int) $stmt->fetchColumn() === 1;
    }

    private static function liberarBloqueo(PDO $conexion, string $nombre): void
    {
        try {
            $stmt = $conexion->prepare('SELECT RELEASE_LOCK(:nombre)');
            $stmt->execute([':nombre' => $nombre]);
        } catch (Throwable $e) {
            // MariaDB también libera el bloqueo al cerrar la conexión.
        }
    }
}
