<?php
require_once __DIR__ . '/../modelos/PromocionesDescuento.php';
require_once __DIR__ . '/../modelos/PromocionesValidador.php';

// Administración de promociones y catálogo con descuento.
class PromocionesControlador extends Controlador
{
    private const ROLES_ADMIN = ['admin', 'vendedor'];

    // index.php?r=promociones/index&estado=VIGENTE&q=honda
    public function index(): void
    {
        $this->requiereLogin(self::ROLES_ADMIN);

        $modelo = $this->modelo('PromocionesPromocionModelo');
        $estado = strtoupper(trim((string) ($_GET['estado'] ?? '')));
        if (!in_array($estado, ['VIGENTE', 'PROGRAMADA', 'VENCIDA', 'INACTIVA'], true)) {
            $estado = '';
        }
        $busqueda = trim((string) ($_GET['q'] ?? ''));

        $this->vista('index', [
            'promociones'  => $modelo->listar($estado !== '' ? $estado : null, $busqueda),
            'estadisticas' => $modelo->estadisticas(),
            'estado'       => $estado,
            'busqueda'     => $busqueda,
            'mensaje'      => $this->tomarFlash(),
        ]);
    }

    // index.php?r=promociones/crear
    public function crear(): void
    {
        $this->requiereLogin(self::ROLES_ADMIN);

        $modelo = $this->modelo('PromocionesPromocionModelo');
        $opciones = $modelo->opcionesAlcance();
        $errores = [];
        $datos = $this->datosVacios();
        $aplicaciones = [];
        $criterio = 'todas';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = PromocionesValidador::validar($_POST, $opciones);
            $datos = $resultado['datos'];
            $criterio = $resultado['criterio'];
            $aplicaciones = array_column($resultado['aplicaciones'], 'valor');

            if ($resultado['valido']) {
                try {
                    $id = $modelo->crear($resultado['datos'], $resultado['aplicaciones']);
                    $this->guardarFlash('exito', 'Promoción #' . $id . ' creada correctamente.');
                    $this->redirigir('promociones/index');
                } catch (Throwable $e) {
                    $errores['general'] = 'No fue posible guardar la promoción.';
                }
            } else {
                $errores = $resultado['errores'];
            }
        }

