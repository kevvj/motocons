# MOTOCONS

Proyecto del diplomado. Cada grupo hace su funcionalidad (módulo) dentro de este mismo proyecto.
PHP puro con Modelo–Vista–Controlador y una sola base de datos MySQL.

## 1. Correrlo en tu PC

1. Copia `config/config.ejemplo.php` como `config/config.php` y pon tu usuario y clave de MySQL.
2. Ejecuta `database/motocons.sql` en MySQL (phpMyAdmin o Workbench).
3. En la carpeta del proyecto, ejecuta:
   ```sh
   php -S localhost:8000 -t public
   ```
4. Abre http://localhost:8000

## 2. Crear tu rama

```sh
git checkout dev
git pull
git checkout -b grupo-<tu_modulo>      # ej.: grupo-garantia
```

## 3. Crear tu módulo

Copia la carpeta `modulos/_plantilla/` y llámala como tu módulo, en minúsculas. Por ejemplo, `modulos/garantia/`.

| Quieres... | Haces esto |
|---|---|
| Una página | Crea el archivo en `modulos/garantia/vistas/`. Ej.: `consultar.php`, con HTML. |
| Que la página se vea | En `controladores/GarantiaControlador.php` agrega un método que la muestre (ver abajo). Entra a `index.php?r=garantia/consultar`. |
| Leer o guardar en la base de datos | Crea un modelo en `modelos/`. Ej.: `GarantiaVehiculoModelo.php` (ver abajo). |
| Estilos | Crea `public/assets/css/garantia.css`. Se carga solo en las páginas de tu módulo. |
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

- **Aprobado:** entra a `dev`. Sigue trabajando en tu misma rama.
- **Cambios solicitados:** lee los comentarios en el PR, corrige en tu misma rama y haz push. El PR se actualiza solo; no abras otro.
- **Cerrado:** algo grave, por ejemplo tocar carpetas de otros, apuntar a `main`, subir contraseñas o romper el proyecto. El motivo queda escrito en el PR. Corrige y abre un PR nuevo.

Mientras tu PR no se apruebe, `dev` y `main` no cambian. Tu código solo está en tu rama y no afecta a nadie.
