<?php
// Taller: registra los servicios y aplica la misma regla de garantía del módulo garantia.
// Se reutiliza GarantiaConsultaModelo::estado() para no duplicar el cálculo.
require_once RAIZ . '/modulos/garantia/modelos/GarantiaConsultaModelo.php';

class MantenimientoModelo extends Modelo
{
    public const VALOR_MANO_OBRA = 30000;
    public const TOPE_GARANTIA   = GarantiaConsultaModelo::MANTENIMIENTOS_GRATIS;

    private const SELECT = "
        SELECT v.id AS id_moto, v.placa, v.chasis,
               m.marca, m.modelo, m.propulsion, m.meses_garantia,
               c.id AS id_cliente, c.nombre AS cliente_nombre, c.documento AS cliente_documento,
               ve.numero_factura, ve.fecha AS fecha_compra
        FROM vehiculo v
        INNER JOIN moto m ON m.id = v.moto_id
        INNER JOIN cliente c ON c.id = v.cliente_id
        INNER JOIN venta ve ON ve.id = v.venta_id";

    // Motos vendidas con su estado de garantía. Filtra por placa, factura, documento o cliente.
    public function listarConGarantia(string $busqueda = ''): array
    {
        $busqueda = trim($busqueda);
        $like     = '%' . $busqueda . '%';

        $motos = $this->consultar(
            self::SELECT . "
            WHERE (? = ''
                   OR v.placa LIKE ?
                   OR c.documento LIKE ?
                   OR c.nombre LIKE ?
                   OR ve.numero_factura LIKE ?)
            ORDER BY v.placa ASC",
            [$busqueda, $like, $like, $like, $like]
        );

        if (!$motos) {
            return [];
        }

        // Todos los servicios de las motos listadas, en una sola consulta.
        $ids        = array_map(static fn(array $moto): int => (int) $moto['id_moto'], $motos);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $servicios  = $this->consultar(
            "SELECT vehiculo_id, tipo FROM mantenimiento WHERE vehiculo_id IN ($marcadores)",
            $ids
        );

        $porVehiculo = [];
        foreach ($servicios as $servicio) {
            $porVehiculo[(int) $servicio['vehiculo_id']][] = $servicio;
        }

        foreach ($motos as &$moto) {
            $moto = $this->conEstado($moto, $porVehiculo[(int) $moto['id_moto']] ?? []);
        }
        unset($moto);

        return $motos;
    }

    // Una moto puntual, con el mismo estado que usa el listado.
    public function buscarPorId(int $idMoto): ?array
    {
        $filas = $this->consultar(self::SELECT . ' WHERE v.id = ? LIMIT 1', [$idMoto]);
        if (!$filas) {
            return null;
        }

        $servicios = $this->consultar(
            'SELECT vehiculo_id, tipo FROM mantenimiento WHERE vehiculo_id = ?',
            [$idMoto]
        );

        return $this->conEstado($filas[0], $servicios);
    }

    // Registra un servicio y aplica la regla de garantía.
    public function registrar(array $post): array
    {
        $idMoto      = (int) ($post['moto_id'] ?? 0);
        $descripcion = trim((string) ($post['descripcion'] ?? ''));

        if ($idMoto <= 0) {
            throw new InvalidArgumentException('Selecciona una moto.');
        }
        if ($descripcion === '') {
            throw new InvalidArgumentException('La descripción del servicio es obligatoria.');
        }

        $longitud = function_exists('mb_strlen') ? mb_strlen($descripcion) : strlen($descripcion);
        if ($longitud > 255) {
            throw new InvalidArgumentException('La descripción no puede pasar de 255 caracteres.');
        }

        $fecha     = $this->validarFecha(trim((string) ($post['fecha'] ?? '')));
        $repuestos = $this->validarCosto($post['costo_repuestos'] ?? '0');

        $moto = $this->buscarPorId($idMoto);
        if ($moto === null) {
            throw new RuntimeException('La motocicleta no existe.');
        }
        if ($fecha < $moto['fecha_compra']) {
            throw new InvalidArgumentException('La fecha del servicio no puede ser anterior a la venta de la moto.');
        }

        $esGarantia = (bool) $moto['aplica_garantia'];
        $manoObra   = (float) $moto['mano_obra'];

        $this->ejecutar(
            "INSERT INTO mantenimiento
                (vehiculo_id, fecha, tipo, descripcion, costo_repuestos, costo_mano_obra)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $idMoto,
                $fecha,
                $esGarantia ? 'garantia' : 'pagado',
                $descripcion,
                $repuestos,
                $manoObra,
            ]
        );

        return [
            'id_moto'         => $idMoto,
            'placa'           => $moto['placa'] ?? 'sin placa',
            'es_garantia'     => $esGarantia,
            'numero_servicio' => (int) $moto['total_servicios'] + 1,
            'total'           => $repuestos + $manoObra,
        ];
    }

    // Completa la fila de la moto con el estado que calcula el módulo garantia.
    private function conEstado(array $moto, array $mantenimientos): array
    {
        $consulta = new GarantiaConsultaModelo();
        $estado   = $consulta->estado($moto, $mantenimientos);

        $usadas    = (int) $estado['mantenimientos_usados'];
        $restantes = (int) $estado['mantenimientos_restantes'];
        $vigente   = (bool) $estado['vigente'];
        $tope      = $usadas >= self::TOPE_GARANTIA;
        $aplica    = $vigente && $restantes > 0;

        $moto['total_servicios']    = count($mantenimientos);
        $moto['garantias_usadas']   = $usadas;
        $moto['garantias_restantes'] = $restantes;
        $moto['aplica_garantia']    = $aplica;
        $moto['vencida_por_tiempo'] = !$vigente;
        $moto['tope_alcanzado']     = $tope;
        $moto['fecha_fin_garantia'] = $estado['fecha_fin'];
        $moto['mano_obra']          = $aplica ? 0.0 : (float) self::VALOR_MANO_OBRA;

        return $moto;
    }

    private function validarFecha(string $fecha): string
    {
        if ($fecha === '') {
            throw new InvalidArgumentException('La fecha del servicio es obligatoria.');
        }

        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($valor === false || $valor->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('La fecha del servicio no es válida.');
        }
        if ($valor > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La fecha del servicio no puede ser futura.');
        }

        return $fecha;
    }

    private function validarCosto($valor): float
    {
        $texto = str_replace(',', '.', trim((string) $valor));
        if ($texto === '' || !is_numeric($texto)) {
            throw new InvalidArgumentException('El costo de repuestos e insumos debe ser un número.');
        }

        $costo = (float) $texto;
        if ($costo < 0) {
            throw new InvalidArgumentException('El costo de repuestos e insumos no puede ser negativo.');
        }

        return round($costo, 2);
    }
}
