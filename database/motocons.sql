-- =============================================================
-- Base de datos única de MOTOCONS.
-- Antes de agregar algo, LEE TODO ESTE ARCHIVO: la tabla que necesitas
-- puede existir ya. Cada grupo agrega sus tablas al final, en su sección.
-- Al ejecutarlo BORRA tu base local y la crea de nuevo: tus datos de
-- prueba deben estar aquí como INSERT, no solo en tu MySQL.
-- =============================================================
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS motocons;
CREATE DATABASE motocons
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE motocons;

-- -------------------------------------------------------------
-- Formato para agregar tu sección (copiar debajo de la última):
-- -------------------------------------------------------------
-- =====================  MÓDULO: <nombre>  =====================
-- Grupo: <integrantes> · Fecha: AAAA-MM-DD
-- Usa tablas de otros módulos: <tabla (módulo)> o "ninguna"
-- CREATE TABLE ... ;

-- =====================  MÓDULO: login  =====================
-- Grupo: Kevin Tapias · Fecha: 2026-10-28
-- Usa tablas de otros módulos: ninguna
-- Tabla común de usuarios: no crear otra; para datos extra de tu módulo,
-- crea tu tabla con usuario_id como llave foránea.
CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    clave VARCHAR(255) NOT NULL,              -- hash de password_hash(), nunca la clave en texto
    rol VARCHAR(30) NOT NULL DEFAULT 'cliente', -- admin, vendedor, mecanico, cliente
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Usuario de prueba: admin@motocons.com / admin123
INSERT INTO usuario (nombre, correo, clave, rol) VALUES
    ('Administrador', 'admin@motocons.com', '$2y$10$ReBIcbLEyUrBmk3IWyX0AeC6TeJuaszQKS3CiCTOln5qjbp4yMhGy', 'admin');

-- =====================  MÓDULO: garantia  =====================
-- Grupo: Kevin Tapias, Anuar Marriaga, Andric Mestra, Sebastian Villera, Samuel Ruiz, Valeria Ospino · Fecha: 2026-10-29
-- Usa tablas de otros módulos: ninguna.
-- OJO: cliente, moto, venta, vehiculo y mantenimiento son tablas COMUNES.
-- Otros módulos (ventas, inventario, mantenimiento...) deben reutilizarlas, no crear otras.
-- Si les falta una columna, agréguenla con ALTER TABLE en su sección.

CREATE TABLE cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    documento VARCHAR(20) NOT NULL UNIQUE,
    correo VARCHAR(150),
    telefono VARCHAR(20),
    usuario_id INT UNIQUE,                   -- su cuenta para entrar (rol cliente), si tiene
    FOREIGN KEY (usuario_id) REFERENCES usuario (id)
);

-- Modelo de moto del catálogo (no una unidad vendida)
CREATE TABLE moto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(60) NOT NULL,
    modelo VARCHAR(80) NOT NULL,
    propulsion ENUM('combustion', 'electrica') NOT NULL DEFAULT 'combustion',
    precio DECIMAL(12, 2) NOT NULL DEFAULT 0,
    meses_garantia INT NOT NULL DEFAULT 12,
    cobertura TEXT
);

CREATE TABLE venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(30) NOT NULL UNIQUE,
    fecha DATE NOT NULL,
    cliente_id INT NOT NULL,
    canal ENUM('tienda', 'web') NOT NULL DEFAULT 'tienda',
    total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (cliente_id) REFERENCES cliente (id)
);

-- Unidad vendida: una moto concreta con placa y chasis
CREATE TABLE vehiculo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(10) UNIQUE,
    chasis VARCHAR(30) NOT NULL UNIQUE,
    moto_id INT NOT NULL,
    cliente_id INT NOT NULL,
    venta_id INT NOT NULL,
    FOREIGN KEY (moto_id) REFERENCES moto (id),
    FOREIGN KEY (cliente_id) REFERENCES cliente (id),
    FOREIGN KEY (venta_id) REFERENCES venta (id)
);

