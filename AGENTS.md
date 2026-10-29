# Instrucciones para agentes de IA

Lee esto antes de escribir código en este repositorio. Las reglas completas para personas están en `README.md`.

## El proyecto
- MOTOCONS: venta de motos. Proyecto conjunto: cada grupo hace un módulo.
- **PHP puro** (8.2), sin frameworks, sin Composer ni dependencias. Patrón MVC propio en `core/`.
- MySQL / MariaDB, una sola base `motocons` definida en `database/motocons.sql`.
- Correr: `php -S localhost:8000 -t public`. Las URLs son `index.php?r=<modulo>/<accion>`.

## Qué puedes tocar
- **Solo** la carpeta del módulo en el que trabaja el usuario: `modulos/<modulo>/`.
- Sus assets: `public/assets/css/<modulo>.css`, `public/assets/js/<modulo>.js`, `public/assets/img/<modulo>/`.
- Su sección al final de `database/motocons.sql`.
- **No toques** `core/`, `public/index.php`, `vistas/compartidas/`, `config/`, `.github/`, `modulos/inicio/`, `modulos/login/` ni módulos de otros grupos. Si hace falta un cambio ahí, díselo al usuario para que lo pida al dueño del repo.

## Cómo se arma un módulo
Copia `modulos/_plantilla/` como `modulos/<modulo>/` (minúsculas y `_`).
- `modulo.php`: nombre, descripción y ruta. Con esto sale la tarjeta en el inicio.
- `controladores/<Modulo>Controlador.php`: clase `<Modulo>Controlador extends Controlador`; cada método público es una acción (`?r=<modulo>/<metodo>`). Para `ventas_tienda` la clase es `VentasTiendaControlador`.
- `modelos/<Modulo><Algo>Modelo.php`: clase `extends Modelo`, siempre con el prefijo del módulo para no chocar con otros grupos.
- `vistas/<nombre>.php`: HTML con PHP. Se muestra dentro del layout común con `$this->vista('nombre', ['dato' => $valor])`.

Métodos que ya existen en el controlador base (úsalos, no los reimplementes):
- `$this->vista($nombre, $datos)`, `$this->modelo($clase)`, `$this->json($datos, $codigo)`, `$this->redirigir('modulo/accion')`.
- `$this->requiereLogin()` o `$this->requiereLogin(['admin', 'vendedor'])`: protege la acción.
- `$this->usuario()`: usuario con sesión (`id`, `nombre`, `correo`, `rol`) o `null`.

En el modelo: `$this->consultar($sql, $params)` devuelve filas y `$this->ejecutar($sql, $params)` devuelve las filas afectadas. `$this->db` es el PDO.

## No dupliques
El profesor evalúa que no haya funcionalidad repetida entre módulos.
- **Login y usuarios ya existen** (`modulos/login`, tabla `usuario`). No crees otro login ni otra tabla de usuarios.
- Antes de crear una tabla, una consulta o una función, busca si ya existe en `database/motocons.sql` y en `modulos/`. Si existe, reutilízala.

## Base de datos
- Lee `database/motocons.sql` **completo** antes de crear tablas.
- Agrega tus tablas al final, en una sección con este encabezado:
  ```sql
  -- =====================  MÓDULO: <nombre>  =====================
  -- Grupo: <integrantes> · Fecha: AAAA-MM-DD
  -- Usa tablas de otros módulos: <tabla (módulo)> o "ninguna"
  ```
- Nombres en minúscula, en singular y con `_` (`detalle_venta`). Llave primaria `id INT AUTO_INCREMENT`. Llaves foráneas `<tabla>_id`.
- Crea primero las tablas que otras referencian. El archivo debe ejecutarse completo, de arriba abajo, en una base vacía (lo prueba GitHub Actions).
- ¿Necesitas una columna en una tabla de otro módulo? Usa `ALTER TABLE` en tu sección y avisa al usuario para que lo hable con ese grupo.

## Estilos
- El diseño sigue `DESIGN.md` (Swiss Modern): fondo blanco como color principal, texto casi negro, acento rojo `#ff3300`, fuente Archivo, botones redondeados, mucho espacio en blanco. Sin degradados ni sombras fuertes, sin emojis en la interfaz.
- Usa primero las clases de `public/assets/css/estilos.css`: `tarjeta`, `btn` (`btn-secundario`, `btn-peligro`, `btn-chico`, `btn-bloque`), `campo` (label + input), `alerta alerta-error|alerta-exito|alerta-aviso`, `tabla` y `rejilla`.
- Colores con variables (`var(--color-primario)`), no códigos sueltos.
- Sigue el ejemplo de `modulos/login/vistas/index.php`. Lo propio del módulo va en `public/assets/css/<modulo>.css`, que se carga solo.

## Seguridad (el repo es público)
- SQL siempre con parámetros `?`. Nunca concatenes datos del usuario.
- En las vistas, todo dato con `htmlspecialchars()`.
- Contraseñas con `password_hash()` y `password_verify()`.
- Nunca escribas claves reales en el código. `config/config.php` no se sube.

## Estilo de código
- Nombres en español (`buscarPorPlaca`, `$vehiculo`), como el resto del proyecto.
- Comentarios cortos solo donde ayuden.
- Antes de terminar, verifica la sintaxis con `php -l <archivo>` y prueba la página en el navegador.

## Git
- Se trabaja en la rama del grupo, que **debe** llamarse `grupo-<modulo>` y sale de `dev`. Nunca hagas commit en `dev` ni en `main`.
- GitHub Actions rechaza el PR si toca archivos fuera de `modulos/<modulo>/`, sus assets o `database/motocons.sql`, si las clases no siguen los nombres, o si alguna página muestra errores o warnings de PHP. Pruébalo antes con `php -l` y en el navegador.
- El PR va hacia `dev`, nunca hacia `main`. Antes de abrirlo: `git merge dev` y resolver los conflictos.
- No hagas commit, push ni PR sin que el usuario lo pida.
