-- Módulo Verificación física — NUEVA MODALIDAD: "Verificación física por asignación".
-- Permite revisar en una sola jornada todos los bienes activos actualmente cargados a una
-- asignación/responsable, reutilizando la verificación individual bien por bien.
-- Fecha: 2026-08-30.
--
-- Alcance: SOLO cambios de esquema (DDL). Sin datos, sin backfill, sin DROP, sin renombrados.
--   1) Tabla nueva `verificaciones_asignacion`: cabecera/jornada (contexto + resumen + snapshots).
--   2) `verificaciones_fisicas`: columna nueva NULLABLE `id_verificacion_asignacion` que enlaza
--      cada verificación individual generada dentro de una jornada con su cabecera.
--        - NULL  => verificación individual (comportamiento actual, intacto).
--        - valor => verificación registrada dentro de una jornada por asignación.
--
-- La verificación por asignación NO modifica el bien (igual que la individual): solo guarda el
-- snapshot observado. Cada bien revisado sigue viviendo como una fila en `verificaciones_fisicas`
-- (historial del bien reutilizado), y la cabecera agrupa la jornada.
--
-- Convenciones tomadas de las tablas existentes (SHOW CREATE TABLE contra la BD real):
--   - InnoDB, utf8mb4_unicode_ci.
--   - FKs SIN ON DELETE CASCADE para preservar trazabilidad histórica (mismo criterio que
--     fk_verificaciones_bienes / fk_verificaciones_responsable_registrado, etc.). ON UPDATE CASCADE.
--   - Snapshots por ID + texto (numero_asignacion) para que una consulta histórica no cambie de
--     significado si el responsable/ubicación de la asignación se modifican después.

-- =============================================================================
-- 1) Cabecera / jornada de verificación por asignación
-- =============================================================================
CREATE TABLE `verificaciones_asignacion` (
  `id_verificacion_asignacion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_asignacion` INT(11) NOT NULL,
  `numero_asignacion` VARCHAR(50) NOT NULL,
  `id_responsable_registrado` INT(11) DEFAULT NULL,
  `id_ubicacion_registrada` INT(11) DEFAULT NULL,
  `id_usuario_verifica` INT(11) NOT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT current_timestamp(),
  `total_esperado` INT(11) NOT NULL DEFAULT 0,
  `total_revisado` INT(11) NOT NULL DEFAULT 0,
  `total_localizados` INT(11) NOT NULL DEFAULT 0,
  `total_no_localizados` INT(11) NOT NULL DEFAULT 0,
  `total_con_diferencias` INT(11) NOT NULL DEFAULT 0,
  `total_sin_diferencias` INT(11) NOT NULL DEFAULT 0,
  `porcentaje_localizacion` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `porcentaje_sin_diferencias` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `observaciones` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_verificacion_asignacion`),
  KEY `fk_verif_asig_asignacion` (`id_asignacion`),
  KEY `fk_verif_asig_responsable` (`id_responsable_registrado`),
  KEY `fk_verif_asig_ubicacion` (`id_ubicacion_registrada`),
  KEY `fk_verif_asig_usuario` (`id_usuario_verifica`),
  CONSTRAINT `fk_verif_asig_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignaciones` (`id_asignacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_verif_asig_responsable` FOREIGN KEY (`id_responsable_registrado`) REFERENCES `responsables` (`id_responsable`) ON UPDATE CASCADE,
  CONSTRAINT `fk_verif_asig_ubicacion` FOREIGN KEY (`id_ubicacion_registrada`) REFERENCES `ubicaciones` (`id_ubicacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_verif_asig_usuario` FOREIGN KEY (`id_usuario_verifica`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2) Enlace de cada verificación individual con su jornada (nullable)
-- =============================================================================
ALTER TABLE `verificaciones_fisicas`
  ADD COLUMN `id_verificacion_asignacion` INT(11) DEFAULT NULL AFTER `id_usuario_verifica`,
  ADD KEY `fk_verificaciones_verif_asignacion` (`id_verificacion_asignacion`),
  ADD CONSTRAINT `fk_verificaciones_verif_asignacion` FOREIGN KEY (`id_verificacion_asignacion`) REFERENCES `verificaciones_asignacion` (`id_verificacion_asignacion`) ON UPDATE CASCADE;