-- tipo garantia = mano de obra gratis (3 durante la garantía); pagado = se cobra
CREATE TABLE mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehiculo_id INT NOT NULL,
    fecha DATE NOT NULL,
    tipo ENUM('garantia', 'pagado') NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    costo_repuestos DECIMAL(12, 2) NOT NULL DEFAULT 0,
    costo_mano_obra DECIMAL(12, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculo (id)
);

-- Datos de prueba. Las fechas dependen del día de hoy para que los casos no cambien con el tiempo:
--   ABC12D  -> vigente, 1 mantenimiento de garantía usado
--   XYZ98E  -> vigente, los 3 gratis ya usados (+1 pagado)
--   MNO45F  -> vencida
--   DEF34G  -> vigente, sin mantenimientos (segunda moto de Laura)
-- Cuenta de cliente para ver "Mis motos": laura@correo.com / cliente123
INSERT INTO usuario (nombre, correo, clave, rol) VALUES
    ('Laura Pérez', 'laura@correo.com', '$2y$10$/.kiM/1nGdzOSD8GSQgM7OVvFlQlwx/HpCOeEiwdxc1vaTulkXAVm', 'cliente');

INSERT INTO cliente (nombre, documento, correo, telefono, usuario_id) VALUES
    ('Laura Pérez', '1067123456', 'laura@correo.com', '3001234567', (SELECT id FROM usuario WHERE correo = 'laura@correo.com')),
    ('Carlos Díaz', '1067987654', 'carlos@correo.com', '3109876543', NULL),
    ('María Gómez', '1003456789', 'maria@correo.com', '3204567890', NULL);

INSERT INTO moto (marca, modelo, propulsion, precio, meses_garantia, cobertura) VALUES
    ('Yamaha', 'FZ 2.0', 'combustion', 9500000, 12,
     'Motor, caja de cambios y sistema eléctrico por defectos de fábrica. No cubre desgaste normal, golpes ni mal uso.'),
    ('Super Soco', 'CPx', 'electrica', 14900000, 24,
     'Motor eléctrico, controlador y batería (hasta 70% de capacidad). No cubre golpes, humedad por inmersión ni mal uso.'),
    ('Honda', 'CB 125F', 'combustion', 7800000, 12,
     'Motor y caja de cambios por defectos de fábrica. No cubre llantas, frenos ni piezas de desgaste.');

INSERT INTO venta (numero_factura, fecha, cliente_id, canal, total) VALUES
    ('FV-0001', DATE_SUB(CURDATE(), INTERVAL 4 MONTH), 1, 'tienda', 9500000),
    ('FV-0002', DATE_SUB(CURDATE(), INTERVAL 10 MONTH), 2, 'web', 14900000),
    ('FV-0003', DATE_SUB(CURDATE(), INTERVAL 15 MONTH), 3, 'tienda', 7800000),
    ('FV-0004', DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 1, 'web', 14900000);

INSERT INTO vehiculo (placa, chasis, moto_id, cliente_id, venta_id) VALUES
    ('ABC12D', '9FKRG2110N0001234', 1, 1, 1),
    ('XYZ98E', 'LXYPCKL06N0005678', 2, 2, 2),
    ('MNO45F', '9C2JC6810M0009012', 3, 3, 3),
    ('DEF34G', 'LXYPCKL06P0004321', 2, 1, 4);

INSERT INTO mantenimiento (vehiculo_id, fecha, tipo, descripcion, costo_repuestos, costo_mano_obra) VALUES
    (1, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 'garantia', 'Primera revisión: cambio de aceite y ajuste de cadena', 45000, 0),
    (2, DATE_SUB(CURDATE(), INTERVAL 9 MONTH), 'garantia', 'Revisión inicial de batería y frenos', 0, 0),
    (2, DATE_SUB(CURDATE(), INTERVAL 6 MONTH), 'garantia', 'Actualización del controlador', 0, 0),
    (2, DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 'garantia', 'Cambio de pastillas de freno', 80000, 0),
    (2, DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 'pagado', 'Revisión general', 0, 60000),
    (3, DATE_SUB(CURDATE(), INTERVAL 11 MONTH), 'garantia', 'Primera revisión', 40000, 0);

