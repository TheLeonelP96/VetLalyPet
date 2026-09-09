<?php

final class ControladorUsuarios
{
    private const ROLES_VALIDOS = [1, 2, 3];
    private const TURNOS_VALIDOS = ['Mañana', 'Tarde', 'Horario continuo'];

    public static function ctrProcesarSolicitud(): void
    {
        if (isset($_POST['nuevoNombre'])) {
            self::ctrCrearUsuarios();
            return;
        }
        if (isset($_POST['editarNombre'])) {
            self::ctrEditarUsuarios();
            return;
        }
        if (isset($_POST['IDusuario'], $_POST['estadoUsuario'])) {
            self::ctrActualizarEstadoUsuario();
        }
    }

    public static function ctrIngresarUsuarios(): void
    {
        $correoRecibido = $_POST['imgEmail'] ?? $_POST['imgUsuario'] ?? null;
        if ($correoRecibido === null) {
            return;
        }

        Seguridad::exigirCsrf();
        $correo = self::normalizarCorreo($correoRecibido);
        $password = $_POST['imgPassword'] ?? null;
        if ($correo === null || !is_string($password) || $password === '' || strlen($password) > 200) {
            self::fallarLogin();
        }

        $usuario = ModeloUsuarios::mdlBuscarPorCorreo($correo);
        if (!is_array($usuario) || !password_verify($password, $usuario['password'])) {
            self::fallarLogin();
        }

        if (password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
            ModeloUsuarios::mdlActualizarPassword(
                (int) $usuario['IDusuario'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        }

        session_regenerate_id(true);
        self::guardarUsuarioEnSesion($usuario);
        ModeloUsuarios::mdlActualizarSesion((int) $usuario['IDusuario'], 1);
        header('Location: Inicio');
        exit;
    }

    public static function ctrCerrarSesion(): void
    {
        Seguridad::iniciarSesion();
        if (!empty($_SESSION['IDusuario'])) {
            ModeloUsuarios::mdlActualizarSesion((int) $_SESSION['IDusuario'], 0);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'],
                $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
        header('Location: ./');
        exit;
    }

    public static function ctrMostrarUsuarios($item = null, $valor = null): mixed
    {
        return ModeloUsuarios::mdlMostrarUsuarios('usuarios', $item, $valor);
    }

    public static function ctrCrearUsuarios(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $datos = self::datosFormulario('nuevo');
        if ($datos === null) {
            self::redirigirUsuarios('danger', 'Revise los datos del usuario e intente nuevamente.');
        }

        $foto = '';
        try {
            $foto = self::guardarFoto($_FILES['nuevaFoto'] ?? null) ?? '';
        } catch (RuntimeException $e) {
            self::redirigirUsuarios('danger', $e->getMessage());
        }

        $datos['foto'] = $foto;
        $datos['password'] = password_hash((string) $_POST['nuevoPassword'], PASSWORD_DEFAULT);
        $respuesta = ModeloUsuarios::mdlCrearUsuario($datos);
        if ($respuesta !== 'ok' && $foto !== '') {
            Seguridad::eliminarImagenGuardada($foto);
        }

        if ($respuesta === 'duplicado') {
            self::redirigirUsuarios('warning', 'Ese correo electrónico ya está registrado.');
        }
        self::redirigirUsuarios(
            $respuesta === 'ok' ? 'success' : 'danger',
            $respuesta === 'ok' ? 'Usuario registrado correctamente.' : 'No se pudo registrar el usuario.'
        );
    }

    public static function ctrEditarUsuarios(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $idUsuario = filter_var($_POST['IDusuario'] ?? null, FILTER_VALIDATE_INT);
        $datos = self::datosFormulario('editar');
        $actual = $idUsuario === false
            ? null
            : ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);

        if ($datos === null || !is_array($actual)) {
            self::redirigirUsuarios('danger', 'Los datos enviados no son válidos.');
        }
        if ((int) ($_SESSION['IDusuario'] ?? 0) === (int) $idUsuario
            && (int) $actual['IDrol'] === 1 && (int) $datos['IDrol'] !== 1) {
            self::redirigirUsuarios('warning', 'No puede quitarse a sí mismo el rol de administrador.');
        }

        $fotoNueva = null;
        try {
            $fotoNueva = self::guardarFoto($_FILES['editarFoto'] ?? null);
        } catch (RuntimeException $e) {
            self::redirigirUsuarios('danger', $e->getMessage());
        }

        $passwordNueva = $_POST['editarPassword'] ?? '';
        if ($passwordNueva !== '' && !self::passwordValida($passwordNueva)) {
            if ($fotoNueva !== null) {
                Seguridad::eliminarImagenGuardada($fotoNueva);
            }
            self::redirigirUsuarios('danger', 'La contraseña debe tener entre 8 y 72 caracteres.');
        }

        $datos['IDusuario'] = (int) $idUsuario;
        $datos['foto'] = $fotoNueva ?? $actual['foto'];
        $datos['password'] = $passwordNueva === ''
            ? $actual['password']
            : password_hash($passwordNueva, PASSWORD_DEFAULT);

        $respuesta = ModeloUsuarios::mdlEditarUsuario($datos);
        if ($respuesta === 'ok') {
            if ($fotoNueva !== null && $actual['foto'] !== '') {
                Seguridad::eliminarImagenGuardada($actual['foto']);
            }
            if ((int) ($_SESSION['IDusuario'] ?? 0) === (int) $idUsuario) {
                $actualizado = ModeloUsuarios::mdlMostrarUsuarios('usuarios', 'IDusuario', $idUsuario);
                if (is_array($actualizado)) {
                    self::guardarUsuarioEnSesion($actualizado);
                }
            }
            self::redirigirUsuarios('success', 'Usuario actualizado correctamente.');
        }

        if ($fotoNueva !== null) {
            Seguridad::eliminarImagenGuardada($fotoNueva);
        }
        self::redirigirUsuarios(
            $respuesta === 'duplicado' ? 'warning' : 'danger',
            $respuesta === 'duplicado'
                ? 'Ese correo electrónico ya pertenece a otro usuario.'
                : 'No se pudo actualizar el usuario.'
        );
    }

    public static function ctrActualizarEstadoUsuario(): void
    {
        Seguridad::exigirRol([1]);
        Seguridad::exigirCsrf();
        $idUsuario = filter_var($_POST['IDusuario'] ?? null, FILTER_VALIDATE_INT);
        $estado = filter_var($_POST['estadoUsuario'] ?? null, FILTER_VALIDATE_INT);
        if ($idUsuario === false || !in_array($estado, [0, 1], true)) {
            self::redirigirUsuarios('danger', 'La operación solicitada no es válida.');
        }
        if ((int) $_SESSION['IDusuario'] === (int) $idUsuario && $estado === 0) {
            self::redirigirUsuarios('warning', 'No puede deshabilitar su propia cuenta.');
        }

        $respuesta = ModeloUsuarios::mdlActualizarEstadoUsuario((int) $idUsuario, (int) $estado);
        self::redirigirUsuarios(
            in_array($respuesta, ['ok', 'sin_cambios'], true) ? 'success' : 'danger',
            $estado === 1 ? 'Usuario habilitado correctamente.' : 'Usuario deshabilitado correctamente.'
        );
    }

    // Nombre anterior conservado para llamadas que aún no fueron migradas.
    public static function crtDesHabilitarUsuarios(): void
    {
        if (isset($_POST['IDusuario'], $_POST['estadoUsuario'])) {
            self::ctrActualizarEstadoUsuario();
        }
    }

    private static function datosFormulario(string $prefijo): ?array
    {
        $campos = $prefijo === 'nuevo'
            ? ['nuevoNombre', 'nuevoApellido', 'nuevoTelefono', 'nuevoEmail', 'nuevoTurno', 'nuevoRol']
            : ['editarNombre', 'editarApellido', 'editarTelefono', 'editarEmail', 'editarTurno', 'editarRol'];
        foreach ($campos as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                return null;
            }
        }

        $nombre = trim($_POST[$campos[0]]);
        $apellido = trim($_POST[$campos[1]]);
        $telefono = trim($_POST[$campos[2]]);
        $correo = self::normalizarCorreo($_POST[$campos[3]]);
        $turno = self::normalizarTurno($_POST[$campos[4]]);
        $idRol = filter_var($_POST[$campos[5]], FILTER_VALIDATE_INT);
        $password = $prefijo === 'nuevo' ? ($_POST['nuevoPassword'] ?? null) : null;

        if (!self::nombreValido($nombre) || !self::nombreValido($apellido)
            || !preg_match('/^[0-9+() -]{7,20}$/', $telefono)
            || $correo === null || $turno === null
            || $idRol === false || !in_array((int) $idRol, self::ROLES_VALIDOS, true)
            || ($prefijo === 'nuevo' && !self::passwordValida($password))) {
            return null;
        }

        return [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'correo' => $correo,
            'turno' => $turno,
            'IDrol' => (int) $idRol,
        ];
    }

    private static function guardarUsuarioEnSesion(array $usuario): void
    {
        $_SESSION['IniciarSesion'] = 'ok';
        $_SESSION['IDusuario'] = (int) $usuario['IDusuario'];
        $_SESSION['IDrol'] = (int) $usuario['IDrol'];
        $_SESSION['nombre'] = $usuario['nombre_U'];
        $_SESSION['apellido'] = $usuario['apellido_U'];
        $_SESSION['telefono'] = $usuario['telefono_U'];
        $_SESSION['correo'] = $usuario['email_U'];
        $_SESSION['turno'] = $usuario['turno_U'];
        $_SESSION['estado'] = 1;
        $_SESSION['habilitado'] = (int) $usuario['estado_U'];
        $_SESSION['foto'] = $usuario['foto'];
    }

    private static function guardarFoto(?array $archivo): ?string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $tipo = Seguridad::tipoImagenSubida($archivo);
        $dimensiones = getimagesize($archivo['tmp_name']);
        if ($dimensiones === false) {
            throw new RuntimeException('No se pudo leer la imagen seleccionada.');
        }

        $raiz = dirname(__DIR__) . '/vistas/js/img/usuarios';
        if (!is_dir($raiz) && !mkdir($raiz, 0755, true) && !is_dir($raiz)) {
            throw new RuntimeException('No se pudo preparar el directorio de imágenes.');
        }
        $carpeta = 'usuario_' . bin2hex(random_bytes(8));
        $directorio = $raiz . '/' . $carpeta;
        if (!mkdir($directorio, 0755)) {
            throw new RuntimeException('No se pudo guardar la imagen del usuario.');
        }

        $extension = $tipo === 'image/png' ? 'png' : 'jpg';
        $rutaRelativa = "vistas/js/img/usuarios/$carpeta/perfil.$extension";
        $rutaAbsoluta = dirname(__DIR__) . '/' . $rutaRelativa;
        $origen = $tipo === 'image/png'
            ? imagecreatefrompng($archivo['tmp_name'])
            : imagecreatefromjpeg($archivo['tmp_name']);
        if ($origen === false) {
            throw new RuntimeException('La imagen seleccionada no es válida.');
        }

        $destino = imagecreatetruecolor(500, 500);
        if ($tipo === 'image/png') {
            imagealphablending($destino, false);
            imagesavealpha($destino, true);
        }
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, 500, 500, $dimensiones[0], $dimensiones[1]);
        $guardada = $tipo === 'image/png'
            ? imagepng($destino, $rutaAbsoluta)
            : imagejpeg($destino, $rutaAbsoluta, 90);
        imagedestroy($origen);
        imagedestroy($destino);
        if (!$guardada) {
            throw new RuntimeException('No se pudo guardar la imagen del usuario.');
        }
        return $rutaRelativa;
    }

    private static function nombreValido(string $valor): bool
    {
        return mb_strlen($valor) >= 2 && mb_strlen($valor) <= 50
            && preg_match("/^[\\p{L}\\p{M} .'-]+$/u", $valor) === 1;
    }

    private static function passwordValida($password): bool
    {
        return is_string($password) && strlen($password) >= 8 && strlen($password) <= 72;
    }

    private static function normalizarCorreo($correo): ?string
    {
        if (!is_string($correo)) {
            return null;
        }
        $correo = mb_strtolower(trim($correo));
        return strlen($correo) <= 254 && filter_var($correo, FILTER_VALIDATE_EMAIL) ? $correo : null;
    }

    private static function normalizarTurno(string $turno): ?string
    {
        $turno = trim($turno);
        if (strcasecmp($turno, 'Horario Continuo') === 0) {
            $turno = 'Horario continuo';
        }
        return in_array($turno, self::TURNOS_VALIDOS, true) ? $turno : null;
    }

    private static function fallarLogin(): void
    {
        $_SESSION['login_error'] = 'Correo o contraseña incorrectos, o la cuenta está deshabilitada.';
        header('Location: ./');
        exit;
    }

    private static function redirigirUsuarios(string $tipo, string $mensaje): void
    {
        $_SESSION['usuario_flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
        header('Location: Usuarios');
        exit;
    }
}
