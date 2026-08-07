# storage/

Carpeta destinada a archivos generados o subidos por el sistema en tiempo de ejecución.

## Subcarpetas

### `storage/qr/`
Códigos QR generados para los bienes institucionales.

**Actualmente es la única carpeta en uso funcional real**, utilizada por el módulo de bienes para la generación y regeneración de códigos QR.

### `storage/fotos_baja/`
Futuras imágenes o evidencias relacionadas con bajas de bienes. Aún no está en uso funcional (el módulo de bajas continúa en desarrollo).

### `storage/respaldos/`
Futuros archivos de respaldo generados por el sistema. Aún no está en uso funcional (el módulo de respaldos continúa en desarrollo).

### `storage/documentos/`
Documentos de respaldo asociados a procesos administrativos. Aún no está en uso funcional.

## Notas importantes

- El contenido real de estas carpetas **no se versiona** en Git (ver reglas en `.gitignore`).
- Los archivos `.gitkeep` en cada subcarpeta existen únicamente para conservar la estructura de directorios dentro del repositorio.
- En el servidor de producción, estas carpetas necesitarán permisos de escritura para el usuario del servidor web (Apache), según qué módulos estén activos y en uso en cada momento.
- Por ahora, solo `storage/qr/` está siendo utilizada funcionalmente por el sistema.
