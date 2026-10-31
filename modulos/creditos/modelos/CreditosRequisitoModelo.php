<?php
// Catálogo maestro de requisitos de crédito (tabla requisito_credito).
// Brilla y ServiCrédito tienen requisitos distintos; cada fila dice cuál y si es obligatoria.
class CreditosRequisitoModelo extends Modelo
{
    // Devuelve todos los requisitos del catálogo, ordenados por medio de pago.
    public function listarTodos(): array
    {
        return $this->consultar(
            'SELECT id, medio_pago, descripcion, obligatorio
             FROM requisito_credito
             ORDER BY medio_pago, id'
        );
    }

    // Requisitos configurados para un medio de pago ('BRILLA' o 'SERVICREDITO').
    // Son los que se copian como checklist al radicar una solicitud.
    public function listarPorMedioPago(string $medioPago): array
    {
        return $this->consultar(
            'SELECT id, medio_pago, descripcion, obligatorio
             FROM requisito_credito
             WHERE medio_pago = ?
             ORDER BY id',
            [strtoupper(trim($medioPago))]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        $filas = $this->consultar(
            'SELECT id, medio_pago, descripcion, obligatorio
             FROM requisito_credito
             WHERE id = ?
             LIMIT 1',
            [$id]
        );

        return $filas[0] ?? null;
    }

    // Medios de pago que ya tienen requisitos cargados, para llenar el formulario
    // sin escribir a mano 'BRILLA' / 'SERVICREDITO'.
    public function mediosDePagoDisponibles(): array
    {
        return $this->consultar(
            'SELECT medio_pago, COUNT(*) AS total
             FROM requisito_credito
             GROUP BY medio_pago
             ORDER BY medio_pago'
        );
    }

    // Convierte una fila del checklist (solicitud_requisito) en la entidad de dominio.
    // Esa consulta trae la columna como `requisito_id`, no `id`, así que se arma aquí
    // en vez de dejarlo a la vista o al controlador.
    public static function aEntidadDesdeChecklist(array $fila): CreditosRequisito
    {
        return CreditosRequisito::desdeFila([
            'id'          => $fila['requisito_id'] ?? null,
            'medio_pago'  => $fila['medio_pago'] ?? '',
            'descripcion' => $fila['descripcion'] ?? '',
            'obligatorio' => $fila['obligatorio'] ?? false,
        ]);
    }

    // Convierte una fila de requisito_credito en la entidad de dominio.
    public static function aEntidad(array $fila): CreditosRequisito
    {
        return CreditosRequisito::desdeFila($fila);
    }
}
