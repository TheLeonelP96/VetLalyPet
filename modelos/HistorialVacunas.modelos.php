<?php

require_once __DIR__ . '/conexion.php';

final class ModeloHistorialVacunas
{
    private const FILTROS = [
        'IDhistorialVac' => 'h.IDhistorialVac',
        'IDmascota' => 'h.IDmascota',
        'IDvacuna' => 'h.IDvacuna',
        'IDcita' => 'h.IDcita',
    ];

    private static function seleccionBase(): string
    {
        return "SELECT h.*, v.nombreVac, v.preciovac, v.estadoVac,
                       m.nombrem, m.habilitadoM,
                       CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                       e.IDespecie, e.nombre_Especie,
                       c.fechaAtencion, c.estadoCita,
                       CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
                FROM historialvac h
                INNER JOIN vacuna v ON v.IDvacuna = h.IDvacuna
                INNER JOIN mascotas m ON m.IDmascota = h.IDmascota
                INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
                INNER JOIN raza r ON r.IDraza = m.IDraza
                INNER JOIN especie e ON e.IDespecie = r.IDespecie
                LEFT JOIN citas c ON c.IDcita = h.IDcita
                LEFT JOIN usuarios u ON u.IDusuario = c.IDusuario";
    }

    public static function mdlMostrarHistorial($item = null, $valor = null): mixed
    {
        $sql = self::seleccionBase();
        $parametros = [];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de historial no permitido.');
            }
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        $sql .= ' ORDER BY h.fechaAplicacion DESC, h.IDhistorialVac DESC';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlListarMascotasActivas(): array
    {
        return Conexion::conectar()->query(
            "SELECT m.IDmascota, m.nombrem, e.IDespecie, e.nombre_Especie,
                    CONCAT_WS(' ', c.nombrec, c.apellidoc) AS propietario
             FROM mascotas m
             INNER JOIN clientes c ON c.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             INNER JOIN especie e ON e.IDespecie = r.IDespecie
             WHERE m.habilitadoM = 1 AND c.habilitadoC = 1
             ORDER BY m.nombrem, propietario"
        )->fetchAll();
    }

    public static function mdlListarOpcionesMascota(int $idMascota): array
    {
        $conexion = Conexion::conectar();
        $stmtVacunas = $conexion->prepare(
            "SELECT v.IDvacuna, v.nombreVac, v.stock, v.preciovac
             FROM mascotas m
             INNER JOIN raza r ON r.IDraza = m.IDraza
             INNER JOIN vacuna v ON v.IDespecie = r.IDespecie
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             WHERE m.IDmascota = :IDmascota
               AND m.habilitadoM = 1 AND cli.habilitadoC = 1
               AND v.estadoVac = 1 AND v.stock > 0
             ORDER BY v.nombreVac"
        );
        $stmtVacunas->execute([':IDmascota' => $idMascota]);

        $stmtCitas = $conexion->prepare(
            "SELECT c.IDcita, c.fechaAtencion, c.estadoCita,
                    s.tipoServicio,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
             FROM citas c
             INNER JOIN servicios s ON s.IDservicio = c.IDservicio
             INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
             WHERE c.IDmascota = :IDmascota
               AND c.estadoCita = 'Completada'
               AND s.tipoServicio = 'Vacunación'
             ORDER BY c.fechaAtencion DESC"
        );
        $stmtCitas->execute([':IDmascota' => $idMascota]);

        return ['vacunas' => $stmtVacunas->fetchAll(), 'citas' => $stmtCitas->fetchAll()];
    }

