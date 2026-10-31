<?php
// Catálogo de requisitos de crédito (tabla requisito_credito).
// Cada fila dice qué soporte pide cada financiera y si es obligatorio.
class CreditosRequisito
{
    public const MEDIO_BRILLA       = 'BRILLA';
    public const MEDIO_SERVICREDITO = 'SERVICREDITO';

    // Palabras que identifican el recibo de gas (sin acentos).
    private const PALABRAS_GAS = ['gas', 'surtigas', 'gases del caribe'];

    // Respaldo por si no existe iconv. Va en minúsculas, después de pasar el texto por strtolower().
    private const MAPA_ACENTOS = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ý' => 'y',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ];

    private ?int $id;
    private string $medioPago;
    private string $descripcion;
    private bool $obligatorio;

    public function __construct(?int $id, string $medioPago, string $descripcion, bool $obligatorio = true)
    {
        $this->id          = $id;
        $this->medioPago   = strtoupper(trim($medioPago));
        $this->descripcion = $descripcion;
        $this->obligatorio = $obligatorio;
    }

    // Arma el requisito desde una fila de la base (claves: id, medio_pago, descripcion, obligatorio).
    public static function desdeFila(array $fila): self
    {
        return new self(
            isset($fila['id']) ? (int) $fila['id'] : null,
            (string) ($fila['medio_pago'] ?? ''),
            (string) ($fila['descripcion'] ?? ''),
            (bool) ($fila['obligatorio'] ?? false)
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedioPago(): string
    {
        return $this->medioPago;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function isObligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function esParaBrilla(): bool
    {
        return $this->getMedioPagoNormalizado() === self::MEDIO_BRILLA;
    }

    public function esParaServiCredito(): bool
    {
        return $this->getMedioPagoNormalizado() === self::MEDIO_SERVICREDITO;
    }

    // Medio de pago en mayúsculas y sin tildes: 'ServiCrédito' -> 'SERVICREDITO'.
    public function getMedioPagoNormalizado(): string
    {
        return strtoupper(self::normalizar($this->medioPago));
    }

    // Regla Brilla: es el recibo de gas si el requisito es de Brilla y habla de gas.
    // La comparación ignora acentos y mayúsculas porque la descripción viene de la base.
    public function esReciboGas(): bool
    {
        if (! $this->esParaBrilla()) {
            return false;
        }

        // stripos sobre la descripción normalizada: aguanta 'Recibo de GAS' o 'Gas'.
        $texto = self::normalizar($this->descripcion);
        foreach (self::PALABRAS_GAS as $palabra) {
            if (stripos($texto, $palabra) !== false) {
                return true;
            }
        }

        return false;
    }

    // Minúsculas y sin tildes, para comparar texto escrito por personas.
    // No depende de mbstring: el CI solo instala pdo_mysql.
    public static function normalizar(string $texto): string
    {
        $texto = trim($texto);

        if (function_exists('mb_strtolower')) {
            $texto = mb_strtolower($texto, 'UTF-8');
        } else {
            $texto = strtolower($texto);
        }

        // Iconv quita las tildes (á -> a, ñ -> n). Si no está, queda el mapa de abajo.
        if (function_exists('iconv')) {
            $sinTildes = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($sinTildes !== false) {
                $texto = $sinTildes;
            }
        }

        return strtr($texto, self::MAPA_ACENTOS);
    }
}
