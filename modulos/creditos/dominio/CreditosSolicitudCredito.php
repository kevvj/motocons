<?php
// Agregado raíz del módulo créditos: una solicitud = una venta en proceso de crédito
// más los soportes de sus requisitos. Aquí viven las invariantes de la radicación.
//
// Decisión de diseño: el agregado NO guarda objetos de catálogo (CreditosRequisito).
// Cada CreditosSoporteRequisito trae copiados descripcion, obligatorio y esReciboGas,
// así el agregado se valida en memoria sin consultar la base en cada paso.
// Los datos copiados son inmutables (readonly) para que no se desincronicen.
class CreditosSolicitudCredito
{
    private string $ventaNumero;
    private string $medioPago;

    /** @var CreditosSoporteRequisito[] */
    private array $requisitos = [];

    public function __construct(string $ventaNumero, string $medioPago, array $requisitos = [])
    {
        $ventaNumero = trim($ventaNumero);
        if ($ventaNumero === '') {
            throw new CreditosExcepcion('La solicitud de crédito necesita el número de la venta.');
        }

        $this->ventaNumero = $ventaNumero;
        $this->medioPago   = strtoupper(trim($medioPago));

        foreach ($requisitos as $requisito) {
            if (! $requisito instanceof CreditosSoporteRequisito) {
                throw new CreditosExcepcion('Los requisitos de la solicitud deben ser instancias de CreditosSoporteRequisito.');
            }
            $this->agregarRequisito($requisito);
        }
    }

    public function getVentaNumero(): string
    {
        return $this->ventaNumero;
    }

    public function getMedioPago(): string
    {
        return $this->medioPago;
    }

    public function esBrilla(): bool
    {
        return $this->medioPago === CreditosRequisito::MEDIO_BRILLA;
    }

    // Un requisito del catálogo se puede asociar una sola vez por venta.
    public function agregarRequisito(CreditosSoporteRequisito $requisito): void
    {
        if ($requisito->getVentaNumero() !== $this->ventaNumero) {
            throw new CreditosExcepcion(
                "El requisito {$requisito->getRequisitoId()} es de la venta {$requisito->getVentaNumero()} y no de la venta {$this->ventaNumero}."
            );
        }

        if ($this->buscarPorRequisitoId($requisito->getRequisitoId()) !== null) {
            throw new CreditosExcepcion("El requisito {$requisito->getRequisitoId()} ya está asociado a la venta {$this->ventaNumero}.");
        }

        $this->requisitos[] = $requisito;
    }

    /**
     * @return CreditosSoporteRequisito[]
     */
    public function getRequisitos(): array
    {
        return $this->requisitos;
    }

    public function buscarPorRequisitoId(int $requisitoId): ?CreditosSoporteRequisito
    {
        foreach ($this->requisitos as $requisito) {
            if ($requisito->getRequisitoId() === $requisitoId) {
                return $requisito;
            }
        }

        return null;
    }

    /**
     * Solo los soportes obligatorios.
     *
     * @return CreditosSoporteRequisito[]
     */
    public function requisitosObligatorios(): array
    {
        return array_values(array_filter(
            $this->requisitos,
            static fn (CreditosSoporteRequisito $r): bool => $r->isObligatorio()
        ));
    }

    /**
     * Obligatorios que todavía no están validados (pendientes o rechazados).
     *
     * @return CreditosSoporteRequisito[]
     */
    public function requisitosFaltantes(): array
    {
        return array_values(array_filter(
            $this->requisitosObligatorios(),
            static fn (CreditosSoporteRequisito $r): bool => ! $r->estaValidado()
        ));
    }

    // Regla Brilla: el recibo de gas es condición aparte, aunque esté marcado como opcional.
    public function faltaReciboGas(): bool
    {
        if (! $this->esBrilla()) {
            return false;
        }

        foreach ($this->requisitos as $requisito) {
            if ($requisito->esReciboGas()) {
                return ! $requisito->estaValidado();
            }
        }

        return true;
    }

    public function puedeAprobarse(): bool
    {
        return $this->requisitosFaltantes() === [] && ! $this->faltaReciboGas();
    }

    // Se llama justo antes de aprobar: si algo falta, lanza y el controlador muestra el aviso.
    public function validarParaAprobar(): void
    {
        if ($this->faltaReciboGas()) {
            throw CreditosRequisitoCreditoInvalidoExcepcion::reciboGasObligatorio($this->ventaNumero);
        }

        $faltantes = $this->requisitosFaltantes();
        if ($faltantes !== []) {
            $falta = $faltantes[0];
            throw new CreditosExcepcion(
                "No se puede aprobar el crédito: el requisito obligatorio '{$falta->getDescripcion()}' no ha sido validado (estado actual: {$falta->getEstado()})."
            );
        }
    }

    // Porcentaje de avance para el KPI de la vista: 0 a 100, con un decimal.
    // Cuenta todos los requisitos si no hay ninguno obligatorio; si los hay, solo esos.
    public function porcentajeAvance(): float
    {
        $base = $this->requisitosObligatorios();
        if ($base === []) {
            $base = $this->requisitos;
        }

        $total = count($base);
        if ($total === 0) {
            return 0.0;
        }

        $validados = 0;
        foreach ($base as $requisito) {
            if ($requisito->estaValidado()) {
                $validados++;
            }
        }

        return round($validados * 100 / $total, 1);
    }
}