-- =====================  MÓDULO: mantenimiento  =====================
-- Grupo: Leocarlos Ospina Causil, Caleb Seña Melo, Jhonatan David Cantero Hernández,
--        Miguel Andrés De Aguas Martínez, Joel David Toscano Cafiel, Adrian Ricardo Villazon Ascanio
-- Fecha: 2026-10-29
-- Usa tablas de otros módulos: usuario (login), cliente, moto, venta, vehiculo, mantenimiento (garantia).
-- No crea tablas nuevas: reutiliza las tablas comunes del taller.
-- Agrega usuario mecanico para hacer pruebas.

-- Usuario de prueba del taller: mecanico@motocons.com / mecanico123
INSERT INTO usuario (nombre, correo, clave, rol) VALUES
    ('Mecánico de prueba', 'mecanico@motocons.com', '$2y$12$GHinqXOnAWaIkiYlSRZXK.iOesUitgC5KY/2kM2DBnaexd.a21QYW', 'mecanico');

-- =====================  MÓDULO: mantenimiento  =====================
-- Grupo: Leocarlos Ospina Causil, Caleb Seña Melo, Jhonatan David Cantero Hernández,
--        Miguel Andrés De Aguas Martínez, Joel David Toscano Cafiel, Adrian Ricardo Villazon Ascanio
-- Fecha: 2026-10-29
-- Usa tablas de otros módulos: motos (inventario), usuarios (login)
-- Crea tablas nuevas: detalle_mantenimiento.
-- Modifica tabla: mantenimiento



-- 1) La tabla mantenimiento ya existe: se le agregan columnas
--    para enlazar el recibo y guardar el total calculado.

ALTER TABLE mantenimiento
    ADD COLUMN numero_servicio INT NULL AFTER id,
    ADD COLUMN total DECIMAL(12, 2) NOT NULL DEFAULT 0 AFTER costo_mano_obra,
    ADD COLUMN usuario_id INT NULL AFTER vehiculo_id,
    ADD CONSTRAINT fk_mantenimiento_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuario (id);

        
-- actualizacion de los datos de prueba para reflejar los cambios en la tabla mantenimiento
UPDATE mantenimiento
SET numero_servicio = 1,
    total = costo_repuestos + costo_mano_obra
WHERE id = 1;

UPDATE mantenimiento
SET numero_servicio = 1,
    total = costo_repuestos + costo_mano_obra
WHERE id = 2;

UPDATE mantenimiento
SET numero_servicio = 2,
    total = costo_repuestos + costo_mano_obra
WHERE id = 3;

UPDATE mantenimiento
SET numero_servicio = 3,
    total = costo_repuestos + costo_mano_obra
WHERE id = 4;

UPDATE mantenimiento
SET numero_servicio = 4,
    total = costo_repuestos + costo_mano_obra
WHERE id = 5;

UPDATE mantenimiento
SET numero_servicio = 1,
    total = costo_repuestos + costo_mano_obra
WHERE id = 6;

