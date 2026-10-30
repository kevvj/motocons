<?php
// Taller: mecánicos y administradores registran los servicios.
// El estado de garantía y su historial se consultan en el módulo garantia (no se duplican aquí).
class MantenimientoControlador extends Controlador
{
    // index.php?r=mantenimiento/index&q=ABC12D&ver_moto=1
    public function index(): void
    {
        $this->requiereLogin(['mecanico', 'admin']);

        $modelo = $this->modelo('MantenimientoModelo');
        $datos  = $this->datosVista(
            $modelo,
            trim($_GET['q'] ?? ''),
            (int) ($_GET['ver_moto'] ?? 0)
        );

        if (isset($_GET['ok'])) {
            $datos['mensaje'] = $this->mensajeExito($_GET);
        }

        $this->vista('index', $datos);
    }

    // POST index.php?r=mantenimiento/registrar
    public function registrar(): void
    {
        $this->requiereLogin(['mecanico', 'admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('mantenimiento/index');
            return;
        }

        $modelo = $this->modelo('MantenimientoModelo');

        try {
            $usuario = $this->usuario();
            $resultado = $modelo->registrar($_POST, (int) ($usuario['id'] ?? 0));

            $params = http_build_query([
                'r'        => 'mantenimiento/index',
                'ok'       => 1,
                'ver_moto' => $resultado['id_moto'],
                'placa'    => $resultado['placa'],
                'garantia' => $resultado['es_garantia'] ? 1 : 0,
                'servicio' => $resultado['numero_servicio'],
                'total'    => $resultado['total'],
            ]);
            header('Location: index.php?' . $params);
            exit;
        } catch (Throwable $e) {
            $error = $e instanceof PDOException
                ? 'No se pudo guardar el servicio. Revisa los datos e inténtalo de nuevo.'
                : $e->getMessage();

            $datos = $this->datosVista(
                $modelo,
                trim($_GET['q'] ?? ''),
                (int) ($_POST['moto_id'] ?? 0),
                null,
                $error
            );
            $this->vista('index', $datos);
        }
    }

    // Datos que comparten el listado y el formulario de registro.
    private function datosVista(
        MantenimientoModelo $modelo,
        string $busqueda,
        int $idSeleccionado,
        ?string $mensaje = null,
        ?string $error = null
    ): array {
        $motos        = $modelo->listarConGarantia($busqueda);
        $seleccionada = null;

        foreach ($motos as $moto) {
            if ((int) $moto['id_moto'] === $idSeleccionado) {
                $seleccionada = $moto;
                break;
            }
        }

        // Si el enlace trae una moto que no está en la búsqueda, se muestra igual.
        if ($seleccionada === null && $idSeleccionado > 0) {
            $seleccionada = $modelo->buscarPorId($idSeleccionado);
        }

        return [
            'motos'         => $motos,
            'busqueda'      => $busqueda,
            'seleccionada'  => $seleccionada,
            'tope_garantia' => MantenimientoModelo::TOPE_GARANTIA,
            'mensaje'       => $mensaje,
            'error'         => $error,
        ];
    }

    // Mensaje de éxito con la regla de negocio aplicada.
    private function mensajeExito(array $get): string
    {
        $placa    = (string) ($get['placa'] ?? '');
        $garantia = (int) ($get['garantia'] ?? 0) === 1;
        $servicio = (int) ($get['servicio'] ?? 0);
        $total    = (float) ($get['total'] ?? 0);
        $pesos    = '$' . number_format($total, 0, ',', '.');

        $regla = $garantia
            ? 'Servicio de garantía: la mano de obra no se cobró.'
            : 'Servicio pagado: se cobró la mano de obra.';

        return "Servicio #{$servicio} registrado para la moto {$placa}. {$regla} Total: {$pesos}.";
    }
}
