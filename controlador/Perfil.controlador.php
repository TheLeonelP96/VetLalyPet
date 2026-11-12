<?php
// Controla la consulta y actualización segura del perfil del usuario.

final class ControladorPerfil
{
    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['editarPerfil'])) {
            self::ctrActualizarPerfil();
        }
    }

    public static function ctrDashboardActual(): ?array
    {
        Seguridad::exigirAutenticacion();
        return ModeloPerfil::mdlObtenerDashboard((int) $_SESSION['IDusuario']);
    }

    private static function ctrActualizarPerfil(): void
    {
        Seguridad::exigirAutenticacion();
        Seguridad::exigirCsrf();

        $idUsuario = (int) $_SESSION['IDusuario'];
        $actual = ModeloPerfil::mdlObtenerCredenciales($idUsuario);
        $nombre = trim((string) ($_POST['perfilNombre'] ?? ''));
        $apellido = trim((string) ($_POST['perfilApellido'] ?? ''));
        $telefono = trim((string) ($_POST['perfilTelefono'] ?? ''));
        $correo = mb_strtolower(trim((string) ($_POST['perfilEmail'] ?? '')));
        $passwordActual = $_POST['perfilPasswordActual'] ?? null;
        $passwordNueva = $_POST['perfilPasswordNueva'] ?? '';

        if (!is_array($actual) || !is_string($passwordActual) || strlen($passwordActual) > 200
            || !password_verify($passwordActual, $actual['password'])) {
            self::redirigir('danger', 'La contraseña actual no es correcta. No se modificaron sus datos.');
        }
        if (!self::nombreValido($nombre) || !self::nombreValido($apellido)
            || preg_match('/^[0-9+() -]{7,20}$/', $telefono) !== 1
            || strlen($correo) > 254 || !filter_var($correo, FILTER_VALIDATE_EMAIL)
            || ($passwordNueva !== '' && (!is_string($passwordNueva)
                || strlen($passwordNueva) < 8 || strlen($passwordNueva) > 72))) {
            self::redirigir('danger', 'Revise los datos ingresados. La nueva contraseña debe tener entre 8 y 72 caracteres.');
        }

        $respuesta = ModeloPerfil::mdlActualizarDatosPersonales([
            'IDusuario' => $idUsuario,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'correo' => $correo,
            'password' => $passwordNueva === ''
                ? $actual['password']
                : password_hash($passwordNueva, PASSWORD_DEFAULT),
        ]);
        if ($respuesta === 'duplicado') {
            self::redirigir('warning', 'Ese correo electrónico ya pertenece a otro usuario.');
        }
        if ($respuesta !== 'ok') {
            self::redirigir('danger', 'No se pudieron actualizar sus datos.');
        }

        $_SESSION['nombre'] = $nombre;
        $_SESSION['apellido'] = $apellido;
        $_SESSION['telefono'] = $telefono;
        $_SESSION['correo'] = $correo;
        self::redirigir('success', $passwordNueva === ''
            ? 'Sus datos personales fueron actualizados.'
            : 'Sus datos y su contraseña fueron actualizados.');
    }

    private static function nombreValido(string $valor): bool
    {
        return mb_strlen($valor) >= 2 && mb_strlen($valor) <= 50
            && preg_match("/^[\\p{L}\\p{M} .'-]+$/u", $valor) === 1;
    }

    private static function redirigir(string $tipo, string $mensaje): void
    {
        $_SESSION['perfil_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Perfil');
        exit;
    }
}
