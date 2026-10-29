<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MOTOCONS</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <?php // El CSS del módulo se carga solo si existe public/assets/css/<modulo>.css ?>
    <?php if (file_exists(RAIZ . '/public/assets/css/' . $this->modulo . '.css')): ?>
        <link rel="stylesheet" href="assets/css/<?= $this->modulo ?>.css">
    <?php endif; ?>
</head>
<body>
<header class="barra">
    <a class="marca" href="index.php">MOTO<span>CONS</span></a>
    <nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <span class="barra-usuario"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
            <a class="btn btn-secundario btn-chico" href="index.php?r=login/salir">Salir</a>
        <?php else: ?>
            <a class="btn btn-chico" href="index.php?r=login/index">Iniciar sesión</a>
        <?php endif; ?>
    </nav>
</header>
<main class="contenedor">
