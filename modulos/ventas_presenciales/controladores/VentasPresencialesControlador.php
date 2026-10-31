<?php
// Ventas en tienda (punto de venta). Solo vendedores y administradores.
// La garantía y el taller leen las motos vendidas de vehiculo: no se duplican aquí.
class VentasPresencialesControlador extends Controlador
{
    private const ROLES = ['vendedor', 'admin'];

    // Texto del botón para pasar a cada estado.
    private const ACCIONES = [
        'PAGADA' => 'Confirmar pago',
        'EN_CREDITO' => 'Pasar a crédito',
        'PENDIENTE' => 'Pasar a contado',
        'ENTREGADA' => 'Registrar entrega',
        'CERRADA' => 'Cerrar venta',
        'ANULADA' => 'Anular venta',
    ];

    // index.php?r=ventas_presenciales/index&q=1067&estado=PENDIENTE
    public function index(): void
    {
        $this->requiereLogin(self::ROLES);
        $modelo = $this->modelo('VentasPresencialesVentaModelo');
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $estado = (string) ($_GET['estado'] ?? '');
        if (!isset(VentasPresencialesVentaModelo::ESTADOS[$estado])) {
            $estado = '';
        }

        $this->vista('index', [
            'ventas' => $modelo->listar($busqueda, $estado),
            'conteo' => $modelo->contarPorEstado(),
            'estados' => VentasPresencialesVentaModelo::ESTADOS,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'mensaje' => $this->tomarMensaje(),
        ]);
    }

    // index.php?r=ventas_presenciales/nueva
    public function nueva(): void
    {
        $this->requiereLogin(self::ROLES);
        $this->formulario();
    }

    // POST index.php?r=ventas_presenciales/registrar
    public function registrar(): void
    {
        $this->requiereLogin(self::ROLES);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('ventas_presenciales/nueva');
        }
        if (!$this->tokenValido()) {
            $this->formulario($_POST, 'La página expiró. Revisa los datos y vuelve a registrar la venta.');
            return;
        }

        try {
            $ventaId = $this->modelo('VentasPresencialesVentaModelo')->registrar($_POST, (int) $this->usuario()['id']);
        } catch (InvalidArgumentException $e) {
            $this->formulario($_POST, $e->getMessage());
            return;
        } catch (PDOException $e) {
            $this->formulario($_POST, 'No se pudo guardar la venta. Revisa los datos e inténtalo de nuevo.');
            return;
        }

