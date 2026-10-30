<?php
// Validación y normalización de datos de una promoción (formulario o API).
class PromocionesValidadorModelo
{
    /**
     * @return array{valido: bool, errores: array, datos: array, aplicaciones: array, criterio: string}
     */
    public static function validar(array $entrada, array $opciones): array
    {
        $errores = [];

        $nombre = trim((string) ($entrada['nombre'] ?? ''));
        $descripcion = trim((string) ($entrada['descripcion'] ?? ''));
        $tipo = strtolower(trim((string) ($entrada['tipo_descuento'] ?? '')));
        $valor = trim((string) ($entrada['valor'] ?? ''));
        $fechaInicio = trim((string) ($entrada['fecha_inicio'] ?? ''));
        $fechaFin = trim((string) ($entrada['fecha_fin'] ?? ''));
        $estado = strtolower(trim((string) ($entrada['estado'] ?? 'activa')));
        $criterio = strtolower(trim((string) ($entrada['criterio'] ?? 'todas')));

        if ($nombre === '') {
            $errores['nombre'] = 'El nombre de la promoción es obligatorio.';
        } elseif (mb_strlen($nombre) > 120) {
            $errores['nombre'] = 'El nombre no puede superar los 120 caracteres.';
        }

        if (mb_strlen($descripcion) > 255) {
            $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
        }

        if (!in_array($tipo, ['porcentaje', 'monto_fijo'], true)) {
            $errores['tipo_descuento'] = 'El tipo de descuento debe ser porcentaje o monto fijo.';
        }

        if ($valor === '' || !is_numeric($valor)) {
            $errores['valor'] = 'El valor del descuento es obligatorio y debe ser numérico.';
            $valorNum = 0.0;
        } else {
            $valorNum = round((float) $valor, 2);
            if ($valorNum <= 0) {
                $errores['valor'] = 'El valor del descuento debe ser mayor que cero.';
            } elseif ($tipo === 'porcentaje' && $valorNum > 100) {
                $errores['valor'] = 'Un descuento porcentual no puede ser mayor a 100%.';
            }
        }

        if (!self::fechaValida($fechaInicio)) {
            $errores['fecha_inicio'] = 'La fecha de inicio no es válida (formato AAAA-MM-DD).';
        }
        if (!self::fechaValida($fechaFin)) {
            $errores['fecha_fin'] = 'La fecha de finalización no es válida (formato AAAA-MM-DD).';
        }
        if (self::fechaValida($fechaInicio) && self::fechaValida($fechaFin)
            && $fechaFin < $fechaInicio) {
            $errores['fecha_fin'] = 'La fecha final no puede ser anterior a la fecha inicial.';
        }

        if (!in_array($estado, ['activa', 'inactiva'], true)) {
            $errores['estado'] = 'El estado debe ser activa o inactiva.';
        }

        if (!in_array($criterio, ['todas', 'marca', 'gama', 'motocicleta'], true)) {
            $errores['criterio'] = 'El criterio de alcance no es válido.';
        }

        $aplicaciones = [];
        $valoresAplicacion = $entrada['aplicaciones'] ?? [];
        if (!is_array($valoresAplicacion)) {
            $valoresAplicacion = [$valoresAplicacion];
        }
        $valoresAplicacion = array_values(array_unique(array_map(
            static fn($item) => trim((string) $item),
            $valoresAplicacion
        )));
        $valoresAplicacion = array_values(array_filter($valoresAplicacion, static fn($v) => $v !== ''));

        if ($criterio !== 'todas' && $criterio !== '') {
            if ($valoresAplicacion === []) {
                $errores['aplicaciones'] = 'Selecciona al menos un valor para el alcance elegido.';
            } else {
                if ($criterio === 'marca') {
                    $permitidos = array_map('strval', $opciones['marcas'] ?? []);
                } elseif ($criterio === 'gama') {
                    $permitidos = array_map('strval', $opciones['gamas'] ?? []);
                } elseif ($criterio === 'motocicleta') {
                    $permitidos = [];
                    foreach ($opciones['motocicletas'] ?? [] as $moto) {
                        $permitidos[] = (string) $moto['id'];
                    }
                } else {
                    $permitidos = [];
                }

                foreach ($valoresAplicacion as $valorAplicacion) {
                    if (!in_array($valorAplicacion, $permitidos, true)) {
                        $errores['aplicaciones'] =
                            'Uno de los valores de alcance no existe en el catálogo.';
                        break;
                    }
                    $aplicaciones[] = [
                        'criterio' => $criterio,
                        'valor'    => $valorAplicacion,
                    ];
                }
            }
        }

        return [
            'valido'       => $errores === [],
            'errores'      => $errores,
            'datos'        => [
                'nombre'         => $nombre,
                'descripcion'    => $descripcion,
                'tipo_descuento' => $tipo,
                'valor'          => $valorNum,
                'fecha_inicio'   => $fechaInicio,
                'fecha_fin'      => $fechaFin,
                'estado'         => $estado,
            ],
            'aplicaciones' => $aplicaciones,
            'criterio'     => $criterio,
        ];
    }

    private static function fechaValida(string $fecha): bool
    {
        $objeto = DateTime::createFromFormat('Y-m-d', $fecha);

        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    }
}
