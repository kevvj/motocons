<?php
// Convierte ?r=modulo/accion en: modulos/modulo/controladores/ModuloControlador.php -> accion()
class Router
{
    public static function despachar(string $ruta): void
    {
        $partes = explode('/', trim($ruta, '/'));
        $modulo = preg_replace('/[^a-z0-9_]/', '', strtolower($partes[0] ?? 'inicio'));
        $accion = preg_replace('/[^a-zA-Z0-9_]/', '', $partes[1] ?? 'index');

        $carpeta = RAIZ . '/modulos/' . $modulo;
        $clase = str_replace(' ', '', ucwords(str_replace('_', ' ', $modulo))) . 'Controlador';
        $archivo = $carpeta . '/controladores/' . $clase . '.php';

        // Las carpetas que empiezan por _ (como _plantilla) no se publican.
        if ($modulo === '' || $modulo[0] === '_' || !file_exists($archivo)) {
            self::noEncontrado("No existe el módulo '$modulo'");
            return;
        }
        require_once $archivo;
        $controlador = new $clase();
        $controlador->carpetaModulo = $carpeta;

        if (!is_callable([$controlador, $accion])) {
            self::noEncontrado("No existe la acción '$accion' en '$modulo'");
            return;
        }
        $controlador->$accion();
    }

    private static function noEncontrado(string $mensaje): void
    {
        http_response_code(404);
        echo htmlspecialchars($mensaje);
    }
}
