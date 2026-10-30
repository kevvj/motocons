<?php
// Consultas de SOLO LECTURA sobre las tablas del negocio que usa el crédito:
// venta y vehiculo (grupo de garantía/ventas) y cliente (gestión de terceros).
//
// Regla del módulo: créditos NO escribe en estas tablas. Solo las lee para armar la
// solicitud y para mostrar datos. Si un día se necesita cambiar algo allí, se acuerda
// con el grupo dueño de esa tabla.
class CreditosRecursoModelo extends Modelo
{
    // Ventas que todavía no tienen solicitud de crédito, para el desplegable de radicación.
    // Al filtrar por cliente se puede radicar sin buscar la factura a mano.
    public function ventasSinSolicitud(?int $clienteId = null): array
    {
        $sql = "SELECT v.id, v.numero_factura, v.fecha, v.canal, v.total,
                       c.nombre AS cliente, c.documento,
                       ve.id AS vehiculo_id, ve.placa, ve.chasis,
                       m.marca, m.modelo
                FROM venta v
                JOIN cliente c ON c.id = v.cliente_id
                LEFT JOIN vehiculo ve ON ve.venta_id = v.id
                LEFT JOIN moto m ON m.id = ve.moto_id
                LEFT JOIN solicitud_credito s ON s.venta_id = v.id
                WHERE s.id IS NULL";
        $parametros = [];

        if ($clienteId !== null) {
            $sql .= ' AND v.cliente_id = ?';
            $parametros[] = $clienteId;
        }

        return $this->consultar($sql . ' ORDER BY v.fecha DESC, v.id DESC LIMIT 100', $parametros);
    }

    public function buscarVenta(int $ventaId): ?array
    {
        $filas = $this->consultar(
            'SELECT v.id, v.numero_factura, v.fecha, v.canal, v.total,
                    v.cliente_id, c.nombre AS cliente, c.documento, c.telefono, c.correo
             FROM venta v
             JOIN cliente c ON c.id = v.cliente_id
             WHERE v.id = ?
             LIMIT 1',
            [$ventaId]
        );

        return $filas[0] ?? null;
    }

    // Clientes con su número de facturas, para el buscador del formulario.
    public function listarClientes(): array
    {
        return $this->consultar(
            'SELECT c.id, c.nombre, c.documento, c.telefono, c.correo,
                    COUNT(v.id) AS total_ventas
             FROM cliente c
             LEFT JOIN venta v ON v.cliente_id = c.id
             GROUP BY c.id, c.nombre, c.documento, c.telefono, c.correo
             ORDER BY c.nombre'
        );
    }

    // Unidades asociadas a una venta. La reserva de la unidad se guarda en solicitud_credito,
    // no en vehiculo (esa tabla es de otro grupo).
    public function vehiculosDeVenta(int $ventaId): array
    {
        return $this->consultar(
            'SELECT ve.id, ve.placa, ve.chasis, m.marca, m.modelo, m.precio
             FROM vehiculo ve
             JOIN moto m ON m.id = ve.moto_id
             WHERE ve.venta_id = ?
             ORDER BY ve.id',
            [$ventaId]
        );
    }

    // True si el vehículo ya está comprometido en otra solicitud que sigue viva.
    // Es el chequeo para no reservar dos veces la misma unidad.
    public function vehiculoReservado(int $vehiculoId, ?int $exceptoSolicitudId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM solicitud_credito
                WHERE vehiculo_id = ?
                  AND estado IN ('BORRADOR', 'EN_PROCESO_CREDITO', 'APROBADA')";
        $parametros = [$vehiculoId];

        if ($exceptoSolicitudId !== null) {
            $sql .= ' AND id <> ?';
            $parametros[] = $exceptoSolicitudId;
        }

        $filas = $this->consultar($sql, $parametros);

        return ((int) ($filas[0]['total'] ?? 0)) > 0;
    }
}
