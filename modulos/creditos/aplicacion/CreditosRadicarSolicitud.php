<?php
// Caso de uso: radicar una solicitud de crédito sobre una venta que ya existe.
// No se escribe en la tabla venta: solo se lee, para confirmar que exista y que el
// vehículo indicado (si hay) sea de esa venta. El estado del crédito nace propio.
class CreditosRadicarSolicitud
{
    private CreditosSolicitudModelo $solicitudes;
    private CreditosSolicitudRequisitoModelo $checklist;
    private CreditosRecursoModelo $recursos;

    public function __construct(
        CreditosSolicitudModelo $solicitudes,
        CreditosSolicitudRequisitoModelo $checklist,
        CreditosRecursoModelo $recursos
    ) {
        $this->solicitudes = $solicitudes;
        $this->checklist   = $checklist;
        $this->recursos    = $recursos;
    }

    // Crea la solicitud y copia el checklist del catálogo. Todo en una transacción.
    // Devuelve el id de la solicitud creada; lanza CreditosExcepcion si algo no cuadra.
    public function ejecutar(
        int $ventaId,
        ?int $vehiculoId,
        string $medioPago,
        float $montoFinanciado,
        ?int $registradoPor,
        ?string $observacion = null
    ): int {
        $medioPago = strtoupper(trim($medioPago));
        if (! in_array($medioPago, [CreditosRequisito::MEDIO_BRILLA, CreditosRequisito::MEDIO_SERVICREDITO], true)) {
            throw new CreditosExcepcion("Medio de pago no válido: '{$medioPago}'.", 'MEDIO_PAGO_INVALIDO');
        }

        $venta = $this->recursos->buscarVenta($ventaId);
        if ($venta === null) {
            throw new CreditosExcepcion("La venta {$ventaId} no existe.", 'VENTA_NO_EXISTE');
        }

        if ($this->solicitudes->buscarPorVenta($ventaId) !== null) {
            $etiqueta = $venta['numero_factura'] ?? $ventaId;
            throw new CreditosExcepcion("La venta {$etiqueta} ya tiene una solicitud de crédito.", 'SOLICITUD_DUPLICADA');
        }

        if ($vehiculoId !== null) {
            $idsDeLaVenta = array_column($this->recursos->vehiculosDeVenta($ventaId), 'id');
            if (! in_array($vehiculoId, $idsDeLaVenta, true)) {
                throw new CreditosExcepcion(
                    "El vehículo {$vehiculoId} no pertenece a la venta {$ventaId}.",
                    'VEHICULO_NO_PERTENECE'
                );
            }
        }

        $db = Database::conexion();
        $db->beginTransaction();
        try {
            $solicitudId = $this->solicitudes->crear(
                $ventaId,
                $vehiculoId,
                $medioPago,
                $montoFinanciado,
                $registradoPor,
                CreditosSolicitudModelo::ESTADO_EN_PROCESO_CREDITO,
                $observacion
            );
            $this->checklist->crearDesdeCatalogo($solicitudId, $medioPago);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return $solicitudId;
    }
}