        $this->vista('formulario', [
            'datos'         => $datos,
            'errores'       => $errores,
            'aplicaciones'  => $aplicaciones,
            'criterio'      => $criterio,
            'opciones'      => $opciones,
            'esEdicion'     => false,
            'accion'        => 'index.php?r=promociones/crear',
        ]);
    }

    // index.php?r=promociones/editar&id=1
    public function editar(): void
    {
        $this->requiereLogin(self::ROLES_ADMIN);

        $modelo = $this->modelo('PromocionesPromocionModelo');
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $promocion = $modelo->obtener($id);

        if ($promocion === null) {
            http_response_code(404);
            echo 'La promoción solicitada no existe.';
            return;
        }

        $opciones = $modelo->opcionesAlcance();
        $errores = [];
        $datos = $promocion;
        $criterio = $promocion['aplica'][0]['criterio'] ?? 'todas';
        $aplicaciones = array_column($promocion['aplica'], 'valor');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resultado = PromocionesValidador::validar($_POST, $opciones);
            $datos = $resultado['datos'];
            $datos['id'] = $id;
            $criterio = $resultado['criterio'];
            $aplicaciones = array_column($resultado['aplicaciones'], 'valor');

            if ($resultado['valido']) {
                try {
                    $modelo->actualizar($id, $resultado['datos'], $resultado['aplicaciones']);
                    $this->guardarFlash('exito', 'Promoción #' . $id . ' actualizada correctamente.');
                    $this->redirigir('promociones/index');
                } catch (Throwable $e) {
                    $errores['general'] = 'No fue posible actualizar la promoción.';
                }
            } else {
                $errores = $resultado['errores'];
            }
        }

        $this->vista('formulario', [
            'datos'         => $datos,
            'errores'       => $errores,
            'aplicaciones'  => $aplicaciones,
            'criterio'      => $criterio,
            'opciones'      => $opciones,
            'esEdicion'     => true,
            'accion'        => 'index.php?r=promociones/editar&id=' . $id,
        ]);
    }

    // POST index.php?r=promociones/estado
    public function estado(): void
    {
        $this->requiereLogin(self::ROLES_ADMIN);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('promociones/index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $estado = strtolower(trim((string) ($_POST['estado'] ?? '')));

        if (!in_array($estado, ['activa', 'inactiva'], true)) {
            $this->guardarFlash('error', 'El estado solicitado no es válido.');
            $this->redirigir('promociones/index');
        }

        $modelo = $this->modelo('PromocionesPromocionModelo');
        if ($modelo->cambiarEstado($id, $estado)) {
            $this->guardarFlash('exito', 'Promoción #' . $id . ' marcada como ' . $estado . '.');
        } else {
            $this->guardarFlash('error', 'No se encontró la promoción #' . $id . '.');
        }

        $this->redirigir('promociones/index');
    }

    // POST index.php?r=promociones/eliminar
    public function eliminar(): void
    {
        $this->requiereLogin(self::ROLES_ADMIN);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('promociones/index');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $modelo = $this->modelo('PromocionesPromocionModelo');

        if ($modelo->eliminar($id)) {
            $this->guardarFlash('exito', 'Promoción #' . $id . ' eliminada correctamente.');
        } else {
            $this->guardarFlash('error', 'No se encontró la promoción #' . $id . '.');
        }

        $this->redirigir('promociones/index');
    }

    // index.php?r=promociones/catalogo
    public function catalogo(): void
    {
        $this->requiereLogin();

        $modelo = $this->modelo('PromocionesMotoModelo');
        $filtros = [
            'marca'      => trim((string) ($_GET['marca'] ?? '')),
            'gama'       => trim((string) ($_GET['gama'] ?? '')),
            'propulsion' => trim((string) ($_GET['propulsion'] ?? '')),
            'busqueda'   => trim((string) ($_GET['q'] ?? '')),
        ];

        $this->vista('catalogo', [
            'catalogo'     => $modelo->catalogoConDescuento($filtros),
            'promociones'  => $this->modelo('PromocionesPromocionModelo')->vigentes(),
            'filtros'      => $filtros,
            'marcas'       => $modelo->marcas(),
            'gamas'        => $modelo->gamas(),
            'propulsiones' => $modelo->propulsiones(),
            'puedeAdmin'   => $this->puedeAdmin(),
        ]);
    }

    // GET index.php?r=promociones/api&vigentes=1 | &id=1 | (lista)
    public function api(): void
    {
        $this->requiereLogin();

        $modelo = $this->modelo('PromocionesPromocionModelo');

        if (isset($_GET['id'])) {
            $promocion = $modelo->obtener((int) $_GET['id']);
            $promocion === null
                ? $this->json(['error' => 'No se encontró la promoción'], 404)
                : $this->json(['exito' => true, 'datos' => $promocion]);
            return;
        }

        if (!empty($_GET['vigentes'])) {
            $fecha = trim((string) ($_GET['fecha'] ?? '')) ?: null;
            $promociones = $modelo->vigentes($fecha);
            $this->json([
                'exito' => true,
                'total' => count($promociones),
                'datos' => $promociones,
            ]);
            return;
        }

        $estado = strtoupper(trim((string) ($_GET['estado'] ?? '')));
        if (!in_array($estado, ['VIGENTE', 'PROGRAMADA', 'VENCIDA', 'INACTIVA'], true)) {
            $estado = '';
        }
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $promociones = $modelo->listar($estado !== '' ? $estado : null, $busqueda);

        $this->json([
            'exito' => true,
            'total' => count($promociones),
            'datos' => $promociones,
        ]);
    }

    // POST index.php?r=promociones/aplicar  body JSON: {"moto_id":1}
    public function aplicar(): void
    {
        $this->requiereLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Usa POST'], 405);
            return;
        }

        $cuerpo = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($cuerpo)) {
            $cuerpo = $_POST;
        }

        $motoId = (int) ($cuerpo['moto_id'] ?? 0);
        if ($motoId <= 0) {
            $this->json(['error' => 'Envía moto_id'], 400);
            return;
        }

        $moto = $this->modelo('PromocionesMotoModelo')->obtener($motoId);
        if ($moto === null) {
            $this->json(['error' => 'La moto no existe en el catálogo'], 404);
            return;
        }

        $vigentes = $this->modelo('PromocionesPromocionModelo')->vigentes();
        $this->json([
            'exito' => true,
            'datos' => array_merge($moto, PromocionesDescuento::precioFinal($moto, $vigentes)),
        ]);
    }

    private function puedeAdmin(): bool
    {
        $usuario = $this->usuario();
        return $usuario !== null && in_array($usuario['rol'], self::ROLES_ADMIN, true);
    }

    private function datosVacios(): array
    {
        return [
            'nombre'         => '',
            'descripcion'    => '',
            'tipo_descuento' => 'porcentaje',
            'valor'          => '',
            'fecha_inicio'   => date('Y-m-d'),
            'fecha_fin'      => date('Y-m-d', strtotime('+15 days')),
            'estado'         => 'activa',
        ];
    }

    private function guardarFlash(string $tipo, string $texto): void
    {
        $_SESSION['promociones_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    }

    private function tomarFlash(): ?array
    {
        $flash = $_SESSION['promociones_flash'] ?? null;
        unset($_SESSION['promociones_flash']);
        return $flash;
    }
}
