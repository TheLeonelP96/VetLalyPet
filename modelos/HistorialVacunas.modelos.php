<?php
// Persiste aplicaciones, dosis e intervalos de vacunación asociados a cada cita.

require_once __DIR__ . '/conexion.php';

final class ModeloHistorialVacunas
{
    private const FILTROS = [
        'IDhistorialVac' => 'h.IDhistorialVac',
        'IDvacuna' => 'ev.IDvacuna',
        'IDcita' => 'h.IDcita',
    ];

    private static function seleccionBase(): string
    {
        return "SELECT h.*, ev.IDvacuna, ev.numeroDosis, c.IDmascota,
                       v.nombreVac, v.preciovac, v.estadoVac,
                       m.nombrem, m.habilitadoM,
                       CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                       e.IDespecie, e.nombre_Especie,
                       c.fechaAtencion, c.estadoCita,
                       EXISTS(SELECT 1 FROM citaServicio cs
                              INNER JOIN pagoservicio dp ON dp.IDcitaServicio = cs.IDcitaServicio
                              WHERE cs.IDcita = c.IDcita) AS citaPagada,
                       CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
                FROM historialvac h
                INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
                INNER JOIN vacuna v ON v.IDvacuna = ev.IDvacuna
                INNER JOIN citas c ON c.IDcita = h.IDcita
                INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
                INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
                INNER JOIN raza r ON r.IDraza = m.IDraza
                INNER JOIN especie e ON e.IDespecie = r.IDespecie
                INNER JOIN usuarios u ON u.IDusuario = c.IDusuario";
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

    public static function mdlListarCitasVacunacion(): array
    {
        return Conexion::conectar()->query(
            "SELECT c.IDcita, c.fechaAtencion, c.IDmascota, m.nombrem,
                    e.IDespecie, e.nombre_Especie,
                    CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             INNER JOIN especie e ON e.IDespecie = r.IDespecie
             INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
             INNER JOIN citaServicio cs ON cs.IDcita = c.IDcita
             INNER JOIN tiposervicios s ON s.IDtiposervicio = cs.IDservicio
             LEFT JOIN pagoservicio dp ON dp.IDcitaServicio = cs.IDcitaServicio
             WHERE c.estadoCita = 'Completada' AND s.tipoServicio = 'Vacunación'
               AND dp.IDpagoServicio IS NULL
             ORDER BY c.fechaAtencion DESC"
        )->fetchAll();
    }

    public static function mdlListarOpcionesCita(int $idCita): array
    {
        $conexion = Conexion::conectar();
        $stmtCita = $conexion->prepare(
            "SELECT c.IDcita, c.IDmascota, c.fechaAtencion, c.estadoCita,
                    m.nombrem, m.fechaNac_m, e.IDespecie, e.nombre_Especie,
                    CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             INNER JOIN especie e ON e.IDespecie = r.IDespecie
             INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
             INNER JOIN citaServicio cs ON cs.IDcita = c.IDcita
             INNER JOIN tiposervicios s ON s.IDtiposervicio = cs.IDservicio
             LEFT JOIN pagoservicio dp ON dp.IDcitaServicio = cs.IDcitaServicio
             WHERE c.IDcita = :IDcita AND c.estadoCita = 'Completada'
               AND s.tipoServicio = 'Vacunación' AND dp.IDpagoServicio IS NULL"
        );
        $stmtCita->execute([':IDcita' => $idCita]);
        $cita = $stmtCita->fetch();
        if (!$cita) {
            return ['cita' => null, 'vacunas' => []];
        }
        $stmtVacunas = $conexion->prepare(
            "SELECT v.IDvacuna, v.nombreVac, v.stock, v.preciovac
             FROM vacuna v
             WHERE v.IDespecie = :IDespecie AND v.estadoVac = 1 AND v.stock > 0
               AND EXISTS (SELECT 1 FROM esquemaVac esquema WHERE esquema.IDvacuna = v.IDvacuna)
               AND NOT EXISTS (
                   SELECT 1 FROM historialvac h
                   INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
                   WHERE h.IDcita = :IDcita AND ev.IDvacuna = v.IDvacuna
               )
             ORDER BY v.nombreVac"
        );
        $stmtVacunas->execute([':IDespecie' => $cita['IDespecie'], ':IDcita' => $idCita]);
        $vacunas = $stmtVacunas->fetchAll();
        foreach ($vacunas as &$vacuna) {
            $vacuna += self::informacionEsquema(
                $conexion,
                (int) $cita['IDmascota'],
                (int) $vacuna['IDvacuna'],
                substr($cita['fechaAtencion'], 0, 10),
                $cita['fechaNac_m'] ?? null
            );
        }
        unset($vacuna);
        return ['cita' => $cita, 'vacunas' => $vacunas];
    }

    public static function mdlCrearHistorial(array $datos): string
    {
        $datos['IDvacunas'] = [(int) $datos['IDvacuna']];
        return self::mdlCrearHistorialMultiple($datos);
    }

    public static function mdlCrearHistorialMultiple(array $datos): string
    {
        $conexion = Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $stmt = $conexion->prepare(
                'INSERT INTO historialvac
                    (fechaAplicacion, fechaProximaDosis, observaciones, ID_esquemaVac, IDcita)
                 VALUES
                    (:fechaAplicacion, :fechaProximaDosis, :observaciones, :ID_esquemaVac, :IDcita)'
            );
            $actualizar = $conexion->prepare(
                'UPDATE vacuna SET stock = stock - 1
                 WHERE IDvacuna = :IDvacuna AND estadoVac = 1 AND stock > 0'
            );
            $aplicaciones = [];
            foreach (array_values(array_unique(array_map('intval', $datos['IDvacunas'] ?? []))) as $idVacuna) {
                $aplicacion = $datos;
                $aplicacion['IDvacuna'] = $idVacuna;
                $validacion = self::validarRelaciones($conexion, $aplicacion, null);
                if ($validacion !== 'ok') {
                    $conexion->rollBack();
                    return $validacion;
                }
                $aplicacion['ID_esquemaVac'] = self::resolverEsquema($conexion, $aplicacion, null);
                if ($aplicacion['ID_esquemaVac'] === null) {
                    $conexion->rollBack();
                    return 'sin_esquema';
                }
                $aplicaciones[] = $aplicacion;
            }
            if ($aplicaciones === []) {
                $conexion->rollBack();
                return 'datos_invalidos';
            }
            foreach ($aplicaciones as $aplicacion) {
                $stmt->execute(self::parametros($aplicacion));
                $actualizar->execute([':IDvacuna' => $aplicacion['IDvacuna']]);
                if ($actualizar->rowCount() !== 1) {
                    $conexion->rollBack();
                    return 'sin_stock';
                }
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
                'SELECT h.IDhistorialVac, h.ID_esquemaVac, h.IDcita,
                        h.fechaAplicacion, h.fechaProximaDosis, ev.IDvacuna
                 FROM historialvac h
                 INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
                 WHERE h.IDhistorialVac = :IDhistorialVac FOR UPDATE'
            );
            $stmtActual->execute([':IDhistorialVac' => $datos['IDhistorialVac']]);
            $actual = $stmtActual->fetch();
            if (!$actual) {
                $conexion->rollBack();
                return 'no_encontrado';
            }

            // Después del pago solo se permite corregir la observación, sin alterar
            // la vacuna, la cita ni las fechas que forman parte del historial clínico.
            if (self::citaTienePago($conexion, (int) $actual['IDcita'])) {
                $mismaAplicacion = (int) $actual['IDcita'] === (int) $datos['IDcita']
                    && (int) $actual['IDvacuna'] === (int) $datos['IDvacuna']
                    && (string) $actual['fechaAplicacion'] === (string) $datos['fechaAplicacion']
                    && (string) ($actual['fechaProximaDosis'] ?? '') === (string) ($datos['fechaProximaDosis'] ?? '');
                if (!$mismaAplicacion) {
                    $conexion->rollBack();
                    return 'cita_pagada';
                }
                $stmtObservacion = $conexion->prepare(
                    'UPDATE historialvac SET observaciones = :observaciones
                     WHERE IDhistorialVac = :IDhistorialVac'
                );
                $stmtObservacion->execute([
                    ':observaciones' => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
                    ':IDhistorialVac' => $datos['IDhistorialVac'],
                ]);
                $conexion->commit();
                return 'ok';
            }

            $validacion = self::validarRelaciones($conexion, $datos, (int) $actual['IDvacuna']);
            if ($validacion !== 'ok') {
                $conexion->rollBack();
                return $validacion;
            }

            $vacunaAnterior = (int) $actual['IDvacuna'];
            $vacunaNueva = (int) $datos['IDvacuna'];
            $datos['ID_esquemaVac'] = $vacunaAnterior === $vacunaNueva
                ? (int) $actual['ID_esquemaVac']
                : self::resolverEsquema($conexion, $datos, (int) $datos['IDhistorialVac']);
            if ($datos['ID_esquemaVac'] === null) {
                $conexion->rollBack();
                return 'sin_esquema';
            }
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
                    ID_esquemaVac = :ID_esquemaVac,
                    IDcita = :IDcita
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

    // Comprueba si alguno de los servicios de la cita ya fue cobrado.
    private static function citaTienePago(PDO $conexion, int $idCita): bool
    {
        $stmt = $conexion->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM citaServicio cs
                INNER JOIN pagoservicio ps ON ps.IDcitaServicio = cs.IDcitaServicio
                WHERE cs.IDcita = :IDcita
            )'
        );
        $stmt->execute([':IDcita' => $idCita]);
        return (bool) $stmt->fetchColumn();
    }

