<?php
// Tabla usuario (ver sección "MÓDULO: login" en database/motocons.sql).
class LoginUsuarioModelo extends Modelo
{
    public function buscarPorCorreo(string $correo): ?array
    {
        $filas = $this->consultar(
            'SELECT id, nombre, correo, clave, rol, activo FROM usuario WHERE correo = ? LIMIT 1',
            [$correo]
        );
        return $filas[0] ?? null;
    }
}
