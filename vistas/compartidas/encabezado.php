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
<header>
    <a href="index.php">MOTOCONS</a>
</header>
<main>
