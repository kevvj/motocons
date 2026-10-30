<?php

require_once RAIZ . '/modulos/garantia/modelos/GarantiaConsultaModelo.php';

class MantenimientoModelo extends Modelo
{
    public const VALOR_MANO_OBRA = 30000;
    public const TOPE_GARANTIA = GarantiaConsultaModelo::MANTENIMIENTOS_GRATIS;

    private const SELECT_MOTOS = "
        SELECT v.id AS id_moto, v.placa, v.chasis,
               m.marca, m.modelo, m.propulsion, m.meses_garantia,
               c.id AS id_cliente, c.nombre AS cliente_nombre, c.documento AS cliente_documento,
               ve.id AS id_venta, ve.numero_factura, ve.fecha AS fecha_compra
        FROM vehiculo v
        INNER JOIN moto m ON m.id = v.moto_id
        INNER JOIN cliente c ON c.id = v.cliente_id
        INNER JOIN venta ve ON ve.id = v.venta_id
    ";

    public function listarConGarantia(string $busqueda = ''): array
    {
        $busqueda = trim($busqueda);
        $like = '%' . $busqueda . '%';
        $motos = $this->consultar(
            self::SELECT_MOTOS . "
            WHERE (? = ''
                   OR v.placa LIKE ?
                   OR v.chasis LIKE ?
                   OR c.documento LIKE ?
                   OR c.nombre LIKE ?
                   OR ve.numero_factura LIKE ?)
            ORDER BY v.placa ASC",
            [$busqueda, $like, $like, $like, $like, $like]
        );

        foreach ($motos as &$moto) {
            $historial = $this->historial((int) $moto['id_moto']);
            $moto = $this->conEstado($moto, $historial);
            $moto['facturas'] = $historial;
        }
        unset($moto);

        return $motos;
    }

    public function buscarPorId(int $idMoto): ?array
    {
        $filas = $this->consultar(self::SELECT_MOTOS . ' WHERE v.id = ? LIMIT 1', [$idMoto]);
        if (!$filas) {
            return null;
        }

        $historial = $this->historial($idMoto);
        $moto = $this->conEstado($filas[0], $historial);
        $moto['facturas'] = $historial;

        return $moto;
    }

