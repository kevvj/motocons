# MOTOCONS

Proyecto del diplomado. Cada grupo hace su funcionalidad (módulo) dentro de este mismo proyecto.
PHP puro con Modelo–Vista–Controlador y una sola base de datos MySQL.

¿Usas un agente de IA (Claude Code, Codex, Cursor, Copilot)? Las reglas para él están en `AGENTS.md` y la mayoría las lee solo.

## 1. Correrlo en tu PC

1. Copia `config/config.ejemplo.php` como `config/config.php` y pon tu usuario y clave de MySQL.
2. Ejecuta `database/motocons.sql` en MySQL (phpMyAdmin o Workbench).
3. En la carpeta del proyecto, ejecuta:
   ```sh
   php -S localhost:8000 -t public
   ```
4. Abre http://localhost:8000. Usuario de prueba: `admin@motocons.com` / `admin123`.

## 2. Crear tu rama

```sh
git checkout dev
git pull
git checkout -b grupo-<tu_modulo>      # ej.: grupo-garantia
```

## 3. Crear tu módulo

Copia la carpeta `modulos/_plantilla/` y llámala como tu módulo, en minúsculas. Por ejemplo, `modulos/garantia/`.

Para que tu módulo salga en la página de inicio, edita `modulos/garantia/modulo.php` con el nombre, una descripción y la ruta. Aparece solo como una tarjeta; no toques el inicio.

| Quieres... | Haces esto |
|---|---|
| Una página | Crea el archivo en `modulos/garantia/vistas/`. Ej.: `consultar.php`, con HTML. |
| Que la página se vea | En `controladores/GarantiaControlador.php` agrega un método que la muestre (ver abajo). Entra a `index.php?r=garantia/consultar`. |
| Leer o guardar en la base de datos | Crea un modelo en `modelos/`. Ej.: `GarantiaVehiculoModelo.php` (ver abajo). |
| Que solo entren usuarios con sesión | Pon `$this->requiereLogin();` al inicio del método (ver "Login"). |
| Estilos | Primero usa las clases comunes (ver "Estilos comunes"). Lo propio va en `public/assets/css/garantia.css`, que se carga solo en tu módulo. |
| JavaScript | Crea `public/assets/js/garantia.js`. También se carga solo. |
| Imágenes | Ponlas en `public/assets/img/garantia/` y úsalas con `<img src="assets/img/garantia/foto.png">`. |
| Tablas nuevas | Agrégalas en `database/motocons.sql` (ver sección 5). |

**Controlador** (`modulos/garantia/controladores/GarantiaControlador.php`).
El nombre de la clase debe ser igual al del archivo.
```php
<?php
class GarantiaControlador extends Controlador
{
    // index.php?r=garantia/consultar
    public function consultar(): void
    {
        $modelo = $this->modelo('GarantiaVehiculoModelo');
        $vehiculo = $modelo->buscarPorPlaca($_GET['placa'] ?? '');
        $this->vista('consultar', ['vehiculo' => $vehiculo]);
    }
}
```

**Modelo** (`modulos/garantia/modelos/GarantiaVehiculoModelo.php`).
Empieza el nombre con tu módulo para no chocar con otros grupos.
```php
<?php
class GarantiaVehiculoModelo extends Modelo
{
    public function buscarPorPlaca(string $placa): array
    {
        return $this->consultar('SELECT * FROM vehiculo WHERE placa = ?', [$placa]);
    }
}
```

**Vista** (`modulos/garantia/vistas/consultar.php`).
Las variables que mandas desde el controlador llegan con su nombre.
```php
<h1>Consulta de garantía</h1>
<?php foreach ($vehiculo as $v): ?>
    <p><?= htmlspecialchars($v['placa']) ?></p>
<?php endforeach; ?>
```

### Estilos comunes

Están en `public/assets/css/estilos.css` y se cargan en todas las páginas. Úsalos para que todo el proyecto se vea igual. El login (`modulos/login/vistas/index.php`) es el ejemplo a seguir.

