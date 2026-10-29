# MOTOCONS

Proyecto integrado del Diplomado en Desarrollo Web en la Nube. Cada grupo construye una funcionalidad (módulo) dentro de este mismo proyecto.

- **Lenguaje:** PHP puro (sin frameworks), patrón **Modelo–Vista–Controlador**.
- **Base de datos:** MySQL, una sola base `motocons`.

---

## Cómo ejecutarlo

1. Copia `config/config.ejemplo.php` como `config/config.php` y pon tus datos de MySQL. Este archivo no se sube al repositorio.
2. Crea la base de datos ejecutando `database/motocons.sql` en MySQL (phpMyAdmin, Workbench o consola).
3. Levanta el servidor desde la raíz del proyecto:
   ```sh
   php -S localhost:8000 -t public
   ```
   Con XAMPP, la carpeta pública del sitio debe ser `public/`.
4. Abre `http://localhost:8000`.

Las URLs tienen la forma `index.php?r=modulo/accion`. Por ejemplo, `index.php?r=garantia/consultar` ejecuta el método `consultar()` de `modulos/garantia/controladores/GarantiaControlador.php`.

---

## Estructura

```
motocons/
├── public/                 Lo único visible desde el navegador
│   ├── index.php           Punto de entrada: todas las peticiones pasan por aquí
│   └── assets/             css/ y js/ (los de cada módulo: <modulo>.css, <modulo>.js)
├── core/                   Base común: Router, Controlador, Modelo, Database (no tocar)
├── config/                 config.ejemplo.php (config.php es local y no se sube)
├── vistas/compartidas/     Encabezado y pie comunes a todas las páginas
├── modulos/                UNA CARPETA POR GRUPO
│   ├── inicio/             Página principal
│   ├── _plantilla/         Copia esto para crear tu módulo
│   └── <tu_modulo>/
│       ├── controladores/  <TuModulo>Controlador.php
│       ├── modelos/        <TuModulo><Algo>Modelo.php  (acceso a la base de datos)
│       └── vistas/         HTML con PHP (lo que ve el usuario)
├── database/motocons.sql   La base de datos de todos
└── .github/                CODEOWNERS y plantilla de PR
```

### Crear tu módulo
1. Copia `modulos/_plantilla/` como `modulos/<tu_modulo>/`. Usa minúsculas y `_`, por ejemplo `garantia` o `ventas_tienda`.
2. Renombra `PlantillaControlador` como `<TuModulo>Controlador`. Para `ventas_tienda` sería `VentasTiendaControlador`. El nombre de la clase y el del archivo deben coincidir.
3. Pon a tus modelos el prefijo de tu módulo (`GarantiaVehiculoModelo`), para no chocar con clases de otros grupos.
4. Prueba en `index.php?r=<tu_modulo>/index`.

---

## Reglas del repositorio

### Ramas
| Rama | Para qué | Quién sube |
|---|---|---|
| `main` | **Producción.** Solo lo que está terminado y probado. | Nadie directo. Solo entra `dev` por PR, cuando todo funciona. |
| `dev` | **Desarrollo.** Aquí se junta el trabajo de todos los grupos. | Nadie directo. Solo entran PRs aprobados de las ramas de grupo. |
| `grupo-<modulo>` | El trabajo de cada grupo. Ej.: `grupo-garantia`. | El grupo, libremente. |

Ambas ramas, `main` y `dev`, están protegidas: no se puede subir código directo, forzar un push ni borrarlas. Todo cambio pasa por un Pull Request y necesita la aprobación del dueño del repositorio.

### Flujo de trabajo
```
grupo-<modulo>  ──PR──▶  dev  ──PR (solo el dueño)──▶  main
```
1. Crea tu rama desde `dev`:
   ```sh
   git checkout dev
   git pull
   git checkout -b grupo-<modulo>
   ```
2. Trabaja y haz commits pequeños con mensajes claros (`garantia: agrega búsqueda por placa`).
3. Antes del PR, trae lo último de `dev` y resuelve tú los conflictos:
   ```sh
   git checkout dev && git pull
   git checkout grupo-<modulo> && git merge dev
   ```
