<?php
// Consulta de garantía: busca las unidades vendidas y calcula su estado.
class GarantiaConsultaModelo extends Modelo
{
    // Mantenimientos con mano de obra gratis durante la garantía.
    public const MANTENIMIENTOS_GRATIS = 3;

    // Campo de búsqueda => columna. Solo se permiten estas.
    public const CRITERIOS = [
        'placa' => 'v.placa',
        'chasis' => 'v.chasis',
        'factura' => 've.numero_factura',
    ];

    private const SELECT = "SELECT v.id, v.placa, v.chasis,
                m.marca, m.modelo, m.propulsion, m.meses_garantia, m.cobertura,
                c.nombre AS cliente, c.documento, c.correo,
                ve.numero_factura, ve.fecha AS fecha_compra, ve.canal
         FROM vehiculo v
         JOIN moto m ON m.id = v.moto_id
         JOIN cliente c ON c.id = v.cliente_id
         JOIN venta ve ON ve.id = v.venta_id";

    // Busca una moto por placa, chasis o factura. Con $usuarioId solo entre las motos de ese cliente.
    public function buscar(string $criterio, string $valor, ?int $usuarioId = null): ?array
    {
        $columna = self::CRITERIOS[$criterio] ?? null;
        if ($columna === null || $valor === '') {
            return null;
        }
        $sql = self::SELECT . " WHERE $columna = ?";
        $parametros = [strtoupper($valor)];
        if ($usuarioId !== null) {
            $sql .= ' AND c.usuario_id = ?';
            $parametros[] = $usuarioId;
        }
        $filas = $this->consultar($sql . ' LIMIT 1', $parametros);
        return $filas[0] ?? null;
    }

    // Motos a nombre del cliente que tiene esta cuenta de usuario, la más reciente primero.
    public function motosDelUsuario(int $usuarioId): array
    {
        return $this->consultar(self::SELECT . ' WHERE c.usuario_id = ? ORDER BY ve.fecha DESC', [$usuarioId]);
    }

    public function mantenimientos(int $vehiculoId): array
    {
        return $this->consultar(
            'SELECT fecha, tipo, descripcion, costo_repuestos, costo_mano_obra
             FROM mantenimiento
             WHERE vehiculo_id = ?
             ORDER BY fecha DESC',
            [$vehiculoId]
        );
    }

    // Estado de la garantía a partir de la fecha de compra y los mantenimientos usados.
    public function estado(array $vehiculo, array $mantenimientos): array
    {
        $hoy = new DateTimeImmutable('today');
        $fin = (new DateTimeImmutable($vehiculo['fecha_compra']))->modify('+' . (int) $vehiculo['meses_garantia'] . ' months');
        $vigente = $hoy <= $fin;
        $usados = count(array_filter($mantenimientos, fn($m) => $m['tipo'] === 'garantia'));

        return [
            'vigente' => $vigente,
            'fecha_fin' => $fin->format('Y-m-d'),
            'dias_restantes' => $vigente ? (int) $hoy->diff($fin)->days : 0,
            'mantenimientos_usados' => $usados,
            'mantenimientos_restantes' => $vigente ? max(0, self::MANTENIMIENTOS_GRATIS - $usados) : 0,
        ];
    }
}
