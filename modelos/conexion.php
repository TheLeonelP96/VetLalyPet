<?php
// Abre la conexión PDO única hacia la base de datos vetlalypets.
    class Conexion  
    {       
        static public function Conectar()
        {
            $host = getenv('LALYPETS_DB_HOST') ?: 'localhost';
            $base = getenv('LALYPETS_DB_NAME') ?: 'vetlalypets';
            $usuario = getenv('LALYPETS_DB_USER') ?: 'root';
            $password = getenv('LALYPETS_DB_PASSWORD');
            $password = $password === false ? '' : $password;
            $link = new PDO(
                "mysql:host={$host};dbname={$base};charset=utf8mb4",
                $usuario,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $link;
        }
    }

?>
