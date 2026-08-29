<?php

    require_once "../core/Seguridad.php";
    Seguridad::exigirAutenticacion(true);

    require_once "../controlador/Clientes.controlador.php";
    require_once "../modelos/Clientes.modelos.php";

    
    class ajaxClientes
    {
        public $IDcliente;

        public function ajaxEditarClientes()
        {
           $item="IDcliente";
           $valor= $this->IDcliente;
           
           $respuesta= ControladorClientes::crtMostrarClientes($item, $valor);
           header('Content-Type: application/json; charset=utf-8');
           echo json_encode($respuesta);
           
        }

    }
    if(isset($_POST["IDcliente"]))
    {
        $editar=new ajaxClientes();
        $editar->IDcliente=$_POST["IDcliente"];
        $editar->ajaxEditarClientes ();
    }
    /* */




?>
