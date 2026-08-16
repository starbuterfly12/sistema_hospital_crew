# Guía de instalación y despliegue

## 1. Estado de esta guía

⚠️ **El despliegue en producción (Ubuntu) TODAVÍA NO SE HA REALIZADO.** Ver la sección 5 para lo que sigue pendiente en ese frente.

Esta guía sí documenta la **instalación local de desarrollo** (Windows + XAMPP), que es el entorno donde el proyecto se ha construido y probado hasta ahora. Los pasos de la sección 3 son reales y utilizables hoy para levantar una copia del proyecto en otra computadora de desarrollo.

## 2. Requisitos

- Apache (XAMPP en desarrollo; Ubuntu + Apache en la futura producción)
- PHP 8.2 o superior, compatible con lo bloqueado en `composer.lock`
- MariaDB
- Composer
- Git (opcional, solo si se va a clonar el repositorio en vez de copiar los archivos)

### Extensiones PHP requeridas

Deben estar **disponibles** en el PHP que se use (la mayoría ya vienen activas por defecto en una instalación estándar de XAMPP; no es necesario habilitarlas manualmente salvo que se indique lo contrario):

- `pdo_mysql` — toda la capa de datos del sistema usa PDO para conectarse a MariaDB.
- `fileinfo` — valida el tipo real de los documentos de respaldo subidos en el módulo de Bienes.
- `gd` — requerida por `endroid/qr-code` para generar las imágenes PNG de los códigos QR.
- `zip` — **obligatoria**, requerida por `phpoffice/phpspreadsheet` para leer y escribir archivos `.xlsx` (un `.xlsx` es internamente un archivo ZIP). Sin ella, la generación y descarga de Tarjetas de Responsabilidad no funciona.

Verificar desde la terminal:

```
php -m | findstr zip
```

(en Linux/macOS: `php -m | grep zip`). Repetir para las demás extensiones si hace falta confirmar alguna en particular.

### Habilitar `ext-zip` en XAMPP (Windows)

En instalaciones de XAMPP esta extensión puede venir deshabilitada por defecto. Si `php -m` no la muestra:

1. Abrir `C:\xampp\php\php.ini` con un editor de texto.
2. Buscar la línea de la extensión zip. Puede aparecer comentada de distintas formas según la versión de XAMPP (por ejemplo `;extension=zip`); lo relevante es que empiece con `;` y contenga `zip`.
3. Quitar el `;` inicial para dejarla activa: `extension=zip`.
4. Guardar el archivo.
5. **Reiniciar Apache** desde el Panel de Control de XAMPP para que tome el cambio.
6. Verificar de nuevo con `php -m | findstr zip`.

**Importante:** el PHP que usa la línea de comandos (CLI) y el PHP que usa Apache pueden estar leyendo archivos `php.ini` distintos o tener extensiones habilitadas de forma independiente. Conviene confirmar ambos — de lo contrario, algo puede funcionar al probarlo por consola y fallar al descargarlo desde el navegador, o viceversa.

`php.ini` **no forma parte del repositorio** (es configuración del entorno PHP del servidor, no del proyecto) — cada instalación nueva debe revisar sus propias extensiones siguiendo esta sección.

## 3. Instalación local (desarrollo)

1. Clonar u obtener una copia del repositorio dentro de `htdocs/` (u otro directorio servido por Apache).
2. Ejecutar `composer install` — **no** `composer update`. Esto instala exactamente las versiones bloqueadas en `composer.lock` (actualmente `endroid/qr-code` y `phpoffice/phpspreadsheet`, junto con sus dependencias), sin arriesgar una actualización no probada.
3. Crear `config/Database.php` a partir de `config/Database.example.php` y completar ahí las credenciales locales de MariaDB. El nombre de base de datos usado por el proyecto es `sistema_hospital`.
4. Crear `config/app.php` a partir de `config/app.example.php` y definir `base_url` según la URL local.
5. Preparar la base de datos — ver advertencia importante en la sección 4, todavía no hay un script SQL consolidado en el repositorio.
6. Confirmar que existan y sean escribibles por PHP: `storage/qr/`, `storage/documentos/`, `storage/fotos_baja/`, `storage/respaldos/` (ver [storage/README.md](storage/README.md) para el detalle de cada una).
7. Confirmar que existan `storage/templates/tarjetas_responsabilidad.xlsx` y `storage/templates/constancia_traslado.xlsx` — son las plantillas institucionales necesarias para que la descarga de Tarjetas de Responsabilidad y de la Constancia de Traslado funcionen, respectivamente. Ambas deben ser **legibles** por PHP (no son carpetas de salida). Vienen incluidas y versionadas en el repositorio; si falta alguna, la exportación correspondiente fallará. Ninguna debe sustituirse por una plantilla vacía o de prueba — tienen que ser las plantillas institucionales reales.
8. Configurar el acceso vía Apache (VirtualHost, o directamente la carpeta del proyecto dentro de `htdocs/` en XAMPP).
9. Verificar las extensiones PHP de la sección 2, en particular `zip`.

**Nota sobre Flatpickr:** los assets del selector de fechas (`public/vendor/flatpickr/`) ya vienen incluidos y versionados en el repositorio como copia local oficial (JS, CSS y localización en español). No requieren `npm`, Node ni conexión a Internet para funcionar — se sirven directamente desde `public/` igual que el resto de assets estáticos del proyecto.

## 4. Estado real de la base de datos

⚠️ El repositorio **todavía no contiene** un script SQL consolidado y actualizado dentro de `database/` que reproduzca el esquema completo actual. Hoy `database/` solo contiene un `.gitkeep`, sin ningún `.sql` versionado.

El esquema real (incluyendo tablas y columnas agregadas durante el desarrollo del módulo de Tarjetas de Responsabilidad — `tarjetas_responsabilidad`, `detalle_tarjeta_responsabilidad`, `historial_sicoin`, además de todas las demás tablas del sistema) existe únicamente en la base de datos del entorno de desarrollo actual. Instalar el proyecto en una computadora nueva **no** reproduce automáticamente esta estructura. Generar y versionar un script SQL consolidado sigue pendiente; hasta que exista, preparar la base de datos en una instalación nueva requiere reconstruir el esquema manualmente a partir del entorno de desarrollo de referencia.

## 5. Entorno futuro de producción

- Ubuntu (versión PENDIENTE de definir)
- Apache
- PHP
- MariaDB
- Red interna del hospital

### Pendientes antes del despliegue

- Finalizar los módulos del sistema que aún están en desarrollo (ver README.md, sección "Módulos pendientes").
- Generar y versionar el script SQL consolidado en `database/` (ver sección 4).
- Determinar qué catálogos iniciales deben incluirse en la base de datos.
- Definir la versión de Ubuntu a utilizar.
- Definir IP fija o hostname institucional del servidor.
- Crear usuario y contraseña de MariaDB para producción.
- Definir el VirtualHost definitivo de Apache.
- Determinar si el servidor tendrá acceso a internet para ejecutar `composer install`, o si se requerirá un procedimiento alterno sin conexión.
- Realizar pruebas completas del sistema antes de pasar a producción.
- Revisar seguridad y permisos de archivos y carpetas en el servidor.

## 6. Credenciales y configuración local

`config/Database.php` y `config/app.php` deben crearse **localmente** en cada entorno (desarrollo o producción) a partir de sus plantillas `.example.php`.

**Estos archivos nunca deben contener credenciales reales de producción dentro de Git.** Permanecen fuera del control de versiones (ya excluidos vía `.gitignore`).
