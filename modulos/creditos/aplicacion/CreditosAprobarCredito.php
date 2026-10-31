<?php
// Caso de uso central: aprobar el crédito. Arma el agregado de dominio con el checklist
// persistido y le delega la validación (regla del recibo de gas incluida); el caso de uso
// solo orquesta, no decide negocio.
class CreditosAprobarCredito
{
    private CreditosSolicitudModelo $solicitudes;
    private CreditosSolicitudRequisitoModelo $checklist;

    public function __construct(CreditosSolicitudModelo $solicitudes, CreditosSolicitudRequisitoModelo $checklist)
    {
        $this->solicitudes = $solicitudes;
        $this->checklist   = $checklist;
    }

    // Devuelve la fila de la solicitud ya APROBADA. Lanza CreditosExcepcion si no corresponde
    // (incluida CreditosRequisitoCreditoInvalidoExcepcion si falta el recibo de gas).
    public function ejecutar(int $solicitudId, int $aprobadoPor): array
    {
        $filaSolicitud = $this->solicitudes->buscarPorId($solicitudId);
        if ($filaSolicitud === null) {
            throw new CreditosExcepcion("La solicitud {$solicitudId} no existe.", 'SOLICITUD_NO_EXISTE');
        }
        if ($filaSolicitud['estado'] !== CreditosSolicitudModelo::ESTADO_EN_PROCESO_CREDITO) {
            throw new CreditosExcepcion(
                "La solicitud {$solicitudId} está en estado '{$filaSolicitud['estado']}' y no se puede aprobar desde ahí.",
                'SOLICITUD_NO_EDITABLE'
            );
        }

        $filasChecklist = $this->checklist->listarPorSolicitud($solicitudId);
        $agregado = CreditosAgregado::reconstruir($filaSolicitud, $filasChecklist);

        // Lanza CreditosRequisitoCreditoInvalidoExcepcion (REQUISITO_GAS) u otra CreditosExcepcion si falta algo.
        $agregado->validarParaAprobar();

        $this->solicitudes->aprobar($solicitudId, $aprobadoPor);

        return $this->solicitudes->buscarPorId($solicitudId);
    }
}
