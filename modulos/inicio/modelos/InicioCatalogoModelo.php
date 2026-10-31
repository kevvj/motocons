<?php
// Motos que se muestran en la portada pública (tabla moto, común a todos los módulos).
class InicioCatalogoModelo extends Modelo
{
    public function destacadas(int $cantidad = 6): array
    {
        return $this->consultar(
            'SELECT marca, modelo, propulsion, precio, meses_garantia
             FROM moto
             ORDER BY precio
             LIMIT ' . (int) $cantidad
        );
    }
}
