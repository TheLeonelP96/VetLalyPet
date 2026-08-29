<?php

require_once "conexion.php";

class ModeloCitas
{
    private const ESTADO_CANCELADA = 'Cancelada';

    public static function mdlMostrarCitas($item = null, $valor = null)
    {
        $sql = "SELECT c.*, m.nombrem, m.IDcliente, cli.nombrec, u.nombre AS nombreUsuario
                FROM citas c
                LEFT JOIN mascotas m ON c.IDmascota = m.IDmascota
                LEFT JOIN clientes cli ON m.IDcliente = cli.IDcliente
                INNER JOIN usuarios u ON c.IDusuario = u.IDusuario";
        $parametros = [];

        if ($item !== null) {
            if (!in_array($item, ['IDcitas', 'IDusuario'], true)) {
                throw new InvalidArgumentException('Filtro de citas no permitido.');
            }
            $sql .= " WHERE c.$item = :valor";
            $parametros[':valor'] = $valor;
        }

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlIngresarCita(array $datos): string
    {
        $conexion = Conexion::conectar();
        $bloqueo = self::nombreBloqueo($datos['IDusuario'], $datos['fechaAtencion']);

        try {
            if (!self::adquirirBloqueo($conexion, $bloqueo)) {
                return 'ocupado';
            }
            $conexion->beginTransaction();
            if (!self::mdlHorarioDisponible($datos['IDusuario'], $datos['fechaAtencion'], null, $conexion)) {
                $conexion->rollBack();
                return 'ocupado';
            }

            $stmt = $conexion->prepare(
                'INSERT INTO citas (motivoCita, fechaAtencion, estadoCita, colorCita, IDmascota, IDusuario)
                 VALUES (:motivoCita, :fechaAtencion, :estadoCita, :colorCita, :IDmascota, :IDusuario)'
            );
            $stmt->execute(self::parametrosEscritura($datos));
            $conexion->commit();
            return 'ok';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        } finally {
            self::liberarBloqueo($conexion, $bloqueo);
        }
    }

    public static function mdlEditarCita(array $datos): string
    {
        $conexion = Conexion::conectar();
        $bloqueo = self::nombreBloqueo($datos['IDusuario'], $datos['fechaAtencion']);

        try {
            if (!self::adquirirBloqueo($conexion, $bloqueo)) {
                return 'ocupado';
            }
            $conexion->beginTransaction();

            $actualStmt = $conexion->prepare(
                'SELECT IDusuario, fechaAtencion, estadoCita FROM citas WHERE IDcitas = :IDcitas FOR UPDATE'
            );
            $actualStmt->execute([':IDcitas' => (int) $datos['IDcitas']]);
            $actual = $actualStmt->fetch();
            if (!$actual) {
                $conexion->rollBack();
                return 'no_encontrada';
            }

            $cambioDeHorario = (int) $actual['IDusuario'] !== (int) $datos['IDusuario']
                || $actual['fechaAtencion'] !== $datos['fechaAtencion'];
            $requiereDisponibilidad = $actual['estadoCita'] === self::ESTADO_CANCELADA || $cambioDeHorario;
            if ($datos['estadoCita'] !== self::ESTADO_CANCELADA
                && $requiereDisponibilidad
                && !self::mdlHorarioDisponible($datos['IDusuario'], $datos['fechaAtencion'], $datos['IDcitas'], $conexion)) {
                $conexion->rollBack();
                return 'ocupado';
            }

            $stmt = $conexion->prepare(
                'UPDATE citas SET motivoCita = :motivoCita, fechaAtencion = :fechaAtencion,
                 estadoCita = :estadoCita, colorCita = :colorCita,
                 IDmascota = :IDmascota, IDusuario = :IDusuario
                 WHERE IDcitas = :IDcitas'
            );
            $parametros = self::parametrosEscritura($datos);
            $parametros[':IDcitas'] = (int) $datos['IDcitas'];
            $stmt->execute($parametros);
            $conexion->commit();
            return 'ok';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        } finally {
            self::liberarBloqueo($conexion, $bloqueo);
        }
    }

    public static function mdlHorarioDisponible($idUsuario, $fechaAtencion, $idCitaExcluir = null, $conexion = null): bool
    {
        $conexion = $conexion ?: Conexion::conectar();
        $sql = "SELECT COUNT(*) FROM citas
                WHERE IDusuario = :IDusuario
                  AND fechaAtencion = :fechaAtencion
                  AND estadoCita <> 'Cancelada'";
        $parametros = [
            ':IDusuario' => (int) $idUsuario,
            ':fechaAtencion' => $fechaAtencion,
        ];
        if ($idCitaExcluir !== null) {
            $sql .= ' AND IDcitas <> :IDcitas';
            $parametros[':IDcitas'] = (int) $idCitaExcluir;
        }

        $stmt = $conexion->prepare($sql);
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn() === 0;
    }

    public static function mdlActualizarEstadoCita(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE citas SET estadoCita = :estadoCita, colorCita = :colorCita
                 WHERE IDcitas = :IDcitas AND estadoCita = :estadoActual'
            );
            $stmt->execute([
                ':estadoCita' => $datos['estadoCita'],
                ':colorCita' => $datos['colorCita'],
                ':IDcitas' => (int) $datos['IDcitas'],
                ':estadoActual' => $datos['estadoActual'],
            ]);
            return $stmt->rowCount() === 1 ? 'ok' : 'no_encontrada';
        } catch (Throwable $e) {
            return 'error';
        }
    }

    private static function parametrosEscritura(array $datos): array
    {
        return [
            ':motivoCita' => $datos['motivoCita'],
            ':fechaAtencion' => $datos['fechaAtencion'],
            ':estadoCita' => $datos['estadoCita'],
            ':colorCita' => $datos['colorCita'],
            ':IDmascota' => (int) $datos['IDmascota'],
            ':IDusuario' => (int) $datos['IDusuario'],
        ];
    }

    private static function nombreBloqueo($idUsuario, $fechaAtencion): string
    {
        return 'lalypets_cita_' . (int) $idUsuario . '_' . sha1((string) $fechaAtencion);
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
            // MariaDB libera el bloqueo al cerrar la conexión.
        }
    }
}
