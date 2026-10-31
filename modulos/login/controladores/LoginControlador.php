<?php
// Login común para todos los módulos. Para proteger una página de tu módulo
// usa $this->requiereLogin() (está en core/Controlador.php).
// También tiene el registro de clientes y la gestión de usuarios del personal (solo admin).
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

        $this->iniciarSesion($usuario);
        $volverA = $_SESSION['volver_a'] ?? 'inicio/index';
        unset($_SESSION['volver_a']);
        $this->redirigir($volverA);
    }

    // index.php?r=login/registro — los clientes crean su propia cuenta.
    public function registro(): void
    {
        if ($this->usuario()) {
            $this->redirigir('inicio/index');
        }
        $datos = ['nombre' => '', 'documento' => '', 'correo' => '', 'telefono' => ''];
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($datos as $campo => $valor) {
                $datos[$campo] = trim((string) ($_POST[$campo] ?? ''));
            }
            $clave = (string) ($_POST['clave'] ?? '');
            $error = $this->validarCuenta($datos['nombre'], $datos['correo'], $clave)
                ?? (preg_match('/^\d{5,15}$/', $datos['documento']) ? null : 'El documento debe tener solo números (5 a 15).')
                ?? ($clave === ($_POST['clave2'] ?? '') ? null : 'Las contraseñas no coinciden.');

            if ($error === null) {
                $modelo = $this->modelo('LoginUsuarioModelo');
                try {
                    $modelo->registrarCliente($datos['nombre'], $datos['documento'], $datos['correo'], $datos['telefono'], $clave);
                    $this->iniciarSesion($modelo->buscarPorCorreo($datos['correo']));
                    $this->redirigir('inicio/index');
                } catch (DomainException $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->vista('registro', ['datos' => $datos, 'error' => $error]);
    }

    // index.php?r=login/usuarios — el admin crea cuentas del personal y las activa o desactiva.
    public function usuarios(): void
    {
        $this->requiereLogin(['admin']);
        $modelo = $this->modelo('LoginUsuarioModelo');
        $nuevo = ['nombre' => '', 'correo' => '', 'rol' => 'vendedor'];
        $error = null;
        $mensaje = $_SESSION['login_mensaje'] ?? null;
        unset($_SESSION['login_mensaje']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($nuevo as $campo => $valor) {
                $nuevo[$campo] = trim((string) ($_POST[$campo] ?? ''));
            }
            $clave = (string) ($_POST['clave'] ?? '');
            $error = $this->validarCuenta($nuevo['nombre'], $nuevo['correo'], $clave)
                ?? (isset(LoginUsuarioModelo::ROLES_PERSONAL[$nuevo['rol']]) ? null : 'Elige un rol válido.')
                ?? ($modelo->buscarPorCorreo($nuevo['correo']) ? 'Ya existe una cuenta con ese correo.' : null);

            if ($error === null) {
                $modelo->crear($nuevo['nombre'], $nuevo['correo'], $clave, $nuevo['rol']);
                $_SESSION['login_mensaje'] = 'Usuario creado. Ya puede entrar con su correo y contraseña.';
                $this->redirigir('login/usuarios');
            }
        }

        $this->vista('usuarios', [
            'usuarios' => $modelo->listar(),
            'roles' => LoginUsuarioModelo::ROLES_PERSONAL + ['cliente' => 'Cliente'],
            'rolesPersonal' => LoginUsuarioModelo::ROLES_PERSONAL,
            'nuevo' => $nuevo,
            'error' => $error,
            'mensaje' => $mensaje,
            'yo' => (int) $this->usuario()['id'],
        ]);
    }

    // POST index.php?r=login/activar (id, activo=0|1) — solo admin, nunca sobre sí mismo.
    public function activar(): void
    {
        $this->requiereLogin(['admin']);
        $id = (int) ($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0 && $id !== (int) $this->usuario()['id']) {
            $activo = ($_POST['activo'] ?? '') === '1';
            $this->modelo('LoginUsuarioModelo')->cambiarActivo($id, $activo);
            $_SESSION['login_mensaje'] = $activo ? 'Usuario activado.' : 'Usuario desactivado: ya no puede entrar.';
        }
        $this->redirigir('login/usuarios');
    }

    // index.php?r=login/salir
    public function salir(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirigir('login/index');
    }

    private function iniciarSesion(array $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'correo' => $usuario['correo'],
            'rol' => $usuario['rol'],
        ];
    }

    // Reglas comunes a cualquier cuenta. Devuelve el error o null si todo está bien.
    private function validarCuenta(string $nombre, string $correo, string $clave): ?string
    {
        if (strlen($nombre) < 3) {
            return 'Escribe el nombre completo.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return 'Escribe un correo válido.';
        }
        if (strlen($clave) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres.';
        }
        return null;
    }
}