    private static function validarRelaciones(PDO $conexion, array $datos, ?int $vacunaActual): string
    {
        $stmtCita = $conexion->prepare(
            "SELECT c.IDmascota, c.fechaAtencion, c.estadoCita,
                    m.habilitadoM, m.fechaNac_m, cli.habilitadoC, r.IDespecie,
                    EXISTS(SELECT 1 FROM citaServicio cs2
                           INNER JOIN pagoservicio dp ON dp.IDcitaServicio=cs2.IDcitaServicio
                           WHERE cs2.IDcita=c.IDcita) AS citaPagada,
                    EXISTS(SELECT 1 FROM citaServicio csv
                           INNER JOIN tiposervicios sv ON sv.IDtiposervicio=csv.IDservicio
                           WHERE csv.IDcita=c.IDcita AND sv.tipoServicio='Vacunación') AS esVacunacion
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             WHERE c.IDcita = :IDcita FOR UPDATE"
        );
        $stmtCita->execute([':IDcita' => $datos['IDcita']]);
        $cita = $stmtCita->fetch();
        if (!$cita || $cita['estadoCita'] !== 'Completada'
            || (int) $cita['esVacunacion'] !== 1
            || (int) $cita['habilitadoM'] !== 1 || (int) $cita['habilitadoC'] !== 1
            || substr($cita['fechaAtencion'], 0, 10) !== $datos['fechaAplicacion']) {
            return 'cita_invalida';
        }
        if ((int) $cita['citaPagada'] === 1) {
            return 'cita_pagada';
        }
        if ($datos['fechaAplicacion'] < $cita['fechaNac_m']) {
            return 'fecha_invalida';
        }

        $stmtVacuna = $conexion->prepare(
            'SELECT IDespecie, estadoVac, stock FROM vacuna
             WHERE IDvacuna = :IDvacuna FOR UPDATE'
        );
        $stmtVacuna->execute([':IDvacuna' => $datos['IDvacuna']]);
        $vacuna = $stmtVacuna->fetch();
        if (!$vacuna || (int) $vacuna['IDespecie'] !== (int) $cita['IDespecie']) {
            return 'incompatible';
        }
        if ((int) $datos['IDvacuna'] !== $vacunaActual
            && ((int) $vacuna['estadoVac'] !== 1 || (int) $vacuna['stock'] < 1)) {
            return 'sin_stock';
        }

        $stmtDuplicado = $conexion->prepare(
            'SELECT 1 FROM historialvac h
             INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
             WHERE h.IDcita = :IDcita AND ev.IDvacuna = :IDvacuna
               AND (:IDhistorialVac IS NULL OR h.IDhistorialVac <> :IDhistorialVac2)
             LIMIT 1'
        );
        $idHistorial = isset($datos['IDhistorialVac']) ? (int) $datos['IDhistorialVac'] : null;
        $stmtDuplicado->execute([
            ':IDcita' => $datos['IDcita'], ':IDvacuna' => $datos['IDvacuna'],
            ':IDhistorialVac' => $idHistorial, ':IDhistorialVac2' => $idHistorial,
        ]);
        if ($stmtDuplicado->fetchColumn()) {
            return 'duplicado';
        }

        return 'ok';
    }

