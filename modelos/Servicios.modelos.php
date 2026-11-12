<?php
// Gestiona el catálogo de tipos de servicio y su estado lógico.

require_once __DIR__ . '/conexion.php';

final class ModeloServicios
{
    private const FILTROS = [
        'IDservicio' => 'IDtiposervicio',
        'tipoServicio' => 'tipoServicio',
        'estadoServicio' => 'estadoServicio',
    ];

    public static function mdlMostrarServicios($item = null, $valor = null): mixed
    {
        $sql = 'SELECT IDtiposervicio AS IDservicio, tipoServicio, descripcion, precio, duracionMinutos, estadoServicio FROM tiposervicios';
        $parametros = [];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de servicio no permitido.');
            }
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        $sql .= ' ORDER BY tipoServicio';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlCrearServicio(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'INSERT INTO tiposervicios (tipoServicio, descripcion, precio, duracionMinutos, estadoServicio)
                 VALUES (:tipo, :descripcion, :precio, :duracion, 1)'
            );
            $stmt->execute(self::parametros($datos));
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlEditarServicio(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE tiposervicios SET tipoServicio = :tipo, descripcion = :descripcion,
                    precio = :precio, duracionMinutos = :duracion
                 WHERE IDtiposervicio = :IDservicio'
            );
            $parametros = self::parametros($datos);
            $parametros[':IDservicio'] = (int) $datos['IDservicio'];
            $stmt->execute($parametros);
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlActualizarEstadoServicio(int $idServicio, int $estado): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE tiposervicios SET estadoServicio = :estado WHERE IDtiposervicio = :IDservicio'
        );
        $stmt->execute([':estado' => $estado, ':IDservicio' => $idServicio]);
        if ($stmt->rowCount() === 1) {
            return 'ok';
        }
        return is_array(self::mdlMostrarServicios('IDservicio', $idServicio))
            ? 'sin_cambios' : 'no_encontrado';
    }

    private static function parametros(array $datos): array
    {
        return [
            ':tipo' => $datos['tipo'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => $datos['precio'],
            ':duracion' => (int) $datos['duracion'],
        ];
    }
}
