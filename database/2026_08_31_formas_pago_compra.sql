-- Nuevo dato para bienes ingresados por COMPRA: "Forma de pago".
-- Fecha: 2026-08-31.
--
-- Alcance: catálogo nuevo + una columna nullable en `ingreso_compra`. Sin ENUM (mismo criterio que
-- formas_ingreso / categorias_bien / estados_bien). Sin DROP, sin DELETE, sin UPDATE masivo.
-- El único INSERT es el valor de catálogo confirmado: 'Directo'. No se inventan otras formas.
--
-- Compatibilidad histórica: las compras ya registradas NO tienen forma de pago conocida, así que
-- `ingreso_compra.id_forma_pago` queda NULL para todas ellas. NO se les asigna 'Directo'
-- automáticamente porque no se sabe si esa fue realmente su forma de pago; se completa manualmente
-- desde "Modificar bien" cuando alguien conozca el dato.
--
-- Convenciones tomadas de los catálogos existentes (SHOW CREATE TABLE contra la BD real):
--   - InnoDB, utf8mb4_unicode_ci, sin timestamps (igual que categorias_bien / estados_bien).
--   - id_<x> PK AUTO_INCREMENT, nombre_<x> VARCHAR(50) NOT NULL con UNIQUE, descripcion TEXT NULL,
--     estado_<x> VARCHAR(20) NOT NULL DEFAULT 'activa'.
--   - FK a catálogo con ON UPDATE CASCADE, sin ON DELETE CASCADE (mismo criterio que
--     fk_bienes_categorias / fk_bienes_estados).

-- =============================================================================
-- 1) Catálogo de formas de pago
-- =============================================================================
CREATE TABLE `formas_pago` (
  `id_forma_pago` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_forma_pago` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado_forma_pago` varchar(20) NOT NULL DEFAULT 'activa',
  PRIMARY KEY (`id_forma_pago`),
  UNIQUE KEY `uq_formas_pago_nombre` (`nombre_forma_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2) Valor inicial confirmado (único)
-- =============================================================================
INSERT INTO `formas_pago` (`nombre_forma_pago`, `descripcion`, `estado_forma_pago`)
VALUES ('Directo', NULL, 'activa');

-- =============================================================================
-- 3) Columna en ingreso_compra (NULLABLE) + 4) índice + 5) FK
-- =============================================================================
ALTER TABLE `ingreso_compra`
  ADD COLUMN `id_forma_pago` int(11) DEFAULT NULL AFTER `numero_liquidacion`,
  ADD KEY `fk_ingreso_compra_forma_pago` (`id_forma_pago`),
  ADD CONSTRAINT `fk_ingreso_compra_forma_pago` FOREIGN KEY (`id_forma_pago`) REFERENCES `formas_pago` (`id_forma_pago`) ON UPDATE CASCADE;
