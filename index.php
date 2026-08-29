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
   

    require_once "modelos/Mascotas.modelos.php";
    require_once "modelos/Clientes.modelos.php";
    require_once "modelos/Usuarios.modelos.php";
    require_once "modelos/Citas.modelos.php";

    // Procesar las citas antes de renderizar cualquier HTML permite usar el
    // patrón POST/Redirect/GET sin errores de "headers already sent".
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_GET['ruta'] ?? '') === 'Calendar') {
        ControladorCitas::ctrProcesarSolicitud();
    }

    $plantilla = new ControladorPlantilla();
    $plantilla->crtPlantilla();

?>
