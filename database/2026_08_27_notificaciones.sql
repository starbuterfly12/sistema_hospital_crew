-- Módulo Notificaciones internas: avisos generados EN EL MOMENTO de un evento real del flujo
-- (registro / autorización / rechazo de Requisiciones y Solicitudes de baja), dirigidos a un
-- usuario concreto. No sustituye a la bitácora (auditoría append-only): la bitácora registra
-- QUÉ pasó para trazabilidad; esta tabla registra A QUIÉN hay que avisarle y si ya lo vio.
-- Fecha: 2026-08-27.
--
-- Alcance: SOLO creación de tabla nueva (DDL). No modifica ninguna tabla existente. Sin DROP.
-- Sin backfill: las notificaciones solo tienen sentido hacia adelante, desde el próximo evento.
-- Fuente de verdad de convenciones (engine / charset / collation / FK ON UPDATE CASCADE sin
-- ON DELETE): SHOW CREATE TABLE `bitacora` de la base real del proyecto.
-- Requiere que ya exista la tabla `usuarios`.

CREATE TABLE `notificaciones` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `url_destino` varchar(255) NOT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_lectura` datetime DEFAULT NULL,
  PRIMARY KEY (`id_notificacion`),
  KEY `fk_notificaciones_usuarios` (`id_usuario`),
  KEY `idx_notificaciones_usuario_estado` (`id_usuario`, `leida`, `fecha_creacion`),
  CONSTRAINT `fk_notificaciones_usuarios` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
