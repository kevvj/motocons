<?php
// Tarjeta del inicio: gestión de usuarios, solo para el administrador.
return [
    'nombre' => 'Usuarios',
    'descripcion' => 'Crea las cuentas del personal (vendedores, mecánicos, analistas) y desactiva las que ya no se usan.',
    'ruta' => 'login/usuarios',
    'orden' => 90,
    'roles' => ['admin'],
];
