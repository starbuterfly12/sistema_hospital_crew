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
- [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) 5.9.0 (generación de las Tarjetas de Responsabilidad en formato XLSX)
- [Flatpickr](https://flatpickr.js.org/) 4.6.13 (selector de fechas en formularios, con presentación `DD/MM/AAAA` y almacenamiento interno `YYYY-MM-DD`; assets servidos localmente desde `public/vendor/flatpickr/`, sin CDN)
- Git y GitHub
- XAMPP (entorno de desarrollo local)

## Entorno futuro de producción

- Ubuntu
- Apache
- PHP
- MariaDB
- Red interna del hospital

## Módulos con implementación real

### Autenticación

- Inicio de sesión con roles **Administrador**, **Operativo** y **Visualizador**.
- Cierre de sesión (destruye la sesión activa y regresa al login).

### Bienes

- Registro de bienes institucionales por Compra, Donación o Traslado (cada forma de ingreso con sus propios datos y validaciones).
- Edición y consulta de bienes, con categorías propias.
- Documentos de respaldo adjuntos al ingreso del bien.
- Generación y regeneración de código QR por bien, con vista de impresión/descarga.
- Trazabilidad de cambios de código SICOIN (`historial_sicoin`): cada vez que el SICOIN de un bien cambia, queda registrado con valor anterior, valor nuevo, fecha y usuario; no se permite dejar vacío un SICOIN ya asignado.

### Responsables

Registro y consulta de responsables institucionales.

### Áreas y ubicaciones

Registro y consulta de áreas/ubicaciones del Hospital.

### Asignaciones

- Un responsable solo puede tener una asignación vigente a la vez.
- La ubicación de la asignación se deriva de la ubicación actual del responsable.
- Incorporación de bienes a la asignación y confirmación formal (paso de estado `Pendiente` a `Asignada`).
- Es posible seguir agregando bienes a una asignación que ya está `Asignada`.
- Al confirmar o agregar bienes, se actualizan los datos "espejo" del bien (responsable actual, ubicación actual, asignación actual).

### Tarjetas de Responsabilidad

- Una asignación puede tener **múltiples emisiones** de tarjeta a lo largo del tiempo (p. ej. `TR-2026-000001`, `TR-2026-000002`, ...).
- Cada emisión es una fotografía histórica **inmutable**: una vez generada, ni su encabezado ni sus operaciones vuelven a modificarse.
- Cada emisión registra operaciones de tipo Debe / Haber / Saldo, no solo un listado de bienes: `ALTA`, `REGULARIZACION_SALIDA` y `REGULARIZACION_ENTRADA`.
- Mientras un bien todavía no tiene código SICOIN, la tarjeta muestra su código interno como respaldo — la columna de código nunca queda vacía.
- Cuando un bien recibe su código SICOIN después de haber sido incorporado sin él, la siguiente emisión refleja esa regularización mediante un descargo (`REGULARIZACION_SALIDA`) y un nuevo cargo ya formalizado con el SICOIN (`REGULARIZACION_ENTRADA`), usando el historial de cambios de SICOIN como fuente.
- Descarga de la tarjeta como archivo **XLSX**, generado a partir de la plantilla institucional (ver más abajo), pensado para imprimirse sobre el formulario físico preimpreso que usa el Hospital.
- Paginación de hasta **21 operaciones por hoja**, con arrastre de saldo entre hojas (`VIENEN`), marca de continuación (`VAN`) y cierre (`ULTIMA LINEA`) calculados automáticamente.
- Configuración de impresión en papel Legal/Oficio, orientación horizontal.
- La descarga del XLSX está disponible también para el rol **Visualizador** (es una acción de consulta).

## Plantilla de Tarjetas de Responsabilidad

`storage/templates/tarjetas_responsabilidad.xlsx` es la plantilla maestra institucional usada para generar las Tarjetas de Responsabilidad. A diferencia del resto de `storage/`, este archivo **forma parte funcional del proyecto y sí está versionado en Git** — no debe eliminarse.

Cada descarga carga esta plantilla, trabaja sobre una copia en memoria y genera el archivo de salida sin modificar el archivo maestro.

## Módulos pendientes

Tienen su estructura base creada (controlador/modelo/vista) pero **continúan en desarrollo** y aún no tienen funcionalidad real:

- Movimientos
- Préstamos
- Devoluciones
- Bajas
- Verificación física
- Reportes
- Usuarios (administración complementaria más allá del login)
- Bitácora (vista de consulta; los registros ya se generan internamente pero no hay una pantalla para navegarlos)
- Respaldos

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
