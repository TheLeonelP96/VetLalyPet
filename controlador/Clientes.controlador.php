<?php 
    
    class ControladorClientes
    {
        public function crtCrearCliente()
        {
            if(isset($_POST["nuevoCliente"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["nuevoCliente"]))
                {
                    $tabla="clientes";
                    $datos=array("nombrec"=> $_POST["nuevoCliente"],
                        "apellidoc"=> $_POST["nuevoApellidoc"],
                        "telefono"=> $_POST["nuevoTelefono"],
                        "registro"=> $_POST["nuevoRegistro"]);
                    $respuesta = ModeloClientes::MdlCrearClientes ($tabla,$datos);
                    //Comparacion de variables o campos de la tabla de usuario
                    if($respuesta == "ok")
                    {
                      
                        echo'<script>
                        Swal({
                            title: "Usuario Agregado!",
                            icon: "success",
                            draggable: true

                            }).then(function(result){

                                if(result.isConfirmed || result.value)
                                {
                                    window.location = "Clientes";
                                }
                            
                            });
                            
                        </script>';

                    }
                    else
                    {
                        echo '<script>
                        Swal.fire({
                            icon: "error",
                            title: "Oops...",
                            text: "Algo esta mal!"

                            }).then(function(result){

                                if(result.isConfirmed || result.value)
                                {
                                    window.location = "Clientes";
                                }
                            
                            });
                           
                        </script>';
                    }   
                }
            }
        }
        static public function crtMostrarClientes($item, $valor)
        {
            $tabla="clientes";
            $respuesta = ModeloClientes :: MdlMostrarClientes($tabla,$item, $valor);
            return $respuesta;
        }
        public function ctrModificarClientes()
        {
            if(isset($_POST["editarCliente"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarCliente"]) &&
                 preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/', $_POST["editarApellidoc"]))
                {
                    

                    $tabla="clientes";
                    $datos=array(
                        "IDcliente" => $_POST["IDcliente"],
                        "nombrec"=> $_POST["editarCliente"], 
                        "apellidoc"=> $_POST["editarApellidoc"],
                        "telefono"=> $_POST["editarTelefono"],
                        "registro"=> $_POST["editarRegistro"],);
                    
                   
                    $respuesta = ModeloClientes::MdlModificarClientes($tabla,$datos);
                    //Comparacion de variables o campos de la tabla de usuario
                    if($respuesta == "ok")
                    {
                      
                        echo'<script>
                        Swal({
                            title: "Usuario Agregado!",
                            icon: "success",
                            draggable: true

                            }).then(function(result){

                                if(result.isConfirmed || result.value)
                                {
                                    window.location = "Clientes";
                                }
                            
                            });
                            
                        </script>';

                    }
                    else
                    {
                        echo '<script>
                        Swal.fire({
                            icon: "error",
                            title: "Oops...",
                            text: "Algo esta mal!"

                            }).then(function(result){

                                if(result.isConfirmed || result.value)
                                {
                                    window.location = "Clientes";
                                }
                            
                            });
                           
                        </script>';
                    }   
                }
            }
        }
        public function crtEliminarClientes()
        {
            if(isset($_POST["eliminarCliente"], $_POST["IDcliente"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                $tabla ="clientes";
                $datos = filter_var($_POST["IDcliente"], FILTER_VALIDATE_INT);
                if ($datos === false) {
                    http_response_code(422);
                    return;
                }
                $respuesta = ModeloClientes::MdlEliminarClientes($tabla, $datos);

                if($respuesta == "ok")
                {
                    
                    echo'<script>
                    Swal({
                        title: "Usuario Agregado!",
                        icon: "success",
                        draggable: true

                        }).then(function(result){

                            if(result.isConfirmed || result.value)
                            {
                                window.location = "Clientes";
                            }
                        
                        });
                    </script>';

                }
                else
                {
                    echo '<script>
                    Swal    ({
                        icon: "error",
                        title: "Oops...",
                        text: "Algo esta mal!"

                        }).then(function(result){

                            if(result.isConfirmed || result.value)
                            {
                                window.location = "Clientes";
                            }
                        
                        });
                    </script>';
                }
            }
            
              
        }

    }
        
?>
