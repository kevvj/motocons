<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MOTOCONS</title>
    <link rel="icon" href="assets/img/logo.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= filemtime(RAIZ . '/public/assets/css/estilos.css') ?>">
    <?php // El CSS del módulo se carga solo si existe public/assets/css/<modulo>.css ?>
    <?php if (file_exists(RAIZ . '/public/assets/css/' . $this->modulo . '.css')): ?>
        <link rel="stylesheet" href="assets/css/<?= $this->modulo ?>.css?v=<?= filemtime(RAIZ . '/public/assets/css/' . $this->modulo . '.css') ?>">
    <?php endif; ?>
</head>
<body>
<header class="barra">
    <a class="marca" href="index.php">
        <img class="marca-logo" src="assets/img/logo.svg" alt="">
        <span>MOTO<b>CONS</b></span>
    </a>
    <nav>
        <?php if (isset($_SESSION['usuario'])): ?>
            <span class="barra-usuario"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></span>
            <a class="btn btn-secundario btn-chico" href="index.php?r=login/salir">Salir</a>
        <?php elseif ($this->modulo !== 'login'): ?>
            <a class="btn btn-chico" href="index.php?r=login/index">Iniciar sesión</a>
        <?php endif; ?>
    </nav>
</header>
<main class="contenedor">
