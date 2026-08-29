<?php
    
    class ControladorMascotas
    {
        public function ctrCrearMascotas()
        {
            if(isset($_POST["nuevoMascota"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["nuevoMascota"]))
               
                {
                    $ruta = "";

                    if(isset($_FILES["nuevaFoto"]["tmp_name"])){
                        $tipoImagen = Seguridad::tipoImagenSubida($_FILES["nuevaFoto"]);

                        list($ancho, $alto) = getimagesize($_FILES["nuevaFoto"]["tmp_name"]);

                        $nuevoAncho = 500;
                        $nuevoAlto = 500;

                      

                        $directorio = "vistas/js/img/usuarios/".$_POST["nuevoMascota"];

                        // AHORA (Solución limpia):
                        if(!file_exists($directorio)){
                            mkdir($directorio, 0755, true);
                        }

                      

                        if($tipoImagen == "image/jpeg"){

                   
                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["nuevoMascota"]."/".$aleatorio.".jpg";

                            $origen = imagecreatefromjpeg($_FILES["nuevaFoto"]["tmp_name"]);                        

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagejpeg($destino, $ruta);

                        }

                        if($tipoImagen == "image/png"){

                           

                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["nuevoMascota"]."/".$aleatorio.".png";

                            $origen = imagecreatefrompng($_FILES["nuevaFoto"]["tmp_name"]);                    

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagepng($destino, $ruta);
                        }
                    }
                    $tabla="mascotas";
                    $datos=array("nombrem"=> $_POST["nuevoMascota"], 
                        "especie"=> $_POST["nuevoEspecie"],
                        "raza"=> $_POST["nuevoRaza"],
                        "fecha_Registro"=> $_POST["nuevaFecha"],
                        "edad"=> $_POST["nuevoEdad"],
                        "sexo"=> $_POST["nuevoSexo"],
                        "peso"=> $_POST["nuevoPeso"],
                        "fotoM"=> $ruta, 
                        "IDcliente"     => $_POST["nuevoCliente"] // <-- AQUÍ LLEGA LA CLAVE FORÁNEA
                        );
                    
                   
                    $respuesta = ModeloMascotas::MdlCrearMascotas ($tabla,$datos);
                    //Comparacion de variables o campos de la tabla de usuario
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
                        Swal.fire({
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
        }
        static public function crtMostrarMascotas($item, $valor)
        {
            $tabla="mascotas";
            $respuesta = ModeloMascotas :: MdlMostrarMascotas($tabla,$item, $valor);
            return $respuesta;
        }
        public function ctrModificarMascotas()
        {
            if(isset($_POST["editarMascota"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                if(preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ ]+$/',$_POST["editarMascota"]))
                {
                    $mascotaActual = ModeloMascotas::MdlMostrarMascotas("mascotas", "IDmascota", $_POST["IDmascota"]);
                    if (!is_array($mascotaActual)) {
                        http_response_code(404);
                        return;
                    }
                    $ruta = $mascotaActual["fotoM"];

                    if(isset($_FILES["editarFoto"]["tmp_name"]) && !empty($_FILES["editarFoto"]["tmp_name"])){
                        $tipoImagen = Seguridad::tipoImagenSubida($_FILES["editarFoto"]);


                       
                        list($ancho, $alto) = getimagesize($_FILES["editarFoto"]["tmp_name"]);

                        $nuevoAncho = 500;
                        $nuevoAlto = 500;

                      

                        $directorio = "vistas/js/img/usuarios/".$_POST["editarMascota"];

                        if(!file_exists($directorio)){
                            mkdir($directorio, 0755);
                        }

                        Seguridad::eliminarImagenGuardada($mascotaActual["fotoM"]);
                                
                      

                        if($tipoImagen == "image/jpeg"){

                   
                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["editarMascota"]."/".$aleatorio.".jpg";

                            $origen = imagecreatefromjpeg($_FILES["editarFoto"]["tmp_name"]);                        

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagejpeg($destino, $ruta);

                        }

                        if($tipoImagen == "image/png"){

                           

                            $aleatorio = mt_rand(100,999);

                            $ruta = "vistas/js/img/usuarios/".$_POST["editarMascota"]."/".$aleatorio.".png";

                            $origen = imagecreatefrompng($_FILES["editarFoto"]["tmp_name"]);                    

                            $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                            imagecopyresized($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

                            imagepng($destino, $ruta);
                        }
                    }
                    $tabla="mascotas";
                    $datos=array(
                        "IDmascota" => $_POST["IDmascota"],
                        "nombrem"=> $_POST["editarMascota"], 
                        "especie"=> $_POST["editarEspecie"],
                        "raza"=> $_POST["editarRaza"],
                        "fecha_Registro"=> $_POST["editarFecha"],
                        "edad"=> $_POST["editarEdad"],
                        "sexo"=> $_POST["editarSexo"],
                        "peso"=> $_POST["editarPeso"],
                        "IDcliente"     => $_POST["editarCliente"], // <-- AQUÍ LLEGA LA CLAVE FORÁNEA
                        "fotoM"=> $ruta);
                    
                   
                    $respuesta = ModeloMascotas::MdlModificarMascotas($tabla,$datos);
                    //Comparacion de variables o campos de la tabla de usuario
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
                                    window.location = "Mascotas";
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

                                if(result.isConfirmed)
                                {
                                    window.location = "Mascotas";
                                }
                            
                            });
                           
                        </script>';
                    }   
                }
            }
        }
        public function crtEliminarMascotas()
        {
            if(isset($_POST["eliminarMascotaDefinitiva"], $_POST["IDmascota"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                $tabla ="mascotas";
                $datos = filter_var($_POST["IDmascota"], FILTER_VALIDATE_INT);
                if ($datos === false) {
                    http_response_code(422);
                    return;
                }
                $respuesta = ModeloMascotas::MdlEliminarMascotas($tabla, $datos);

                if($respuesta == "ok")
                {
                    
                    echo'<script>
                    Swal({
                        title: "Mascota eliminada!",
                        icon: "success",
                        draggable: true

                        }).then(function(result){

                            if(result.isConfirmed)
                            {
                                window.location = "Mascotas";
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
                                window.location = "Mascotas";
                            }
                        
                        });
                    </script>';
                }
            }
            
            
        }
         public function crtDesHabilitarMascotas()
        {
            // Corregido: isset para ambos
            if(isset($_POST["IDmascota"], $_POST["estadoMascota"]))
            {
                Seguridad::exigirAutenticacion();
                Seguridad::exigirCsrf();
                $tabla = "mascotas";
                $IDusuario = filter_var($_POST["IDmascota"], FILTER_VALIDATE_INT);
                $estado = filter_var($_POST["estadoMascota"], FILTER_VALIDATE_INT);
                if ($IDusuario === false || !in_array($estado, [0, 1], true)) {
                    http_response_code(422);
                    return;
                }

                // Pasamos las variables correctas
                $respuesta = ModeloMascotas::MdlActualizarMascotas($tabla, $estado, $IDusuario);

                if($respuesta == "ok")
                {
                    echo'<script>
                    Swal.fire({ // Corregido a Swal.fire
                        title: "¡Estado Actualizado!",
                        icon: "success"
                    }).then(function(result){
                        window.location = "Mascotas";
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
                        window.location = "Mascotas";
                    });
                    </script>';
                }
            }
        }   
    }
        
?>
