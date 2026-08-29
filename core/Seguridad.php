<?php

// Toda validación de fechas del sistema debe usar la hora local de Bolivia,
// independientemente de la zona configurada globalmente en XAMPP/PHP.
date_default_timezone_set('America/La_Paz');

final class Seguridad
{
    public static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
            session_start();
        }
    }

    public static function autenticado(): bool
    {
        self::iniciarSesion();
        return isset($_SESSION['IniciarSesion'], $_SESSION['IDusuario'])
            && $_SESSION['IniciarSesion'] === 'ok';
    }

    public static function exigirAutenticacion(bool $json = false): void
    {
        if (self::autenticado()) {
            return;
        }

        http_response_code(401);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Sesión requerida']);
        }
        exit;
    }

    public static function exigirRol(array $roles, bool $json = false): void
    {
        self::exigirAutenticacion($json);
        if (in_array((int) ($_SESSION['IDrol'] ?? 0), $roles, true)) {
            return;
        }

        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'No tiene permiso para esta operación']);
        }
        exit;
    }

    public static function tokenCsrf(): string
    {
        self::iniciarSesion();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function campoCsrf(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . self::escapar(self::tokenCsrf()) . '">';
    }

    public static function validarCsrf(): bool
    {
        self::iniciarSesion();
        $recibido = $_POST['csrf_token'] ?? '';
        return is_string($recibido)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $recibido);
    }

    public static function exigirCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !self::validarCsrf()) {
            http_response_code(403);
            exit('Solicitud inválida o vencida. Recargue la página e intente nuevamente.');
        }
    }

    public static function escapar($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function tipoImagenSubida(array $archivo): string
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || empty($archivo['tmp_name'])
            || ($archivo['size'] ?? 0) > 2_000_000
            || !is_uploaded_file($archivo['tmp_name'])) {
            throw new RuntimeException('La imagen no es válida o supera los 2 MB.');
        }

        $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!in_array($tipo, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('Solo se permiten imágenes JPG o PNG.');
        }
        return $tipo;
    }

    public static function eliminarImagenGuardada(string $rutaRelativa): void
    {
        if ($rutaRelativa === '') {
            return;
        }
        $raizPermitida = realpath(dirname(__DIR__) . '/vistas/js/img/usuarios');
        $archivo = realpath(dirname(__DIR__) . '/' . ltrim(str_replace('\\', '/', $rutaRelativa), '/'));
        if ($raizPermitida !== false && $archivo !== false
            && str_starts_with($archivo, $raizPermitida . DIRECTORY_SEPARATOR)
            && is_file($archivo)) {
            unlink($archivo);
        }
    }
}
