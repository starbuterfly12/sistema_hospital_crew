-- Fotografía principal del bien (apoyo visual para identificarlo físicamente).
-- Fecha: 2026-09-01.
--
-- Por qué hace falta: `imagen_bien` YA existe en `detalle_baja` (foto tomada al dar de baja), pero
-- NO en `bienes`. Este bloque agrega la foto propia del bien, cargable al registrarlo y sustituible
-- desde "Modificar bien". 1 bien = 1 fotografía (sin galería, sin flujo de eliminación por ahora).
--
-- Alcance: solo una columna NULLABLE en `bienes`. Sin catálogo, sin FK (guarda una ruta relativa a
-- storage/, nunca un BLOB), sin backfill (los bienes existentes quedan con imagen_bien = NULL, que
-- es válido). Sin DROP, sin DELETE, sin UPDATE. No toca ninguna otra tabla.
--
-- El archivo físico vive en storage/fotos_bienes/ (protegida por storage/.htaccess, igual que
-- documentos/ y fotos_baja/) y se sirve por un controlador autenticado
-- (index.php?modulo=bienes&accion=imagen&id=...), nunca por URL directa.

ALTER TABLE `bienes`
  ADD COLUMN `imagen_bien` varchar(255) DEFAULT NULL AFTER `ruta_qr`;
