<?php
// Soporte que una venta entrega para un requisito del catálogo (tabla venta_requisito).
// Equivale a VentaRequisito de Laravel, más dos datos copiados del catálogo:
// descripcion y obligatorio. Así el agregado valida sin volver a la base.
class CreditosSoporteRequisito
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_VALIDADO  = 'VALIDADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    private string $ventaNumero;
    private int $requisitoId;
    private string $estado;
    private ?string $soporteAdjunto;
    private ?string $validadoPor;

    // Copiados de CreditosRequisito al crear el soporte (ver desdeRequisito).
    private readonly string $descripcion;
    private readonly bool $obligatorio;
    private readonly bool $esReciboGas;

    public function __construct(
        string $ventaNumero,
        int $requisitoId,
        string $estado = self::ESTADO_PENDIENTE,
        ?string $soporteAdjunto = null,
        ?string $validadoPor = null,
        string $descripcion = '',
        bool $obligatorio = true,
        bool $esReciboGas = false
    ) {
        $this->ventaNumero    = trim($ventaNumero);
        $this->requisitoId    = $requisitoId;
        $this->estado         = self::normalizarEstado($estado);
        $this->soporteAdjunto = $soporteAdjunto;
        $this->validadoPor    = $validadoPor;
        $this->descripcion    = $descripcion;
        $this->obligatorio    = $obligatorio;
        $this->esReciboGas    = $esReciboGas;
    }

    // Copia los datos del catálogo dentro del soporte (desnormalización).
    public static function desdeRequisito(string $ventaNumero, CreditosRequisito $requisito): self
    {
        $id = $requisito->getId();
        if ($id === null) {
            throw new CreditosExcepcion('El requisito de crédito debe estar guardado antes de asociarlo a una venta.');
        }

        return new self(
            $ventaNumero,
            $id,
            self::ESTADO_PENDIENTE,
            null,
            null,
            $requisito->getDescripcion(),
            $requisito->isObligatorio(),
            $requisito->esReciboGas()
        );
    }

    public function getVentaNumero(): string
    {
        return $this->ventaNumero;
    }

    public function getRequisitoId(): int
    {
        return $this->requisitoId;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getSoporteAdjunto(): ?string
    {
        return $this->soporteAdjunto;
    }

    public function getValidadoPor(): ?string
    {
        return $this->validadoPor;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function isObligatorio(): bool
    {
        return $this->obligatorio;
    }

    // True si este soporte es el recibo de gas de un crédito Brilla.
    public function esReciboGas(): bool
    {
        return $this->esReciboGas;
    }

    // Subir un soporte nuevo invalida la revisión anterior: vuelve a PENDIENTE.
    public function adjuntarSoporte(string $rutaArchivo): void
    {
        $this->soporteAdjunto = $rutaArchivo;
        $this->estado         = self::ESTADO_PENDIENTE;
        $this->validadoPor    = null;
    }

    public function validar(string $usuario): void
    {
        $this->estado      = self::ESTADO_VALIDADO;
        $this->validadoPor = trim($usuario);
    }

    public function rechazar(string $usuario): void
    {
        $this->estado      = self::ESTADO_RECHAZADO;
        $this->validadoPor = trim($usuario);
    }

    public function estaValidado(): bool
    {
        return $this->estado === self::ESTADO_VALIDADO;
    }

    public function estaRechazado(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    // Deja el estado en uno de los tres valores válidos.
    private static function normalizarEstado(string $estado): string
    {
        $estado = strtoupper(trim($estado));

        if (in_array($estado, [self::ESTADO_PENDIENTE, self::ESTADO_VALIDADO, self::ESTADO_RECHAZADO], true)) {
            return $estado;
        }

        throw new CreditosExcepcion("Estado de requisito no válido: '{$estado}'.");
    }
}
