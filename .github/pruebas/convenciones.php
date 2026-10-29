<?php
// Revisa que cada módulo siga las convenciones del README. Uso: php convenciones.php
// Imprime una línea por error y sale con código 1 si hay alguno.
$raiz = dirname(__DIR__, 2);
$errores = [];
$clases = [];  // clase => archivo, para detectar nombres repetidos entre módulos
$comunes = ['inicio', 'login'];  // módulos comunes que no salen en el menú

foreach (glob($raiz . '/modulos/*', GLOB_ONLYDIR) as $carpeta) {
    $modulo = basename($carpeta);
    if ($modulo[0] === '_') {
        continue;
    }
    if (!preg_match('/^[a-z][a-z0-9_]*$/', $modulo)) {
        $errores[] = "modulos/$modulo: el nombre de la carpeta va en minúsculas, con letras, números y _";
        continue;
    }
    $prefijo = str_replace(' ', '', ucwords(str_replace('_', ' ', $modulo)));

    // Controlador principal, el que busca el Router
    if (!file_exists("$carpeta/controladores/{$prefijo}Controlador.php")) {
        $errores[] = "modulos/$modulo: falta controladores/{$prefijo}Controlador.php";
    }

    // Cada archivo de controladores/ y modelos/ define una clase con su mismo nombre y el prefijo del módulo
    foreach (['controladores' => 'Controlador', 'modelos' => 'Modelo'] as $sub => $sufijo) {
        foreach (glob("$carpeta/$sub/*.php") as $archivo) {
            $relativo = "modulos/$modulo/$sub/" . basename($archivo);
            $nombre = basename($archivo, '.php');
            if (!preg_match('/^\s*(?:abstract\s+|final\s+)?class\s+(\w+)/m', file_get_contents($archivo), $m)) {
                $errores[] = "$relativo: no define ninguna clase";
                continue;
            }
            if ($m[1] !== $nombre) {
                $errores[] = "$relativo: la clase se llama {$m[1]} y debe llamarse igual que el archivo ($nombre)";
            }
            if (!str_starts_with($nombre, $prefijo) || !str_ends_with($nombre, $sufijo)) {
                $errores[] = "$relativo: el nombre debe empezar por $prefijo y terminar en $sufijo (ej.: {$prefijo}Algo$sufijo)";
            }
            if (isset($clases[$m[1]])) {
                $errores[] = "$relativo: la clase {$m[1]} ya existe en {$clases[$m[1]]}";
            }
            $clases[$m[1]] = $relativo;
        }
    }

    // modulo.php: la tarjeta del inicio
    if (in_array($modulo, $comunes, true)) {
        continue;
    }
    if (!file_exists("$carpeta/modulo.php")) {
        $errores[] = "modulos/$modulo: falta modulo.php (copia el de _plantilla)";
        continue;
    }
    $datos = require "$carpeta/modulo.php";
    if (!is_array($datos) || empty($datos['nombre']) || empty($datos['ruta'])) {
        $errores[] = "modulos/$modulo/modulo.php: debe devolver un array con 'nombre' y 'ruta'";
    } elseif (!str_starts_with($datos['ruta'], "$modulo/")) {
        $errores[] = "modulos/$modulo/modulo.php: la ruta debe empezar por '$modulo/' (ej.: '$modulo/index')";
    }
}

foreach ($errores as $error) {
    echo "::error::$error\n";
}
if ($errores) {
    exit(1);
}
echo "OK: todos los módulos siguen las convenciones.\n";
