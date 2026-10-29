<?php
// Página principal común. Muestra una tarjeta por cada módulo que tenga modulos/<modulo>/modulo.php
class InicioControlador extends Controlador
{
    public function index(): void
    {
        $this->vista('index', ['modulos' => $this->modulosDelMenu()]);
    }

    private function modulosDelMenu(): array
    {
        $modulos = [];
        foreach (glob(RAIZ . '/modulos/*/modulo.php') as $archivo) {
            $carpeta = basename(dirname($archivo));
            if ($carpeta[0] === '_') {
                continue;
            }
            $datos = require $archivo;
            if (!is_array($datos) || empty($datos['nombre'])) {
                continue;
            }
            $modulos[] = [
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? '',
                'ruta' => $datos['ruta'] ?? $carpeta . '/index',
                'orden' => $datos['orden'] ?? 100,
            ];
        }
        usort($modulos, fn($a, $b) => [$a['orden'], $a['nombre']] <=> [$b['orden'], $b['nombre']]);
        return $modulos;
    }
}
