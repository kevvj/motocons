<?php
// Reglas de negocio: vigencia, alcance y mejor descuento aplicable.
class PromocionesDescuentoModelo
{
    public static function estadoVigencia(array $promocion, ?string $fecha = null): string
    {
        if (($promocion['estado'] ?? 'activa') !== 'activa') {
            return 'INACTIVA';
        }

        $hoy = self::normalizarFecha($fecha);

        if ($hoy < substr((string) $promocion['fecha_inicio'], 0, 10)) {
            return 'PROGRAMADA';
        }
        if ($hoy > substr((string) $promocion['fecha_fin'], 0, 10)) {
            return 'VENCIDA';
        }

        return 'VIGENTE';
    }

    public static function aplicaA(array $promocion, array $moto): bool
    {
        $aplicaciones = $promocion['aplica'] ?? [];

        if ($aplicaciones === []) {
            return true;
        }

        foreach ($aplicaciones as $fila) {
            $criterio = strtolower((string) ($fila['criterio'] ?? ''));
            $valor = (string) ($fila['valor'] ?? '');

            if ($criterio === 'marca'
                && strcasecmp($valor, (string) ($moto['marca'] ?? '')) === 0) {
                return true;
            }
            if ($criterio === 'gama'
                && strcasecmp($valor, (string) ($moto['gama'] ?? '')) === 0) {
                return true;
            }
            if ($criterio === 'motocicleta'
                && (int) $valor === (int) ($moto['id'] ?? 0)) {
                return true;
            }
        }

        return false;
    }

    public static function calcular(array $promocion, float $precio): array
    {
        $precio = round(max(0, $precio), 2);
        $valor = (float) $promocion['valor'];

        if (($promocion['tipo_descuento'] ?? '') === 'porcentaje') {
            $descuento = $precio * ($valor / 100);
        } else {
            $descuento = $valor;
        }

        $descuento = round(min($descuento, $precio), 2);
        $precioFinal = round($precio - $descuento, 2);

        return [
            'precio_original'   => $precio,
            'descuento'         => $descuento,
            'precio_final'      => $precioFinal,
            'ahorro_porcentaje' => $precio > 0
                ? round(($descuento / $precio) * 100, 2)
                : 0.0,
        ];
    }

    public static function mejorPromocion(
        array $promociones,
        array $moto,
        ?string $fecha = null
    ): ?array {
        $mejor = null;

        foreach ($promociones as $promocion) {
            if (self::estadoVigencia($promocion, $fecha) !== 'VIGENTE') {
                continue;
            }
            if (!self::aplicaA($promocion, $moto)) {
                continue;
            }

            $calculo = self::calcular($promocion, (float) $moto['precio']);

            if ($calculo['descuento'] <= 0) {
                continue;
            }

            $calculo['promocion'] = [
                'id'             => (int) $promocion['id'],
                'nombre'         => $promocion['nombre'],
                'tipo_descuento' => $promocion['tipo_descuento'],
                'valor'          => (float) $promocion['valor'],
                'fecha_fin'      => $promocion['fecha_fin'],
            ];

            if ($mejor === null
                || $calculo['descuento'] > $mejor['descuento']
                || ($calculo['descuento'] === $mejor['descuento']
                    && (int) $calculo['promocion']['id'] > (int) $mejor['promocion']['id'])) {
                $mejor = $calculo;
            }
        }

        return $mejor;
    }

    public static function precioFinal(
        array $moto,
        array $promociones,
        ?string $fecha = null
    ): array {
        $precioOriginal = round((float) $moto['precio'], 2);
        $mejor = self::mejorPromocion($promociones, $moto, $fecha);

        if ($mejor === null) {
            return [
                'precio_original'   => $precioOriginal,
                'descuento'         => 0.0,
                'precio_final'      => $precioOriginal,
                'ahorro_porcentaje' => 0.0,
                'promocion'         => null,
            ];
        }

        return $mejor;
    }

    private static function normalizarFecha(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return date('Y-m-d');
        }

        return substr($fecha, 0, 10);
    }
}
