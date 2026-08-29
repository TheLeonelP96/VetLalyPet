<?php

    require_once "../core/Seguridad.php";
    Seguridad::exigirAutenticacion(true);

    require_once "../controlador/Mascotas.controlador.php";
    require_once "../modelos/Mascotas.modelos.php";

    class AjaxMascotas
    {

        // 1. Variable para el método de editar que ya tenías
        public $IDmascota;

        public function ajaxEditarMascota()
        {
            $item = "IDmascota";
            $valor = $this->IDmascota;
            
            $respuesta = ControladorMascotas::crtMostrarMascotas($item, $valor);
            echo json_encode($respuesta);
        }

        // ========================================================
        // NUEVA VARIABLE Y MÉTODO PARA EL SELECT DINÁMICO EN CITAS
        // ========================================================
        public $IDcliente;

        public function ajaxListarMascotasPorCliente() {
            $valor = $this->IDcliente;
            // Llamamos al método del modelo que filtra por dueño
           $respuesta = ModeloMascotas::mdlMostrarMascotasPorCliente("mascotas", "IDcliente", $valor);
            echo json_encode($respuesta);
        }
    }   
    if (isset($_POST["IDmascota"])) 
    {

        $editar = new AjaxMascotas();
        $editar->IDmascota = $_POST["IDmascota"];
        $editar->ajaxEditarMascota();
    }

    // Acción 2: Si lo que viene es el IDcliente enviado desde el calendario de citas
    if (isset($_POST["IDcliente"])) {
        $listar = new AjaxMascotas();
        $listar->IDcliente = $_POST["IDcliente"];
        $listar->ajaxListarMascotasPorCliente();
    }

?>
