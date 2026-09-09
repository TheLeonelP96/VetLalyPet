<?php
    require_once "core/Seguridad.php";
    Seguridad::iniciarSesion();
    if (ob_get_level() === 0) {
        ob_start();
    }

    require_once "controlador/plantilla.controlador.php";
    require_once "controlador/Usuarios.controlador.php";
    require_once "controlador/Mascotas.controlador.php";
    require_once "controlador/Clientes.controlador.php";
    require_once "controlador/Citas.controlador.php";
    require_once "controlador/Vacunas.controlador.php";
    require_once "controlador/HistorialVacunas.controlador.php";
    require_once "controlador/Pagos.controlador.php";

    require_once "modelos/Mascotas.modelos.php";
    require_once "modelos/Clientes.modelos.php";
    require_once "modelos/Usuarios.modelos.php";
    require_once "modelos/Citas.modelos.php";
    require_once "modelos/Vacunas.modelos.php";
    require_once "modelos/HistorialVacunas.modelos.php";
    require_once "modelos/Pagos.modelos.php";

    // Procesar acciones antes de renderizar HTML permite redirigir con seguridad.
    $rutaActual = $_GET['ruta'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($rutaActual === 'Calendar') {
            ControladorCitas::ctrProcesarSolicitud();
        } elseif ($rutaActual === 'Clientes') {
            ControladorClientes::ctrProcesarSolicitud();
        } elseif ($rutaActual === 'Mascotas') {
            ControladorMascotas::ctrProcesarSolicitud();
        } elseif ($rutaActual === 'Usuarios') {
            ControladorUsuarios::ctrProcesarSolicitud();
        } elseif ($rutaActual === 'Vacunas') {
            ControladorVacunas::ctrProcesarSolicitud();
        } elseif ($rutaActual === 'HistorialVac') {
            ControladorHistorialVacunas::ctrProcesarSolicitud();
        } elseif ($rutaActual === 'Pagos') {
            ControladorPagos::ctrProcesarSolicitud();
        } elseif ($rutaActual === '' && (isset($_POST['imgEmail']) || isset($_POST['imgUsuario']))) {
            ControladorUsuarios::ctrIngresarUsuarios();
        }
    }

    if ($rutaActual === 'salir' && Seguridad::autenticado()) {
        ControladorUsuarios::ctrCerrarSesion();
    }

    $plantilla = new ControladorPlantilla();
    $plantilla->crtPlantilla();

?>
