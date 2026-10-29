<?php
// Base de los modelos. Usar siempre consultas preparadas (nunca concatenar datos del usuario).
abstract class Modelo
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::conexion();
    }

    protected function consultar(string $sql, array $parametros = []): array
    {
        $sentencia = $this->db->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->fetchAll();
    }

    protected function ejecutar(string $sql, array $parametros = []): int
    {
        $sentencia = $this->db->prepare($sql);
        $sentencia->execute($parametros);
        return $sentencia->rowCount();
    }
}
