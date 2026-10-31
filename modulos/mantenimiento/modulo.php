<?php
// Tarjeta del módulo en la página de inicio.
return [
    'nombre'      => 'Taller y mantenimientos',
    'descripcion' => 'Registra los servicios del taller y controla cuántos mantenimientos de garantía le quedan a cada moto.',
    'ruta'        => 'mantenimiento/index',
    'orden'       => 40,
    'roles' => ['admin', 'mecanico'],
];