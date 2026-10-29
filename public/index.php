<?php
// Punto de entrada único. Todas las URLs pasan por aquí: index.php?r=modulo/accion
define('RAIZ', dirname(__DIR__));

require RAIZ . '/core/Database.php';
require RAIZ . '/core/Controlador.php';
require RAIZ . '/core/Modelo.php';
require RAIZ . '/core/Router.php';

$config = RAIZ . '/config/config.php';
if (!file_exists($config)) {
    exit('Falta config/config.php. Copia config/config.ejemplo.php y pon tus datos.');
}
require $config;

Router::despachar($_GET['r'] ?? 'inicio/index');
