-- Historial estructurado de modificaciones de un bien hechas desde "Bienes -> Ver -> Modificar".
-- Fecha: 2026-09-01.
--
-- Por qué: hasta ahora una edición normal del bien solo dejaba una fila genérica MODIFICAR_BIEN en
-- `bitacora` ("Se modificó la información del bien ...") sin decir QUÉ campo cambió ni su valor
-- anterior, y el UPDATE ya había sobrescrito el dato. Esta tabla guarda, campo por campo, el valor
-- anterior y el nuevo de cada guardado real, agrupados por `grupo_cambio` (un clic en "Guardar
-- cambios" = un grupo = un evento en el Historial integral del bien).
--
-- Alcance: SOLO creación de tabla nueva (DDL). No modifica ninguna tabla existente. Sin DROP, sin
-- DELETE, sin UPDATE. Sin backfill: empieza a registrar desde la próxima edición en adelante; los
-- valores perdidos antes de esta migración NO se reconstruyen.
--
-- Qué NO entra aquí (tienen trazabilidad propia y no deben duplicarse):
--   - Código SICOIN            -> `historial_sicoin` (fuente única, ya sale en el Historial integral).
--   - Estado del bien          -> deja de ser editable desde Modificar; cambia solo por el flujo formal de Baja.
--   - Responsable / ubicación / asignación actual -> Requisiciones / Traslados / Bajas / demás movimientos.
--   - Código interno           -> inmutable.
--   - QR, created_at, updated_at.
--
-- Convenciones (SHOW CREATE TABLE contra la BD real de `bitacora` / `notificaciones` /
-- `historial_sicoin`): InnoDB, utf8mb4_unicode_ci, FK con ON UPDATE CASCADE y sin ON DELETE CASCADE.
-- `seccion` es VARCHAR, no ENUM (mismo criterio que estados_bien / formas_ingreso / formas_pago).
-- Requiere que ya existan las tablas `bienes` y `usuarios`.
-- Append-only por lógica: la aplicación nunca hace UPDATE ni DELETE sobre esta tabla.

CREATE TABLE `historial_modificaciones_bien` (
  `id_historial_modificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_bien` int(11) NOT NULL,
  `grupo_cambio` char(32) NOT NULL,
  `seccion` varchar(20) NOT NULL,
  `campo` varchar(60) NOT NULL,
  `valor_anterior` text DEFAULT NULL,
  `valor_nuevo` text DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial_modificacion`),
  KEY `fk_histmod_bien` (`id_bien`),
  KEY `fk_histmod_usuario` (`id_usuario`),
  KEY `idx_histmod_grupo` (`grupo_cambio`),
  KEY `idx_histmod_bien_fecha` (`id_bien`, `fecha_hora`),
  CONSTRAINT `fk_histmod_bien` FOREIGN KEY (`id_bien`) REFERENCES `bienes` (`id_bien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_histmod_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