4. Sube tu rama y abre un **Pull Request hacia `dev`**, nunca hacia `main`. Llena la plantilla.
5. El dueño del repositorio revisa y **acepta**, **pide cambios** o **cierra** el PR (ver abajo).

### Qué puede tocar cada grupo
- ✅ Su carpeta `modulos/<su_modulo>/`.
- ✅ Sus archivos `public/assets/css/<su_modulo>.css` y `public/assets/js/<su_modulo>.js`.
- ✅ Su sección en `database/motocons.sql`.
- ❌ `core/`, `public/index.php`, `vistas/compartidas/`, `config/` y los módulos de otros grupos. Si necesitas un cambio ahí, pídelo en un *issue* o al dueño del repositorio.

### Base de datos
- Solo existe **un archivo**: `database/motocons.sql`. Al inicio solo tiene el `CREATE DATABASE`, sin tablas.
- **Antes de crear cualquier tabla, lee el archivo completo.** Si la tabla ya existe (por ejemplo `cliente`), úsala; no crees otra parecida.
- Agrega tus tablas al final, en una sección con el nombre de tu módulo, siguiendo el formato del archivo.
- Si necesitas una columna nueva en una tabla de otro grupo, agrégala con `ALTER TABLE` en tu sección, explícalo en el PR y avisa a ese grupo.
- Crea las tablas en orden: primero las que otras tablas referencian con llaves foráneas.
- Nombres en minúscula y en singular, con `_` (`cliente`, `detalle_venta`).

### Seguridad (el repositorio es público)
- Nunca subas contraseñas, `config/config.php` ni `.env`.
- En los modelos usa siempre consultas preparadas (`$this->consultar($sql, [$valor])`). Nunca concatenes datos del usuario en el SQL.
- En las vistas, muestra los datos con `htmlspecialchars()`.

---

## ¿Qué pasa con mi Pull Request?

El dueño del repositorio revisa cada PR hacia `dev`. Hay tres resultados posibles:

### ✅ Aprobado
El PR se fusiona en `dev`. Tu código ya es parte del proyecto común. Puedes seguir trabajando en tu misma rama. Antes de cada PR nuevo, vuelve a traer lo último de `dev`.

### ✏️ Cambios solicitados
El PR **no se fusiona**, pero tampoco se cierra. En el PR verás comentarios con lo que hay que corregir.
1. Corrige en **tu misma rama** (`grupo-<modulo>`).
2. Haz commit y push. **El PR se actualiza solo**; no abras otro.
3. Responde los comentarios y avisa que ya está listo. Se revisa de nuevo.

Se piden cambios cuando, por ejemplo:
- hay errores o algo no funciona al probarlo;
- falta llenar la plantilla del PR o explicar cómo probarlo;
- hay conflictos con `dev` (debes resolverlos tú, con `git merge dev`);
- el SQL tiene errores o duplica una tabla que ya existe.

### ❌ Cerrado (rechazado)
El PR se cierra sin fusionar. El motivo queda escrito en el PR. Pasa cuando:
- toca carpetas de otros grupos o de `core/`, `config/` o `vistas/compartidas/`;
- va dirigido a `main` en vez de a `dev`;
- sube contraseñas o `config/config.php`;
- rompe el proyecto o cambia tablas de otros sin avisar;
- trae cambios que no tienen que ver con el módulo.

Para volver a intentarlo: corrige en tu rama, actualízala con `dev` y **abre un PR nuevo**.

> Si subiste una contraseña por error, avisa de inmediato y cámbiala. Aunque se borre del PR, queda en el historial público.

Mientras un PR no se apruebe, **`dev` y `main` no cambian**: ese código solo existe en la rama del grupo y no afecta a nadie más.

### Paso a `main` (producción)
Solo el dueño del repositorio abre el PR de `dev` a `main`. Lo hace cuando todo lo que hay en `dev` funciona junto, por ejemplo antes de cada entrega. Ningún grupo abre PRs hacia `main`.
