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
- [phpoffice/phpspreadsheet](https://github.com/PHPOffice/PhpSpreadsheet) 5.9.0 (generación de las Tarjetas de Responsabilidad en formato XLSX, y de la exportación Excel del módulo Reportes)
- [dompdf/dompdf](https://github.com/dompdf/dompdf) 3.1.6 (exportación PDF del módulo Reportes, usando la fuente DejaVu Sans incluida en el propio paquete — sin fuentes externas)
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
- Generación y regeneración de código QR por bien, con etiqueta visual `Código: XXXXX` (SICOIN si existe, si no código interno) impresa dentro del propio PNG debajo del QR, y vista de impresión/descarga ajustada a un tamaño físico fijo.
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

### Movimientos

Panel único (`Movimientos`, con una sola entrada en el Dashboard) que agrupa los procesos administrativos que movilizan bienes. Por ahora solo **Traslado** tiene implementación real; Préstamo, Devolución, Baja, Verificación física y Solicitudes de baja aparecen listados como pendientes dentro del mismo panel, sin funcionalidad todavía.

**Traslado multi-bien:**

- Flujo: se elige un **responsable origen**, se listan todos sus bienes actuales (sin importar de qué asignación histórica provienen), se seleccionan uno o varios, se elige un **responsable destino** distinto (la ubicación destino se deriva automáticamente de ese responsable).
- Resolución de la asignación destino: reutiliza una asignación `Asignada` existente, rechaza el traslado si el destino tiene una asignación `Pendiente` (debe confirmarse primero), o crea una nueva asignación `Asignada` automáticamente si el responsable no tiene ninguna vigente.
- Un traslado es una sola operación (`movimientos`) con un detalle por bien (`detalle_movimiento`), cada uno con su propia asignación/detalle de origen y destino, snapshot de código (SICOIN o interno) y snapshot de valor histórico — todo dentro de una única transacción con bloqueos (`FOR UPDATE`), actualización de los espejos del bien, y una sola entrada de bitácora por traslado (no una por bien).
- **Constancia de Traslado**: descarga en XLSX generada en memoria a partir de la plantilla institucional (ver más abajo), con fecha en español, origen/destino, responsables, todos los bienes trasladados, usuario de Inventarios, y el número de traslado (`TRA-YYYY-NNNNNN`) únicamente como referencia discreta en el pie de página.
- **Integración con Tarjetas de Responsabilidad**: una nueva emisión de tarjeta reconstruye automáticamente el historial de bienes trasladados como `TRASLADO_SALIDA` (en la tarjeta del responsable de origen) y `TRASLADO_ENTRADA` (en la del responsable de destino), sin duplicar el alta original ni el saldo. Las tarjetas ya emitidas antes de un traslado no se modifican.

### Reportes

Módulo de solo consulta (los tres roles pueden consultar y exportar) con **7 reportes definitivos**:

1. Movimientos por período
2. Bienes con actividad en un período
3. Préstamos pendientes o vencidos
4. Bajas por período
5. Verificaciones con diferencias
6. Ingresos de bienes por período
7. Resumen de movimientos por período

Cada reporte tiene vista web con sus propios filtros, y exportación en **Excel (XLSX)** y **PDF**. Ambos formatos comparten exactamente los mismos datos, filtros, orden y totales que la vista web — el PDF se genera en Carta horizontal (una sola página de ancho) con Dompdf.

### Usuarios

Acceso exclusivo al rol **Administrador** (protegido tanto en el menú como en el backend de cada acción).

- Listado con filtros por nombre/usuario, rol y estado.
- Registro de usuarios: nombre completo, usuario, correo (opcional, único), teléfono (opcional), rol, contraseña y estado inicial.
- Edición de datos generales, rol y estado.
- Roles cargados dinámicamente desde la tabla `roles` (solo los activos se ofrecen para nuevas asignaciones).
- Activar/inactivar usuario — baja lógica únicamente, nunca eliminación física.
- Cambio de contraseña: un Administrador puede restablecer la de otro usuario sin conocer la actual; para la propia, se exige la contraseña actual.
- Protecciones de backend (no solo ocultar botones): un Administrador no puede inactivarse a sí mismo ni cambiar su propio rol de Administrador; y ninguna operación puede dejar el sistema sin al menos un Administrador activo con un `password_hash` realmente evaluable por `password_verify()` — verificación hecha dentro de una transacción corta con bloqueo `FOR UPDATE` para cerrar condiciones de carrera.
- `ultimo_acceso` se actualiza automáticamente en cada inicio de sesión exitoso.
- Bitácora de todas las acciones administrativas (`REGISTRAR_USUARIO`, `MODIFICAR_USUARIO`, `CAMBIAR_ESTADO_USUARIO`, `CAMBIAR_PASSWORD_USUARIO`) y de autenticación (`INICIAR_SESION` exitoso/fallido, `CERRAR_SESION`) — nunca se registra contraseña ni hash.

### Bitácora

Acceso exclusivo al rol **Administrador**. Módulo de **solo lectura** — sin edición ni eliminación de registros.

- Listado de eventos con filtros por rango de fechas, búsqueda libre, módulo y resultado (Exitoso/Fallido), paginado de 25 registros por página.
- Detalle individual de cada evento.
- Registra autenticación (inicio de sesión exitoso/fallido, cierre de sesión), además de las acciones administrativas de los demás módulos.
- Muestra el usuario autenticado (nombre y username) cuando existe, o el username intentado cuando el inicio de sesión falló sin llegar a autenticar a nadie.
- Fecha/hora con precisión de segundos.
- El detalle incluye además tabla y registro afectados, e IP de origen.

## Plantillas XLSX

Ambas plantillas **forman parte funcional del proyecto y sí están versionadas en Git** (a diferencia del resto de `storage/`, que son archivos generados/subidos) — no deben eliminarse:

- `storage/templates/tarjetas_responsabilidad.xlsx` — plantilla maestra de las Tarjetas de Responsabilidad.
- `storage/templates/constancia_traslado.xlsx` — plantilla maestra de la Constancia de Traslado.

Cada descarga carga la plantilla correspondiente, trabaja sobre una copia en memoria y genera el archivo de salida sin modificar el archivo maestro.

## Módulos pendientes

Dentro del panel Movimientos, listados pero **sin funcionalidad real todavía**:

- Préstamos
- Devoluciones
- Bajas
- Verificación física
- Solicitudes de baja

Fuera de Movimientos, con su estructura base creada (controlador/modelo/vista) pero **aún en desarrollo**:

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
