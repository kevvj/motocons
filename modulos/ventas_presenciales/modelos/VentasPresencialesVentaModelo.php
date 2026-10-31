<?php
require_once __DIR__ . '/VentasPresencialesClienteModelo.php';

// Ventas en tienda: registro, factura consecutiva y ciclo de vida de la venta.
// Financiación reutiliza confirmarPago() y cambiarEstado() con:
//   require_once RAIZ . '/modulos/ventas_presenciales/modelos/VentasPresencialesVentaModelo.php';
class VentasPresencialesVentaModelo extends Modelo
{
    public const ESTADOS = [
        'PENDIENTE' => 'Pendiente de pago',
        'EN_CREDITO' => 'En crédito',
        'PAGADA' => 'Pagada',
        'ENTREGADA' => 'Entregada',
        'CERRADA' => 'Cerrada',
        'ANULADA' => 'Anulada',
    ];

    // Cambios de estado permitidos. Cualquier otro se rechaza.
    public const TRANSICIONES = [
        'PENDIENTE' => ['PAGADA', 'EN_CREDITO', 'ANULADA'],
        'EN_CREDITO' => ['PAGADA', 'PENDIENTE', 'ANULADA'],
        'PAGADA' => ['ENTREGADA', 'ANULADA'],
        'ENTREGADA' => ['CERRADA'],
        'CERRADA' => [],
        'ANULADA' => [],
    ];

    public const MAX_UNIDADES = 10;
    private const PREFIJO_FACTURA = 'FV-';

    private const SELECT_VENTA = "SELECT ve.id, ve.numero_factura, ve.fecha, ve.canal, ve.total, ve.estado,
                c.id AS cliente_id, c.nombre AS cliente, c.documento, c.correo, c.telefono,
                u.nombre AS vendedor,
                (SELECT COUNT(*) FROM detalle_venta d WHERE d.venta_id = ve.id) AS unidades
         FROM venta ve
         JOIN cliente c ON c.id = ve.cliente_id
         LEFT JOIN usuario u ON u.id = ve.usuario_id";

    // Ventas en tienda, la más reciente primero. Busca por factura, documento o nombre del cliente.
    public function listar(string $busqueda = '', string $estado = ''): array
    {
        $like = '%' . $busqueda . '%';
        return $this->consultar(
            self::SELECT_VENTA . "
            WHERE ve.canal = 'tienda'
              AND (? = '' OR ve.estado = ?)
              AND (? = '' OR ve.numero_factura LIKE ? OR c.documento LIKE ? OR c.nombre LIKE ?)
            ORDER BY ve.id DESC
            LIMIT 200",
            [$estado, $estado, $busqueda, $like, $like, $like]
        );
    }

    // Cantidad de ventas en tienda por estado: ['PENDIENTE' => 2, ...]
    public function contarPorEstado(): array
    {
        $conteo = array_fill_keys(array_keys(self::ESTADOS), 0);
        $filas = $this->consultar("SELECT estado, COUNT(*) AS total FROM venta WHERE canal = 'tienda' GROUP BY estado");
        foreach ($filas as $fila) {
            $conteo[$fila['estado']] = (int) $fila['total'];
        }
        return $conteo;
    }

    // Venta en tienda con sus motos, o null si no existe.
    public function buscar(int $ventaId): ?array
    {
        $filas = $this->consultar(self::SELECT_VENTA . " WHERE ve.id = ? AND ve.canal = 'tienda'", [$ventaId]);
        if (!$filas) {
            return null;
        }
        $venta = $filas[0];
        $venta['detalle'] = $this->unidades($ventaId);
        return $venta;
    }

    public function buscarPorFactura(string $factura): ?array
    {
        $filas = $this->consultar("SELECT id FROM venta WHERE numero_factura = ? AND canal = 'tienda'", [strtoupper($factura)]);
        return $filas ? $this->buscar((int) $filas[0]['id']) : null;
    }

