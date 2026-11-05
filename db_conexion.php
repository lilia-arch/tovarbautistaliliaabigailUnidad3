<?php

class Conexion {

    private $host = "localhost";
    private $dbname = "coffeeapp_db";
    private $usuario = "root";
    private $contrasena = "";

    public function getConexion() {
        try {
            $conexion = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->usuario,
                $this->contrasena
            );

            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conexion;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }
}