<?php
// Gestiona las dosis configurables y el seguimiento por mascota.

require_once __DIR__ . '/conexion.php';

final class ModeloSeguimientoVacunas
{
    public static function mdlListarEsquemas(): array
    {
        return Conexion::conectar()->query(
            "SELECT ev.IDesquemaVac, ev.numeroDosis, ev.edadAplicacion, ev.unidadEdad,
                    ev.intervaloSiguienteDias, v.IDvacuna, v.nombreVac, v.estadoVac,
                    e.IDespecie, e.nombre_Especie,
                    EXISTS(SELECT 1 FROM historialvac h WHERE h.ID_esquemaVac = ev.IDesquemaVac) AS utilizado
             FROM esquemaVac ev
             INNER JOIN vacuna v ON v.IDvacuna = ev.IDvacuna
             INNER JOIN especie e ON e.IDespecie = v.IDespecie
             ORDER BY e.nombre_Especie, v.nombreVac, ev.numeroDosis"
        )->fetchAll();
    }

    public static function mdlMostrarEsquema(int $idEsquema): ?array
    {
        $stmt = Conexion::conectar()->prepare(
            "SELECT ev.IDesquemaVac, ev.numeroDosis, ev.edadAplicacion, ev.unidadEdad,
                    ev.intervaloSiguienteDias, v.IDvacuna, v.nombreVac,
                    e.IDespecie, e.nombre_Especie,
                    EXISTS(SELECT 1 FROM historialvac h WHERE h.ID_esquemaVac = ev.IDesquemaVac) AS utilizado
             FROM esquemaVac ev
             INNER JOIN vacuna v ON v.IDvacuna = ev.IDvacuna
             INNER JOIN especie e ON e.IDespecie = v.IDespecie
             WHERE ev.IDesquemaVac = :id"
        );
        $stmt->execute([':id' => $idEsquema]);
        return $stmt->fetch() ?: null;
    }

    public static function mdlListarEspecies(): array
    {
        return Conexion::conectar()->query(
            'SELECT IDespecie, nombre_Especie FROM especie ORDER BY nombre_Especie'
        )->fetchAll();
    }

    public static function mdlListarVacunas(): array
    {
        return Conexion::conectar()->query(
            "SELECT v.IDvacuna, v.nombreVac, v.IDespecie, v.estadoVac, e.nombre_Especie
             FROM vacuna v INNER JOIN especie e ON e.IDespecie = v.IDespecie
             ORDER BY e.nombre_Especie, v.nombreVac"
        )->fetchAll();
    }

    public static function mdlVacunaExiste(int $idVacuna): bool
    {
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM vacuna WHERE IDvacuna = :id');
        $stmt->execute([':id' => $idVacuna]);
        return (bool) $stmt->fetchColumn();
    }

    public static function mdlCrearEsquema(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'INSERT INTO esquemaVac
                    (numeroDosis, edadAplicacion, unidadEdad, intervaloSiguienteDias, IDvacuna)
                 VALUES (:dosis, :edad, :unidad, :intervalo, :vacuna)'
            );
            $stmt->execute(self::parametrosEsquema($datos));
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlEditarEsquema(array $datos): string
    {
        $actual = self::mdlMostrarEsquema((int) $datos['IDesquemaVac']);
        if ($actual === null) {
            return 'no_encontrado';
        }
        if ((int) $actual['utilizado'] === 1
            && ((int) $actual['IDvacuna'] !== (int) $datos['IDvacuna']
                || (int) $actual['numeroDosis'] !== (int) $datos['numeroDosis'])) {
            return 'clave_en_uso';
        }
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE esquemaVac SET numeroDosis = :dosis, edadAplicacion = :edad,
                    unidadEdad = :unidad, intervaloSiguienteDias = :intervalo,
                    IDvacuna = :vacuna
                 WHERE IDesquemaVac = :id'
            );
            $parametros = self::parametrosEsquema($datos);
            $parametros[':id'] = (int) $datos['IDesquemaVac'];
            $stmt->execute($parametros);
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlEliminarEsquema(int $idEsquema): string
    {
        $actual = self::mdlMostrarEsquema($idEsquema);
        if ($actual === null) {
            return 'no_encontrado';
        }
        if ((int) $actual['utilizado'] === 1) {
            return 'en_uso';
        }
        try {
            $stmt = Conexion::conectar()->prepare(
                'DELETE FROM esquemaVac WHERE IDesquemaVac = :id'
            );
            $stmt->execute([':id' => $idEsquema]);
            return $stmt->rowCount() === 1 ? 'ok' : 'no_encontrado';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'en_uso' : 'error';
        }
    }

