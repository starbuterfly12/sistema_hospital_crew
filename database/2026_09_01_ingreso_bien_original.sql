-- Snapshot congelado de los datos de INGRESO de un bien, para que el evento "Ingreso del bien" del
-- Historial integral (HistorialBien::eventoIngreso()) nunca cambie retroactivamente cuando después
-- se corrija información desde "Bienes -> Modificar".
-- Fecha: 2026-09-01.
--
-- Problema que resuelve: hoy eventoIngreso() lee valores MUTABLES en vivo de `bienes` / `ingreso_*`
-- (fecha de ingreso, proveedor, entidad donante, procedencia, unidad ejecutora de origen). Si esos
-- datos se editan luego, el evento histórico pasa a mostrar los valores nuevos como si hubieran sido
-- los del ingreso. Con este snapshot el evento queda fijo; los cambios posteriores se ven en el
-- evento "Modificación de información" (tabla historial_modificaciones_bien).
--
-- Alcance: SOLO tabla nueva + backfill. NO modifica `bienes` ni `ingreso_*`. Sin DROP, sin DELETE.
-- 1 fila por bien (UNIQUE id_bien). Snapshot append-once: la aplicación nunca hace UPDATE ni DELETE
-- sobre esta tabla (ver IngresoBienOriginal.php).
--
-- Solo se congelan las columnas MUTABLES que eventoIngreso() realmente usa. NO se duplican datos que
-- ya son inmutables o tienen fuente histórica propia:
--   - codigo_interno            -> inmutable (nunca se reescribe).
--   - forma de ingreso          -> bloqueada tras el registro; se guarda `tipo_ingreso` como
--                                  conveniencia denormalizada (compra | donacion | traslado).
--   - SICOIN "al ingreso"       -> ya se reconstruye desde historial_sicoin en eventoIngreso().
--   - resguardo / asignación inicial -> ya se reconstruye del primer detalle_asignacion.
--
-- Backfill (bienes existentes): se copian los valores ACTUALES al momento de ejecutar esta
-- migración. Para bienes que YA habían sido editados antes de esta mejora, esos valores son un
-- BASELINE CONGELADO, no necesariamente los originales (las entradas viejas de MODIFICAR_BIEN eran
-- genéricas y no permiten reconstruir el valor real). No se inventa nada; a partir de aquí el evento
-- Ingreso queda fijo.
--
-- Convenciones (SHOW CREATE TABLE de `ingreso_compra` / `formas_pago`): InnoDB, utf8mb4_unicode_ci,
-- FK con ON UPDATE CASCADE y sin ON DELETE CASCADE. Requiere que ya existan `bienes`, `ingreso_compra`,
-- `ingreso_donacion`, `ingreso_traslado`, `formas_ingreso`.

-- =============================================================================
-- 1) Tabla
-- =============================================================================
CREATE TABLE `ingreso_bien_original` (
  `id_snapshot_ingreso` int(11) NOT NULL AUTO_INCREMENT,
  `id_bien` int(11) NOT NULL,
  `tipo_ingreso` varchar(20) NOT NULL,
  `fecha_ingreso_original` date NOT NULL,
  `proveedor_original` varchar(150) DEFAULT NULL,
  `entidad_donante_original` varchar(150) DEFAULT NULL,
  `procedencia_donacion_original` varchar(150) DEFAULT NULL,
  `unidad_ejecutora_origen_original` varchar(150) DEFAULT NULL,
  `procedencia_traslado_original` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_snapshot_ingreso`),
  UNIQUE KEY `uq_ingreso_bien_original_bien` (`id_bien`),
  CONSTRAINT `fk_ingreso_bien_original_bien` FOREIGN KEY (`id_bien`) REFERENCES `bienes` (`id_bien`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2) Backfill de los bienes existentes (valores actuales = baseline congelado)
-- =============================================================================
-- tipo_ingreso se resuelve por nombre de `formas_ingreso` con LIKE para no depender de la
-- codificación del acento en "Donación" ni de ids fijos del catálogo.
INSERT INTO `ingreso_bien_original`
  (`id_bien`, `tipo_ingreso`, `fecha_ingreso_original`,
   `proveedor_original`, `entidad_donante_original`, `procedencia_donacion_original`,
   `unidad_ejecutora_origen_original`, `procedencia_traslado_original`)
SELECT
  b.id_bien,
  CASE
    WHEN fi.nombre_forma LIKE 'Compra%'   THEN 'compra'
    WHEN fi.nombre_forma LIKE 'Donaci%'   THEN 'donacion'
    WHEN fi.nombre_forma LIKE 'Traslado%' THEN 'traslado'
    ELSE LOWER(fi.nombre_forma)
  END AS tipo_ingreso,
  b.fecha_ingreso,
  ic.proveedor,
  idn.entidad_donante,
  idn.procedencia,
  itr.unidad_ejecutora_origen,
  itr.procedencia
FROM `bienes` b
JOIN `formas_ingreso` fi          ON b.id_forma_ingreso = fi.id_forma_ingreso
LEFT JOIN `ingreso_compra` ic     ON ic.id_bien = b.id_bien
LEFT JOIN `ingreso_donacion` idn  ON idn.id_bien = b.id_bien
LEFT JOIN `ingreso_traslado` itr  ON itr.id_bien = b.id_bien;
