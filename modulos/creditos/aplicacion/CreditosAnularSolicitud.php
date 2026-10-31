<?php
// Caso de uso: el asesor anula el trámite antes de que se resuelva
// (p. ej. el cliente se retira o decide pagar de otra forma).
class CreditosAnularSolicitud
{
    private CreditosSolicitudModelo $solicitudes;

    public function __construct(CreditosSolicitudModelo $solicitudes)
    {
        $this->solicitudes = $solicitudes;
    }

    // Devuelve la fila de la solicitud ya ANULADA. Lanza CreditosExcepcion si no corresponde.
    public function ejecutar(int $solicitudId): array
    {
        $fila = $this->solicitudes->buscarPorId($solicitudId);
        if ($fila === null) {
            throw new CreditosExcepcion("La solicitud {$solicitudId} no existe.", 'SOLICITUD_NO_EXISTE');
        }
        if (! CreditosSolicitudModelo::esEditable($fila['estado'])) {
            throw new CreditosExcepcion(
                "La solicitud {$solicitudId} está en estado '{$fila['estado']}' y ya no se puede anular.",
                'SOLICITUD_NO_EDITABLE'
            );
        }

        $this->solicitudes->cambiarEstado($solicitudId, CreditosSolicitudModelo::ESTADO_ANULADA);

        return $this->solicitudes->buscarPorId($solicitudId);
    }
}
