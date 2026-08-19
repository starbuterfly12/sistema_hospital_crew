-- Migración: módulo Requisiciones + snapshots de modelo/serie en constancias.
-- Fecha: 2026-08-19
-- Alcance: SOLO cambios de esquema (DDL). No contiene datos, catálogos, ni IDs de este entorno.
-- Fuente de verdad: SHOW CREATE TABLE ejecutado contra la base de datos real del proyecto.
-- Requiere aplicarse sobre el esquema previo a este bloque (ya debe existir: bienes,
-- responsables, ubicaciones, asignaciones, detalle_asignacion, prestamos, detalle_prestamo,
-- tarjetas_responsabilidad, detalle_tarjeta_responsabilidad, usuarios).

-- =============================================================================
-- 1. Tabla nueva: requisiciones (cabecera)
-- =============================================================================
CREATE TABLE `requisiciones` (
  `id_requisicion` int(11) NOT NULL AUTO_INCREMENT,
  `numero_requisicion_sistema` varchar(50) NOT NULL,
  `numero_oficio` varchar(50) NOT NULL,
  `id_responsable_solicitante` int(11) NOT NULL,
  `responsable_solicitante_mostrado` varchar(150) NOT NULL,
  `id_ubicacion_solicitante` int(11) NOT NULL,
  `ubicacion_solicitante_mostrada` varchar(150) NOT NULL,
  `id_usuario_registra` int(11) NOT NULL,
  `id_usuario_autoriza` int(11) DEFAULT NULL,
  `fecha_autorizacion` datetime DEFAULT NULL,
  `id_usuario_entrega` int(11) DEFAULT NULL,
  `fecha_entrega` datetime DEFAULT NULL,
  `estado_requisicion` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `motivo_anulacion` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_requisicion`),
  UNIQUE KEY `uq_requisiciones_numero_sistema` (`numero_requisicion_sistema`),
  KEY `fk_requisiciones_responsable_solicitante` (`id_responsable_solicitante`),
  KEY `fk_requisiciones_ubicacion_solicitante` (`id_ubicacion_solicitante`),
  KEY `fk_requisiciones_usuario_registra` (`id_usuario_registra`),
  KEY `fk_requisiciones_usuario_autoriza` (`id_usuario_autoriza`),
  KEY `fk_requisiciones_usuario_entrega` (`id_usuario_entrega`),
  CONSTRAINT `fk_requisiciones_responsable_solicitante` FOREIGN KEY (`id_responsable_solicitante`) REFERENCES `responsables` (`id_responsable`) ON UPDATE CASCADE,
  CONSTRAINT `fk_requisiciones_ubicacion_solicitante` FOREIGN KEY (`id_ubicacion_solicitante`) REFERENCES `ubicaciones` (`id_ubicacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_requisiciones_usuario_autoriza` FOREIGN KEY (`id_usuario_autoriza`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_requisiciones_usuario_entrega` FOREIGN KEY (`id_usuario_entrega`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_requisiciones_usuario_registra` FOREIGN KEY (`id_usuario_registra`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. Tabla nueva: numeros_requisicion (1..N números físicos por requisición)
-- =============================================================================
CREATE TABLE `numeros_requisicion` (
  `id_numero_requisicion` int(11) NOT NULL AUTO_INCREMENT,
  `id_requisicion` int(11) NOT NULL,
  `numero_requisicion` varchar(50) NOT NULL,
  `fecha_requisicion` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_numero_requisicion`),
  UNIQUE KEY `uq_numeros_requisicion_requisicion_numero` (`id_requisicion`,`numero_requisicion`),
  CONSTRAINT `fk_numeros_requisicion_requisiciones` FOREIGN KEY (`id_requisicion`) REFERENCES `requisiciones` (`id_requisicion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. Tabla nueva: detalle_requisicion (bienes por requisición, con snapshots ya
--    incluidos desde su creación — sin ALTER redundante posterior)
-- =============================================================================
CREATE TABLE `detalle_requisicion` (
  `id_detalle_requisicion` int(11) NOT NULL AUTO_INCREMENT,
  `id_requisicion` int(11) NOT NULL,
  `id_bien` int(11) NOT NULL,
  `id_detalle_asignacion_origen` int(11) NOT NULL,
  `id_detalle_asignacion_destino` int(11) DEFAULT NULL,
  `codigo_interno_mostrado` varchar(50) NOT NULL,
  `codigo_sicoin_mostrado` varchar(50) DEFAULT NULL,
  `descripcion_mostrada` text NOT NULL,
  `serie_mostrada` varchar(100) DEFAULT NULL,
  `modelo_mostrado` varchar(100) DEFAULT NULL,
  `valor_mostrado` decimal(12,2) NOT NULL,
  `estado_detalle` varchar(20) NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_detalle_requisicion`),
  UNIQUE KEY `uq_detalle_requisicion_bien` (`id_requisicion`,`id_bien`),
  KEY `fk_detalle_requisicion_bienes` (`id_bien`),
  KEY `fk_detalle_requisicion_det_asig_origen` (`id_detalle_asignacion_origen`),
  KEY `fk_detalle_requisicion_det_asig_destino` (`id_detalle_asignacion_destino`),
  CONSTRAINT `fk_detalle_requisicion_bienes` FOREIGN KEY (`id_bien`) REFERENCES `bienes` (`id_bien`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_requisicion_det_asig_destino` FOREIGN KEY (`id_detalle_asignacion_destino`) REFERENCES `detalle_asignacion` (`id_detalle_asignacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_requisicion_det_asig_origen` FOREIGN KEY (`id_detalle_asignacion_origen`) REFERENCES `detalle_asignacion` (`id_detalle_asignacion`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_requisicion_requisiciones` FOREIGN KEY (`id_requisicion`) REFERENCES `requisiciones` (`id_requisicion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. detalle_prestamo: agregar snapshot de modelo (serie_mostrada ya existía)
-- =============================================================================
ALTER TABLE `detalle_prestamo`
  ADD COLUMN `modelo_mostrado` VARCHAR(100) NULL AFTER `serie_mostrada`;

-- =============================================================================
-- 5. detalle_tarjeta_responsabilidad: agregar snapshots de modelo y serie
-- =============================================================================
ALTER TABLE `detalle_tarjeta_responsabilidad`
  ADD COLUMN `modelo_mostrado` VARCHAR(100) NULL AFTER `descripcion_mostrada`,
  ADD COLUMN `serie_mostrada` VARCHAR(100) NULL AFTER `modelo_mostrado`;
