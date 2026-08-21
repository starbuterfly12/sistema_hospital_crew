-- Módulo Verificación física: snapshots históricos de responsable, ubicación y condición
-- registrados en el sistema al momento exacto de la verificación. Sin esto, una consulta futura
-- mostraría los datos ACTUALES del bien (que pueden haber cambiado) en vez de los que existían
-- cuando se verificó, rompiendo la trazabilidad histórica. Fecha: 2026-08-20.
-- Alcance: SOLO cambio de esquema (DDL). Sin datos. Tabla `verificaciones_fisicas` con 0 filas
-- antes y después de este cambio (nunca se ha usado) — sin backfill.
-- Fuente de verdad: SHOW CREATE TABLE ejecutado contra la base de datos real del proyecto.
-- Requiere aplicarse sobre el esquema previo a este bloque (ya debe existir: verificaciones_fisicas,
-- bienes, responsables, ubicaciones, usuarios).

-- =============================================================================
-- verificaciones_fisicas: agregar snapshots de responsable/ubicación (por ID, sin ON DELETE
-- CASCADE para preservar trazabilidad — mismo criterio que fk_verificaciones_bienes/
-- fk_verificaciones_usuario ya existentes en esta misma tabla) y condición (texto libre,
-- validado en PHP contra Bien::CONDICIONES_VALIDAS, mismo patrón que condicion_observada).
-- =============================================================================
ALTER TABLE `verificaciones_fisicas`
  ADD COLUMN `id_responsable_registrado` INT(11) DEFAULT NULL AFTER `id_bien`,
  ADD COLUMN `id_ubicacion_registrada` INT(11) DEFAULT NULL AFTER `id_responsable_registrado`,
  ADD COLUMN `condicion_registrada` VARCHAR(50) DEFAULT NULL AFTER `id_ubicacion_registrada`,
  ADD KEY `fk_verificaciones_responsable_registrado` (`id_responsable_registrado`),
  ADD KEY `fk_verificaciones_ubicacion_registrada` (`id_ubicacion_registrada`),
  ADD CONSTRAINT `fk_verificaciones_responsable_registrado` FOREIGN KEY (`id_responsable_registrado`) REFERENCES `responsables` (`id_responsable`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_verificaciones_ubicacion_registrada` FOREIGN KEY (`id_ubicacion_registrada`) REFERENCES `ubicaciones` (`id_ubicacion`) ON UPDATE CASCADE;