-- 2) Detalle de repuestos e insumos usados en cada mantenimiento
CREATE TABLE detalle_mantenimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mantenimiento_id INT NOT NULL,
    tipo ENUM('repuesto', 'insumo') NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL,
    valor_unitario DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (mantenimiento_id)
        REFERENCES mantenimiento (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO detalle_mantenimiento
    (mantenimiento_id, tipo, descripcion, cantidad, valor_unitario)
VALUES
    (1, 'insumo', 'Aceite semi-sintético', 1, 45000),
    (2, 'insumo', 'Revisión de batería', 1, 0),
    (3, 'insumo', 'Actualización del controlador', 1, 0),
    (4, 'repuesto', 'Pastillas de freno', 1, 80000),
    (5, 'insumo', 'Revisión general', 1, 0),
    (6, 'insumo', 'Primera revisión', 1, 40000);

-- =====================  MÓDULO: promociones  =====================
-- Grupo: Andres Benavides Velasquez, Camilo Diaz Cabarcas, Julian Llamas Hernandez,
--        Misael Mejia Cruz, Elias Talaigua Hoyos, Wilmer Sierra Tejada
-- Fecha: 2026-10-30
-- Usa tablas de otros módulos: moto (garantia)
-- Crea tablas nuevas: promocion, promocion_aplica
-- Modifica tabla: moto (agrega columna gama) — avisar al grupo de garantía

ALTER TABLE moto
    ADD COLUMN gama ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media' AFTER propulsion;

UPDATE moto SET gama = 'media' WHERE id = 1; -- Yamaha FZ 2.0
UPDATE moto SET gama = 'alta'  WHERE id = 2; -- Super Soco CPx
UPDATE moto SET gama = 'baja'  WHERE id = 3; -- Honda CB 125F

CREATE TABLE promocion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    tipo_descuento ENUM('porcentaje', 'monto_fijo') NOT NULL,
    valor DECIMAL(12, 2) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('activa', 'inactiva') NOT NULL DEFAULT 'activa',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_promocion_vigencia (estado, fecha_inicio, fecha_fin)
) ENGINE=InnoDB;

-- Sin filas en promocion_aplica = la promoción aplica a todo el catálogo.
CREATE TABLE promocion_aplica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promocion_id INT NOT NULL,
    criterio ENUM('marca', 'gama', 'motocicleta') NOT NULL,
    valor VARCHAR(80) NOT NULL,
    FOREIGN KEY (promocion_id) REFERENCES promocion (id) ON DELETE CASCADE,
    UNIQUE KEY uq_promocion_aplica (promocion_id, criterio, valor)
) ENGINE=InnoDB;

INSERT INTO promocion
    (nombre, descripcion, tipo_descuento, valor, fecha_inicio, fecha_fin, estado)
VALUES
('Temporada Honda', 'Descuento de temporada para toda la línea Honda.',
 'porcentaje', 8.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'activa'),
('Semana de la gama media', 'Beneficio para motocicletas de gama media.',
 'porcentaje', 12.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'activa'),
('Bono gama alta', 'Bono de compra para motocicletas de gama alta.',
 'monto_fijo', 800000.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'activa'),
('Descuento Yamaha FZ 2.0', 'Beneficio puntual sobre la Yamaha FZ 2.0.',
 'monto_fijo', 500000.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'activa'),
('Black Friday MOTOCONS', 'Descuento general de fin de año para todo el catálogo.',
 'porcentaje', 15.00, DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'activa'),
('Liquidación año anterior', 'Promoción finalizada del periodo anterior.',
 'monto_fijo', 500000.00, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'activa'),
('Promo interna desactivada', 'Promoción creada por error, ya no debe aplicarse.',
 'porcentaje', 5.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'inactiva');

INSERT INTO promocion_aplica (promocion_id, criterio, valor) VALUES
(1, 'marca', 'Honda'),
(2, 'gama', 'media'),
(3, 'gama', 'alta'),
(4, 'motocicleta', '1'),
(7, 'marca', 'Yamaha');
-- Promociones 5 (Black Friday) y 6 (Liquidación) sin filas: aplican a todas.

-- =====================  MÓDULO: ventas_presenciales  =====================
-- Grupo: <Miguel Martínez, Luis Galarcio, Juan Vargas, Santiago Hernandez > · Fecha: 2026-10-30
-- Usa tablas de otros módulos: usuario (login), cliente, moto, venta, vehiculo, mantenimiento (garantia)
-- Modifica tabla: venta (factura opcional, vendedor y estado). Crea tabla: detalle_venta.
-- El ALTER TABLE venta va solo aquí: financiación no lo repite y su sección va debajo de esta.

