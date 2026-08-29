<?php

require_once "conexion.php";
  class ModeloMascotas
    {
        static public function MdlMostrarMascotas($tabla,$item, $valor): mixed
        {
            if($item!=null)
            {
               
                $stmt = Conexion::conectar()->prepare("SELECT m.*, c.nombrec FROM $tabla m INNER JOIN clientes c ON m.IDcliente = c.IDcliente WHERE m.$item = :$item");
                $stmt->bindParam(":".$item, $valor, PDO::PARAM_STR);
                $stmt->execute();
                
                return $stmt->fetch();
            }
            else
            {
                $stmt = Conexion::conectar()->prepare("SELECT m.*, c.nombrec FROM $tabla m INNER JOIN clientes c ON m.IDcliente = c.IDcliente");
                $stmt->execute();
                
                return $stmt->fetchAll();
            }
            $stmt ->close();

            $stmt= null;
        }
      
        static public function  MdlCrearMascotas($tabla,$datos)
        {
           
            $stmt=Conexion :: Conectar()-> prepare("INSERT INTO $tabla(nombrem, especie, raza, fecha_Registro, edad, sexo, peso, fotoM, IDcliente)
             VALUES (:nombrem, :especie, :raza, :fecha_Registro ,:edad, :sexo, :peso, :fotoM, :IDcliente)");

            $stmt-> bindParam(":nombrem",$datos["nombrem"],PDO::PARAM_STR );
            $stmt-> bindParam(":especie",$datos["especie"],PDO::PARAM_STR );
            $stmt-> bindParam(":raza",$datos["raza"],PDO::PARAM_STR );
            $stmt-> bindParam(":fecha_Registro",$datos["fecha_Registro"],PDO::PARAM_STR );
            $stmt-> bindParam(":edad",$datos["edad"],PDO::PARAM_STR );
            $stmt-> bindParam(":sexo",$datos["sexo"],PDO::PARAM_STR );
            $stmt-> bindParam(":peso",$datos["peso"],PDO::PARAM_STR );
            $stmt-> bindParam(":fotoM",$datos["fotoM"],PDO::PARAM_STR );
            $stmt->bindParam(":IDcliente",     $datos["IDcliente"],     PDO::PARAM_INT); // Bind de la Foreign Key

            
           

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
        static public function  MdlModificarMascotas($tabla,$datos)
        {
           
            $stmt=Conexion :: Conectar()-> prepare("UPDATE $tabla SET 
            nombrem= :nombrem,
            especie= :especie,
            raza= :raza,
            fecha_Registro= :fecha_Registro,
            edad= :edad,
            sexo= :sexo,
            peso= :peso,
            fotoM=:fotoM,
            IDcliente= :IDcliente
            WHERE IDmascota= :IDmascota");

            $stmt-> bindParam(":IDmascota",$datos["IDmascota"],PDO::PARAM_INT );
            $stmt-> bindParam(":nombrem",$datos["nombrem"],PDO::PARAM_STR );
            $stmt-> bindParam(":especie",$datos["especie"],PDO::PARAM_STR );
            $stmt-> bindParam(":raza",$datos["raza"],PDO::PARAM_STR );
            $stmt-> bindParam(":fecha_Registro",$datos["fecha_Registro"],PDO::PARAM_STR );
            $stmt-> bindParam(":edad",$datos["edad"],PDO::PARAM_STR );
            $stmt-> bindParam(":sexo",$datos["sexo"],PDO::PARAM_STR );
            $stmt-> bindParam(":peso",$datos["peso"],PDO::PARAM_STR );
            $stmt-> bindParam(":fotoM",$datos["fotoM"],PDO::PARAM_STR );
            $stmt->bindParam(":IDcliente",     $datos["IDcliente"],     PDO::PARAM_INT); 

            
           

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
        static public function MdlEliminarMascotas($tabla, $datos)
        {
            $stmt = Conexion::Conectar()->prepare("DELETE FROM $tabla WHERE IDmascota= :IDmascota");

            $stmt->bindParam(":IDmascota", $datos, PDO::PARAM_INT);
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
        static public function MdlActualizarMascotas($tabla, $estado, $IDusuario)
        {
            // Cambiado: SET habilitado = :habilitado
            $stmt = Conexion::Conectar()->prepare("UPDATE $tabla SET habilitadoM = :habilitadoM WHERE IDmascota = :IDmascota");

            $stmt->bindParam(":habilitadoM", $estado, PDO::PARAM_INT);
            $stmt->bindParam(":IDmascota", $IDusuario, PDO::PARAM_INT);

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

        static public function mdlMostrarMascotasPorCliente($tabla, $item, $valor)
        {

            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");
            $stmt->bindParam(":".$item, $valor, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(); // Devuelve la lista completa de mascotas del cliente

            $stmt = null;
        }

    }
    
        
?>