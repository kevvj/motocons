<?php
// Clientes del punto de venta. Usa la tabla común cliente (módulo garantia).
class VentasPresencialesClienteModelo extends Modelo
{
    public function buscarPorDocumento(string $documento): ?array
    {
        $filas = $this->consultar(
            'SELECT id, nombre, documento, correo, telefono FROM cliente WHERE documento = ? LIMIT 1',
            [$documento]
        );
        return $filas[0] ?? null;
    }

    // Devuelve el id del cliente con ese documento; si no existe lo crea.
    public function obtenerOCrear(array $datos): int
    {
        $cliente = $this->buscarPorDocumento($datos['documento']);
        if ($cliente !== null) {
            return (int) $cliente['id'];
        }
        if ($datos['nombre'] === '') {
            throw new InvalidArgumentException('El cliente es nuevo: escribe su nombre.');
        }
        $this->ejecutar(
            'INSERT INTO cliente (nombre, documento, correo, telefono) VALUES (?, ?, ?, ?)',
            [$datos['nombre'], $datos['documento'], $datos['correo'] ?: null, $datos['telefono'] ?: null]
        );
        return (int) $this->db->lastInsertId();
    }
}
