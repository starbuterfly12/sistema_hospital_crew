# Guía de instalación y despliegue

## 1. Estado de esta guía

⚠️ **El despliegue en Ubuntu TODAVÍA NO SE HA REALIZADO.**

Este documento describe la instalación prevista a futuro. Algunos pasos y datos permanecen **PENDIENTES** hasta finalizar el desarrollo del sistema y definir el entorno de producción real. No debe interpretarse como una guía ya ejecutada ni validada en producción.

## 2. Requisitos conocidos actualmente

- Ubuntu (versión PENDIENTE de definir)
- Apache
- PHP 8.2 o superior
- MariaDB
- Composer
- Extensiones PHP requeridas:
  - `pdo`
  - `pdo_mysql`
  - `mbstring`
  - `gd`
  - `iconv`
  - `session`

## 3. Instalación futura prevista

Estos pasos describen el proceso previsto. **No deben ejecutarse todavía** salvo cuando se decida iniciar el despliegue real.

1. Clonar el repositorio.
2. Ejecutar `composer install`.
3. Crear `config/Database.php` a partir de `config/Database.example.php`.
4. Crear `config/app.php` a partir de `config/app.example.php`.
5. Configurar las credenciales de base de datos en `config/Database.php`.
6. Definir `base_url` en `config/app.php`.
7. Preparar la base de datos (crear la base y cargar el esquema correspondiente).
8. Configurar permisos de escritura en `storage/` según los módulos que los requieran (ver [storage/README.md](storage/README.md)).
9. Configurar el VirtualHost de Apache para el proyecto.
10. Comprobar que las extensiones PHP requeridas estén habilitadas.

## 4. PENDIENTES ANTES DEL DESPLIEGUE

- Finalizar los módulos del sistema que aún están en desarrollo.
- Exportar el esquema definitivo de MariaDB a `database/sistema_hospital.sql`.
- Determinar qué catálogos iniciales deben incluirse en la base de datos.
- Definir la versión de Ubuntu a utilizar.
- Definir IP fija o hostname institucional del servidor.
- Crear usuario y contraseña de MariaDB para producción.
- Definir el VirtualHost definitivo de Apache.
- Determinar si el servidor tendrá acceso a internet para poder ejecutar `composer install` (o si se requerirá un procedimiento alterno sin conexión).
- Realizar pruebas completas del sistema antes de pasar a producción.
- Revisar seguridad y permisos de archivos y carpetas en el servidor.

## 5. Credenciales y configuración local

`config/Database.php` y `config/app.php` deben crearse **localmente** en cada entorno (desarrollo o producción) a partir de sus plantillas `.example.php`.

**Estos archivos nunca deben contener credenciales reales de producción dentro de Git.** Deben permanecer fuera del control de versiones (ya están excluidos vía `.gitignore`).
