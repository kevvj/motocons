<?php
// Tabla usuario (ver sección "MÓDULO: login" en database/motocons.sql).
// El registro de clientes también usa la tabla cliente (sección garantia).
class LoginUsuarioModelo extends Modelo
{
    // Roles que el administrador puede asignar a una cuenta del personal.
    public const ROLES_PERSONAL = [
        'admin' => 'Administrador',
        'vendedor' => 'Vendedor',
        'mecanico' => 'Mecánico',
        'analista' => 'Analista de crédito',
    ];

    public function buscarPorCorreo(string $correo): ?array
    {
        $filas = $this->consultar(
            'SELECT id, nombre, correo, clave, rol, activo FROM usuario WHERE correo = ? LIMIT 1',
            [$correo]
        );
        return $filas[0] ?? null;
    }

    public function listar(): array
    {
        return $this->consultar(
            "SELECT id, nombre, correo, rol, activo, creado_en FROM usuario ORDER BY rol = 'cliente', nombre"
        );
    }

    public function crear(string $nombre, string $correo, string $clave, string $rol): int
    {
        $this->ejecutar(
            'INSERT INTO usuario (nombre, correo, clave, rol) VALUES (?, ?, ?, ?)',
            [$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT), $rol]
        );
        return (int) $this->db->lastInsertId();
    }

    public function cambiarActivo(int $id, bool $activo): void
    {
        $this->ejecutar('UPDATE usuario SET activo = ? WHERE id = ?', [$activo ? 1 : 0, $id]);
    }

    // Crea la cuenta de un cliente. Si ya compró en la tienda (su documento está en cliente
    // y aún no tiene cuenta), se le vincula ese registro para que vea sus motos.
    // Devuelve el id del usuario. Lanza DomainException con un mensaje para mostrar.
    public function registrarCliente(string $nombre, string $documento, string $correo, string $telefono, string $clave): int
    {
        if ($this->buscarPorCorreo($correo)) {
            throw new DomainException('Ya existe una cuenta con ese correo. Inicia sesión.');
        }
        $cliente = $this->consultar('SELECT id, usuario_id FROM cliente WHERE documento = ? LIMIT 1', [$documento])[0] ?? null;
        if ($cliente && $cliente['usuario_id']) {
            throw new DomainException('Ese documento ya tiene una cuenta. Inicia sesión con su correo.');
        }

        $this->db->beginTransaction();
        try {
            $usuarioId = $this->crear($nombre, $correo, $clave, 'cliente');
            if ($cliente) {
                $this->ejecutar(
                    'UPDATE cliente SET usuario_id = ?, correo = COALESCE(correo, ?), telefono = COALESCE(telefono, ?) WHERE id = ?',
                    [$usuarioId, $correo, $telefono, $cliente['id']]
                );
            } else {
                $this->ejecutar(
                    'INSERT INTO cliente (nombre, documento, correo, telefono, usuario_id) VALUES (?, ?, ?, ?, ?)',
                    [$nombre, $documento, $correo, $telefono, $usuarioId]
                );
            }
            $this->db->commit();
            return $usuarioId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
