-- =============================================================
-- Base de datos única de MOTOCONS.
-- Antes de agregar algo, LEE TODO ESTE ARCHIVO: la tabla que necesitas
-- puede existir ya. Cada grupo agrega sus tablas al final, en su sección.
-- =============================================================
CREATE DATABASE IF NOT EXISTS motocons
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
