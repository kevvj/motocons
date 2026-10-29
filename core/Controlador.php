<?php
// Base de los controladores. Cada módulo extiende esta clase.
abstract class Controlador
{
    // Nombre y carpeta del módulo, los pone el Router.
    public string $modulo = '';
    public string $carpetaModulo = '';

    // Muestra una vista del módulo dentro del layout común.
    protected function vista(string $nombre, array $datos = []): void
    {
        $archivo = $this->carpetaModulo . '/vistas/' . $nombre . '.php';
        if (!file_exists($archivo)) {
            throw new RuntimeException("No existe la vista $archivo");
        }
        extract($datos);
        require RAIZ . '/vistas/compartidas/encabezado.php';
        require $archivo;
        require RAIZ . '/vistas/compartidas/pie.php';
    }

    // Carga un modelo del módulo: modelo('GarantiaVehiculoModelo').
    protected function modelo(string $clase): object
    {
        require_once $this->carpetaModulo . '/modelos/' . $clase . '.php';
        return new $clase();
    }

    // Respuesta JSON, para APIs y llamadas con fetch.
    protected function json($datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    protected function redirigir(string $ruta): void
    {
        header('Location: ' . URL_BASE . '/index.php?r=' . $ruta);
        exit;
    }

    // Usuario que inició sesión (id, nombre, correo, rol) o null.
    protected function usuario(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    // Pon esto al inicio de una acción para que solo entren usuarios con sesión.
    // Con roles: requiereLogin(['admin', 'vendedor']).
    protected function requiereLogin(array $roles = []): void
    {
        $usuario = $this->usuario();
        if ($usuario === null) {
            $_SESSION['volver_a'] = $_GET['r'] ?? 'inicio/index';
            $this->redirigir('login/index');
        }
        if ($roles && !in_array($usuario['rol'], $roles, true)) {
            http_response_code(403);
            exit('No tienes permiso para entrar aquí.');
        }
    }
}
