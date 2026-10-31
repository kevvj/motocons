<?php
// Página principal común.
// - Sin sesión: portada pública de la tienda con algunas motos del catálogo.
// - Con sesión: una tarjeta por cada modulos/<modulo>/modulo.php que el rol del usuario puede abrir.
class InicioControlador extends Controlador
{
    public function index(): void
    {
        $usuario = $this->usuario();

        if ($usuario === null) {
            $this->vista('publico', ['motos' => $this->modelo('InicioCatalogoModelo')->destacadas()]);
            return;
        }

        $this->vista('index', [
            'usuario' => $usuario,
            'esCliente' => $usuario['rol'] === 'cliente',
            'modulos' => $this->modulosDelMenu($usuario['rol']),
        ]);
    }

    // Solo los módulos cuyo 'roles' incluye el rol del usuario (sin 'roles' = todos los que inician sesión).
    private function modulosDelMenu(string $rol): array
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
            $roles = $datos['roles'] ?? [];
            if ($roles && !in_array($rol, $roles, true)) {
                continue;
            }
            // Texto pensado para el cliente, si el módulo lo trae.
            $texto = ($rol === 'cliente' && isset($datos['cliente'])) ? $datos['cliente'] + $datos : $datos;
            $modulos[] = [
                'nombre' => $texto['nombre'],
                'descripcion' => $texto['descripcion'] ?? '',
                'ruta' => $datos['ruta'] ?? $carpeta . '/index',
                'orden' => $datos['orden'] ?? 100,
            ];
        }
        usort($modulos, fn($a, $b) => [$a['orden'], $a['nombre']] <=> [$b['orden'], $b['nombre']]);
        return $modulos;
    }
}
