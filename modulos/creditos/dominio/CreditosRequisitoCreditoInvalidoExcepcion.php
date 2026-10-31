<?php
// Regla de negocio Brilla: no se aprueba el crédito sin el recibo de gas validado.
// Equivale a RequisitoCreditoInvalidoException::reciboGasObligatorio() de Laravel.
class CreditosRequisitoCreditoInvalidoExcepcion extends CreditosExcepcion
{
    public const CODIGO_RECIBO_GAS = 'REQUISITO_GAS';

    public static function reciboGasObligatorio(string $ventaNumero): self
    {
        return new self(
            "Para compras con Crédito Brilla en la venta {$ventaNumero}, es obligatorio adjuntar y validar el recibo de gas.",
            self::CODIGO_RECIBO_GAS
        );
    }
}
