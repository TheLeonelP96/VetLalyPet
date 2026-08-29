<?php


    class ControladorUsuarios
    {
        public function ctrIngresarUsuarios()
        {
            //Programar siguiente clase
            if(isset($_POST["imgUsuario"]))
            {
                Seguridad::exigirCsrf();
                if(preg_match('/^[a-zA-Z0-9 ]+$/', $_POST["imgUsuario"])
                    && is_string($_POST["imgPassword"])
                    && $_POST["imgPassword"] !== ''
                    && strlen($_POST["imgPassword"]) <= 200)
                {
                    $tabla="usuarios";
                    $item="Usunombre";
                    $valor = $_POST["imgUsuario"];
                    $respuesta = ModeloUsuarios :: MdlMostrarUsuarios($tabla,$item, $valor);
                    //Comparacion de variables o campos de la tabla de usuario
                    if(is_array($respuesta) && password_verify($_POST["imgPassword"], $respuesta["password"]))
                    {
                        session_regenerate_id(true);
                        $_SESSION['IniciarSesion']= "ok";

                        $_SESSION['IDrol']= $respuesta["IDrol"];    
                        $_SESSION['IDusuario']= $respuesta["IDusuario"];
                        $_SESSION['nombre']= $respuesta["nombre"];
                        $_SESSION['apellido']= $respuesta["apellido"];
                        $_SESSION['Usunombre']= $respuesta["Usunombre"];
                        $_SESSION['telefono']= $respuesta["telefono"];
                        $_SESSION['correo']= $respuesta["correo"];
                        $_SESSION['turno']= $respuesta["turno"];
                        $_SESSION['estado']= 1;
                        $_SESSION['habilitado']= $respuesta["habilitado"]; 
                        
                        $_SESSION['foto']= $respuesta["foto"];

                        ModeloUsuarios::MdlActualizarEstado($tabla, 1, $respuesta["IDusuario"]);
                        echo'<script>window.location="Inicio";</script>';

                    }
                    else
                    {
                        echo '<br><div class="alert alert-danger">Error al entrar al sistema, usuario o contraseña incorrectos</div> ';
                       
                    }
                   
                }
            }
        }
        public function ctrCrearUsuarios()
        {
            if(isset($_POST["nuevoNombre"]))
            {
                Seguridad::exigirRol([1]);
                Seguridad::exigirCsrf();
                if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["nuevoNombre"])&& 
                preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["nuevoApellido"])&& 
                preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["nuevoUsuario"]) &&
                strlen($_POST["nuevoPassword"]) >= 8 && strlen($_POST["nuevoPassword"]) <= 72)
                {
                    $ruta = "";

                    if((isset($_FILES["nuevaFoto"]["tmp_name"]) && !empty($_FILES["nuevaFoto"]["tmp_name"])))
                    {
                        $tipoImagen = Seguridad::tipoImagenSubida($_FILES["nuevaFoto"]);

                        list($ancho, $alto) = getimagesize($_FILES["nuevaFoto"]["tmp_name"]);

                        $nuevoAncho = 500;
                        $nuevoAlto = 500;

                        

                        $directorio = "vistas/js/img/usuarios/".trim($_POST["nuevoNombre"]);

                        mkdir($directorio, 0755);


                        if($tipoImagen == "image/jpeg"){

          
                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["nuevoNombre"]."/".$aleatorio.".jpg";

                            $origen = imagecreatefromjpeg($_FILES["nuevaFoto"]["tmp_name"]);                        

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagejpeg($destino, $ruta);

                        }

                        if($tipoImagen == "image/png"){

                          

                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["nuevoNombre"]."/".$aleatorio.".png";

                            $origen = imagecreatefrompng($_FILES["nuevaFoto"]["tmp_name"]);                    

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagepng($destino, $ruta);
                        }
                    }
                    $tabla="usuarios";
                    $datos=array(
                        "nombre"=> $_POST["nuevoNombre"],
                        "apellido"=> $_POST["nuevoApellido"],
                        "Usunombre"=> $_POST["nuevoUsuario"],
                        "telefono"=> $_POST["nuevoTelefono"],
                        "correo"=> $_POST["nuevoEmail"],
                        "password"=> password_hash($_POST["nuevoPassword"], PASSWORD_DEFAULT),
                        "turno"=> $_POST["nuevoTurno"],
                        "foto"=> $ruta);
                    
                   
                    $respuesta = ModeloUsuarios::MdlCrearUsuarios ($tabla,$datos);
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
                                    window.location = "Usuarios";
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
                                    window.location = "Usuarios";
                                }
                            
                            });
                           
                        </script>';
                    }   
                }
            }
        }
        static public function ctrMostrarUsuarios($item, $valor)
        {
            $tabla="usuarios";
            $respuesta = ModeloUsuarios :: MdlMostrarUsuarios($tabla,$item, $valor);
            return $respuesta;
        }
         public function ctrEditarUsuarios()
        {
            if(isset($_POST["editarNombre"]))
            {
                Seguridad::exigirRol([1]);
                Seguridad::exigirCsrf();
                //var_dump($_POST);
                if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["editarNombre"])&& 
                preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["editarApellido"])&& 
                preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["editarUsuario"]) &&
                (empty($_POST["editarPassword"]) || (strlen($_POST["editarPassword"]) >= 8 && strlen($_POST["editarPassword"]) <= 72)))
                {
                    $usuarioActual = ModeloUsuarios::MdlMostrarUsuarios("usuarios", "IDusuario", $_POST["IDusuario"]);
                    if (!is_array($usuarioActual)) {
                        http_response_code(404);
                        return;
                    }
                    $ruta = $usuarioActual["foto"];
                    if((isset($_FILES["editarFoto"]["tmp_name"]) && !empty($_FILES["editarFoto"]["tmp_name"])))
                    {
                        $tipoImagen = Seguridad::tipoImagenSubida($_FILES["editarFoto"]);
                        

                        list($ancho, $alto) = getimagesize($_FILES["editarFoto"]["tmp_name"]);

                        $nuevoAncho = 500;
                        $nuevoAlto = 500;

                    
                        $directorio = "vistas/js/img/usuarios/".$_POST["editarNombre"];

                        // Crear carpeta SOLO si no existe
                        if(!file_exists($directorio)){
                            mkdir($directorio, 0755);
                        }

                        Seguridad::eliminarImagenGuardada($usuarioActual["foto"]);
                                            

                        if($tipoImagen == "image/jpeg"){

                
                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["editarNombre"]."/".$aleatorio.".jpg";

                            $origen = imagecreatefromjpeg($_FILES["editarFoto"]["tmp_name"]);                        

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagejpeg($destino, $ruta);

                        }

                        if($tipoImagen == "image/png"){

                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["editarNombre"]."/".$aleatorio.".png";

                            $origen = imagecreatefrompng($_FILES["editarFoto"]["tmp_name"]);                    

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagepng($destino, $ruta);
                        }
                        
                    }
                    
                    $tabla="usuarios";
                    $password = empty($_POST["editarPassword"])
                        ? $usuarioActual["password"]
                        : password_hash($_POST["editarPassword"], PASSWORD_DEFAULT);

                    $datos=array(
                        "IDusuario" => $_POST["IDusuario"], 
                        "nombre"=> $_POST["editarNombre"],
                        "apellido"=> $_POST["editarApellido"],
                        "Usunombre"=> $_POST["editarUsuario"],
                        "telefono"=> $_POST["editarTelefono"],
                        "correo"=> $_POST["editarEmail"],
                        "password"=> $password,
                        "turno"=> $_POST["editarTurno"],
                        "foto"=> $ruta);
                    
                
                    $respuesta = ModeloUsuarios::MdlEditarUsuarios ($tabla,$datos);
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
                                    window.location = "Usuarios";
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
                                    window.location = "Usuarios ";
                                }
                            
                            });
                           
                        </script>';
                    }   
                   
                }
            }
        }
        public function crtEliminarUsuarios()
        {
            if(isset($_POST["eliminarUsuarioDefinitivo"], $_POST["IDusuario"]))
            {
                Seguridad::exigirRol([1]);
                Seguridad::exigirCsrf();
                $tabla ="usuarios";
                $datos = filter_var($_POST["IDusuario"], FILTER_VALIDATE_INT);
                if ($datos === false) {
                    http_response_code(422);
                    return;
                }
                $respuesta = ModeloUsuarios::MdlEliminarUsuarios($tabla, $datos);

                if($respuesta == "ok")
                {
                    
                    echo'<script>
                    Swal({
                        title: "Usuario Agregado!",
                        icon: "success",
                        draggable: true

                        }).then(function(result){

                            if(result.isConfirmed)
                            {
                                window.location = "Usuarios";
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

                            if(result.isConfirmed)
                            {
                                window.location = "Usuarios";
                            }
                        
                        });
                    </script>';
                }
            }
            
              
        }
        public function crtDesHabilitarUsuarios()
        {
            // Corregido: isset para ambos
            if(isset($_POST["IDusuario"], $_POST["estadoUsuario"]))
            {
                Seguridad::exigirRol([1]);
                Seguridad::exigirCsrf();
                $tabla = "usuarios";
                $IDusuario = filter_var($_POST["IDusuario"], FILTER_VALIDATE_INT);
                $estado = filter_var($_POST["estadoUsuario"], FILTER_VALIDATE_INT);
                if ($IDusuario === false || !in_array($estado, [0, 1], true)) {
                    http_response_code(422);
                    return;
                }

                // Pasamos las variables correctas
                $respuesta = ModeloUsuarios::MdlActualizarUsuarios($tabla, $estado, $IDusuario);

                if($respuesta == "ok")
                {
                    echo'<script>
                    Swal.fire({ // Corregido a Swal.fire
                        title: "¡Estado Actualizado!",
                        icon: "success"
                    }).then(function(result){
                        window.location = "Usuarios";
                    });
                    </script>';
                }
                else
                {
                    echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Algo está mal!"
                    }).then(function(result){
                        window.location = "Usuarios";
                    });
                    </script>';
                }
            }
        }   
    }



?>
