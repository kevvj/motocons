<?php
// Consulta de garantía.
// - Cliente (rol cliente): ve "Mis motos" y solo puede consultar las suyas.
// - Personal (admin, vendedor, mecanico...): busca cualquier moto por placa, chasis o factura.
class GarantiaControlador extends Controlador
{
    // index.php?r=garantia/index&criterio=placa&valor=ABC12D
    public function index(): void
    {
        $this->requiereLogin();
        $modelo = $this->modelo('GarantiaConsultaModelo');
        $criterio = $_GET['criterio'] ?? 'placa';
        $valor = trim($_GET['valor'] ?? '');

        $datos = [
            'esCliente' => $this->esCliente(),
            'misMotos' => [],
            'criterio' => $criterio,
            'valor' => $valor,
            'resultado' => null,
            'error' => null,
        ];
        if ($datos['esCliente']) {
            foreach ($modelo->motosDelUsuario($this->usuario()['id']) as $vehiculo) {
                $datos['misMotos'][] = $this->armar($modelo, $vehiculo);
            }
        }
        if ($valor !== '') {
            $datos['resultado'] = $this->consultar($modelo, $criterio, $valor);
            if ($datos['resultado'] === null) {
                http_response_code(404);
                $datos['error'] = $datos['esCliente']
                    ? 'Esa moto no está a tu nombre.'
                    : 'No encontramos ninguna moto con ese dato. Revisa que esté bien escrito.';
            }
        }
        $this->vista('index', $datos);
    }

    // API para otros módulos o el portal: index.php?r=garantia/api&placa=ABC12D (o chasis= / factura=)
    public function api(): void
    {
        $this->requiereLogin();
        $modelo = $this->modelo('GarantiaConsultaModelo');
        foreach (array_keys(GarantiaConsultaModelo::CRITERIOS) as $criterio) {
            if (!empty($_GET[$criterio])) {
                $resultado = $this->consultar($modelo, $criterio, trim($_GET[$criterio]));
                $resultado === null
                    ? $this->json(['error' => 'No se encontró la moto'], 404)
                    : $this->json($resultado);
                return;
            }
        }
        $this->json(['error' => 'Envía placa, chasis o factura'], 400);
    }

    private function esCliente(): bool
    {
        return $this->usuario()['rol'] === 'cliente';
    }

    // Un cliente solo encuentra sus propias motos.
    private function consultar(GarantiaConsultaModelo $modelo, string $criterio, string $valor): ?array
    {
        $soloDe = $this->esCliente() ? (int) $this->usuario()['id'] : null;
        $vehiculo = $modelo->buscar($criterio, $valor, $soloDe);
        return $vehiculo === null ? null : $this->armar($modelo, $vehiculo, true);
    }

    // Une los datos del vehículo, el estado de la garantía y, si se pide, el historial.
    private function armar(GarantiaConsultaModelo $modelo, array $vehiculo, bool $conHistorial = false): array
    {
        $mantenimientos = $modelo->mantenimientos((int) $vehiculo['id']);
        return [
            'vehiculo' => $vehiculo,
            'garantia' => $modelo->estado($vehiculo, $mantenimientos),
            'mantenimientos' => $conHistorial ? $mantenimientos : [],
        ];
    }
}
