<?php

require_once "conexion.php";
 class ModeloClientes
    {
        static public function MdlMostrarClientes($tabla,$item, $valor): mixed
        {
            if($item!=null)
            {
                $stmt=Conexion :: Conectar()-> prepare("SELECT * FROM $tabla WHERE $item=:$item ");
                $stmt->bindParam(":".$item, $valor, PDO:: PARAM_STR);
                $stmt->execute();

                return $stmt->fetch();
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
        static public function  MdlCrearClientes($tabla,$datos)
        {
           
            $stmt=Conexion :: Conectar()-> prepare("INSERT INTO $tabla(nombrec, apellidoc, telefono, registro)
             VALUES (:nombrec, :apellidoc, :telefono, :registro)");

            $stmt-> bindParam(":nombrec",$datos["nombrec"],PDO::PARAM_STR );
            $stmt-> bindParam(":apellidoc",$datos["apellidoc"],PDO::PARAM_STR );
            $stmt-> bindParam(":telefono",$datos["telefono"],PDO::PARAM_STR );
            $stmt-> bindParam(":registro",$datos["registro"],PDO::PARAM_STR );
           

            if($stmt->execute())
            {
                
                return 'ok';
            }
            else
            {
               return 'error';
            }
            $stmt ->close();

            $stmt= null;
        }
        static public function  MdlModificarClientes($tabla,$datos)
        {
           
            $stmt=Conexion :: Conectar()-> prepare("UPDATE $tabla SET 
            nombrec= :nombrec,
            apellidoc= :apellidoc,
            telefono= :telefono,
            registro= :registro
            WHERE IDcliente = :IDcliente ");

            $stmt-> bindParam(":IDcliente",$datos["IDcliente"],PDO::PARAM_INT );
            $stmt-> bindParam(":nombrec",$datos["nombrec"],PDO::PARAM_STR );
            $stmt-> bindParam(":apellidoc",$datos["apellidoc"],PDO::PARAM_STR );
            $stmt-> bindParam(":telefono",$datos["telefono"],PDO::PARAM_STR );
            $stmt-> bindParam(":registro",$datos["registro"],PDO::PARAM_STR );
           
            
           

            if($stmt->execute())
            {
                
                return 'ok';
                  
            }
            else
            {
               return 'error';
            }
            $stmt ->close();

            $stmt= null;
        }
         static public function MdlEliminarClientes($tabla, $datos)
        {
            $stmt = Conexion::Conectar()->prepare("DELETE FROM $tabla WHERE IDcliente = :IDcliente ");

            $stmt->bindParam(":IDcliente", $datos, PDO::PARAM_INT);
            if($stmt->execute())
            {

                return 'ok';

            }
            else
            {
                
                return "error";
            }
            $stmt =null;
        }

    }
  
            
?>