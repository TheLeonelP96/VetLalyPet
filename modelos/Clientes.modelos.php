<?php

require_once __DIR__ . '/conexion.php';

final class ModeloClientes
{
    private const FILTROS = [
        'IDcliente' => 'IDcliente',
        'habilitadoC' => 'habilitadoC',
    ];

    public static function mdlMostrarClientes(...$argumentos): mixed
    {
        if (($argumentos[0] ?? null) === 'clientes' && count($argumentos) >= 3) {
            $item = $argumentos[1];
            $valor = $argumentos[2];
        } else {
            $item = $argumentos[0] ?? null;
            $valor = $argumentos[1] ?? null;
        }
        $sql = 'SELECT *, telefono_c AS telefono FROM clientes';
        $condiciones = [];
        $parametros = [];

        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) {
                throw new InvalidArgumentException('Filtro de cliente no permitido.');
            }
            $condiciones[] = self::FILTROS[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' ORDER BY nombrec, apellidoc';

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlListarClientesHabilitados(): array
    {
        $stmt = Conexion::conectar()->query(
            'SELECT *, telefono_c AS telefono FROM clientes
             WHERE habilitadoC = 1 ORDER BY nombrec, apellidoc'
        );
        return $stmt->fetchAll();
    }

    public static function mdlCrearCliente(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'INSERT INTO clientes (nombrec, apellidoc, telefono_c, registro, habilitadoC)
                 VALUES (:nombre, :apellido, :telefono, :registro, 1)'
            );
            $stmt->execute(self::parametros($datos));
            return 'ok';
        } catch (PDOException $e) {
            return 'error';
        }
    }

    public static function mdlEditarCliente(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE clientes SET
                    nombrec = :nombre,
                    apellidoc = :apellido,
                    telefono_c = :telefono,
                    registro = :registro
                 WHERE IDcliente = :IDcliente'
            );
            $parametros = self::parametros($datos);
            $parametros[':IDcliente'] = (int) $datos['IDcliente'];
            $stmt->execute($parametros);
            return 'ok';
        } catch (PDOException $e) {
            return 'error';
        }
    }

    public static function mdlActualizarEstadoCliente(int $idCliente, int $estado): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE clientes SET habilitadoC = :estado WHERE IDcliente = :IDcliente'
        );
        $stmt->execute([':estado' => $estado, ':IDcliente' => $idCliente]);
        if ($stmt->rowCount() === 1) {
            return 'ok';
        }
        $existe = self::mdlMostrarClientes('IDcliente', $idCliente);
        return is_array($existe) ? 'sin_cambios' : 'no_encontrado';
    }

    private static function parametros(array $datos): array
    {
        return [
            ':nombre' => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':telefono' => $datos['telefono'],
            ':registro' => $datos['registro'],
        ];
    }

}
