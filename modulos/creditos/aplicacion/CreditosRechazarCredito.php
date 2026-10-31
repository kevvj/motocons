<?php
// Caso de uso: el analista rechaza formalmente el crédito (no cumple los requisitos).
// Es la contraparte de CreditosAprobarCredito: ambos solo se pueden dictaminar
// mientras la solicitud está EN_PROCESO_CREDITO.
class CreditosRechazarCredito
{
    private CreditosSolicitudModelo $solicitudes;

    public function __construct(CreditosSolicitudModelo $solicitudes)
    {
        $this->solicitudes = $solicitudes;
    }

    // Devuelve la fila de la solicitud ya RECHAZADA. Lanza CreditosExcepcion si no corresponde.
    public function ejecutar(int $solicitudId, int $rechazadoPor): array
    {
        $fila = $this->solicitudes->buscarPorId($solicitudId);
        if ($fila === null) {
            throw new CreditosExcepcion("La solicitud {$solicitudId} no existe.", 'SOLICITUD_NO_EXISTE');
        }
        if ($fila['estado'] !== CreditosSolicitudModelo::ESTADO_EN_PROCESO_CREDITO) {
            throw new CreditosExcepcion(
                "La solicitud {$solicitudId} está en estado '{$fila['estado']}' y no se puede rechazar desde ahí.",
                'SOLICITUD_NO_EDITABLE'
            );
        }

        $this->solicitudes->cambiarEstado($solicitudId, CreditosSolicitudModelo::ESTADO_RECHAZADA, $rechazadoPor);

        return $this->solicitudes->buscarPorId($solicitudId);
    }
}
