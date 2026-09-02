-- Documentos de respaldo del INGRESO de un bien — colección ACUMULATIVA (0..N por bien).
-- Fecha: 2026-09-01.
--
-- Por qué: hasta ahora cada tabla de ingreso (ingreso_compra / ingreso_donacion / ingreso_traslado)
-- guardaba UNA sola ruta en `documento_respaldo`, y al modificar el bien el documento nuevo
-- SUSTITUÍA al anterior: se perdía la evidencia documental. Esta tabla conserva todos los
-- documentos: se agregan y NUNCA se reemplazan ni se borran desde la aplicación (append-only, ver
-- app/models/DocumentoBien.php).
--
-- Alcance: SOLO tabla nueva + backfill de los documentos existentes. NO modifica `bienes` ni las
-- tablas `ingreso_*`. Las columnas `ingreso_*.documento_respaldo` quedan como LEGADO: no se tocan y
-- NO se eliminan en esta migración (eso sería una migración destructiva mientras hay muchos cambios
-- acumulados). La aplicación deja de leerlas y de escribirlas, y pasa a usar `documentos_bien`.
-- Sin DROP, sin DELETE. Los archivos físicos de storage/documentos/ NO se mueven ni se borran: las
-- filas migradas apuntan al MISMO archivo que ya referenciaba la columna vieja.
--
-- Convenciones (SHOW CREATE TABLE de `historial_modificaciones_bien` / `ingreso_bien_original`):
-- InnoDB, utf8mb4_unicode_ci, FK con ON UPDATE CASCADE y sin ON DELETE CASCADE. `tipo_ingreso` es
-- VARCHAR, no ENUM (mismo criterio que `ingreso_bien_original.tipo_ingreso`). Requiere que ya
-- existan `bienes`, `usuarios`, `ingreso_compra`, `ingreso_donacion`, `ingreso_traslado`.
-- Append-only por lógica: la aplicación nunca hace UPDATE ni DELETE sobre esta tabla.

-- =============================================================================
-- 1) Tabla
-- =============================================================================
CREATE TABLE `documentos_bien` (
  `id_documento_bien` int(11) NOT NULL AUTO_INCREMENT,
  `id_bien` int(11) NOT NULL,
  `tipo_ingreso` varchar(20) NOT NULL,
  `nombre_original` varchar(255) DEFAULT NULL,
  `ruta_documento` varchar(255) NOT NULL,
  `fecha_registro` datetime NOT NULL,
  `id_usuario_registra` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_documento_bien`),
  KEY `fk_documentos_bien_bien` (`id_bien`),
  KEY `fk_documentos_bien_usuario` (`id_usuario_registra`),
  KEY `idx_documentos_bien_bien_fecha` (`id_bien`, `fecha_registro`),
  CONSTRAINT `fk_documentos_bien_bien` FOREIGN KEY (`id_bien`) REFERENCES `bienes` (`id_bien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_documentos_bien_usuario` FOREIGN KEY (`id_usuario_registra`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2) Backfill de los documentos existentes
-- =============================================================================
-- Una fila por cada `ingreso_*.documento_respaldo` no vacío. Detalles:
--   - nombre_original -> NULL: la columna vieja solo guardaba el nombre físico aleatorio, no el
--     nombre real que subió la persona; la aplicación muestra "Documento de respaldo" en ese caso.
--   - id_usuario_registra -> NULL: no se sabe quién lo cargó.
--   - fecha_registro -> alta del bien (b.created_at): baseline defendible ("documento registrado
--     desde el inicio"), sin inventar una fecha que no consta.
INSERT INTO `documentos_bien`
  (`id_bien`, `tipo_ingreso`, `nombre_original`, `ruta_documento`, `fecha_registro`, `id_usuario_registra`)
SELECT src.id_bien, src.tipo_ingreso, NULL, src.ruta_documento, COALESCE(b.created_at, NOW()), NULL
FROM (
  SELECT ic.id_bien, 'compra' AS tipo_ingreso, ic.documento_respaldo AS ruta_documento
  FROM `ingreso_compra` ic
  WHERE ic.documento_respaldo IS NOT NULL AND ic.documento_respaldo <> ''
  UNION ALL
  SELECT idn.id_bien, 'donacion', idn.documento_respaldo
  FROM `ingreso_donacion` idn
  WHERE idn.documento_respaldo IS NOT NULL AND idn.documento_respaldo <> ''
  UNION ALL
  SELECT itr.id_bien, 'traslado', itr.documento_respaldo
  FROM `ingreso_traslado` itr
  WHERE itr.documento_respaldo IS NOT NULL AND itr.documento_respaldo <> ''
) src
JOIN `bienes` b ON b.id_bien = src.id_bien;
