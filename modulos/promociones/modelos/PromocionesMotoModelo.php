<?php
require_once __DIR__ . '/PromocionesDescuento.php';
require_once __DIR__ . '/PromocionesPromocionModelo.php';

// Catálogo de motos (tabla común) con precio y descuento aplicado.
class PromocionesMotoModelo extends Modelo
{
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT id, marca, modelo, gama, propulsion, precio FROM moto WHERE 1 = 1';
        $params = [];

        if (!empty($filtros['marca'])) {
            $sql .= ' AND marca = ?';
            $params[] = $filtros['marca'];
        }
        if (!empty($filtros['gama'])) {
            $sql .= ' AND gama = ?';
            $params[] = $filtros['gama'];
        }
        if (!empty($filtros['propulsion'])) {
            $sql .= ' AND propulsion = ?';
            $params[] = $filtros['propulsion'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= ' AND (marca LIKE ? OR modelo LIKE ?)';
            $params[] = '%' . $filtros['busqueda'] . '%';
            $params[] = '%' . $filtros['busqueda'] . '%';
        }

        $sql .= ' ORDER BY marca, modelo';

        $motos = [];
        foreach ($this->consultar($sql, $params) as $fila) {
            $motos[] = [
                'id'         => (int) $fila['id'],
                'marca'      => $fila['marca'],
                'modelo'     => $fila['modelo'],
                'gama'       => $fila['gama'],
                'propulsion' => $fila['propulsion'],
                'precio'     => (float) $fila['precio'],
            ];
        }

        return $motos;
    }

    public function obtener(int $id): ?array
    {
        $filas = $this->consultar(
            'SELECT id, marca, modelo, gama, propulsion, precio FROM moto WHERE id = ?',
            [$id]
        );

        if (!$filas) {
            return null;
        }

        $fila = $filas[0];

        return [
            'id'         => (int) $fila['id'],
            'marca'      => $fila['marca'],
            'modelo'     => $fila['modelo'],
            'gama'       => $fila['gama'],
            'propulsion' => $fila['propulsion'],
            'precio'     => (float) $fila['precio'],
        ];
    }

    public function catalogoConDescuento(array $filtros = [], ?string $fecha = null): array
    {
        $promociones = (new PromocionesPromocionModelo())->vigentes($fecha);
        $catalogo = [];

        foreach ($this->listar($filtros) as $moto) {
            $catalogo[] = array_merge(
                $moto,
                PromocionesDescuento::precioFinal($moto, $promociones, $fecha)
            );
        }

        return $catalogo;
    }

    public function marcas(): array
    {
        return array_column(
            $this->consultar('SELECT DISTINCT marca FROM moto ORDER BY marca'),
            'marca'
        );
    }

    public function gamas(): array
    {
        return array_column(
            $this->consultar('SELECT DISTINCT gama FROM moto ORDER BY gama'),
            'gama'
        );
    }

    public function propulsiones(): array
    {
        return array_column(
            $this->consultar('SELECT DISTINCT propulsion FROM moto ORDER BY propulsion'),
            'propulsion'
        );
    }
}
