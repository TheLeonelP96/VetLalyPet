<?php
    $tabla = "usuarios";

    // 2. Llamamos al mismo modelo que creamos antes para cambiar el TINYINT a 0 
    // Usamos el IDusuario que guardamos en la sesión antes de borrarla
    ModeloUsuarios::MdlActualizarEstado($tabla, 0, $_SESSION["IDusuario"]);

    // 3. Ahora sí, limpiamos las variables y destruimos la sesión de forma segura
    $_SESSION["estado"]=0;
    session_destroy();
  

    echo'<script>
            window.location="login";
        </script>';
        

?>