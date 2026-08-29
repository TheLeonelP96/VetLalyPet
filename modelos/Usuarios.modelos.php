<?php 

    require_once "conexion.php";
    class ModeloUsuarios
    {
        static public function MdlMostrarUsuarios($tabla,$item, $valor): mixed
        {
            if($item != null)
            {
                $stmt=Conexion :: Conectar()-> prepare("SELECT * FROM $tabla WHERE $item=:$item AND habilitado = 1");
                $stmt->bindParam(":".$item, $valor, PDO:: PARAM_STR);
                $stmt->execute();

                return $stmt->fetch() ?: null;
            }
            else
            {
                $stmt=Conexion :: Conectar()-> prepare("SELECT * FROM $tabla ");
                $stmt->execute();
                return  $stmt->fetchAll();
            }
            $stmt ->close();

            $stmt= null;
        }
        static public function MdlActualizarEstado($tabla, $estado, $idUsuario) 
        {

            $stmt = Conexion::Conectar()->prepare("UPDATE $tabla SET estado = :estado WHERE IDusuario = :IDusuario");

            $stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":IDusuario", $idUsuario, PDO::PARAM_INT);

            if($stmt->execute()) {
                return "ok";
            } else {
                return "error";
            }

            $stmt->close();
            $stmt = null;
        }
        static public function MdlCrearUsuarios($tabla, $datos)
        {
            $stmt = Conexion::Conectar()->prepare("INSERT INTO $tabla(nombre, apellido, Usunombre, password, telefono, correo, turno, foto) VALUES (:nombre, :apellido, :Usunombre , :password, :telefono, :correo, :turno, :foto)");

            // Separar cada bindParam con punto y coma (;)
            $stmt->bindParam(":nombre", $datos["nombre"], PDO::PARAM_STR);
            $stmt->bindParam(":apellido", $datos["apellido"], PDO::PARAM_STR);
            $stmt->bindParam(":Usunombre", $datos["Usunombre"], PDO::PARAM_STR);
            $stmt->bindParam(":password", $datos["password"], PDO::PARAM_STR);
            $stmt->bindParam(":telefono", $datos["telefono"], PDO::PARAM_STR);
            $stmt->bindParam(":correo", $datos["correo"], PDO::PARAM_STR);
            $stmt->bindParam(":turno", $datos["turno"], PDO::PARAM_STR);
            $stmt->bindParam(":foto", $datos["foto"], PDO::PARAM_STR);

            if($stmt->execute()){
                return "ok"; // Cambiar 'esta bien' por 'ok' para que el controlador lo entienda
            } else {
                return "error";
            }

            $stmt = null;
        }
        static public function  MdlEditarUsuarios($tabla,$datos)
        {
            
            $stmt = Conexion::Conectar()->prepare("UPDATE $tabla SET 
            nombre = :nombre,
            apellido = :apellido,
            Usunombre = :Usunombre,
            password = :password,
            telefono = :telefono,
            correo = :correo,
            turno = :turno,
            foto = :foto
            WHERE IDusuario = :IDusuario");

            $stmt->bindParam(":IDusuario", $datos["IDusuario"], PDO::PARAM_INT);
            $stmt-> bindParam(":nombre",$datos["nombre"],PDO::PARAM_STR );
            $stmt-> bindParam(":apellido",$datos["apellido"],PDO::PARAM_STR );
            $stmt-> bindParam(":Usunombre",$datos["Usunombre"],PDO::PARAM_STR );
            $stmt-> bindParam(":password",$datos["password"],PDO::PARAM_STR );
            $stmt-> bindParam(":telefono",$datos["telefono"],PDO::PARAM_STR );
            $stmt-> bindParam(":correo",$datos["correo"],PDO::PARAM_STR )     ;  
            $stmt-> bindParam(":turno",$datos["turno"],PDO::PARAM_STR );
            $stmt-> bindParam(":foto",$datos["foto"],PDO::PARAM_STR );

            

            if($stmt->execute())
            {

                return 'ok';

            }
            else
            {
               
                return "error";
            }
            $stmt ->close();

            $stmt= null;
        }
        static public function MdlEliminarUsuarios($tabla, $datos)
        {
            $stmt = Conexion::Conectar()->prepare("DELETE FROM $tabla WHERE IDusuario= :IDusuario");

            $stmt->bindParam(":IDusuario", $datos, PDO::PARAM_INT);
            if($stmt->execute())
            {

                return 'ok';

            }
            else
            {
                
                return "error";
            }
            $stmt ->close();
        }
        
        // Cambiado para recibir la tabla, el estado (0 o 1) y el IDusuario
        static public function MdlActualizarUsuarios($tabla, $estado, $IDusuario)
        {
            // Cambiado: SET habilitado = :habilitado
            $stmt = Conexion::Conectar()->prepare("UPDATE $tabla SET habilitado = :habilitado WHERE IDusuario = :IDusuario");

            $stmt->bindParam(":habilitado", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":IDusuario", $IDusuario, PDO::PARAM_INT);

            if($stmt->execute())
            {
                return 'ok';
            }
            else
            {
                return "error";
            }

            $stmt->close();
            $stmt = null;
        }
    }
    






?>
