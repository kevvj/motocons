<?php
// Datos para la tarjeta de tu módulo en la página de inicio.
// Si borras este archivo, tu módulo no aparece en el inicio (pero sigue funcionando).
return [
    'nombre' => 'Nombre de tu módulo',
    'descripcion' => 'Una frase corta de lo que hace.',
    'ruta' => 'plantilla/index',   // la página que abre el botón "Entrar"
    'orden' => 100,                // posición en el inicio: menor sale primero
    // Opcional: roles que ven la tarjeta. Sin 'roles' la ven todos los que inician sesión.
    // 'roles' => ['admin', 'vendedor'],
];
