<?php
// Login común para todos los módulos. Para proteger una página de tu módulo
// usa $this->requiereLogin() (está en core/Controlador.php).
class LoginControlador extends Controlador
{
    // index.php?r=login/index
    public function index(): void
    {
        if ($this->usuario()) {
            $this->redirigir('inicio/index');
        }
        $this->vista('index', ['error' => null, 'correo' => '']);
    }

    // index.php?r=login/entrar (POST del formulario)
    public function entrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('login/index');
        }
        $correo = trim($_POST['correo'] ?? '');
        $clave = $_POST['clave'] ?? '';

        $usuario = $this->modelo('LoginUsuarioModelo')->buscarPorCorreo($correo);

        if (!$usuario || !$usuario['activo'] || !password_verify($clave, $usuario['clave'])) {
            $this->vista('index', ['error' => 'Correo o contraseña incorrectos.', 'correo' => $correo]);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
        ];
        $volverA = $_SESSION['volver_a'] ?? 'inicio/index';
        unset($_SESSION['volver_a']);
        $this->redirigir($volverA);
    }

    // index.php?r=login/salir
    public function salir(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirigir('login/index');
    }
}
