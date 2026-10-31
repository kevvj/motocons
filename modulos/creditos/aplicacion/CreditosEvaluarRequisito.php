<?php
// Caso de uso: el analista marca un requisito del checklist como VALIDADO o RECHAZADO.
class CreditosEvaluarRequisito
{
    private CreditosSolicitudModelo $solicitudes;
    private CreditosSolicitudRequisitoModelo $checklist;

    public function __construct(CreditosSolicitudModelo $solicitudes, CreditosSolicitudRequisitoModelo $checklist)
    {
        $this->solicitudes = $solicitudes;
        $this->checklist   = $checklist;
    }

    // Devuelve las filas afectadas (1 si se guardó). Lanza CreditosExcepcion si no corresponde.
    public function ejecutar(int $solicitudId, int $requisitoId, string $estado, ?int $validadoPor): int
    {
        $estado = strtoupper(trim($estado));
        if (! in_array($estado, [CreditosSoporteRequisito::ESTADO_VALIDADO, CreditosSoporteRequisito::ESTADO_RECHAZADO], true)) {
            throw new CreditosExcepcion("Estado de evaluación no válido: '{$estado}'.", 'ESTADO_INVALIDO');
        }

        $solicitud = $this->solicitudes->buscarPorId($solicitudId);
        if ($solicitud === null) {
            throw new CreditosExcepcion("La solicitud {$solicitudId} no existe.", 'SOLICITUD_NO_EXISTE');
        }
        if (! CreditosSolicitudModelo::esEditable($solicitud['estado'])) {
            throw new CreditosExcepcion(
                "La solicitud {$solicitudId} está en estado '{$solicitud['estado']}' y no admite cambios.",
                'SOLICITUD_NO_EDITABLE'
            );
        }
        if ($this->checklist->buscar($solicitudId, $requisitoId) === null) {
            throw new CreditosExcepcion(
                "El requisito {$requisitoId} no está en el checklist de la solicitud {$solicitudId}.",
                'REQUISITO_NO_EXISTE'
            );
        }

        return $this->checklist->evaluar($solicitudId, $requisitoId, $estado, $validadoPor);
    }
}
