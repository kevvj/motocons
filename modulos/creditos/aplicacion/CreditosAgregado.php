<?php
// Único punto que traduce filas de BD al agregado de dominio (CreditosSolicitudCredito).
// Lo usa CreditosAprobarCredito para validar, y el controlador para mostrar el avance
// en el tablero y el detalle — la misma reconstrucción, sin repetir la traducción dos veces.
class CreditosAgregado
{
    public static function reconstruir(array $filaSolicitud, array $filasChecklist): CreditosSolicitudCredito
    {
        $ventaNumero = $filaSolicitud['numero_factura'] ?? ('VENTA-' . $filaSolicitud['venta_id']);

        $soportes = [];
        foreach ($filasChecklist as $fila) {
            $requisito = CreditosRequisitoModelo::aEntidadDesdeChecklist($fila);
            $soportes[] = new CreditosSoporteRequisito(
                $ventaNumero,
                (int) $fila['requisito_id'],
                $fila['estado'],
                $fila['soporte_adjunto'],
                $fila['validado_por'] !== null ? (string) ($fila['validador'] ?? $fila['validado_por']) : null,
                $fila['descripcion'],
                (bool) $fila['obligatorio'],
                $requisito->esReciboGas()
            );
        }

        return new CreditosSolicitudCredito($ventaNumero, $filaSolicitud['medio_pago'], $soportes);
    }
}
