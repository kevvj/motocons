<?php
// La venta ya no admite cambios (está PAGADA, ENTREGADA o ANULADA).
// Equivale a VentaNoEditableException de Laravel.
class CreditosVentaNoEditableExcepcion extends CreditosExcepcion
{
    public const CODIGO_NO_EDITABLE = 'VENTA_NO_EDITABLE';

    public static function estadoNoPermiteCambios(string $numero, string $estado): self
    {
        return new self(
            "La venta {$numero} se encuentra en estado '{$estado}' y no puede ser modificada.",
            self::CODIGO_NO_EDITABLE
        );
    }
}
