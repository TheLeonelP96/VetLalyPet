<?php

require_once __DIR__ . '/conexion.php';

final class ModeloVacunas
{
    private const FILTROS = [
        'IDvacuna' => 'v.IDvacuna',
        'IDespecie' => 'v.IDespecie',
        'estadoVac' => 'v.estadoVac',
    ];

    public static function mdlMostrarVacunas($item = null, $valor = null): mixed
    {
        $sql = 'SELECT v.*, e.nombre_Especie
                FROM vacuna v
                INNER JOIN especie e ON e.IDespecie = v.IDespecie';
        $parametros = [];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de vacuna no permitido.');
            }
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        $sql .= ' ORDER BY e.nombre_Especie, v.nombreVac';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlListarEspecies(): array
    {
        return Conexion::conectar()->query(
            'SELECT IDespecie, nombre_Especie FROM especie ORDER BY nombre_Especie'
        )->fetchAll();
    }

    public static function mdlEspecieExiste(int $idEspecie): bool
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT 1 FROM especie WHERE IDespecie = :IDespecie'
        );
        $stmt->execute([':IDespecie' => $idEspecie]);
        return (bool) $stmt->fetchColumn();
    }

    public static function mdlCrearVacuna(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'INSERT INTO vacuna
                    (nombreVac, Descripcion, preciovac, stock, estadoVac, IDespecie)
                 VALUES
                    (:nombre, :descripcion, :precio, :stock, 1, :IDespecie)'
            );
            $stmt->execute(self::parametros($datos));
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlEditarVacuna(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE vacuna SET
                    nombreVac = :nombre,
                    Descripcion = :descripcion,
                    preciovac = :precio,
                    stock = :stock,
                    IDespecie = :IDespecie
                 WHERE IDvacuna = :IDvacuna'
            );
            $parametros = self::parametros($datos);
            $parametros[':IDvacuna'] = (int) $datos['IDvacuna'];
            $stmt->execute($parametros);
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlActualizarEstadoVacuna(int $idVacuna, int $estado): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE vacuna SET estadoVac = :estado WHERE IDvacuna = :IDvacuna'
        );
        $stmt->execute([':estado' => $estado, ':IDvacuna' => $idVacuna]);
        if ($stmt->rowCount() === 1) {
            return 'ok';
        }
        return is_array(self::mdlMostrarVacunas('IDvacuna', $idVacuna))
            ? 'sin_cambios' : 'no_encontrada';
    }

    private static function parametros(array $datos): array
    {
        return [
            ':nombre' => $datos['nombre'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => $datos['precio'],
            ':stock' => (int) $datos['stock'],
            ':IDespecie' => (int) $datos['IDespecie'],
        ];
    }
}