-- Usuario de prueba del punto de venta: vendedor@motocons.com / vendedor123
INSERT INTO usuario (nombre, correo, clave, rol) VALUES
    ('Vendedor de prueba', 'vendedor@motocons.com', '$2y$10$2e4PQPEV0sAeJYgvirAGaupSV7Btf.JqZnjXWcGlpoFQUHCYWkEva', 'vendedor');

-- 1) Cambios a la tabla venta:
--    - numero_factura pasa a opcional: se asigna cuando la venta queda PAGADA.
--      Sigue siendo UNIQUE (se permiten varias filas con NULL).
--    - usuario_id: vendedor que hizo la venta (para comisiones).
--    - estado: ciclo de vida de la venta, acordado con financiación.
ALTER TABLE venta
    MODIFY COLUMN numero_factura VARCHAR(30) NULL,
    ADD COLUMN usuario_id INT NULL AFTER cliente_id,
    ADD COLUMN estado ENUM('PENDIENTE', 'EN_CREDITO', 'PAGADA', 'ENTREGADA', 'CERRADA', 'ANULADA')
        NOT NULL DEFAULT 'PENDIENTE' AFTER total,
    ADD CONSTRAINT fk_venta_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id);

-- Las ventas de prueba de garantía ya tienen factura y la moto entregada.
UPDATE venta SET estado = 'ENTREGADA' WHERE numero_factura IS NOT NULL;

-- 2) Motos de cada venta. Mientras la venta no esté pagada solo viven aquí;
--    al pasar a PAGADA se crean en vehiculo y se guarda vehiculo_id.
--    chasis y placa no son UNIQUE aquí para que una venta ANULADA no bloquee
--    esa moto: la repetición se valida en el modelo y vehiculo sí es UNIQUE.
CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    moto_id INT NOT NULL,
    chasis VARCHAR(30) NOT NULL,
    placa VARCHAR(10),
    precio DECIMAL(12, 2) NOT NULL,           -- moto.precio al momento de la venta
    vehiculo_id INT NULL UNIQUE,              -- se llena cuando la venta queda PAGADA
    INDEX idx_detalle_venta_chasis (chasis),
    FOREIGN KEY (venta_id) REFERENCES venta (id) ON DELETE CASCADE,
    FOREIGN KEY (moto_id) REFERENCES moto (id),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculo (id)
) ENGINE=InnoDB;

-- Detalle de las ventas de prueba que ya existen (sale de sus vehiculo).
INSERT INTO detalle_venta (venta_id, moto_id, chasis, placa, precio, vehiculo_id)
SELECT v.venta_id, v.moto_id, v.chasis, v.placa, m.precio, v.id
FROM vehiculo v
JOIN moto m ON m.id = v.moto_id;

-- Datos de prueba del módulo: una venta de contado sin pagar y una en revisión de crédito.
INSERT INTO venta (numero_factura, fecha, cliente_id, usuario_id, canal, total, estado) VALUES
    (NULL, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, (SELECT id FROM usuario WHERE correo = 'vendedor@motocons.com'), 'tienda', 7800000, 'PENDIENTE'),
    (NULL, CURDATE(), 3, (SELECT id FROM usuario WHERE correo = 'vendedor@motocons.com'), 'tienda', 14900000, 'EN_CREDITO');

INSERT INTO detalle_venta (venta_id, moto_id, chasis, placa, precio) VALUES
    ((SELECT id FROM venta WHERE cliente_id = 2 AND estado = 'PENDIENTE'), 3, '9C2JC6810N0011223', NULL, 7800000),
    ((SELECT id FROM venta WHERE cliente_id = 3 AND estado = 'EN_CREDITO'), 2, 'LXYPCKL06R0007788', NULL, 14900000);