    private static function parametros(array $datos): array
    {
        return [
            ':fechaAplicacion' => $datos['fechaAplicacion'],
            ':fechaProximaDosis' => $datos['fechaProximaDosis'],
            ':observaciones' => $datos['observaciones'] !== '' ? $datos['observaciones'] : null,
            ':ID_esquemaVac' => (int) $datos['ID_esquemaVac'],
            ':IDcita' => (int) $datos['IDcita'],
        ];
    }

    private static function resolverEsquema(PDO $conexion, array $datos, ?int $excluirHistorial): ?int
    {
        $stmt = $conexion->prepare(
            'SELECT COUNT(*)
             FROM historialvac h
             INNER JOIN citas cHist ON cHist.IDcita = h.IDcita
             INNER JOIN citas cNueva ON cNueva.IDcita = :IDcita
             INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
             WHERE cHist.IDmascota = cNueva.IDmascota AND ev.IDvacuna = :IDvacuna
               AND (:excluir IS NULL OR h.IDhistorialVac <> :excluir2)'
        );
        $stmt->execute([
            ':IDcita' => $datos['IDcita'], ':IDvacuna' => $datos['IDvacuna'],
            ':excluir' => $excluirHistorial, ':excluir2' => $excluirHistorial,
        ]);
        $numero = (int) $stmt->fetchColumn() + 1;
        $stmtEsquema = $conexion->prepare(
            'SELECT IDesquemaVac FROM esquemaVac
             WHERE IDvacuna = :IDvacuna
             ORDER BY (numeroDosis = :numero) DESC, numeroDosis DESC LIMIT 1'
        );
        $stmtEsquema->execute([':IDvacuna' => $datos['IDvacuna'], ':numero' => $numero]);
        $id = $stmtEsquema->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private static function informacionEsquema(
        PDO $conexion,
        int $idMascota,
        int $idVacuna,
        string $fechaCita,
        ?string $fechaNacimiento
    ): array {
        $stmtEsquema = $conexion->prepare(
            'SELECT numeroDosis, edadAplicacion, unidadEdad, intervaloSiguienteDias
             FROM esquemaVac WHERE IDvacuna = :IDvacuna ORDER BY numeroDosis'
        );
        $stmtEsquema->execute([':IDvacuna' => $idVacuna]);
        $esquema = $stmtEsquema->fetchAll();

        $stmtHistorial = $conexion->prepare(
            'SELECT h.fechaAplicacion, ev.intervaloSiguienteDias
             FROM historialvac h
             INNER JOIN citas c ON c.IDcita = h.IDcita
             INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
             WHERE c.IDmascota = :IDmascota AND ev.IDvacuna = :IDvacuna
             ORDER BY h.fechaAplicacion DESC, h.IDhistorialVac DESC'
        );
        $stmtHistorial->execute([':IDmascota' => $idMascota, ':IDvacuna' => $idVacuna]);
        $aplicaciones = $stmtHistorial->fetchAll();
        $cantidad = count($aplicaciones);
        $esRefuerzo = $cantidad >= count($esquema);
        $siguiente = $esquema[min($cantidad, max(0, count($esquema) - 1))] ?? null;
        $fechaRecomendada = null;

        if ($cantidad > 0 && !empty($aplicaciones[0]['intervaloSiguienteDias'])) {
            $fechaRecomendada = (new DateTimeImmutable($aplicaciones[0]['fechaAplicacion']))
                ->modify('+' . (int) $aplicaciones[0]['intervaloSiguienteDias'] . ' days');
        } elseif ($cantidad === 0 && $siguiente && $fechaNacimiento) {
            $unidades = ['Días' => 'days', 'Semanas' => 'weeks', 'Meses' => 'months', 'Años' => 'years'];
            $unidad = $unidades[$siguiente['unidadEdad']] ?? 'days';
            $fechaRecomendada = (new DateTimeImmutable($fechaNacimiento))
                ->modify('+' . (int) $siguiente['edadAplicacion'] . ' ' . $unidad);
        }

        $advertencia = '';
        $nivel = 'info';
        $fechaTexto = $fechaRecomendada ? $fechaRecomendada->format('d/m/Y') : null;
        if ($esRefuerzo) {
            $advertencia = 'El esquema principal ya está completo. Esta aplicación se registrará como refuerzo';
            $advertencia .= $fechaTexto ? ', recomendado desde el ' . $fechaTexto . '.' : '.';
            $advertencia .= ' Confirme que el veterinario lo considera necesario.';
            $nivel = 'warning';
        } elseif ($fechaRecomendada && $fechaRecomendada->format('Y-m-d') > $fechaCita) {
            $advertencia = 'El intervalo recomendado todavía no se ha cumplido. La dosis '
                . $siguiente['numeroDosis'] . ' corresponde desde el ' . $fechaTexto . '.';
            $nivel = 'warning';
        } elseif ($fechaRecomendada && $fechaRecomendada->format('Y-m-d') < $fechaCita) {
            $advertencia = 'La dosis ' . $siguiente['numeroDosis']
                . ' estaba recomendada desde el ' . $fechaTexto . '.';
        }

        return [
            'numeroDosisSiguiente' => (int) ($siguiente['numeroDosis'] ?? 1),
            'esRefuerzo' => $esRefuerzo,
            'fechaRecomendada' => $fechaRecomendada?->format('Y-m-d'),
            'advertencia' => $advertencia,
            'nivelAdvertencia' => $nivel,
        ];
    }
}