    public function registrar(array $post, int $usuarioId): array
    {
        $idMoto = (int) ($post['moto_id'] ?? 0);
        $fecha = trim((string) ($post['fecha'] ?? ''));
        $tipos = $post['linea_tipo'] ?? [];
        $descripciones = $post['linea_desc'] ?? [];
        $cantidades = $post['linea_cant'] ?? [];
        $valores = $post['linea_valor'] ?? [];

        if ($idMoto <= 0) {
            throw new InvalidArgumentException('Selecciona una moto.');
        }
        if ($usuarioId <= 0) {
            throw new InvalidArgumentException('No se pudo identificar al usuario responsable.');
        }
        $fecha = $this->validarFecha($fecha);

        $lineas = [];
        $totalFilas = max(count($descripciones), count($tipos), count($cantidades), count($valores));
        for ($i = 0; $i < $totalFilas; $i++) {
            $descripcion = trim((string) ($descripciones[$i] ?? ''));
            $valor = $valores[$i] ?? '';

            if ($descripcion === '' && ($valor === '' || $valor === null)) {
                continue;
            }

            $linea = $i + 1;
            $tipo = strtolower(trim((string) ($tipos[$i] ?? 'insumo')));
            $cantidad = $cantidades[$i] ?? '';
            if ($descripcion === '') {
                throw new InvalidArgumentException("En la línea {$linea} falta la descripción.");
            }
            if (!in_array($tipo, ['repuesto', 'insumo'], true)) {
                throw new InvalidArgumentException("El tipo de la línea {$linea} no es válido.");
            }
            if (!is_numeric($cantidad) || (int) $cantidad <= 0) {
                throw new InvalidArgumentException("En la línea {$linea}, la cantidad debe ser mayor que cero.");
            }
            if (!is_numeric($valor) || (float) $valor < 0) {
                throw new InvalidArgumentException("En la línea {$linea}, el valor unitario no puede ser negativo.");
            }

            $lineas[] = [
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'cantidad' => (int) $cantidad,
                'valor_unitario' => round((float) $valor, 2),
            ];
        }

        if (!$lineas) {
            throw new InvalidArgumentException('Agrega al menos un repuesto o insumo válido.');
        }

        $this->db->beginTransaction();
        try {
            $stmtMoto = $this->db->prepare(
                self::SELECT_MOTOS . ' WHERE v.id = ? FOR UPDATE'
            );
            $stmtMoto->execute([$idMoto]);
            $moto = $stmtMoto->fetch();
            if (!$moto) {
                throw new RuntimeException('La motocicleta no existe.');
            }
            if ($fecha < $moto['fecha_compra']) {
                throw new InvalidArgumentException('La fecha no puede ser anterior a la compra de la moto.');
            }

            $stmtUsuario = $this->db->prepare(
                "SELECT id FROM usuario WHERE id = ? AND activo = 1 AND rol IN ('mecanico', 'admin')"
            );
            $stmtUsuario->execute([$usuarioId]);
            if (!$stmtUsuario->fetch()) {
                throw new RuntimeException('El usuario no es un mecánico o administrador activo.');
            }

            $stmtCount = $this->db->prepare(
                'SELECT COUNT(*) FROM mantenimiento WHERE vehiculo_id = ?'
            );
            $stmtCount->execute([$idMoto]);
            $numeroServicio = (int) $stmtCount->fetchColumn() + 1;
            $esGarantia = $numeroServicio <= self::TOPE_GARANTIA;
            $manoObra = $esGarantia ? 0.0 : (float) self::VALOR_MANO_OBRA;

            $subtotal = array_reduce(
                $lineas,
                static fn(float $total, array $linea): float =>
                    $total + ($linea['cantidad'] * $linea['valor_unitario']),
                0.0
            );
            $total = round($subtotal + $manoObra, 2);
            $descripcionCompleta = implode('; ', array_column($lineas, 'descripcion'));
            $descripcion = function_exists('mb_substr')
                ? mb_substr($descripcionCompleta, 0, 255)
                : substr($descripcionCompleta, 0, 255);

            $stmtMantenimiento = $this->db->prepare(
                'INSERT INTO mantenimiento
                    (numero_servicio, vehiculo_id, fecha, tipo, descripcion,
                     costo_repuestos, costo_mano_obra, total, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmtMantenimiento->execute([
                $numeroServicio,
                $idMoto,
                $fecha,
                $esGarantia ? 'garantia' : 'pagado',
                $descripcion,
                $subtotal,
                $manoObra,
                $total,
                $usuarioId,
            ]);
            $idMantenimiento = (int) $this->db->lastInsertId();

            $stmtDetalle = $this->db->prepare(
                'INSERT INTO detalle_mantenimiento
                    (mantenimiento_id, tipo, descripcion, cantidad, valor_unitario)
                 VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($lineas as $linea) {
                $stmtDetalle->execute([
                    $idMantenimiento,
                    $linea['tipo'],
                    $linea['descripcion'],
                    $linea['cantidad'],
                    $linea['valor_unitario'],
                ]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }

        return [
            'id_moto' => $idMoto,
            'placa' => $moto['placa'] ?? 'sin placa',
            'es_garantia' => $esGarantia,
            'numero_servicio' => $numeroServicio,
            'total' => $total,
        ];
    }

    private function historial(int $idMoto): array
    {
        $mantenimientos = $this->consultar(
            "SELECT mant.id, mant.numero_servicio, mant.tipo, mant.fecha,
                    mant.costo_mano_obra, mant.total, u.nombre AS mecanico_nombre
             FROM mantenimiento mant
             LEFT JOIN usuario u ON u.id = mant.usuario_id
             WHERE mant.vehiculo_id = ?
             ORDER BY mant.numero_servicio ASC, mant.id ASC",
            [$idMoto]
        );
        if (!$mantenimientos) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT id, tipo, descripcion, cantidad, valor_unitario
             FROM detalle_mantenimiento
             WHERE mantenimiento_id = ?
             ORDER BY id ASC'
        );
        foreach ($mantenimientos as &$mantenimiento) {
            $stmt->execute([(int) $mantenimiento['id']]);
            $mantenimiento['lineas'] = $stmt->fetchAll();
        }
        unset($mantenimiento);

        return $mantenimientos;
    }

    private function conEstado(array $moto, array $historial): array
    {
        $garantiasUsadas = count(array_filter(
            $historial,
            static fn(array $mantenimiento): bool => $mantenimiento['tipo'] === 'garantia'
        ));
        $fechaFin = (new DateTimeImmutable($moto['fecha_compra']))
            ->modify('+' . (int) $moto['meses_garantia'] . ' months');
        $vigentePorFecha = new DateTimeImmutable('today') <= $fechaFin;
        $usadas = min($garantiasUsadas, self::TOPE_GARANTIA);
        $garantiaDisponible = $vigentePorFecha && $usadas < self::TOPE_GARANTIA;

        $moto['total_servicios'] = count($historial);
        $moto['garantias_usadas'] = $usadas;
        $moto['garantias_restantes'] = $vigentePorFecha
            ? max(0, self::TOPE_GARANTIA - $usadas)
            : 0;
        $moto['garantia_vigente'] = $garantiaDisponible;
        $moto['garantia_vencida_por_fecha'] = !$vigentePorFecha;
        $moto['fecha_fin_garantia'] = $fechaFin->format('Y-m-d');
        return $moto;
    }

    private function validarFecha(string $fecha): string
    {
        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($valor === false || $valor->format('Y-m-d') !== $fecha) {
            throw new InvalidArgumentException('La fecha del servicio no es válida.');
        }
        if ($valor > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('La fecha del servicio no puede ser futura.');
        }
        return $fecha;
    }
}