-- =====================  MÓDULO: creditos  =====================
-- Grupo: José Andrés Ríos Quiñonez, Albeiro Luis Hoyos Gonzalez, Hernan Bula Quiñonez,
--        Carlos David Ricardo Reino, Maiver Antonio Hernandez Acosta,
--        Juan Esteban Cardozo Ricardo, Rafael Morales · Fecha: 2026-10-29
-- Usa tablas de otros módulos: cliente, moto, venta, vehiculo (garantia), usuario (login)
-- No modifica tablas de otros módulos: el estado del crédito vive en solicitud_credito.
-- Si el negocio exige que una venta quede 'PAGADA', se habla con el grupo de ventas.

-- Catálogo maestro de requisitos según el medio de pago (convenio Brilla, ServiCrédito).
CREATE TABLE requisito_credito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medio_pago ENUM('BRILLA', 'SERVICREDITO') NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    obligatorio TINYINT(1) NOT NULL DEFAULT 1
);

-- Una solicitud de crédito por venta. Aquí vive el estado del negocio de créditos:
-- BORRADOR -> EN_PROCESO_CREDITO -> APROBADA | RECHAZADA | ANULADA.
-- NO se altera la tabla venta: el crédito es una cosa distinta de la venta.
CREATE TABLE solicitud_credito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    vehiculo_id INT NULL,                    -- unidad que se reserva/entrega con este crédito
    medio_pago ENUM('BRILLA', 'SERVICREDITO') NOT NULL,
    estado ENUM('BORRADOR', 'EN_PROCESO_CREDITO', 'APROBADA', 'RECHAZADA', 'ANULADA')
        NOT NULL DEFAULT 'BORRADOR',
    monto_financiado DECIMAL(12, 2) NOT NULL DEFAULT 0,
    observacion VARCHAR(255) NULL,
    registrado_por INT NULL,                 -- usuario que radicó la solicitud
    aprobado_por INT NULL,                   -- usuario que dictaminó el crédito
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY solicitud_credito_venta_unica (venta_id),  -- una sola solicitud activa por venta
    FOREIGN KEY (venta_id) REFERENCES venta (id),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculo (id),
    FOREIGN KEY (registrado_por) REFERENCES usuario (id),
    FOREIGN KEY (aprobado_por) REFERENCES usuario (id)
);

-- Checklist de la solicitud: qué requisito está adjunto y en qué estado.
CREATE TABLE solicitud_requisito (
    solicitud_id INT NOT NULL,
    requisito_id INT NOT NULL,
    estado ENUM('PENDIENTE', 'VALIDADO', 'RECHAZADO') NOT NULL DEFAULT 'PENDIENTE',
    soporte_adjunto VARCHAR(255) NULL,       -- ruta relativa dentro de storage/soportes/
    validado_por INT NULL,
    validado_en DATETIME NULL,
    PRIMARY KEY (solicitud_id, requisito_id),
    FOREIGN KEY (solicitud_id) REFERENCES solicitud_credito (id) ON DELETE CASCADE,
    FOREIGN KEY (requisito_id) REFERENCES requisito_credito (id),
    FOREIGN KEY (validado_por) REFERENCES usuario (id)
);

-- Catálogo de requisitos de prueba.
INSERT INTO requisito_credito (medio_pago, descripcion, obligatorio) VALUES
    ('BRILLA', 'Recibo de gas del último mes cancelado y con cupo aprobado disponible', 1),
    ('BRILLA', 'Cédula de ciudadanía del titular ampliada al 150%', 1),
    ('BRILLA', 'Dos referencias familiares o personales verificables', 1),
    ('SERVICREDITO', 'Cédula de ciudadanía original del solicitante', 1),
    ('SERVICREDITO', 'Desprendibles de nómina de los dos últimos meses', 1),
    ('SERVICREDITO', 'Certificado laboral vigente con antigüedad mínima de 6 meses', 0);