    public static function mdlObtenerSeguimiento(): array
    {
        $conexion = Conexion::conectar();
        $mascotas = $conexion->query(
            "SELECT m.IDmascota, m.nombrem, m.fechaNac_m,
                    TIMESTAMPDIFF(YEAR, m.fechaNac_m, CURDATE()) AS edadAnios,
                    TIMESTAMPDIFF(MONTH, m.fechaNac_m, CURDATE()) AS edadMeses,
                    DATEDIFF(CURDATE(), m.fechaNac_m) AS edadDias,
                    cli.IDcliente, CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    e.IDespecie, e.nombre_Especie
             FROM mascotas m
             INNER JOIN clientes cli ON cli.IDcliente = m.IDcliente
             INNER JOIN raza r ON r.IDraza = m.IDraza
             INNER JOIN especie e ON e.IDespecie = r.IDespecie
             WHERE m.habilitadoM = 1 AND cli.habilitadoC = 1
             ORDER BY m.nombrem, propietario"
        )->fetchAll();

        $stmtVacunas = $conexion->prepare(
            "SELECT v.IDvacuna, v.nombreVac, ev.IDesquemaVac, ev.numeroDosis,
                    ev.edadAplicacion, ev.unidadEdad, ev.intervaloSiguienteDias
             FROM vacuna v
             INNER JOIN esquemaVac ev ON ev.IDvacuna = v.IDvacuna
             WHERE v.IDespecie = :IDespecie AND v.estadoVac = 1
             ORDER BY v.nombreVac, ev.numeroDosis"
        );
        $stmtHistorial = $conexion->prepare(
            "SELECT h.IDhistorialVac, h.fechaAplicacion, h.fechaProximaDosis,
                    h.observaciones, h.ID_esquemaVac, ev.IDvacuna, ev.numeroDosis,
                    c.IDcita
             FROM historialvac h
             INNER JOIN esquemaVac ev ON ev.IDesquemaVac = h.ID_esquemaVac
             INNER JOIN citas c ON c.IDcita = h.IDcita
             WHERE c.IDmascota = :IDmascota
             ORDER BY h.fechaAplicacion, h.IDhistorialVac"
        );

        $resultado = [];
        $vacunasPorEspecie = [];
        foreach ($mascotas as $mascota) {
            $idEspecie = (int) $mascota['IDespecie'];
            if (!isset($vacunasPorEspecie[$idEspecie])) {
                $stmtVacunas->execute([':IDespecie' => $idEspecie]);
                $filas = $stmtVacunas->fetchAll();
                $vacunasPorEspecie[$idEspecie] = [];
                foreach ($filas as $fila) {
                    $idVacuna = (int) $fila['IDvacuna'];
                    $vacunasPorEspecie[$idEspecie][$idVacuna]['IDvacuna'] = $idVacuna;
                    $vacunasPorEspecie[$idEspecie][$idVacuna]['nombreVac'] = $fila['nombreVac'];
                    $vacunasPorEspecie[$idEspecie][$idVacuna]['dosis'][] = $fila;
                }
            }

            $stmtHistorial->execute([':IDmascota' => $mascota['IDmascota']]);
            $historialPorVacuna = [];
            foreach ($stmtHistorial->fetchAll() as $aplicacion) {
                $historialPorVacuna[(int) $aplicacion['IDvacuna']][] = $aplicacion;
            }

            $seguimientos = [];
            foreach ($vacunasPorEspecie[$idEspecie] as $vacuna) {
                $seguimientos[] = self::construirSeguimiento(
                    $mascota,
                    $vacuna,
                    $historialPorVacuna[$vacuna['IDvacuna']] ?? []
                );
            }
            $mascota['edadTexto'] = self::edadTexto($mascota);
            $mascota['vacunas'] = $seguimientos;
            $resultado[] = $mascota;
        }
        return $resultado;
    }

