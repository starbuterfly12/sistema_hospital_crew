<?php

require_once __DIR__ . '/../core/Controller.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrestamosController extends Controller
{
    // Clasificación vigente: Requisición no pertenece a Préstamos — es otro proceso, pendiente de
    // definir en Asignaciones/Almacén, NO se implementa aquí.
    private const TIPOS_VALIDOS = ['entre_servicios', 'desde_inventarios'];

    public const ETIQUETAS_TIPO = [
        'entre_servicios' => 'Entre servicios',
        'desde_inventarios' => 'Desde Inventarios',
    ];

    // "Desde Inventarios" resuelve su responsable origen de forma institucional, no por un id fijo
    // disperso en esta clase: Ubicacion::findInventariosInstitucional() localiza la ubicación de
    // Inventarios (id fijo allí, no aquí) y Responsable::findActivoPorUbicacion() resuelve a su
    // encargada activa. Mismo par de métodos que ya usa RequisicionesController para el nombre que
    // firma "Autoriza". $inventariosDisponible en crear() refleja si esa resolución tuvo éxito
    // (ubicación configurada + encargada activa única) — nunca se asume ni se hardcodea.

    // Única plantilla de constancia: ambos tipos generan exactamente el mismo documento, diferenciado
    // solo por el texto de "Justificación del préstamo". Coordenadas confirmadas por inspección real.
    private const PLANTILLA_ARCHIVO = 'prestamo_general.xlsx';
    private const PLANTILLA_FECHA = 'F3';
    private const PLANTILLA_OFICIO = 'F4';
    private const PLANTILLA_NOMBRE_RECIBE = 'C7';
    private const PLANTILLA_AREA_RECIBE = 'C8';
    private const PLANTILLA_JUSTIFICACION = 'C9';
    private const PLANTILLA_PERIODO = 'C13';
    private const PLANTILLA_FILA_ENCABEZADO_TABLA = 15;
    private const PLANTILLA_FILA_INICIO_TABLA = 16;
    private const PLANTILLA_FILA_FIN_TABLA_BASE = 32;
    private const PLANTILLA_RECIBI_NOMBRE = 'B38';
    private const PLANTILLA_RECIBI_AREA = 'B39';

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $prestamoModel = $this->model('Prestamo');

        $this->view('prestamos/index', [
            'prestamos' => $prestamoModel->getAll(),
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idPrestamo = (int) ($_GET['id'] ?? 0);

        if ($idPrestamo <= 0) {
            echo 'Préstamo no válido.';
            return;
        }

        $prestamoModel = $this->model('Prestamo');
        $prestamo = $prestamoModel->findById($idPrestamo);

        if ($prestamo === false) {
            echo 'Préstamo no encontrado.';
            return;
        }

        $detalleModel = $this->model('DetallePrestamo');
        $detalles = $detalleModel->listarPorPrestamo($idPrestamo);

        $this->view('prestamos/ver', [
            'prestamo' => $prestamo,
            'detalles' => $detalles,
        ]);
    }

    public function descargarConstancia(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idPrestamo = (int) ($_GET['id'] ?? 0);

        if ($idPrestamo <= 0) {
            echo 'Préstamo no válido.';
            return;
        }

        $prestamoModel = $this->model('Prestamo');
        $prestamo = $prestamoModel->findById($idPrestamo);

        if ($prestamo === false) {
            echo 'Préstamo no encontrado.';
            return;
        }

        if (!isset(self::ETIQUETAS_TIPO[$prestamo['tipo_prestamo']])) {
            echo 'Tipo de préstamo no reconocido. No es posible generar la constancia.';
            return;
        }

        $detalleModel = $this->model('DetallePrestamo');
        $detalles = $detalleModel->listarPorPrestamo($idPrestamo);

        if (empty($detalles)) {
            echo 'Este préstamo no tiene bienes registrados.';
            return;
        }

        if (trim((string) ($prestamo['motivo'] ?? '')) === '') {
            echo 'Este préstamo no tiene justificación registrada. No es posible generar la constancia.';
            return;
        }

        try {
            $spreadsheet = $this->generarWorkbookConstanciaPrestamo($prestamo, $detalles);
        } catch (Throwable $e) {
            error_log('Error al generar la constancia del préstamo ' . $idPrestamo . ': ' . $e->getMessage());
            echo 'No fue posible generar la constancia de préstamo.';
            return;
        }

        $etiquetaArchivo = str_replace(' ', '', self::ETIQUETAS_TIPO[$prestamo['tipo_prestamo']]);

        $nombreArchivo = 'Constancia_Prestamo_'
            . $etiquetaArchivo . '_'
            . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $prestamo['numero_prestamo'])
            . '.xlsx';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Reconstruye la constancia de Préstamo (Entre servicios / Desde Inventarios) como workbook XLSX
     * a partir de la plantilla maestra única, SIN modificarla: se carga en memoria y se retorna el
     * objeto Spreadsheet ya listo para escribir. Misma filosofía que
     * TarjetasController::generarWorkbookTarjeta() y TrasladosController::generarWorkbookConstancia().
     * Usa exclusivamente snapshots de prestamos/detalle_prestamo — nunca datos actuales de
     * bienes/responsables/ubicaciones.
     */
    private function generarWorkbookConstanciaPrestamo(array $prestamo, array $detalles): Spreadsheet
    {
        $rutaPlantilla = dirname(__DIR__, 2) . '/storage/templates/' . self::PLANTILLA_ARCHIVO;

        if (!is_file($rutaPlantilla)) {
            throw new RuntimeException('No se encontró la plantilla maestra de constancia de préstamo.');
        }

        $spreadsheet = IOFactory::load($rutaPlantilla);
        $hoja = $spreadsheet->getSheet(0);

        // Repetir la fila de encabezado de la tabla de bienes en cada página física adicional al
        // imprimir. Solo indica la configuración a Excel (rowsToRepeatAtTop) — no escribe ni duplica
        // nada dentro de la hoja, no mueve filas ni afecta orientación/escala/FitToPage/márgenes.
        $hoja->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(
            self::PLANTILLA_FILA_ENCABEZADO_TABLA,
            self::PLANTILLA_FILA_ENCABEZADO_TABLA
        );

        $hoja->setCellValueExplicit(
            self::PLANTILLA_FECHA,
            $this->formatearFechaLarga((string) $prestamo['fecha_prestamo']),
            DataType::TYPE_STRING
        );
        $hoja->setCellValueExplicit(self::PLANTILLA_OFICIO, (string) $prestamo['numero_oficio'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_NOMBRE_RECIBE, (string) $prestamo['responsable_destino_mostrado'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_AREA_RECIBE, (string) $prestamo['ubicacion_destino_mostrada'], DataType::TYPE_STRING);

        $periodo = formatDate((string) $prestamo['fecha_prestamo']) . ' al ' . formatDate((string) $prestamo['fecha_devolucion_estimada']);
        $hoja->setCellValueExplicit(self::PLANTILLA_PERIODO, $periodo, DataType::TYPE_STRING);

        $hoja->setCellValueExplicit(self::PLANTILLA_JUSTIFICACION, (string) $prestamo['motivo'], DataType::TYPE_STRING);

        // Recibí conforme: misma persona/área que "Nombre:"/"Área /Servicio:" (confirmado por
        // inspección real: ambos bloques repiten el mismo dato).
        $hoja->setCellValueExplicit(self::PLANTILLA_RECIBI_NOMBRE, (string) $prestamo['responsable_destino_mostrado'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit(self::PLANTILLA_RECIBI_AREA, (string) $prestamo['ubicacion_destino_mostrada'], DataType::TYPE_STRING);

        // "Autoriza" (cargo institucional fijo junto al bloque Recibí conforme) NO se toca: se deja
        // exactamente como la usuaria lo diseñó en la plantilla.

        // Tabla de bienes: la plantilla trae 17 filas de ejemplo. Si el préstamo tiene más bienes, se
        // insertan filas adicionales justo después de la última fila base (antes de Total/firmas, que
        // PhpSpreadsheet desplaza hacia abajo junto con su contenido y estilos), copiando estilo/borde/
        // alto de la última fila base y recreando el merge de descripción — mismo criterio que
        // TrasladosController::generarWorkbookConstancia().
        $filaInicio = self::PLANTILLA_FILA_INICIO_TABLA;
        $filaFinBase = self::PLANTILLA_FILA_FIN_TABLA_BASE;
        $capacidadBase = $filaFinBase - $filaInicio + 1;
        $totalBienes = count($detalles);
        $filasExtra = max(0, $totalBienes - $capacidadBase);

        if ($filasExtra > 0) {
            $filaInsercion = $filaFinBase + 1;
            $hoja->insertNewRowBefore($filaInsercion, $filasExtra);

            for ($i = 0; $i < $filasExtra; $i++) {
                $filaNueva = $filaInsercion + $i;

                foreach (['A', 'B', 'E', 'F', 'G'] as $columna) {
                    $hoja->duplicateStyle($hoja->getStyle($columna . $filaFinBase), $columna . $filaNueva);
                }

                $hoja->getRowDimension($filaNueva)->setRowHeight($hoja->getRowDimension($filaFinBase)->getRowHeight());
                $hoja->mergeCells('B' . $filaNueva . ':D' . $filaNueva);
            }
        }

        $fila = $filaInicio;

        // IMPORTANTE — igual que TarjetasController::generarWorkbookTarjeta() (ver comentario allí):
        // anchos de columna, ALTURAS DE FILA, wrapText y demás formato son los de la plantilla y NUNCA
        // se tocan por código aquí — ni con setRowHeight() ni con setWrapText(). Solo se escriben
        // VALORES de celda. Si una descripción larga necesita más espacio del que la fila ya tiene, es
        // la usuaria quien ajusta la fila manualmente en Excel — no este generador.
        foreach ($detalles as $detalle) {
            $codigoSicoin = trim((string) ($detalle['codigo_sicoin_mostrado'] ?? ''));
            $descripcionCompleta = $this->construirDescripcionCompleta($detalle);

            $hoja->setCellValue('A' . $fila, 1);
            $hoja->setCellValueExplicit('B' . $fila, $descripcionCompleta, DataType::TYPE_STRING);
            $hoja->setCellValueExplicit('E' . $fila, (string) $detalle['codigo_interno_mostrado'], DataType::TYPE_STRING);

            if ($codigoSicoin !== '') {
                $hoja->setCellValueExplicit('F' . $fila, $codigoSicoin, DataType::TYPE_STRING);
            }

            $hoja->setCellValue('G' . $fila, (float) $detalle['valor_prestamo']);

            $fila++;
        }

        // Limpiar filas base de ejemplo que no se usaron (la plantilla trae bienes de muestra reales
        // de la usuaria, ej. "Vitrina de metal" — deben desaparecer si el préstamo tiene menos bienes).
        for ($filaLimpiar = $fila; $filaLimpiar <= $filaFinBase; $filaLimpiar++) {
            $hoja->setCellValue('A' . $filaLimpiar, null);
            $hoja->setCellValue('B' . $filaLimpiar, null);
            $hoja->setCellValue('E' . $filaLimpiar, null);
            $hoja->setCellValue('F' . $filaLimpiar, null);
            $hoja->setCellValue('G' . $filaLimpiar, null);
        }

        // Total: nunca se confía en la fórmula =SUM(...) original de la plantilla (rango fijo, se
        // rompe si hubo filas insertadas) — se escribe el total calculado como valor plano, igual
        // criterio que ULTIMA LINEA en TarjetasController. El formato de moneda (Quetzal) ya está en
        // la celda de la plantilla y se conserva porque no se toca numberFormat.
        $filaTotal = $filaFinBase + 1 + $filasExtra;
        $totalCalculado = array_sum(array_map(
            static fn (array $d): float => (float) $d['valor_prestamo'],
            $detalles
        ));
        $hoja->setCellValue('G' . $filaTotal, $totalCalculado);

        return $spreadsheet;
    }

    /**
     * Compone "Descripción, modelo: X, serie: Y" para la columna B:D de la constancia, igual regla
     * que RequisicionesController::construirDescripcionCompleta(). detalle_prestamo ya tiene columna
     * modelo_mostrado (agregada junto con el resto de snapshots de este bloque — ver
     * database/2026_08_19_requisiciones_y_snapshots.sql), además de descripcion_mostrada y
     * serie_mostrada ya existentes. Nunca usa bienes.modelo/serie actuales, solo snapshots
     * congelados en el momento del préstamo.
     */
    private function construirDescripcionCompleta(array $detalle): string
    {
        $partes = [trim((string) $detalle['descripcion_mostrada'])];

        $modelo = trim((string) ($detalle['modelo_mostrado'] ?? ''));

        if ($modelo !== '') {
            $partes[] = 'modelo: ' . $modelo;
        }

        $serie = trim((string) ($detalle['serie_mostrada'] ?? ''));

        if ($serie !== '') {
            $partes[] = 'serie: ' . $serie;
        }

        return implode(', ', $partes);
    }

    /**
     * "16 de agosto de 2026" — controlado explícitamente en español, sin depender de
     * setlocale()/LC_TIME del sistema operativo (poco fiable en Windows). Misma implementación que
     * TrasladosController::formatearFechaLarga().
     */
    private function formatearFechaLarga(string $fechaHora): string
    {
        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaHora)
            ?: DateTime::createFromFormat('Y-m-d', substr($fechaHora, 0, 10));

        if ($fecha === false) {
            return $fechaHora;
        }

        $dia = (int) $fecha->format('j');
        $mes = $meses[(int) $fecha->format('n')];
        $anio = $fecha->format('Y');

        return $dia . ' de ' . $mes . ' de ' . $anio;
    }

    public function crear(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $responsableModel = $this->model('Responsable');
        $bienModel = $this->model('Bien');
        $ubicacionModel = $this->model('Ubicacion');

        $responsablesOrigen = $responsableModel->getActivosConBienesAsignados();
        $responsablesDestino = $responsableModel->getActivos();

        // Resolución institucional de "Desde Inventarios" (ver comentario junto a la clase): nunca
        // por id fijo ni por nombre. $inventariosDisponible solo es true si ambos pasos resuelven
        // sin ambigüedad; en cualquier otro caso la opción queda deshabilitada en el formulario, sin
        // adivinar ni sustituir por otra ubicación/responsable.
        $ubicacionInventarios = $ubicacionModel->findInventariosInstitucional();
        $responsableInventarios = $ubicacionInventarios !== false
            ? $responsableModel->findActivoPorUbicacion((int) $ubicacionInventarios['id_ubicacion'])
            : false;
        $inventariosDisponible = $responsableInventarios !== false;

        $origenInventarios = $inventariosDisponible ? [
            'id_responsable' => (int) $responsableInventarios['id_responsable'],
            'nombre_completo' => $responsableInventarios['nombre_completo'],
            'nombre_ubicacion' => $ubicacionInventarios['nombre_ubicacion'],
        ] : null;

        $mapearBienPrestable = static function (array $bien): array {
            $codigoSicoin = trim((string) ($bien['codigo_sicoin'] ?? ''));
            $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

            return [
                'id_bien' => (int) $bien['id_bien'],
                'codigo_interno' => $bien['codigo_interno'],
                'codigo_sicoin' => $codigoSicoin !== '' ? $codigoSicoin : null,
                'descripcion' => $bien['descripcion'],
                'marca' => $bien['marca'],
                'modelo' => $bien['modelo'],
                'serie' => $bien['serie'],
                'condicion_bien' => $bien['condicion_bien'],
                'valor' => $valor,
                'ubicacion_actual' => $bien['nombre_ubicacion'],
                'numero_asignacion' => $bien['numero_asignacion'],
            ];
        };

        $bienesPorResponsable = [];

        foreach ($responsablesOrigen as $responsable) {
            $idResponsable = (int) $responsable['id_responsable'];
            $bienesPorResponsable[$idResponsable] = array_map(
                $mapearBienPrestable,
                $bienModel->getPrestablesPorResponsable($idResponsable)
            );
        }

        // La encargada de Inventarios debe tener sus bienes prestables precargados aunque
        // getActivosConBienesAsignados() no la haya incluido (p. ej. si hoy no tiene ningún bien
        // asignado): "Desde Inventarios" no depende de que ella aparezca en ese listado genérico.
        if ($inventariosDisponible && !array_key_exists($origenInventarios['id_responsable'], $bienesPorResponsable)) {
            $bienesPorResponsable[$origenInventarios['id_responsable']] = array_map(
                $mapearBienPrestable,
                $bienModel->getPrestablesPorResponsable($origenInventarios['id_responsable'])
            );
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('prestamos/crear', [
                'responsablesOrigen' => $responsablesOrigen,
                'responsablesDestino' => $responsablesDestino,
                'bienesPorResponsable' => $bienesPorResponsable,
                'inventariosDisponible' => $inventariosDisponible,
                'origenInventarios' => $origenInventarios,
                'error' => null,
                'datosFormulario' => [],
            ]);
            return;
        }

        verifyCsrf();

        $tipoPrestamo = (string) ($_POST['tipo_prestamo'] ?? '');
        $idResponsableOrigen = (int) ($_POST['id_responsable_origen'] ?? 0);

        // Regla obligatoria de backend: en "Desde Inventarios" el origen NUNCA se toma del POST.
        // Se sobreescribe aquí con el responsable institucional ya resuelto arriba (Ubicacion::
        // findInventariosInstitucional() + Responsable::findActivoPorUbicacion()), antes de construir
        // $datosFormulario y de cualquier validación, así que un id_responsable_origen manipulado en
        // el POST queda completamente ignorado para este tipo de préstamo.
        if ($tipoPrestamo === 'desde_inventarios' && $inventariosDisponible) {
            $idResponsableOrigen = $origenInventarios['id_responsable'];
        }

        $idsBienes = array_values(array_unique(array_map('intval', $_POST['bienes'] ?? [])));
        $condicionesEntregaPost = is_array($_POST['condicion_entrega'] ?? null) ? $_POST['condicion_entrega'] : [];
        $idResponsableDestino = (int) ($_POST['id_responsable_destino'] ?? 0);
        $fechaPrestamo = trim((string) ($_POST['fecha_prestamo'] ?? ''));
        $fechaDevolucionEstimada = trim((string) ($_POST['fecha_devolucion_estimada'] ?? ''));
        $numeroOficio = trim((string) ($_POST['numero_oficio'] ?? ''));
        $justificacionPost = trim((string) ($_POST['motivo'] ?? ''));
        $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

        $datosFormulario = [
            'tipo_prestamo' => $tipoPrestamo,
            'id_responsable_origen' => $idResponsableOrigen,
            'bienes' => $idsBienes,
            'condicion_entrega' => $condicionesEntregaPost,
            'id_responsable_destino' => $idResponsableDestino,
            'fecha_prestamo' => $fechaPrestamo,
            'fecha_devolucion_estimada' => $fechaDevolucionEstimada,
            'numero_oficio' => $numeroOficio,
            'motivo' => $justificacionPost,
            'observaciones' => $observaciones,
        ];

        $error = null;

        if (!in_array($tipoPrestamo, self::TIPOS_VALIDOS, true)) {
            $error = 'Debe seleccionar un tipo de préstamo válido.';
        } elseif ($tipoPrestamo === 'desde_inventarios' && !$inventariosDisponible) {
            $error = 'No fue posible registrar el préstamo: no se identificó de forma única a la Encargada de Inventarios.';
        } elseif ($idResponsableOrigen <= 0) {
            $error = 'Debe seleccionar el responsable origen.';
        } elseif (empty($idsBienes)) {
            $error = 'Debe seleccionar al menos un bien para prestar.';
        } elseif ($idResponsableDestino <= 0) {
            $error = 'Debe seleccionar el responsable temporal.';
        } elseif ($idResponsableDestino === $idResponsableOrigen) {
            $error = 'El responsable temporal no puede ser el mismo que el de origen.';
        } elseif ($fechaPrestamo === '' || !isValidIsoDate($fechaPrestamo)) {
            $error = 'La fecha del préstamo no es válida.';
        } elseif ($fechaDevolucionEstimada === '' || !isValidIsoDate($fechaDevolucionEstimada)) {
            $error = 'La fecha estimada de devolución no es válida.';
        } elseif ($fechaDevolucionEstimada < $fechaPrestamo) {
            $error = 'La fecha estimada de devolución debe ser igual o posterior a la fecha del préstamo.';
        } elseif ($numeroOficio === '') {
            $error = 'El número de oficio es obligatorio.';
        } elseif (mb_strlen($numeroOficio) > 50) {
            $error = 'El número de oficio no puede superar 50 caracteres.';
        } elseif ($justificacionPost === '') {
            $error = 'La justificación del préstamo es obligatoria.';
        }

        if ($error !== null) {
            $this->view('prestamos/crear', [
                'responsablesOrigen' => $responsablesOrigen,
                'responsablesDestino' => $responsablesDestino,
                'bienesPorResponsable' => $bienesPorResponsable,
                'inventariosDisponible' => $inventariosDisponible,
                'origenInventarios' => $origenInventarios,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $asignacionModel = $this->model('Asignacion');
        $prestamoModel = $this->model('Prestamo');
        $detalleModel = $this->model('DetallePrestamo');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $prestamoModel->beginTransaction();

            // 1. Bloquear responsable origen.
            $responsableOrigen = $responsableModel->findActivoByIdForUpdate($idResponsableOrigen);

            if ($responsableOrigen === false) {
                throw new RuntimeException('El responsable de origen no está activo.');
            }

            // 2. Bloquear responsable destino.
            $responsableDestino = $responsableModel->findActivoByIdForUpdate($idResponsableDestino);

            if ($responsableDestino === false) {
                throw new RuntimeException('El responsable temporal seleccionado no es válido o está inactivo.');
            }

            // 3-8. Por cada bien: bloquear el bien, validar asignación actual, bloquear asignación y
            // detalle activo, validar que no tenga préstamo activo, preparar snapshots y condición.
            $bienesValidados = [];

            foreach ($idsBienes as $idBien) {
                $bien = $bienModel->findPrestableForUpdate($idBien, $idResponsableOrigen);

                if ($bien === false) {
                    throw new RuntimeException('Uno de los bienes seleccionados ya no pertenece al responsable origen o no está disponible.');
                }

                $idAsignacionActual = (int) $bien['id_asignacion_actual'];
                $asignacionActual = $asignacionModel->findByIdForUpdate($idAsignacionActual);

                if ($asignacionActual === false) {
                    throw new RuntimeException('No se pudo verificar la asignación actual de uno de los bienes seleccionados.');
                }

                $detalleAsignacionOrigen = $asignacionModel->findDetalleActivoPorBienForUpdate($idAsignacionActual, $idBien);

                if ($detalleAsignacionOrigen === false) {
                    throw new RuntimeException(
                        'El bien ' . $bien['codigo_interno']
                        . ' no tiene un detalle de asignación activo identificable y no puede prestarse.'
                    );
                }

                if ($detalleModel->existePrestamoActivoPorBienForUpdate($idBien)) {
                    throw new RuntimeException('El bien ' . $bien['codigo_interno'] . ' ya se encuentra en un préstamo activo.');
                }

                $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

                if ($valor === null) {
                    throw new RuntimeException(
                        'El bien ' . $bien['codigo_interno'] . ' no tiene costo ni valor estimado registrado y no puede prestarse.'
                    );
                }

                $codigoSicoin = trim((string) ($bien['codigo_sicoin'] ?? ''));

                $condicionEntrega = trim((string) ($condicionesEntregaPost[$idBien] ?? ''));

                // Mismo catálogo controlado que usa Bienes (Bien::CONDICIONES_VALIDAS), no texto libre:
                // rechaza tanto vacío como cualquier valor inventado o manipulado en el POST, sin
                // confiar en que el <select> del formulario haya restringido las opciones.
                if (!in_array($condicionEntrega, Bien::CONDICIONES_VALIDAS, true)) {
                    throw new RuntimeException('Debe indicar una condición de entrega válida para el bien ' . $bien['codigo_interno'] . '.');
                }

                $bienesValidados[$idBien] = [
                    'bien' => $bien,
                    'id_detalle_asignacion_origen' => (int) $detalleAsignacionOrigen['id_detalle_asignacion'],
                    'codigo_sicoin' => $codigoSicoin !== '' ? $codigoSicoin : null,
                    'valor' => $valor,
                    'condicion_entrega' => $condicionEntrega,
                ];
            }

            // No asumir silenciosamente una ubicación origen común: verificar que todos los bienes
            // seleccionados compartan la misma id_ubicacion_actual (mismo criterio que Traslado).
            $ubicacionesActualesBienes = [];

            foreach ($bienesValidados as $info) {
                $ubicacionesActualesBienes[(int) $info['bien']['id_ubicacion_actual']] = true;
            }

            if (count($ubicacionesActualesBienes) > 1) {
                throw new RuntimeException(
                    'Los bienes seleccionados no comparten una ubicación actual coherente y deben revisarse antes de prestarse.'
                );
            }

            $idUbicacionOrigen = (int) array_key_first($ubicacionesActualesBienes);

            $ubicacionOrigen = $ubicacionModel->findActivaByIdForUpdate($idUbicacionOrigen);

            if ($ubicacionOrigen === false) {
                throw new RuntimeException('La ubicación actual de los bienes seleccionados no está activa.');
            }

            // Ubicación destino: derivada del responsable destino ya bloqueado, nunca de un hidden del POST.
            $idUbicacionDestino = (int) $responsableDestino['id_ubicacion'];

            $ubicacionDestino = $ubicacionModel->findActivaByIdForUpdate($idUbicacionDestino);

            if ($ubicacionDestino === false) {
                throw new RuntimeException('No se puede prestar porque la ubicación del responsable temporal está inactiva.');
            }

            // 9. Numerar y crear la cabecera del préstamo, con snapshots textuales de los registros bloqueados.
            $anio = (int) substr($fechaPrestamo, 0, 4);
            $numeroPrestamo = $prestamoModel->generarSiguienteNumero($anio);

            $idPrestamo = $prestamoModel->crear([
                'numero_prestamo' => $numeroPrestamo,
                'tipo_prestamo' => $tipoPrestamo,
                'numero_oficio' => $numeroOficio,
                'id_responsable_origen' => $idResponsableOrigen,
                'responsable_origen_mostrado' => $responsableOrigen['nombre_completo'],
                'id_responsable_destino' => $idResponsableDestino,
                'responsable_destino_mostrado' => $responsableDestino['nombre_completo'],
                'id_ubicacion_origen' => $idUbicacionOrigen,
                'ubicacion_origen_mostrada' => $ubicacionOrigen['nombre_ubicacion'],
                'id_ubicacion_destino' => $idUbicacionDestino,
                'ubicacion_destino_mostrada' => $ubicacionDestino['nombre_ubicacion'],
                'fecha_prestamo' => $fechaPrestamo,
                'fecha_devolucion_estimada' => $fechaDevolucionEstimada,
                'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                'motivo' => $justificacionPost,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ]);

            // 10. Detalles del préstamo (snapshots por bien).
            foreach ($bienesValidados as $idBien => $info) {
                $bien = $info['bien'];

                $detalleModel->insertar([
                    'id_prestamo' => $idPrestamo,
                    'id_bien' => $idBien,
                    'id_detalle_asignacion_origen' => $info['id_detalle_asignacion_origen'],
                    'codigo_interno_mostrado' => $bien['codigo_interno'],
                    'codigo_sicoin_mostrado' => $info['codigo_sicoin'],
                    'descripcion_mostrada' => $bien['descripcion'],
                    'serie_mostrada' => $bien['serie'],
                    'modelo_mostrado' => $bien['modelo'],
                    'valor_prestamo' => $info['valor'],
                    'condicion_entrega' => $info['condicion_entrega'],
                    'observaciones' => null,
                ]);
            }

            // 11. Bitácora: una sola entrada por préstamo.
            $descripcionBitacora = sprintf(
                'Se registró el préstamo %s (%s) de %s a %s con %d bien(es), devolución estimada %s. Justificación: %s.',
                $numeroPrestamo,
                self::ETIQUETAS_TIPO[$tipoPrestamo] ?? $tipoPrestamo,
                $responsableOrigen['nombre_completo'],
                $responsableDestino['nombre_completo'],
                count($bienesValidados),
                $fechaDevolucionEstimada,
                $justificacionPost
            );

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_PRESTAMO',
                modulo: 'Movimientos',
                resultado: 'exitoso',
                descripcion: $descripcionBitacora,
                tablaAfectada: 'prestamos',
                idRegistroAfectado: $idPrestamo,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $prestamoModel->commit();

            header('Location: index.php?modulo=prestamos&accion=ver&id=' . $idPrestamo);
            exit;
        } catch (Throwable $e) {
            if ($prestamoModel->inTransaction()) {
                $prestamoModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar el préstamo: ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible registrar el préstamo. Verifique los datos e intente nuevamente.';
            }

            $this->view('prestamos/crear', [
                'responsablesOrigen' => $responsablesOrigen,
                'responsablesDestino' => $responsablesDestino,
                'bienesPorResponsable' => $bienesPorResponsable,
                'inventariosDisponible' => $inventariosDisponible,
                'origenInventarios' => $origenInventarios,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }
    }

    private function mensajeErrorDuplicado(Throwable $e): ?string
    {
        if (!($e instanceof PDOException)) {
            return null;
        }

        if (($e->errorInfo[0] ?? null) !== '23000') {
            return null;
        }

        $detalleError = $e->errorInfo[2] ?? '';

        if (str_contains($detalleError, 'uq_prestamos_numero')) {
            return 'No fue posible generar un número único para el préstamo. Intente nuevamente.';
        }

        if (str_contains($detalleError, 'uq_detalle_prestamo_bien')) {
            return 'Un bien fue seleccionado más de una vez en el préstamo.';
        }

        return null;
    }
}
