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