    private static function construirSeguimiento(array $mascota, array $vacuna, array $aplicaciones): array
    {
        $porEsquema = [];
        foreach ($aplicaciones as $aplicacion) {
            $porEsquema[(int) $aplicacion['ID_esquemaVac']][] = $aplicacion;
        }

        $dosis = [];
        $ultimaAplicacion = null;
        foreach ($vacuna['dosis'] as $filaEsquema) {
            $idEsquema = (int) $filaEsquema['IDesquemaVac'];
            $aplicacion = !empty($porEsquema[$idEsquema])
                ? array_shift($porEsquema[$idEsquema]) : null;
            $fechaRecomendada = self::fechaPorEdad($mascota['fechaNac_m'], $filaEsquema);
            if ($ultimaAplicacion && !empty($ultimaAplicacion['intervaloSiguienteDias'])) {
                $fechaRecomendada = (new DateTimeImmutable($ultimaAplicacion['fechaAplicacion']))
                    ->modify('+' . (int) $ultimaAplicacion['intervaloSiguienteDias'] . ' days')
                    ->format('Y-m-d');
            }
            $estado = $aplicacion ? 'Aplicada' : self::estadoFecha($fechaRecomendada);
            $dosis[] = [
                'numero' => (int) $filaEsquema['numeroDosis'],
                'estado' => $estado,
                'fechaAplicacion' => $aplicacion['fechaAplicacion'] ?? null,
                'fechaRecomendada' => $fechaRecomendada,
            ];
            if ($aplicacion) {
                $ultimaAplicacion = $aplicacion + [
                    'intervaloSiguienteDias' => $filaEsquema['intervaloSiguienteDias'],
                ];
            }
        }

        $extras = [];
        foreach ($porEsquema as $restantes) {
            foreach ($restantes as $aplicacion) {
                $extras[] = $aplicacion;
            }
        }
        usort($extras, static fn(array $a, array $b): int =>
            strcmp($a['fechaAplicacion'], $b['fechaAplicacion']));
        foreach ($extras as $refuerzo) {
            $dosis[] = [
                'numero' => 'R', 'estado' => 'Refuerzo aplicado',
                'fechaAplicacion' => $refuerzo['fechaAplicacion'], 'fechaRecomendada' => null,
            ];
            $ultimaAplicacion = $refuerzo;
        }

        $aplicadas = count(array_filter($dosis, static fn(array $fila): bool =>
            $fila['estado'] === 'Aplicada' || $fila['estado'] === 'Refuerzo aplicado'));
        $totalBase = count($vacuna['dosis']);
        $proxima = null;
        foreach ($dosis as $fila) {
            if ($fila['estado'] !== 'Aplicada' && $fila['estado'] !== 'Refuerzo aplicado') {
                $proxima = $fila;
                break;
            }
        }
        if ($proxima === null && $ultimaAplicacion) {
            $ultimoEsquema = end($vacuna['dosis']);
            if (!empty($ultimoEsquema['intervaloSiguienteDias'])) {
                $fecha = (new DateTimeImmutable($ultimaAplicacion['fechaAplicacion']))
                    ->modify('+' . (int) $ultimoEsquema['intervaloSiguienteDias'] . ' days')
                    ->format('Y-m-d');
                $proxima = ['numero' => 'R', 'estado' => self::estadoFecha($fecha),
                    'fechaAplicacion' => null, 'fechaRecomendada' => $fecha];
            }
        }

        return $vacuna + [
            'dosisSeguimiento' => $dosis,
            'aplicadas' => min($aplicadas, $totalBase),
            'totalBase' => $totalBase,
            'proxima' => $proxima,
        ];
    }

    private static function fechaPorEdad(string $nacimiento, array $esquema): string
    {
        $unidades = ['Días' => 'days', 'Semanas' => 'weeks', 'Meses' => 'months', 'Años' => 'years'];
        return (new DateTimeImmutable($nacimiento))
            ->modify('+' . (int) $esquema['edadAplicacion'] . ' '
                . ($unidades[$esquema['unidadEdad']] ?? 'days'))
            ->format('Y-m-d');
    }

    private static function estadoFecha(string $fecha): string
    {
        $hoy = new DateTimeImmutable('today');
        $recomendada = new DateTimeImmutable($fecha);
        if ($recomendada < $hoy) {
            return 'Atrasada';
        }
        if ($recomendada <= $hoy->modify('+30 days')) {
            return 'Próxima';
        }
        return 'Pendiente';
    }

    private static function edadTexto(array $mascota): string
    {
        $anios = (int) $mascota['edadAnios'];
        $meses = (int) $mascota['edadMeses'];
        if ($anios > 0) {
            $resto = $meses % 12;
            return $anios . ($anios === 1 ? ' año' : ' años')
                . ($resto ? ' y ' . $resto . ($resto === 1 ? ' mes' : ' meses') : '');
        }
        if ($meses > 0) {
            return $meses . ($meses === 1 ? ' mes' : ' meses');
        }
        $dias = max(0, (int) $mascota['edadDias']);
        return $dias . ($dias === 1 ? ' día' : ' días');
    }

    private static function parametrosEsquema(array $datos): array
    {
        return [
            ':dosis' => (int) $datos['numeroDosis'],
            ':edad' => (int) $datos['edadAplicacion'],
            ':unidad' => $datos['unidadEdad'],
            ':intervalo' => $datos['intervaloSiguienteDias'],
            ':vacuna' => (int) $datos['IDvacuna'],
        ];
    }
}
