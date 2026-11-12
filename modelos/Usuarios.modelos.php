<?php
// Accede a usuarios, roles y credenciales usando consultas preparadas.

require_once __DIR__ . '/conexion.php';

final class ModeloUsuarios
{
    private const COLUMNAS_FILTRO = [
        'IDusuario' => 'u.IDusuario',
        'IDrol' => 'u.IDrol',
        'email_U' => 'u.email_U',
        'correo' => 'u.email_U',
        'Usunombre' => 'u.email_U',
        'estado_U' => 'u.estado_U',
    ];

    private static function seleccionBase(): string
    {
        // Alias temporales para los módulos que todavía esperan el esquema anterior.
        return "SELECT u.*, r.nombre_Rol,
                       u.nombre_U AS nombre,
                       u.apellido_U AS apellido,
                       u.telefono_U AS telefono,
                       u.email_U AS correo,
                       u.email_U AS Usunombre,
                       u.turno_U AS turno,
                       u.sesion_U AS estado,
                       u.estado_U AS habilitado
                FROM usuarios u
                INNER JOIN rol r ON r.IDrol = u.IDrol";
    }

    public static function mdlMostrarUsuarios($tabla = 'usuarios', $item = null, $valor = null): mixed
    {
        $sql = self::seleccionBase();
        $parametros = [];

        if ($item !== null) {
            if (!isset(self::COLUMNAS_FILTRO[$item])) {
                throw new InvalidArgumentException('Filtro de usuario no permitido.');
            }
            $sql .= ' WHERE ' . self::COLUMNAS_FILTRO[$item] . ' = :valor';
            $parametros[':valor'] = $valor;
        }

        $sql .= ' ORDER BY u.nombre_U, u.apellido_U';
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    public static function mdlBuscarPorCorreo(string $correo): ?array
    {
        $stmt = Conexion::conectar()->prepare(
            self::seleccionBase() . ' WHERE u.email_U = :correo AND u.estado_U = 1 LIMIT 1'
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch() ?: null;
    }

    public static function mdlBuscarPorCorreoSinFiltrarEstado(string $correo): ?array
    {
        $stmt = Conexion::conectar()->prepare(
            self::seleccionBase() . ' WHERE u.email_U = :correo LIMIT 1'
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch() ?: null;
    }

    public static function mdlCrearUsuario(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'INSERT INTO usuarios
                    (nombre_U, apellido_U, telefono_U, email_U, password, turno_U,
                     estado_U, sesion_U, foto, IDrol)
                 VALUES
                    (:nombre, :apellido, :telefono, :correo, :password, :turno,
                     1, 0, :foto, :IDrol)'
            );
            $stmt->execute(self::parametrosEscritura($datos));
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlEditarUsuario(array $datos): string
    {
        try {
            $stmt = Conexion::conectar()->prepare(
                'UPDATE usuarios SET
                    nombre_U = :nombre,
                    apellido_U = :apellido,
                    telefono_U = :telefono,
                    email_U = :correo,
                    password = :password,
                    turno_U = :turno,
                    foto = :foto,
                    IDrol = :IDrol
                 WHERE IDusuario = :IDusuario'
            );
            $parametros = self::parametrosEscritura($datos);
            $parametros[':IDusuario'] = (int) $datos['IDusuario'];
            $stmt->execute($parametros);
            return 'ok';
        } catch (PDOException $e) {
            return $e->getCode() === '23000' ? 'duplicado' : 'error';
        }
    }

    public static function mdlActualizarEstadoUsuario(int $idUsuario, int $estado): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE usuarios
             SET estado_U = :estado,
                 sesion_U = CASE WHEN :estadoSesion = 0 THEN 0 ELSE sesion_U END
             WHERE IDusuario = :IDusuario'
        );
        $stmt->execute([
            ':estado' => $estado,
            ':estadoSesion' => $estado,
            ':IDusuario' => $idUsuario,
        ]);
        return $stmt->rowCount() === 1 ? 'ok' : 'sin_cambios';
    }

    public static function mdlActualizarSesion(int $idUsuario, int $sesion): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE usuarios SET sesion_U = :sesion WHERE IDusuario = :IDusuario'
        );
        $stmt->execute([':sesion' => $sesion, ':IDusuario' => $idUsuario]);
        return 'ok';
    }

    public static function mdlActualizarPassword(int $idUsuario, string $hash): string
    {
        $stmt = Conexion::conectar()->prepare(
            'UPDATE usuarios SET password = :password WHERE IDusuario = :IDusuario'
        );
        return $stmt->execute([':password' => $hash, ':IDusuario' => $idUsuario]) ? 'ok' : 'error';
    }

    private static function parametrosEscritura(array $datos): array
    {
        return [
            ':nombre' => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':telefono' => $datos['telefono'],
            ':correo' => $datos['correo'],
            ':password' => $datos['password'],
            ':turno' => $datos['turno'],
            ':foto' => $datos['foto'],
            ':IDrol' => (int) $datos['IDrol'],
        ];
    }

    // Compatibilidad con llamadas existentes durante la migración por módulos.
    public static function MdlCrearUsuarios($tabla, array $datos): string
    {
        return self::mdlCrearUsuario($datos);
    }

    public static function MdlEditarUsuarios($tabla, array $datos): string
    {
        return self::mdlEditarUsuario($datos);
    }

    public static function MdlActualizarEstado($tabla, $estado, $idUsuario): string
    {
        return self::mdlActualizarSesion((int) $idUsuario, (int) $estado);
    }

    public static function MdlActualizarUsuarios($tabla, $estado, $idUsuario): string
    {
        return self::mdlActualizarEstadoUsuario((int) $idUsuario, (int) $estado);
    }
}