    public static function mdlCrearHistorial(array $datos): string
    {
        $conexion = Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $validacion = self::validarRelaciones($conexion, $datos, null);
            if ($validacion !== 'ok') {
                $conexion->rollBack();
                return $validacion;
            }
            $stmt = $conexion->prepare(
                'INSERT INTO historialvac
                    (fechaAplicacion, fechaProximaDosis, observaciones, IDvacuna, IDcita, IDmascota)
                 VALUES
                    (:fechaAplicacion, :fechaProximaDosis, :observaciones, :IDvacuna, :IDcita, :IDmascota)'
            );
            $stmt->execute(self::parametros($datos));
            $actualizar = $conexion->prepare(
                'UPDATE vacuna SET stock = stock - 1
                 WHERE IDvacuna = :IDvacuna AND estadoVac = 1 AND stock > 0'
            );
            $actualizar->execute([':IDvacuna' => $datos['IDvacuna']]);
            if ($actualizar->rowCount() !== 1) {
                $conexion->rollBack();
                return 'sin_stock';
            }
            $conexion->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        }
    }

    public static function mdlEditarHistorial(array $datos): string
    {
        $conexion = Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $stmtActual = $conexion->prepare(
                'SELECT IDhistorialVac, IDvacuna FROM historialvac
                 WHERE IDhistorialVac = :IDhistorialVac FOR UPDATE'
            );
            $stmtActual->execute([':IDhistorialVac' => $datos['IDhistorialVac']]);
            $actual = $stmtActual->fetch();
            if (!$actual) {
                $conexion->rollBack();
                return 'no_encontrado';
            }

            $validacion = self::validarRelaciones($conexion, $datos, (int) $actual['IDvacuna']);
            if ($validacion !== 'ok') {
                $conexion->rollBack();
                return $validacion;
            }

            $vacunaAnterior = (int) $actual['IDvacuna'];
            $vacunaNueva = (int) $datos['IDvacuna'];
            if ($vacunaAnterior !== $vacunaNueva) {
                $devolver = $conexion->prepare(
                    'UPDATE vacuna SET stock = stock + 1 WHERE IDvacuna = :IDvacuna'
                );
                $devolver->execute([':IDvacuna' => $vacunaAnterior]);
                $consumir = $conexion->prepare(
                    'UPDATE vacuna SET stock = stock - 1
                     WHERE IDvacuna = :IDvacuna AND estadoVac = 1 AND stock > 0'
                );
                $consumir->execute([':IDvacuna' => $vacunaNueva]);
                if ($consumir->rowCount() !== 1) {
                    $conexion->rollBack();
                    return 'sin_stock';
                }
            }

            $stmt = $conexion->prepare(
                'UPDATE historialvac SET
                    fechaAplicacion = :fechaAplicacion,
                    fechaProximaDosis = :fechaProximaDosis,
                    observaciones = :observaciones,
                    IDvacuna = :IDvacuna,
                    IDcita = :IDcita,
                    IDmascota = :IDmascota
                 WHERE IDhistorialVac = :IDhistorialVac'
            );
            $parametros = self::parametros($datos);
            $parametros[':IDhistorialVac'] = $datos['IDhistorialVac'];
            $stmt->execute($parametros);
            $conexion->commit();
            return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        }
    }

    private static function validarRelaciones(PDO $conexion, array $datos, ?int $vacunaActual): string
    {
        $stmtMascota = $conexion->prepare(
            'SELECT m.habilitadoM, m.fechaNac_m, cli.habilitadoC, r.IDespecie
             FROM mascotas m
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             WHERE m.IDmascota = :IDmascota FOR UPDATE'
        );
        $stmtMascota->execute([':IDmascota' => $datos['IDmascota']]);
        $mascota = $stmtMascota->fetch();
        if (!$mascota || (int) $mascota['habilitadoM'] !== 1 || (int) $mascota['habilitadoC'] !== 1) {
            return 'mascota_invalida';
        }
        if ($datos['fechaAplicacion'] < $mascota['fechaNac_m']) {
            return 'fecha_invalida';
        }

        $stmtVacuna = $conexion->prepare(
            'SELECT IDespecie, estadoVac, stock FROM vacuna
             WHERE IDvacuna = :IDvacuna FOR UPDATE'
        );
        $stmtVacuna->execute([':IDvacuna' => $datos['IDvacuna']]);
        $vacuna = $stmtVacuna->fetch();
        if (!$vacuna || (int) $vacuna['IDespecie'] !== (int) $mascota['IDespecie']) {
            return 'incompatible';
        }
        if ((int) $datos['IDvacuna'] !== $vacunaActual
            && ((int) $vacuna['estadoVac'] !== 1 || (int) $vacuna['stock'] < 1)) {
            return 'sin_stock';
        }

        if ($datos['IDcita'] !== null) {
            $stmtCita = $conexion->prepare(
                'SELECT c.IDmascota, c.fechaAtencion, c.estadoCita, s.tipoServicio,
                        EXISTS(SELECT 1 FROM pago p WHERE p.IDcita = c.IDcita) AS citaPagada
                 FROM citas c
                 INNER JOIN servicios s ON s.IDservicio = c.IDservicio
                 WHERE c.IDcita = :IDcita FOR UPDATE'
            );
            $stmtCita->execute([':IDcita' => $datos['IDcita']]);
            $cita = $stmtCita->fetch();
            if (!$cita || (int) $cita['IDmascota'] !== (int) $datos['IDmascota']
                || $cita['estadoCita'] !== 'Completada'
                || $cita['tipoServicio'] !== 'Vacunación'
                || substr($cita['fechaAtencion'], 0, 10) !== $datos['fechaAplicacion']) {
                return 'cita_invalida';
            }
            if ((int) $cita['citaPagada'] === 1) {
                return 'cita_pagada';
            }
        }
        return 'ok';
    }

    private static function parametros(array $datos): array
    {
        return [
            ':fechaAplicacion' => $datos['fechaAplicacion'],
            ':fechaProximaDosis' => $datos['fechaProximaDosis'],
            ':observaciones' => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
            ':IDvacuna' => (int) $datos['IDvacuna'],
            ':IDcita' => $datos['IDcita'],
            ':IDmascota' => (int) $datos['IDmascota'],
        ];
    }
}
