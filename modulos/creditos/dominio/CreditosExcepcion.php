<?php
// Excepción base del dominio de créditos (equivale a DomainException de Laravel).
// Lleva un código corto para que el controlador decida qué mostrar sin leer el texto.
class CreditosExcepcion extends Exception
{
    public const CODIGO_GENERICO = 'CREDITOS_ERROR';

    private string $codigo;

    public function __construct(string $mensaje, string $codigo = self::CODIGO_GENERICO, ?Throwable $anterior = null)
    {
        parent::__construct($mensaje, 0, $anterior);
        $this->codigo = $codigo;
    }

    // Código de negocio, p. ej. 'REQUISITO_GAS'. La vista no lo muestra: lo usa el controlador.
    public function getCodigo(): string
    {
        return $this->codigo;
    }
}
