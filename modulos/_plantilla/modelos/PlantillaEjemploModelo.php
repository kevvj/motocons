<?php
// Nombre de clase con el prefijo del módulo para no chocar con otros grupos.
class PlantillaEjemploModelo extends Modelo
{
    public function todos(): array
    {
        return $this->consultar('SELECT 1 AS id');
    }
}