    public function unidades(int $ventaId): array
    {
        return $this->consultar(
            'SELECT d.id, d.moto_id, d.chasis, d.placa, d.precio, d.vehiculo_id,
                    m.marca, m.modelo, m.propulsion, m.meses_garantia
             FROM detalle_venta d
             JOIN moto m ON m.id = d.moto_id
             WHERE d.venta_id = ?
             ORDER BY d.id',
            [$ventaId]
        );
    }

    // Modelos de moto que se pueden vender (tabla moto de garantía).
    public function catalogo(): array
    {
        return $this->consultar(
            'SELECT id, marca, modelo, propulsion, precio, meses_garantia FROM moto ORDER BY marca, modelo'
        );
    }

    // Guarda cliente (si es nuevo), venta y motos en una transacción. Devuelve el id de la venta.
    // La venta queda sin factura y sin vehiculo hasta que se confirme el pago.
    public function registrar(array $post, int $usuarioId): int
    {
        $cliente = $this->leerCliente($post);
        $forma = $this->texto($post['forma_pago'] ?? '');
        if (!in_array($forma, ['contado', 'credito'], true)) {
            throw new InvalidArgumentException('Elige si la venta es de contado o financiada.');
        }
        $unidades = $this->leerUnidades($post);
        $total = array_sum(array_column($unidades, 'precio'));

        $this->db->beginTransaction();
        try {
            foreach ($unidades as $unidad) {
                $this->verificarDisponible($unidad['chasis'], $unidad['placa']);
            }
            $clienteId = (new VentasPresencialesClienteModelo())->obtenerOCrear($cliente);

            $this->ejecutar(
                "INSERT INTO venta (numero_factura, fecha, cliente_id, usuario_id, canal, total, estado)
                 VALUES (NULL, CURDATE(), ?, ?, 'tienda', ?, ?)",
                [$clienteId, $usuarioId ?: null, $total, $forma === 'credito' ? 'EN_CREDITO' : 'PENDIENTE']
            );
            $ventaId = (int) $this->db->lastInsertId();

            $detalle = $this->db->prepare(
                'INSERT INTO detalle_venta (venta_id, moto_id, chasis, placa, precio) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($unidades as $unidad) {
                $detalle->execute([$ventaId, $unidad['moto_id'], $unidad['chasis'], $unidad['placa'] ?: null, $unidad['precio']]);
            }
            $this->db->commit();
            return $ventaId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Pasa la venta a PAGADA: asigna la factura consecutiva y crea sus motos en vehiculo,
    // con lo que aparecen en garantía y taller. Devuelve el número de factura.
    // Si quien llama ya abrió una transacción, se usa la suya.
    public function confirmarPago(int $ventaId): string
    {
        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }
        try {
            $venta = $this->bloquear($ventaId);
            $this->validarTransicion($venta['estado'], 'PAGADA');
            $unidades = $this->unidades($ventaId);
            if (!$unidades) {
                throw new InvalidArgumentException('La venta no tiene motos registradas.');
            }

            // La garantía se cuenta desde la fecha de la factura.
            $factura = $this->siguienteFactura();
            $this->ejecutar(
                "UPDATE venta SET estado = 'PAGADA', numero_factura = ?, fecha = CURDATE() WHERE id = ?",
                [$factura, $ventaId]
            );

            foreach ($unidades as $unidad) {
                if ($this->consultar('SELECT 1 FROM vehiculo WHERE chasis = ?', [$unidad['chasis']])) {
                    throw new InvalidArgumentException("El chasis {$unidad['chasis']} ya fue vendido en otra venta.");
                }
                if ($unidad['placa'] !== null && $this->consultar('SELECT 1 FROM vehiculo WHERE placa = ?', [$unidad['placa']])) {
                    throw new InvalidArgumentException("La placa {$unidad['placa']} ya está registrada en otra moto.");
                }
                $this->ejecutar(
                    'INSERT INTO vehiculo (placa, chasis, moto_id, cliente_id, venta_id) VALUES (?, ?, ?, ?, ?)',
                    [$unidad['placa'], $unidad['chasis'], $unidad['moto_id'], $venta['cliente_id'], $ventaId]
                );
                $this->ejecutar(
                    'UPDATE detalle_venta SET vehiculo_id = ? WHERE id = ?',
                    [(int) $this->db->lastInsertId(), $unidad['id']]
                );
            }

            if ($propia) {
                $this->db->commit();
            }
            return $factura;
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Cambia el estado si la transición está permitida. Devuelve la factura cuando pasa a PAGADA.
    public function cambiarEstado(int $ventaId, string $nuevo): ?string
    {
        if (!isset(self::ESTADOS[$nuevo])) {
            throw new InvalidArgumentException('El estado no es válido.');
        }
        if ($nuevo === 'PAGADA') {
            return $this->confirmarPago($ventaId);
        }

        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }
        try {
            $venta = $this->bloquear($ventaId);
            $this->validarTransicion($venta['estado'], $nuevo);
            if ($nuevo === 'ANULADA' && $venta['estado'] === 'PAGADA') {
                $this->quitarVehiculos($ventaId);
            }
            $this->ejecutar('UPDATE venta SET estado = ? WHERE id = ?', [$nuevo, $ventaId]);
            if ($propia) {
                $this->db->commit();
            }
            return null;
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Anular una venta pagada saca sus motos de garantía y taller.
    // Si alguna ya pasó por el taller no se anula. La factura se conserva.
    private function quitarVehiculos(int $ventaId): void
    {
        $servicios = $this->consultar(
            'SELECT COUNT(*) AS total FROM mantenimiento mt JOIN vehiculo v ON v.id = mt.vehiculo_id WHERE v.venta_id = ?',
            [$ventaId]
        );
        if ((int) $servicios[0]['total'] > 0) {
            throw new InvalidArgumentException('No se puede anular: una de sus motos ya tiene servicios en el taller.');
        }
        $this->ejecutar('UPDATE detalle_venta SET vehiculo_id = NULL WHERE venta_id = ?', [$ventaId]);
        $this->ejecutar('DELETE FROM vehiculo WHERE venta_id = ?', [$ventaId]);
    }

    private function bloquear(int $ventaId): array
    {
        $filas = $this->consultar('SELECT id, estado, cliente_id FROM venta WHERE id = ? FOR UPDATE', [$ventaId]);
        if (!$filas) {
            throw new InvalidArgumentException('La venta no existe.');
        }
        return $filas[0];
    }

    private function validarTransicion(string $actual, string $nuevo): void
    {
        if (!in_array($nuevo, self::TRANSICIONES[$actual] ?? [], true)) {
            throw new InvalidArgumentException(
                'Una venta en estado "' . self::ESTADOS[$actual] . '" no puede pasar a "' . self::ESTADOS[$nuevo] . '".'
            );
        }
    }

    // FV-0005, FV-0006... Bloquea las facturas para que dos ventas no reciban el mismo número.
    private function siguienteFactura(): string
    {
        $filas = $this->consultar(
            'SELECT MAX(CAST(SUBSTRING(numero_factura, ?) AS UNSIGNED)) AS ultimo
             FROM venta WHERE numero_factura LIKE ? FOR UPDATE',
            [strlen(self::PREFIJO_FACTURA) + 1, self::PREFIJO_FACTURA . '%']
        );
        return sprintf('%s%04d', self::PREFIJO_FACTURA, (int) $filas[0]['ultimo'] + 1);
    }

    // Un chasis o placa no puede estar en una moto vendida ni en otra venta sin anular.
    private function verificarDisponible(string $chasis, string $placa): void
    {
        $repetido = 'SELECT 1 FROM vehiculo WHERE %1$s = ?
                     UNION ALL
                     SELECT 1 FROM detalle_venta d JOIN venta ve ON ve.id = d.venta_id
                     WHERE d.%1$s = ? AND ve.estado <> \'ANULADA\'
                     LIMIT 1';
        if ($this->consultar(sprintf($repetido, 'chasis'), [$chasis, $chasis])) {
            throw new InvalidArgumentException("El chasis $chasis ya está registrado en otra venta.");
        }
        if ($placa !== '' && $this->consultar(sprintf($repetido, 'placa'), [$placa, $placa])) {
            throw new InvalidArgumentException("La placa $placa ya está registrada en otra moto.");
        }
    }

    private function leerCliente(array $post): array
    {
        $cliente = [
            'documento' => preg_replace('/[\s.]/', '', $this->texto($post['documento'] ?? '')),
            'nombre' => preg_replace('/\s+/u', ' ', $this->texto($post['nombre'] ?? '')),
            'correo' => strtolower($this->texto($post['correo'] ?? '')),
            'telefono' => $this->texto($post['telefono'] ?? ''),
        ];
        if ($cliente['documento'] === '') {
            throw new InvalidArgumentException('Escribe el documento del cliente.');
        }
        if (!preg_match('/^[0-9A-Za-z-]{5,20}$/', $cliente['documento'])) {
            throw new InvalidArgumentException('El documento debe tener entre 5 y 20 letras o números.');
        }
        if (!preg_match('/^.{0,120}$/u', $cliente['nombre'])) {
            throw new InvalidArgumentException('El nombre del cliente es demasiado largo (máximo 120 caracteres).');
        }
        if ($cliente['correo'] !== '' && (strlen($cliente['correo']) > 150 || !filter_var($cliente['correo'], FILTER_VALIDATE_EMAIL))) {
            throw new InvalidArgumentException('El correo del cliente no es válido.');
        }
        if ($cliente['telefono'] !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $cliente['telefono'])) {
            throw new InvalidArgumentException('El teléfono debe tener entre 7 y 20 números.');
        }
        return $cliente;
    }

    // Lee las filas de motos del formulario. Las filas vacías se ignoran; el precio sale del catálogo.
    private function leerUnidades(array $post): array
    {
        $catalogo = array_column($this->catalogo(), null, 'id');
        $motos = (array) ($post['unidad_moto'] ?? []);
        $chasises = (array) ($post['unidad_chasis'] ?? []);
        $placas = (array) ($post['unidad_placa'] ?? []);

        $unidades = [];
        $vistos = [];
        $filas = max(count($motos), count($chasises), count($placas));
        for ($i = 0; $i < $filas; $i++) {
            $motoId = (int) $this->texto($motos[$i] ?? '');
            $chasis = strtoupper(preg_replace('/[\s-]/', '', $this->texto($chasises[$i] ?? '')));
            $placa = strtoupper(preg_replace('/[\s-]/', '', $this->texto($placas[$i] ?? '')));
            if ($motoId === 0 && $chasis === '' && $placa === '') {
                continue;
            }

            $n = count($unidades) + 1;
            if (!isset($catalogo[$motoId])) {
                throw new InvalidArgumentException("Elige el modelo de la moto $n.");
            }
            if ($chasis === '') {
                throw new InvalidArgumentException("Escribe el número de chasis de la moto $n.");
            }
            if (!preg_match('/^[A-Z0-9]{5,30}$/', $chasis)) {
                throw new InvalidArgumentException("El chasis de la moto $n debe tener entre 5 y 30 letras o números.");
            }
            if ($placa !== '' && !preg_match('/^[A-Z0-9]{5,10}$/', $placa)) {
                throw new InvalidArgumentException("La placa de la moto $n no es válida (ej.: ABC12D).");
            }
            if (isset($vistos['c' . $chasis]) || ($placa !== '' && isset($vistos['p' . $placa]))) {
                throw new InvalidArgumentException("La moto $n repite un chasis o una placa de otra moto de esta venta.");
            }
            $vistos['c' . $chasis] = true;
            if ($placa !== '') {
                $vistos['p' . $placa] = true;
            }

            $unidades[] = [
                'moto_id' => $motoId,
                'chasis' => $chasis,
                'placa' => $placa,
                'precio' => (float) $catalogo[$motoId]['precio'],
            ];
        }

        if (!$unidades) {
            throw new InvalidArgumentException('Agrega al menos una moto a la venta.');
        }
        if (count($unidades) > self::MAX_UNIDADES) {
            throw new InvalidArgumentException('Una venta puede tener máximo ' . self::MAX_UNIDADES . ' motos.');
        }
        return $unidades;
    }

    // Valor de un campo del formulario como texto (ignora arreglos enviados a propósito).
    private function texto($valor): string
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }
}
