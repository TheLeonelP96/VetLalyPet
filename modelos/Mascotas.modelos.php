<?php

require_once __DIR__ . '/conexion.php';

final class ModeloMascotas
{
    private const FILTROS = [
        'IDmascota' => 'm.IDmascota',
        'IDcliente' => 'm.IDcliente',
        'IDraza' => 'm.IDraza',
        'habilitadoM' => 'm.habilitadoM',
    ];

    private static function seleccionBase(): string
    {
        return "SELECT m.*, c.nombrec, c.apellidoc, c.habilitadoC,
                       r.nombre_Raza, e.IDespecie, e.nombre_Especie,
                       r.nombre_Raza AS raza, e.nombre_Especie AS especie,
                       TIMESTAMPDIFF(YEAR, m.fechaNac_m, CURDATE()) AS edad
                FROM mascotas m
                INNER JOIN clientes c ON c.IDcliente = m.IDcliente
                INNER JOIN raza r ON r.IDraza = m.IDraza
                INNER JOIN especie e ON e.IDespecie = r.IDespecie";
    }

    public static function mdlMostrarMascotas(...$argumentos): mixed
    {
        if (($argumentos[0] ?? null) === 'mascotas' && count($argumentos) >= 3) {
            $item = $argumentos[1];
            $valor = $argumentos[2];
        } else {
            $item = $argumentos[0] ?? null;
            $valor = $argumentos[1] ?? null;
        }

        $sql = self::seleccionBase();
        $parametros = [];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de mascota no permitido.');
            }
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        $sql .= ' ORDER BY m.nombrem';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlListarMascotasPorCliente(int $idCliente, bool $soloHabilitadas = true): array
    {
        $sql = self::seleccionBase() . ' WHERE m.IDcliente = :IDcliente';
        if ($soloHabilitadas) {
            $sql .= ' AND m.habilitadoM = 1';
        }
        $sql .= ' ORDER BY m.nombrem';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute([':IDcliente' => $idCliente]);
        return $stmt->fetchAll();
    }

    public static function mdlListarEspecies(): array
    {
        return Conexion::conectar()
            ->query('SELECT IDespecie, nombre_Especie FROM especie ORDER BY nombre_Especie')
            ->fetchAll();
    }

    public static function mdlListarRazas(?int $idEspecie = null): array
    {
        $sql = 'SELECT IDraza, nombre_Raza, IDespecie FROM raza';
        $parametros = [];
        if ($idEspecie !== null) {
            $sql .= ' WHERE IDespecie = :IDespecie';
            $parametros[':IDespecie'] = $idEspecie;
        }
        $sql .= ' ORDER BY nombre_Raza';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    public static function mdlCrearMascota(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'INSERT INTO mascotas
                    (nombrem, fecha_Registro, fechaNac_m, peso, sexo, habilitadoM,
                     descripcion, color, IDcliente, IDraza)
                 VALUES
                    (:nombre, :registro, :nacimiento, :peso, :sexo, 1,
                     :descripcion, :color, :IDcliente, :IDraza)'
            );
            $stmt->execute(self::parametros($datos));
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'relacion_invalida' : 'error';
        }
    }

    public static function mdlEditarMascota(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE mascotas SET
                    nombrem = :nombre,
                    fecha_Registro = :registro,
                    fechaNac_m = :nacimiento,
                    peso = :peso,
                    sexo = :sexo,
                    descripcion = :descripcion,
                    color = :color,
                    IDcliente = :IDcliente,
                    IDraza = :IDraza
                 WHERE IDmascota = :IDmascota'
            );
            $parametros = self::parametros($datos);
            $parametros[':IDmascota'] = (int) $datos['IDmascota'];
            $stmt->execute($parametros);
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'relacion_invalida' : 'error';
        }
    }

    public static function mdlActualizarEstadoMascota(int $idMascota, int $estado): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE mascotas SET habilitadoM = :estado WHERE IDmascota = :IDmascota'
        );
        $stmt->execute([':estado' => $estado, ':IDmascota' => $idMascota]);
        if ($stmt->rowCount() === 1) {
            return 'ok';
        }
        return is_array(self::mdlMostrarMascotas('IDmascota', $idMascota))
            ? 'sin_cambios' : 'no_encontrado';
    }

    public static function mdlExisteRaza(int $idRaza): bool
    {
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM raza WHERE IDraza = :IDraza');
        $stmt->execute([':IDraza' => $idRaza]);
        return (bool) $stmt->fetchColumn();
    }

    private static function parametros(array $datos): array
    {
        return [
            ':nombre' => $datos['nombre'],
            ':registro' => $datos['registro'],
            ':nacimiento' => $datos['nacimiento'],
            ':peso' => $datos['peso'],
            ':sexo' => $datos['sexo'],
            ':descripcion' => $datos['descripcion'] !== '' ? $datos['descripcion'] : null,
            ':color' => $datos['color'] !== '' ? $datos['color'] : null,
            ':IDcliente' => (int) $datos['IDcliente'],
            ':IDraza' => (int) $datos['IDraza'],
        ];
    }

    // Compatibilidad temporal con el selector de mascotas usado por Citas.
    public static function mdlMostrarMascotasPorCliente($tabla, $item, $valor): array
    {
        return self::mdlListarMascotasPorCliente((int) $valor);
    }

}
