<?php
// Checklist de una solicitud (tabla solicitud_requisito): qué requisito del catálogo
// se entregó, en qué estado está y quién lo revisó.
class CreditosSolicitudRequisitoModelo extends Modelo
{
    private const SELECT = "SELECT sr.solicitud_id, sr.requisito_id, sr.estado, sr.soporte_adjunto,
                sr.validado_por, sr.validado_en,
                rc.descripcion, rc.obligatorio, rc.medio_pago,
                u.nombre AS validador
         FROM solicitud_requisito sr
         JOIN requisito_credito rc ON rc.id = sr.requisito_id
         LEFT JOIN usuario u ON u.id = sr.validado_por";

    // Checklist completo de una solicitud, en el orden del catálogo.
    public function listarPorSolicitud(int $solicitudId): array
    {
        return $this->consultar(
            self::SELECT . ' WHERE sr.solicitud_id = ? ORDER BY rc.id',
            [$solicitudId]
        );
    }

    public function buscar(int $solicitudId, int $requisitoId): ?array
    {
        $filas = $this->consultar(
            self::SELECT . ' WHERE sr.solicitud_id = ? AND sr.requisito_id = ? LIMIT 1',
            [$solicitudId, $requisitoId]
        );

        return $filas[0] ?? null;
    }

    // Copia el catálogo como checklist PENDIENTE al radicar la solicitud.
    // Se hace en una sola sentencia (INSERT ... SELECT) para no hacer un INSERT por requisito.
    public function crearDesdeCatalogo(int $solicitudId, string $medioPago): int
    {
        return $this->ejecutar(
            "INSERT INTO solicitud_requisito (solicitud_id, requisito_id, estado)
             SELECT ?, id, 'PENDIENTE'
             FROM requisito_credito
             WHERE medio_pago = ?
             ORDER BY id",
            [$solicitudId, strtoupper(trim($medioPago))]
        );
    }

    // Marca un requisito como VALIDADO o RECHAZADO y guarda quién y cuándo.
    public function evaluar(int $solicitudId, int $requisitoId, string $estado, ?int $validadoPor): int
    {
        return $this->ejecutar(
            'UPDATE solicitud_requisito
             SET estado = ?, validado_por = ?, validado_en = NOW()
             WHERE solicitud_id = ? AND requisito_id = ?',
            [strtoupper(trim($estado)), $validadoPor, $solicitudId, $requisitoId]
        );
    }

    // Guarda la ruta del soporte y devuelve el requisito a PENDIENTE: subir un
    // documento nuevo invalida la revisión anterior (misma regla que el dominio).
    public function adjuntarSoporte(int $solicitudId, int $requisitoId, string $ruta): int
    {
        return $this->ejecutar(
            "UPDATE solicitud_requisito
             SET soporte_adjunto = ?, estado = 'PENDIENTE', validado_por = NULL, validado_en = NULL
             WHERE solicitud_id = ? AND requisito_id = ?",
            [$ruta, $solicitudId, $requisitoId]
        );
    }

    // Requisitos obligatorios que todavía no están validados. Si devuelve filas, no se puede aprobar.
    public function obligatoriosPendientes(int $solicitudId): array
    {
        return $this->consultar(
            self::SELECT . " WHERE sr.solicitud_id = ?
                AND rc.obligatorio = 1
                AND sr.estado <> 'VALIDADO'
             ORDER BY rc.id",
            [$solicitudId]
        );
    }

    // True si el recibo de gas (convenio Brilla) está validado. Sin él no se aprueba.
    public function reciboGasValidado(int $solicitudId): bool
    {
        $filas = $this->consultar(
            "SELECT COUNT(*) AS total
             FROM solicitud_requisito sr
             JOIN requisito_credito rc ON rc.id = sr.requisito_id
             WHERE sr.solicitud_id = ?
               AND rc.medio_pago = 'BRILLA'
               AND rc.obligatorio = 1
               AND rc.descripcion LIKE '%gas%'
               AND sr.estado = 'VALIDADO'",
            [$solicitudId]
        );

        return ((int) ($filas[0]['total'] ?? 0)) > 0;
    }
}
