<?php
// Caso de uso: catálogo de requisitos de crédito, filtrado por medio de pago.
class CreditosListarRequisitos
{
    private CreditosRequisitoModelo $requisitos;

    public function __construct(CreditosRequisitoModelo $requisitos)
    {
        $this->requisitos = $requisitos;
    }

    /**
     * @return CreditosRequisito[]
     */
    public function ejecutar(?string $medioPago = null): array
    {
        $medioPago = trim((string) $medioPago);
        $filas = $medioPago === ''
            ? $this->requisitos->listarTodos()
            : $this->requisitos->listarPorMedioPago($medioPago);

        return array_map([CreditosRequisitoModelo::class, 'aEntidad'], $filas);
    }
}
