# MOTOCONS

Sistema web para un concesionario de motos: venta en tienda, créditos, taller, garantías, inventario y promociones, todo en un mismo sitio.

**Página en vivo:** https://motocons.gt.tc

Es el proyecto conjunto de un diplomado. Cada grupo construye un módulo dentro del mismo código, con un diseño y un login comunes.

## Qué hace

| Módulo | Para qué sirve |
|---|---|
| Ventas en tienda | Registra la venta de motos en el almacén, confirma el pago y genera la factura. |
| Créditos y financiación | Radica solicitudes de crédito, valida el checklist de Brilla y ServiCrédito, y las aprueba o rechaza. |
| Taller y mantenimientos | Registra los servicios del taller y controla los mantenimientos de garantía que le quedan a cada moto. |
| Garantía | Busca una moto por placa, chasis o factura y dice si la garantía sigue vigente. El cliente ve sus propias motos. |
| Inventario | Controla los productos en stock. |
| Promociones | Descuentos por marca, gama o modelo, con el precio final ya aplicado. |
| Usuarios | Crea las cuentas del personal (vendedores, mecánicos, analistas) y desactiva las que ya no se usan. |

Cada persona entra con su cuenta y ve solo lo que le permite su rol: admin, vendedor, mecánico, analista o cliente.

## Tecnologías

- PHP 8 sin framework, con Modelo–Vista–Controlador.
- MySQL.
- HTML, CSS y JavaScript sin librerías.
- GitHub Actions: pruebas automáticas en cada PR y despliegue por FTP al hosting cuando cambia `main`.

## Estructura

```
core/        Base común: router, Controlador, Modelo, conexión, sesión
modulos/     Un módulo por grupo (controladores, modelos, vistas, modulo.php)
vistas/      Plantilla compartida (encabezado, pie)
public/      Punto de entrada (index.php) y archivos CSS, JS e imágenes
database/    motocons.sql: todas las tablas y datos de prueba
config/      config.ejemplo.php (el config.php real nunca se sube)
```

## Correrlo en tu PC

Necesitas PHP 8 y MySQL.

1. Copia `config/config.ejemplo.php` como `config/config.php` y pon tu usuario y clave de MySQL.
2. Ejecuta `database/motocons.sql` en MySQL.
3. `php -S localhost:8000 -t public`
4. Abre http://localhost:8000

## Participar

Si eres de un grupo del diplomado, lee **[CONTRIBUTING.md](CONTRIBUTING.md)**. Ahí está cómo crear tu rama y tu módulo, las reglas de la base de datos y cómo subir tu trabajo.
