<?php
// Solicitud de crédito (tabla solicitud_credito). Aquí vive el estado del negocio:
// BORRADOR -> EN_PROCESO_CREDITO -> APROBADA | RECHAZADA | ANULADA.
//
// No se modifica la tabla venta (es de otro módulo): la solicitud APUNTA a una venta
// existente y guarda su propio estado. Si el negocio exige que la venta quede 'PAGADA',
// eso se acuerda con el grupo de ventas.
class CreditosSolicitudModelo extends Modelo
{
    public const ESTADO_BORRADOR            = 'BORRADOR';
    public const ESTADO_EN_PROCESO_CREDITO  = 'EN_PROCESO_CREDITO';
    public const ESTADO_APROBADA            = 'APROBADA';
    public const ESTADO_RECHAZADA           = 'RECHAZADA';
    public const ESTADO_ANULADA             = 'ANULADA';

    // Estados en los que la solicitud todavía se puede trabajar (adjuntar, evaluar, aprobar).
    public const ESTADOS_EDITABLES = [self::ESTADO_BORRADOR, self::ESTADO_EN_PROCESO_CREDITO];

    private const SELECT = "SELECT s.id, s.venta_id, s.vehiculo_id, s.medio_pago, s.estado,
                s.monto_financiado, s.observacion, s.creado_en, s.actualizado_en,
                v.numero_factura, v.fecha AS fecha_venta, v.canal, v.total,
                c.id AS cliente_id, c.nombre AS cliente, c.documento,
                ve.placa, ve.chasis,
                m.marca, m.modelo,
                ur.nombre AS registrado_por, ua.nombre AS aprobado_por
         FROM solicitud_credito s
         JOIN venta v   ON v.id = s.venta_id
         JOIN cliente c ON c.id = v.cliente_id
         LEFT JOIN vehiculo ve ON ve.id = s.vehiculo_id
         LEFT JOIN moto m      ON m.id = ve.moto_id
         LEFT JOIN usuario ur  ON ur.id = s.registrado_por
         LEFT JOIN usuario ua  ON ua.id = s.aprobado_por";

    public function buscarPorId(int $id): ?array
    {
        $filas = $this->consultar(self::SELECT . ' WHERE s.id = ? LIMIT 1', [$id]);
        return $filas[0] ?? null;
    }

    // La solicitud de una venta (hay máximo una por venta: llave UNIQUE en la tabla).
    public function buscarPorVenta(int $ventaId): ?array
    {
        $filas = $this->consultar(self::SELECT . ' WHERE s.venta_id = ? LIMIT 1', [$ventaId]);
        return $filas[0] ?? null;
    }

    // Listado para el tablero. Con $estado se filtra; sin él salen todas.
    public function listar(?string $estado = null): array
    {
        if ($estado !== null && trim($estado) !== '') {
            return $this->consultar(
                self::SELECT . ' WHERE s.estado = ? ORDER BY s.creado_en DESC, s.id DESC',
                [strtoupper(trim($estado))]
            );
        }

        return $this->consultar(self::SELECT . ' ORDER BY s.creado_en DESC, s.id DESC');
    }

    // Métricas para las tarjetas del tablero (total, en revisión, aprobadas, rechazadas).
    public function resumen(): array
    {
        $resumen = [
            'total'      => 0,
            'en_proceso' => 0,
            'aprobadas'  => 0,
            'rechazadas' => 0,
            'monto'      => 0.0,
        ];

        $filas = $this->consultar(
            'SELECT estado, COUNT(*) AS total, COALESCE(SUM(monto_financiado), 0) AS monto
             FROM solicitud_credito
             GROUP BY estado'
        );

        foreach ($filas as $fila) {
            $cantidad = (int) $fila['total'];
            $resumen['total'] += $cantidad;

            switch ($fila['estado']) {
                case self::ESTADO_EN_PROCESO_CREDITO:
                case self::ESTADO_BORRADOR:
                    $resumen['en_proceso'] += $cantidad;
                    break;
                case self::ESTADO_APROBADA:
                    $resumen['aprobadas'] += $cantidad;
                    $resumen['monto'] += (float) $fila['monto'];
                    break;
                case self::ESTADO_RECHAZADA:
                    $resumen['rechazadas'] += $cantidad;
                    break;
            }
        }

        return $resumen;
    }

    // Crea la solicitud y devuelve su id. Nace en el estado indicado (por defecto EN_PROCESO_CREDITO,
    // porque el asesor la radica cuando el cliente ya decidió financiar).
    public function crear(
        int $ventaId,
        ?int $vehiculoId,
        string $medioPago,
        float $montoFinanciado,
        ?int $registradoPor = null,
        string $estado = self::ESTADO_EN_PROCESO_CREDITO,
        ?string $observacion = null
    ): int {
        $this->ejecutar(
            'INSERT INTO solicitud_credito
                (venta_id, vehiculo_id, medio_pago, estado, monto_financiado, observacion, registrado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $ventaId,
                $vehiculoId,
                strtoupper(trim($medioPago)),
                strtoupper(trim($estado)),
                round($montoFinanciado, 2),
                $observacion,
                $registradoPor,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    // Cambia el estado de la solicitud. Devuelve las filas afectadas (0 si no existía).
    public function cambiarEstado(int $id, string $estado, ?int $aprobadoPor = null): int
    {
        return $this->ejecutar(
            'UPDATE solicitud_credito
             SET estado = ?, aprobado_por = COALESCE(?, aprobado_por)
             WHERE id = ?',
            [strtoupper(trim($estado)), $aprobadoPor, $id]
        );
    }

    // Guarda quién aprobó y deja la solicitud en APROBADA.
    public function aprobar(int $id, int $aprobadoPor): int
    {
        return $this->cambiarEstado($id, self::ESTADO_APROBADA, $aprobadoPor);
    }

    // True si la solicitud todavía admite cambios.
    public static function esEditable(string $estado): bool
    {
        return in_array(strtoupper(trim($estado)), self::ESTADOS_EDITABLES, true);
    }
}
