<?php

    require_once "../core/Seguridad.php";
    Seguridad::exigirRol([1], true);

    require_once "../controlador/Usuarios.controlador.php";
    require_once "../modelos/Usuarios.modelos.php";

    
    class ajaxUsuarios
    {
        public $IDusuario;

        public function ajaxEditarUsuarios()
        {
           $item="IDusuario";
           $valor= $this->IDusuario;
           
           $respuesta= ControladorUsuarios::ctrMostrarUsuarios($item, $valor);
           if (is_array($respuesta)) {
               unset($respuesta['password']);
           }
           header('Content-Type: application/json; charset=utf-8');
           echo json_encode($respuesta);
           
        }

    }
    if(isset($_POST["IDusuario"]))
    {
        $editar=new ajaxUsuarios();
        $editar->IDusuario=$_POST["IDusuario"];
        $editar->ajaxEditarUsuarios ();
    }





?>
