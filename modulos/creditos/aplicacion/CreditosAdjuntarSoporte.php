<?php
// Caso de uso: adjuntar (o reemplazar) el soporte de un requisito del checklist.
// Subir un documento nuevo invalida la revisión anterior: el requisito vuelve a PENDIENTE
// (misma regla que el dominio en CreditosSoporteRequisito::adjuntarSoporte).
class CreditosAdjuntarSoporte
{
    private CreditosSolicitudModelo $solicitudes;
    private CreditosSolicitudRequisitoModelo $checklist;

    public function __construct(CreditosSolicitudModelo $solicitudes, CreditosSolicitudRequisitoModelo $checklist)
    {
        $this->solicitudes = $solicitudes;
        $this->checklist   = $checklist;
    }

    // Devuelve las filas afectadas (1 si se guardó). Lanza CreditosExcepcion si no corresponde.
    public function ejecutar(int $solicitudId, int $requisitoId, string $rutaArchivo): int
    {
        $this->buscarEditable($solicitudId);
        $this->verificarEnChecklist($solicitudId, $requisitoId);

        return $this->checklist->adjuntarSoporte($solicitudId, $requisitoId, $rutaArchivo);
    }

    private function buscarEditable(int $solicitudId): array
    {
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

        return $solicitud;
    }

    private function verificarEnChecklist(int $solicitudId, int $requisitoId): void
    {
        if ($this->checklist->buscar($solicitudId, $requisitoId) === null) {
            throw new CreditosExcepcion(
                "El requisito {$requisitoId} no está en el checklist de la solicitud {$solicitudId}.",
                'REQUISITO_NO_EXISTE'
            );
        }
    }
}
