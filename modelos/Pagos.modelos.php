<?php

require_once __DIR__ . '/conexion.php';

final class ModeloPagos
{
    private const FILTROS = [
        'IDpago' => 'p.IDpago',
        'IDcita' => 'p.IDcita',
        'metodopago' => 'p.metodopago',
    ];

    private static function seleccionBase(): string
    {
        return "SELECT p.*, c.fechaAtencion, c.estadoCita,
                       m.nombrem,
                       CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                       s.tipoServicio,
                       CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario
                FROM pago p
                INNER JOIN citas c ON c.IDcita = p.IDcita
                INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
                INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
                INNER JOIN servicios s ON s.IDservicio = c.IDservicio
                INNER JOIN usuarios u ON u.IDusuario = c.IDusuario";
    }

    public static function mdlMostrarPagos($item = null, $valor = null): mixed
    {
        $sql = self::seleccionBase();
        $parametros = [];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de pago no permitido.');
            }
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        $sql .= ' ORDER BY p.fechapago DESC, p.IDpago DESC';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlListarCitasPendientesPago(): array
    {
        return Conexion::conectar()->query(
            "SELECT c.IDcita, c.fechaAtencion, m.nombrem,
                    CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    s.tipoServicio,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario,
                    COUNT(h.IDhistorialVac) AS vacunasAplicadas,
                    CASE WHEN s.tipoServicio = 'Vacunación'
                         THEN COALESCE(SUM(v.preciovac), 0)
                         ELSE s.precio END AS montoCalculado
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN servicios s ON s.IDservicio = c.IDservicio
             INNER JOIN usuarios u ON u.IDusuario = c.IDusuario
             LEFT JOIN pago p ON p.IDcita = c.IDcita
             LEFT JOIN historialvac h ON h.IDcita = c.IDcita
             LEFT JOIN vacuna v ON v.IDvacuna = h.IDvacuna
             WHERE c.estadoCita = 'Completada' AND p.IDpago IS NULL
             GROUP BY c.IDcita, c.fechaAtencion, m.nombrem, cli.nombrec, cli.apellidoc,
                      s.tipoServicio, s.precio, u.nombre_U, u.apellido_U
             ORDER BY c.fechaAtencion DESC"
        )->fetchAll();
    }

    public static function mdlDetalleCobroCita(int $idCita): ?array
    {
        $conexion = Conexion::conectar();
        $stmt = $conexion->prepare(
            "SELECT c.IDcita, c.estadoCita, c.fechaAtencion,
                    m.nombrem, CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    s.tipoServicio, s.precio AS precioServicio,
                    p.IDpago
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN servicios s ON s.IDservicio = c.IDservicio
             LEFT JOIN pago p ON p.IDcita = c.IDcita
             WHERE c.IDcita = :IDcita"
        );
        $stmt->execute([':IDcita' => $idCita]);
        $cita = $stmt->fetch();
        if (!$cita) {
            return null;
        }
        $stmtVacunas = $conexion->prepare(
            'SELECT h.IDhistorialVac, v.nombreVac, v.preciovac
             FROM historialvac h
             INNER JOIN vacuna v ON v.IDvacuna = h.IDvacuna
             WHERE h.IDcita = :IDcita ORDER BY v.nombreVac'
        );
        $stmtVacunas->execute([':IDcita' => $idCita]);
        $vacunas = $stmtVacunas->fetchAll();
        $monto = $cita['tipoServicio'] === 'Vacunación'
            ? array_sum(array_map(static fn(array $vacuna): float => (float) $vacuna['preciovac'], $vacunas))
            : (float) $cita['precioServicio'];
        $cita['vacunas'] = $vacunas;
        $cita['montoCalculado'] = number_format($monto, 2, '.', '');
        $cita['cobrable'] = $cita['estadoCita'] === 'Completada'
            && $cita['IDpago'] === null
            && $monto > 0
            && ($cita['tipoServicio'] !== 'Vacunación' || $vacunas !== []);
        return $cita;
    }

    public static function mdlCrearPago(array $datos): string
    {
        $conexion = Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $stmtCita = $conexion->prepare(
                'SELECT c.estadoCita, s.tipoServicio, s.precio
                 FROM citas c
                 INNER JOIN servicios s ON s.IDservicio = c.IDservicio
                 WHERE c.IDcita = :IDcita FOR UPDATE'
            );
            $stmtCita->execute([':IDcita' => $datos['IDcita']]);
            $cita = $stmtCita->fetch();
            if (!$cita || $cita['estadoCita'] !== 'Completada') {
                $conexion->rollBack();
                return 'cita_invalida';
            }
            $stmtExiste = $conexion->prepare('SELECT 1 FROM pago WHERE IDcita = :IDcita FOR UPDATE');
            $stmtExiste->execute([':IDcita' => $datos['IDcita']]);
            if ($stmtExiste->fetchColumn()) {
                $conexion->rollBack();
                return 'duplicado';
            }
            $monto = (float) $cita['precio'];
            if ($cita['tipoServicio'] === 'Vacunación') {
                $stmtVacunas = $conexion->prepare(
                    'SELECT v.preciovac
                     FROM historialvac h
                     INNER JOIN vacuna v ON v.IDvacuna = h.IDvacuna
                     WHERE h.IDcita = :IDcita FOR UPDATE'
                );
                $stmtVacunas->execute([':IDcita' => $datos['IDcita']]);
                $aplicaciones = $stmtVacunas->fetchAll();
                if ($aplicaciones === []) {
                    $conexion->rollBack();
                    return 'sin_vacunas';
                }
                $monto = array_sum(array_map(
                    static fn(array $aplicacion): float => (float) $aplicacion['preciovac'],
                    $aplicaciones
                ));
            }
            if ($monto <= 0 || $monto > 99999999.99) {
                $conexion->rollBack();
                return 'monto_invalido';
            }
            $datos['monto'] = number_format($monto, 2, '.', '');
            $stmt = $conexion->prepare(
                'INSERT INTO pago (montopago, metodopago, referenciapago, fechapago, IDcita)
                 VALUES (:monto, :metodo, :referencia, :fecha, :IDcita)'
            );
            $stmt->execute(self::parametros($datos));
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

    public static function mdlEditarPago(array $datos): string
    {
        $conexion = Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $stmtActual = $conexion->prepare(
                'SELECT IDpago FROM pago WHERE IDpago = :IDpago FOR UPDATE'
            );
            $stmtActual->execute([':IDpago' => $datos['IDpago']]);
            if (!$stmtActual->fetch()) {
                $conexion->rollBack();
                return 'no_encontrado';
            }
            $stmt = $conexion->prepare(
                'UPDATE pago SET metodopago = :metodo,
                        referenciapago = :referencia, fechapago = :fecha
                 WHERE IDpago = :IDpago'
            );
            $parametros = [
                ':metodo' => $datos['metodo'],
                ':referencia' => $datos['referencia'] !== '' ? $datos['referencia'] : null,
                ':fecha' => $datos['fecha'],
                ':IDpago' => $datos['IDpago'],
            ];
            $stmt->execute($parametros);
            $conexion->commit();
            return 'ok';
        } catch (Throwable $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            return 'error';
        }
    }

    private static function parametros(array $datos): array
    {
        return [
            ':monto' => $datos['monto'],
            ':metodo' => $datos['metodo'],
            ':referencia' => $datos['referencia'] !== '' ? $datos['referencia'] : null,
            ':fecha' => $datos['fecha'],
            ':IDcita' => (int) $datos['IDcita'],
        ];
    }
}
