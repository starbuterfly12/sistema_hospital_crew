-- Módulo Bajas: correlativo institucional en cabecera, snapshots históricos en detalle, tipo de
-- baja + justificación movidos de cabecera a detalle (cada bien de una misma solicitud puede tener
-- un tipo de baja y una justificación distintos), y columnas de decisión administrativa de rechazo
-- (id_usuario_rechaza, fecha_rechazo — SIN motivo_rechazo: el proceso aprobado no exige
-- justificación de rechazo, solo Aceptar/Rechazar). Revisión funcional 2026-08-19. Alcance: solo
-- DDL, sin datos. Aplicable sobre el esquema de bajas/detalle_baja tal como quedó definido antes de
-- esta fase (0 filas en ambas tablas durante todo el desarrollo).

ALTER TABLE `bajas`
  ADD COLUMN `numero_baja` VARCHAR(50) NOT NULL AFTER `id_baja`,
  ADD UNIQUE KEY `uq_bajas_numero_baja` (`numero_baja`),
  DROP FOREIGN KEY `fk_bajas_tipos_baja`,
  DROP COLUMN `id_tipo_baja`,
  DROP COLUMN `motivo`,
  ADD COLUMN `id_usuario_rechaza` INT(11) DEFAULT NULL AFTER `id_usuario_autoriza`,
  ADD COLUMN `fecha_rechazo` DATETIME DEFAULT NULL AFTER `fecha_autorizacion`,
  ADD KEY `fk_bajas_usuario_rechaza` (`id_usuario_rechaza`),
  ADD CONSTRAINT `fk_bajas_usuario_rechaza` FOREIGN KEY (`id_usuario_rechaza`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE;

ALTER TABLE `detalle_baja`
  ADD COLUMN `codigo_interno_mostrado` VARCHAR(50) NOT NULL AFTER `id_bien`,
  ADD COLUMN `codigo_sicoin_mostrado` VARCHAR(50) DEFAULT NULL AFTER `codigo_interno_mostrado`,
  ADD COLUMN `descripcion_mostrada` TEXT NOT NULL AFTER `codigo_sicoin_mostrado`,
  ADD COLUMN `marca_mostrada` VARCHAR(100) DEFAULT NULL AFTER `descripcion_mostrada`,
  ADD COLUMN `modelo_mostrado` VARCHAR(100) DEFAULT NULL AFTER `marca_mostrada`,
  ADD COLUMN `serie_mostrada` VARCHAR(100) DEFAULT NULL AFTER `modelo_mostrado`,
  ADD COLUMN `valor_mostrado` DECIMAL(12,2) NOT NULL AFTER `serie_mostrada`,
  ADD COLUMN `id_tipo_baja` INT(11) NOT NULL AFTER `valor_mostrado`,
  ADD COLUMN `justificacion` TEXT NOT NULL AFTER `id_tipo_baja`,
  ADD UNIQUE KEY `uq_detalle_baja_bien` (`id_baja`, `id_bien`),
  ADD KEY `fk_detalle_baja_tipos_baja` (`id_tipo_baja`),
  ADD CONSTRAINT `fk_detalle_baja_tipos_baja` FOREIGN KEY (`id_tipo_baja`) REFERENCES `tipos_baja` (`id_tipo_baja`) ON UPDATE CASCADE;
