# Sistema web para la gestión administrativa de bienes institucionales

## Contexto

Proyecto de graduación aplicado al área de inventario del **Hospital General de Chiquimula**.

> **Importante:** este sistema administra **bienes institucionales** (mobiliario, equipo, activos del inventario). **No administra información clínica ni de pacientes.**

## Estado del proyecto

🚧 **En desarrollo.** No se ha desplegado en producción todavía. Ver [INSTALL.md](INSTALL.md) para el checklist de despliegue futuro.

## Tecnologías actuales

- PHP 8.2+
- Arquitectura MVC propia (sin framework)
- MariaDB
- PDO
- HTML / CSS / JavaScript
- Composer
- [endroid/qr-code](https://github.com/endroid/qr-code) (generación de códigos QR)
- Git y GitHub
- XAMPP (entorno de desarrollo local)

## Entorno futuro de producción

- Ubuntu
- Apache
- PHP
- MariaDB
- Red interna del hospital

## Módulos con implementación real

- Autenticación
- Dashboard
- Bienes institucionales
- Registro de bienes por compra, donación y traslado
- Edición y consulta de bienes
- Cambio de condición de un bien
- Generación y regeneración de código QR

Los demás módulos (asignaciones, bajas, bitácora, devoluciones, movimientos, préstamos, reportes, respaldos, responsables, tarjetas de responsabilidad, ubicaciones, usuarios, verificaciones) tienen su estructura base creada (controlador/modelo/vista) pero **continúan en desarrollo** y aún no tienen funcionalidad real.

## Estructura principal de carpetas

```
sistema_hospital/
├── app/
│   ├── controllers/   # Controladores por módulo
│   ├── core/           # Controller, Model, Database (núcleo del MVC propio)
│   ├── helpers/        # Funciones auxiliares (url.php, etc.)
│   ├── models/         # Modelos de datos
│   └── views/          # Vistas por módulo renderizadas con PHP
├── config/              # Configuración (ver sección Configuración)
├── database/            # Esquema SQL (PENDIENTE de generar)
├── public/              # Assets estáticos (css, js, img)
├── storage/              # Archivos generados/subidos (ver storage/README.md)
├── tests/                # Pruebas (sin contenido todavía)
├── vendor/               # Dependencias de Composer (no versionado)
└── index.php             # Punto de entrada / enrutador
```

## Configuración

Los archivos de configuración específicos de cada entorno no están versionados en Git.

- `config/Database.php`
- `config/app.php`

Sus plantillas sí están versionadas y sirven de base para crear los archivos reales en cada entorno:

- `config/Database.example.php`
- `config/app.example.php`

## Dependencias (Composer)

La carpeta `vendor/` no se versiona. Se reconstruye ejecutando `composer install` a partir de `composer.json` / `composer.lock`.

## Instalación

Este proyecto aún está en desarrollo y no se ha desplegado formalmente. Los pasos previstos para la instalación y el despliegue futuro están documentados en [INSTALL.md](INSTALL.md).