| Clase | Para qué |
|---|---|
| `tarjeta` | Caja blanca con borde y sombra |
| `btn`, `btn-secundario`, `btn-peligro`, `btn-chico`, `btn-bloque` | Botones, en `<button>` o `<a>` |
| `campo` | Envuelve un `<label>` y un `<input>`, `<select>` o `<textarea>` |
| `alerta alerta-error`, `alerta-exito`, `alerta-aviso` | Mensajes |
| `tabla` | Tablas |
| `rejilla` | Pone tarjetas en columnas que se adaptan al celular |

Colores: usa las variables, no códigos sueltos. Por ejemplo `color: var(--color-primario);`. La lista está al inicio de `estilos.css`.

```php
<div class="tarjeta">
    <h2>Buscar garantía</h2>
    <form method="post" action="index.php?r=garantia/consultar">
        <div class="campo">
            <label for="placa">Placa</label>
            <input id="placa" name="placa" required>
        </div>
        <button class="btn">Buscar</button>
    </form>
</div>
```

### Login

El login es común; no hagas otro. La tabla `usuario` ya está en `motocons.sql`.

```php
public function consultar(): void
{
    $this->requiereLogin();                        // si no hay sesión, lo manda al login
    // $this->requiereLogin(['admin', 'vendedor']); // solo esos roles
    $usuario = $this->usuario();                   // ['id', 'nombre', 'correo', 'rol']
}
```

## 4. Subir tu trabajo (Pull Request)

```sh
git add .
git commit -m "garantia: consulta por placa"
git checkout dev && git pull               # trae lo último de los demás
git checkout grupo-garantia && git merge dev
git push -u origin grupo-garantia
```
Luego, en GitHub: **Compare & pull request**, hacia **`dev`**, nunca hacia `main`. Llena la plantilla.

## 5. Base de datos

- Hay **un solo archivo**: `database/motocons.sql`.
- **Léelo completo antes de crear una tabla.** Si ya existe (por ejemplo `cliente`), úsala; no la vuelvas a crear.
- Pon tus tablas al final, en una sección con el nombre de tu módulo.
- ¿Necesitas una columna en una tabla de otro grupo? Usa `ALTER TABLE` en tu sección y avísale a ese grupo.

## 6. Reglas

- **Ramas:** `main` es producción y `dev` es desarrollo. Nadie sube directo a ninguna de las dos. Todo entra por PR, y el paso de `dev` a `main` lo hace solo el dueño del repo.
- **Solo toca lo tuyo:** tu carpeta de `modulos/`, tu `.css`, tu `.js`, tus imágenes y tu sección del `.sql`. Si necesitas cambiar `core/`, `vistas/compartidas/` o algo de otro grupo, pídelo.
- **El repo es público:** nunca subas `config/config.php` ni contraseñas.
- **Seguridad básica:** en el SQL usa `?` con parámetros, como en el ejemplo, nunca concatenes datos del usuario. En las vistas, muestra los datos con `htmlspecialchars()`.

## 7. ¿Qué pasa con mi PR?

Primero corren las **pruebas automáticas**. Revisan que:
- la rama se llame `grupo-<modulo>` y que el PR solo toque tu módulo, tus assets y `motocons.sql`;
- los PR a `main` vengan solo de `dev`;
- tus clases sigan los nombres (clase = archivo, prefijo del módulo) y exista `modulo.php`;
- el PHP no tenga errores de sintaxis, `motocons.sql` se ejecute y no subas contraseñas;
- el inicio, el login y la página de tu módulo carguen sin errores ni warnings de PHP.

Si sale una X roja, entra a "Details", mira qué falló, corrige y haz push. Sin el check verde no se puede fusionar.

- **Aprobado:** entra a `dev`. Sigue trabajando en tu misma rama.
- **Cambios solicitados:** lee los comentarios en el PR, corrige en tu misma rama y haz push. El PR se actualiza solo; no abras otro.
- **Cerrado:** algo grave, por ejemplo tocar carpetas de otros, apuntar a `main`, subir contraseñas o romper el proyecto. El motivo queda escrito en el PR. Corrige y abre un PR nuevo.

Mientras tu PR no se apruebe, `dev` y `main` no cambian. Tu código solo está en tu rama y no afecta a nadie.