        $credito = ($_POST['forma_pago'] ?? '') === 'credito';
        $this->darMensaje($credito
            ? 'Venta registrada en crédito. Cuando financiación apruebe el crédito, la venta pasa a pagada y se factura.'
            : 'Venta registrada. Confirma el pago para generar la factura.');
        $this->redirigir('ventas_presenciales/ver&id=' . $ventaId);
    }

    // index.php?r=ventas_presenciales/ver&id=5
    public function ver(): void
    {
        $this->requiereLogin(self::ROLES);
        $venta = $this->modelo('VentasPresencialesVentaModelo')->buscar((int) ($_GET['id'] ?? 0));
        if ($venta === null) {
            http_response_code(404);
        }

        $this->vista('ver', [
            'venta' => $venta,
            'estados' => VentasPresencialesVentaModelo::ESTADOS,
            'acciones' => $venta ? $this->accionesPara($venta['estado']) : [],
            'mensaje' => $this->tomarMensaje(),
            'error' => $this->tomarMensaje('error'),
            'token' => $this->token(),
        ]);
    }

    // POST index.php?r=ventas_presenciales/cambiarEstado (id, estado)
    public function cambiarEstado(): void
    {
        $this->requiereLogin(self::ROLES);
        $ventaId = (int) ($_POST['id'] ?? 0);
        $nuevo = (string) ($_POST['estado'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $ventaId <= 0) {
            $this->redirigir('ventas_presenciales/index');
        }

        if (!$this->tokenValido()) {
            $this->darMensaje('La página expiró. Vuelve a intentarlo.', 'error');
        } elseif ($nuevo === 'ANULADA' && $this->usuario()['rol'] !== 'admin') {
            $this->darMensaje('Solo un administrador puede anular una venta.', 'error');
        } else {
            try {
                $factura = $this->modelo('VentasPresencialesVentaModelo')->cambiarEstado($ventaId, $nuevo);
                $this->darMensaje($this->mensajeEstado($nuevo, $factura));
            } catch (InvalidArgumentException $e) {
                $this->darMensaje($e->getMessage(), 'error');
            } catch (PDOException $e) {
                $this->darMensaje('No se pudo cambiar el estado de la venta. Inténtalo de nuevo.', 'error');
            }
        }
        $this->redirigir('ventas_presenciales/ver&id=' . $ventaId);
    }

    // Para el formulario: index.php?r=ventas_presenciales/cliente&documento=1067123456
    public function cliente(): void
    {
        $this->requiereLogin(self::ROLES);
        $documento = preg_replace('/[\s.]/', '', (string) ($_GET['documento'] ?? ''));
        $cliente = $documento === ''
            ? null
            : $this->modelo('VentasPresencialesClienteModelo')->buscarPorDocumento($documento);
        $this->json(['encontrado' => $cliente !== null, 'cliente' => $cliente]);
    }

    // API para otros módulos: index.php?r=ventas_presenciales/api&id=5 (o &factura=FV-0005)
    public function api(): void
    {
        $this->requiereLogin(self::ROLES);
        $modelo = $this->modelo('VentasPresencialesVentaModelo');
        if (!empty($_GET['id'])) {
            $venta = $modelo->buscar((int) $_GET['id']);
        } elseif (!empty($_GET['factura'])) {
            $venta = $modelo->buscarPorFactura(trim((string) $_GET['factura']));
        } else {
            $this->json(['error' => 'Envía id o factura'], 400);
            return;
        }
        $venta === null
            ? $this->json(['error' => 'No se encontró la venta'], 404)
            : $this->json($venta);
    }

    private function formulario(array $anterior = [], ?string $error = null): void
    {
        $this->vista('nueva', [
            'catalogo' => $this->modelo('VentasPresencialesVentaModelo')->catalogo(),
            'anterior' => $this->datosAnteriores($anterior),
            'maxUnidades' => VentasPresencialesVentaModelo::MAX_UNIDADES,
            'error' => $error,
            'token' => $this->token(),
        ]);
    }

    // Lo que escribió el vendedor, para no perderlo si hay un error.
    private function datosAnteriores(array $post): array
    {
        $texto = static fn($valor): string => is_scalar($valor) ? trim((string) $valor) : '';
        $unidades = [];
        $motos = (array) ($post['unidad_moto'] ?? []);
        foreach (array_keys($motos) as $i) {
            $unidades[] = [
                'moto_id' => (int) $texto($motos[$i]),
                'chasis' => $texto($post['unidad_chasis'][$i] ?? ''),
                'placa' => $texto($post['unidad_placa'][$i] ?? ''),
            ];
        }
        return [
            'documento' => $texto($post['documento'] ?? ''),
            'nombre' => $texto($post['nombre'] ?? ''),
            'correo' => $texto($post['correo'] ?? ''),
            'telefono' => $texto($post['telefono'] ?? ''),
            'forma_pago' => $texto($post['forma_pago'] ?? 'contado'),
            'unidades' => $unidades ?: [['moto_id' => 0, 'chasis' => '', 'placa' => '']],
        ];
    }

    // Estados a los que puede pasar la venta, con el texto de su botón. Anular solo para admin.
    private function accionesPara(string $estado): array
    {
        $acciones = [];
        foreach (VentasPresencialesVentaModelo::TRANSICIONES[$estado] ?? [] as $destino) {
            if ($destino !== 'ANULADA' || $this->usuario()['rol'] === 'admin') {
                $acciones[$destino] = self::ACCIONES[$destino];
            }
        }
        return $acciones;
    }

    private function mensajeEstado(string $estado, ?string $factura): string
    {
        return match ($estado) {
            'PAGADA' => "Pago confirmado. Factura $factura. Las motos ya aparecen en garantía y en el taller.",
            'EN_CREDITO' => 'La venta pasó a crédito. Financiación debe aprobarlo para facturar.',
            'PENDIENTE' => 'La venta pasó a contado. Confirma el pago para generar la factura.',
            'ENTREGADA' => 'Entrega registrada.',
            'CERRADA' => 'Venta cerrada.',
            'ANULADA' => 'Venta anulada.',
            default => 'Estado actualizado.',
        };
    }

    // Mensajes que sobreviven a la redirección (POST -> redirect -> GET).
    private function darMensaje(string $texto, string $tipo = 'exito'): void
    {
        $_SESSION['ventas_presenciales_' . $tipo] = $texto;
    }

    private function tomarMensaje(string $tipo = 'exito'): ?string
    {
        $texto = $_SESSION['ventas_presenciales_' . $tipo] ?? null;
        unset($_SESSION['ventas_presenciales_' . $tipo]);
        return $texto;
    }

    // Token contra envíos de formularios desde otros sitios.
    private function token(): string
    {
        return $_SESSION['ventas_presenciales_token'] ??= bin2hex(random_bytes(16));
    }

    private function tokenValido(): bool
    {
        return hash_equals($this->token(), (string) ($_POST['token'] ?? ''));
    }
}
