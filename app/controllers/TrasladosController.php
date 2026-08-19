<?php

require_once __DIR__ . '/../core/Controller.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TrasladosController extends Controller
{
    private const CONSTANCIA_FILA_PRIMER_BIEN = 11;
    private const CONSTANCIA_FILAS_TABLA_BASE = 11;

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $movimientoModel = $this->model('Movimiento');

        $this->view('traslados/index', [
            'movimientos' => $movimientoModel->getAll(),
        ]);
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idMovimiento = (int) ($_GET['id'] ?? 0);

        if ($idMovimiento <= 0) {
            echo 'Traslado no válido.';
            return;
        }

        $movimientoModel = $this->model('Movimiento');
        $movimiento = $movimientoModel->findById($idMovimiento);

        if ($movimiento === false) {
            echo 'Traslado no encontrado.';
            return;
        }

        $detalleMovimientoModel = $this->model('DetalleMovimiento');
        $detalles = $detalleMovimientoModel->listarPorMovimiento($idMovimiento);

        $this->view('traslados/ver', [
            'movimiento' => $movimiento,
            'detalles' => $detalles,
        ]);
    }

    public function descargarConstancia(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idMovimiento = (int) ($_GET['id'] ?? 0);

        if ($idMovimiento <= 0) {
            echo 'Traslado no válido.';
            return;
        }

        $movimientoModel = $this->model('Movimiento');
        $movimiento = $movimientoModel->findById($idMovimiento);

        if ($movimiento === false) {
            echo 'Traslado no encontrado.';
            return;
        }

        $detalleMovimientoModel = $this->model('DetalleMovimiento');
        $detalles = $detalleMovimientoModel->listarPorMovimiento($idMovimiento);

        if (empty($detalles)) {
            echo 'Este traslado no tiene bienes registrados.';
            return;
        }

        try {
            $spreadsheet = $this->generarWorkbookConstancia($movimiento, $detalles);
        } catch (Throwable $e) {
            error_log('Error al generar la constancia del traslado ' . $idMovimiento . ': ' . $e->getMessage());
            echo 'No fue posible generar la constancia de traslado.';
            return;
        }

        $nombreArchivo = 'Constancia_Traslado_'
            . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $movimiento['numero_movimiento'])
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
     * Reconstruye la constancia de Traslado como workbook XLSX a partir de la plantilla
     * maestra, SIN modificarla: se carga en memoria y se retorna el objeto Spreadsheet
     * ya listo para escribir. Misma filosofía que TarjetasController::generarWorkbookTarjeta().
     */
    private function generarWorkbookConstancia(array $movimiento, array $detalles): Spreadsheet
    {
        $rutaPlantilla = dirname(__DIR__, 2) . '/storage/templates/constancia_traslado.xlsx';

        if (!is_file($rutaPlantilla)) {
            throw new RuntimeException('No se encontró la plantilla maestra de constancia de traslado.');
        }

        $spreadsheet = IOFactory::load($rutaPlantilla);
        $hoja = $spreadsheet->getSheet(0);

        $totalBienes = count($detalles);

        // IMPORTANTE: todo el bloque de firmas/observaciones (filas 23 en adelante en la plantilla
        // original) se escribe ANTES de insertar filas de crecimiento. insertNewRowBefore() desplaza
        // hacia abajo el CONTENIDO ya escrito junto con las filas — si se escribiera después de
        // insertar, los datos caerían en las filas nuevas (vacías) en lugar de en el bloque real.
        // Detectado y corregido durante la validación técnica de esta entrega.

        // Encabezado: fecha en español controlado (sin depender del locale del SO).
        $hoja->setCellValueExplicit(
            'F5',
            $this->formatearFechaLarga((string) $movimiento['fecha_movimiento']),
            DataType::TYPE_STRING
        );

        $ubicacionOrigen = (string) ($movimiento['ubicacion_origen_nombre'] ?? '');
        $ubicacionDestino = (string) ($movimiento['ubicacion_destino_nombre'] ?? '');
        $responsableOrigen = (string) ($movimiento['responsable_origen_nombre'] ?? '');
        $responsableDestino = (string) ($movimiento['responsable_destino_nombre'] ?? '');

        $hoja->setCellValueExplicit('F7', $ubicacionOrigen, DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('D8', $ubicacionDestino, DataType::TYPE_STRING);

        // Bloque "Servicio que entrega".
        $hoja->setCellValueExplicit('A23', $ubicacionOrigen, DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('D23', $responsableOrigen, DataType::TYPE_STRING);

        // Bloque "Servicio que recibe".
        $hoja->setCellValueExplicit('A26', $ubicacionDestino, DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('D26', $responsableDestino, DataType::TYPE_STRING);

        // Personal de Inventarios que verifica: usuario que registró el traslado.
        $hoja->setCellValueExplicit(
            'A32',
            (string) ($movimiento['usuario_registra_nombre'] ?? ''),
            DataType::TYPE_STRING
        );

        // Vo. Bo. Jefe del Departamento de Inventario (A35/D35): se deja en blanco a propósito,
        // para firma física — no se escribe nada aquí.

        $observaciones = trim((string) ($movimiento['observaciones'] ?? ''));

        if ($observaciones !== '') {
            $hoja->setCellValueExplicit('C38', $observaciones, DataType::TYPE_STRING);
        }

        // La plantilla trae 11 filas de detalle (11-21). Si hay más bienes, se insertan filas
        // adicionales justo después de la última (antes del bloque de firmas/observaciones ya
        // escrito arriba), que PhpSpreadsheet desplaza hacia abajo junto con su contenido, estilos,
        // bordes, merges y el área de impresión — validado empíricamente antes de implementar.
        if ($totalBienes > self::CONSTANCIA_FILAS_TABLA_BASE) {
            $filasExtra = $totalBienes - self::CONSTANCIA_FILAS_TABLA_BASE;
            $filaInsercion = self::CONSTANCIA_FILA_PRIMER_BIEN + self::CONSTANCIA_FILAS_TABLA_BASE;

            $hoja->insertNewRowBefore($filaInsercion, $filasExtra);

            for ($i = 0; $i < $filasExtra; $i++) {
                $filaNueva = $filaInsercion + $i;
                $hoja->mergeCells('B' . $filaNueva . ':D' . $filaNueva);
            }
        }

        // Tabla de bienes: código/valor/estado son snapshots históricos de detalle_movimiento.
        // Descripción/marca/modelo/serie NO lo son (ver nota en DetalleMovimiento::listarPorMovimiento())
        // — se leen dinámicamente de bienes, igual que ya hacía este método con descripción/serie
        // antes de este ajuste. Serie va exclusivamente en su columna propia (E) — nunca se concatena
        // dentro de la descripción, porque la plantilla ya la muestra por separado.
        //
        // IMPORTANTE — igual que TarjetasController::generarWorkbookTarjeta() (ver comentario allí):
        // anchos de columna, ALTURAS DE FILA, wrapText, merges y demás formato son los de la plantilla
        // y NUNCA se tocan por código — ni con setRowHeight() ni con setWrapText(). Aquí solo se
        // escriben VALORES de celda. Si una descripción larga necesita más espacio del que la fila ya
        // tiene, es la usuaria quien ajusta la fila manualmente en Excel — no este generador.
        $fila = self::CONSTANCIA_FILA_PRIMER_BIEN;

        foreach ($detalles as $detalle) {
            $serie = trim((string) ($detalle['serie'] ?? ''));
            $descripcionCompleta = $this->construirDescripcionCompleta($detalle);

            $hoja->setCellValue('A' . $fila, 1);
            $hoja->setCellValueExplicit('B' . $fila, $descripcionCompleta, DataType::TYPE_STRING);
            $hoja->setCellValueExplicit('E' . $fila, $serie !== '' ? $serie : '-', DataType::TYPE_STRING);
            $hoja->setCellValue('F' . $fila, (float) $detalle['valor_movimiento']);
            $hoja->setCellValueExplicit('G' . $fila, (string) ($detalle['codigo_mostrado'] ?? ''), DataType::TYPE_STRING);

            $fila++;
        }

        // Referencia interna discreta (pie de página derecho, tamaño pequeño) — no compite
        // visualmente con el formato institucional, tal como se pidió.
        $hoja->getHeaderFooter()->setOddFooter('&R&6Ref. interna: ' . $movimiento['numero_movimiento']);

        // Si la tabla creció a varias páginas, repetir las filas de encabezado (título + cabecera
        // de tabla) en cada página impresa para mantener el documento identificable.
        $hoja->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 10);

        return $spreadsheet;
    }

    /**
     * Compone "Descripción, marca: X, modelo: Y" para la columna B:D de la constancia. Regla propia
     * de Traslados — DISTINTA de RequisicionesController::construirDescripcionCompleta() y
     * PrestamosController::construirDescripcionCompleta() (que usan modelo/serie), porque la
     * plantilla de Traslado ya tiene una columna institucional independiente para Serie (columna E,
     * ver generarWorkbookConstancia()): la serie nunca debe duplicarse dentro de la descripción.
     * Fragmentos opcionales: marca/modelo solo se añaden si no están vacíos, nunca "marca: " ni
     * "modelo: " sueltos.
     */
    private function construirDescripcionCompleta(array $detalle): string
    {
        $partes = [trim((string) ($detalle['descripcion'] ?? ''))];

        $marca = trim((string) ($detalle['marca'] ?? ''));

        if ($marca !== '') {
            $partes[] = 'marca: ' . $marca;
        }

        $modelo = trim((string) ($detalle['modelo'] ?? ''));

        if ($modelo !== '') {
            $partes[] = 'modelo: ' . $modelo;
        }

        return implode(', ', $partes);
    }

    /**
     * "16 de agosto de 2026" — controlado explícitamente en español, sin depender de
     * setlocale()/LC_TIME del sistema operativo (poco fiable en Windows).
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

        $asignacionModel = $this->model('Asignacion');
        $responsableModel = $this->model('Responsable');
        $bienModel = $this->model('Bien');

        $responsablesOrigen = $responsableModel->getActivosConBienesAsignados();
        $responsablesDestino = $responsableModel->getActivos();

        $bienesPorResponsable = [];

        foreach ($responsablesOrigen as $responsable) {
            $idResponsable = (int) $responsable['id_responsable'];
            $bienes = $bienModel->getActualesPorResponsable($idResponsable);

            $bienesPorResponsable[$idResponsable] = array_map(
                static function (array $bien): array {
                    $codigoSicoin = trim((string) ($bien['codigo_sicoin'] ?? ''));
                    $codigoMostrado = $codigoSicoin !== '' ? $codigoSicoin : $bien['codigo_interno'];
                    $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

                    return [
                        'id_bien' => (int) $bien['id_bien'],
                        'codigo_mostrado' => $codigoMostrado,
                        'descripcion' => $bien['descripcion'],
                        'marca' => $bien['marca'],
                        'modelo' => $bien['modelo'],
                        'serie' => $bien['serie'],
                        'condicion_bien' => $bien['condicion_bien'],
                        'valor' => $valor,
                        'ubicacion_actual' => $bien['nombre_ubicacion'],
                        'numero_asignacion' => $bien['numero_asignacion'],
                    ];
                },
                $bienes
            );
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('traslados/crear', [
                'responsablesOrigen' => $responsablesOrigen,
                'responsablesDestino' => $responsablesDestino,
                'bienesPorResponsable' => $bienesPorResponsable,
                'error' => null,
                'datosFormulario' => [],
            ]);
            return;
        }

        verifyCsrf();

        $idResponsableOrigen = (int) ($_POST['id_responsable_origen'] ?? 0);
        $idsBienes = array_values(array_unique(array_map('intval', $_POST['bienes'] ?? [])));
        $idResponsableDestino = (int) ($_POST['id_responsable_destino'] ?? 0);
        $fechaMovimiento = trim($_POST['fecha_movimiento'] ?? '');
        $motivo = trim($_POST['motivo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        $datosFormulario = [
            'id_responsable_origen' => $idResponsableOrigen,
            'bienes' => $idsBienes,
            'id_responsable_destino' => $idResponsableDestino,
            'fecha_movimiento' => $fechaMovimiento,
            'motivo' => $motivo,
            'observaciones' => $observaciones,
        ];

        $error = null;

        if ($idResponsableOrigen <= 0) {
            $error = 'Debe seleccionar el responsable origen.';
        } elseif (empty($idsBienes)) {
            $error = 'Debe seleccionar al menos un bien para trasladar.';
        } elseif ($idResponsableDestino <= 0) {
            $error = 'Debe seleccionar el responsable destino.';
        } elseif ($idResponsableDestino === $idResponsableOrigen) {
            $error = 'El responsable destino no puede ser el mismo que el de origen.';
        } elseif ($fechaMovimiento === '') {
            $error = 'La fecha del traslado es obligatoria.';
        } elseif (!isValidIsoDate($fechaMovimiento)) {
            $error = 'La fecha del traslado no es válida.';
        } elseif ($motivo === '') {
            $error = 'El motivo del traslado es obligatorio.';
        }

        if ($error !== null) {
            $this->view('traslados/crear', [
                'responsablesOrigen' => $responsablesOrigen,
                'responsablesDestino' => $responsablesDestino,
                'bienesPorResponsable' => $bienesPorResponsable,
                'error' => $error,
                'datosFormulario' => $datosFormulario,
            ]);
            return;
        }

        $ubicacionModel = $this->model('Ubicacion');
        $movimientoModel = $this->model('Movimiento');
        $detalleMovimientoModel = $this->model('DetalleMovimiento');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $movimientoModel->beginTransaction();

            // 1-2. Bloquear y validar responsable origen.
            $responsableOrigen = $responsableModel->findActivoByIdForUpdate($idResponsableOrigen);

            if ($responsableOrigen === false) {
                throw new RuntimeException('El responsable de origen no está activo.');
            }

            // 3-8. Por cada bien: bloquear el bien (sin asumir su asignación), obtener y bloquear su
            // asignación actual, localizar y bloquear su detalle activo exacto. No se confía en ningún
            // ID de asignación/detalle enviado por el formulario: todo se recalcula aquí.
            $bienesValidados = [];

            foreach ($idsBienes as $idBien) {
                $bien = $bienModel->findActualParaTrasladoForUpdate($idBien, $idResponsableOrigen);

                if ($bien === false) {
                    throw new RuntimeException('Uno de los bienes seleccionados ya no pertenece al responsable origen o no está disponible.');
                }

                $idAsignacionActualBien = (int) $bien['id_asignacion_actual'];

                $asignacionActualBien = $asignacionModel->findByIdForUpdate($idAsignacionActualBien);

                if ($asignacionActualBien === false) {
                    throw new RuntimeException('No se pudo verificar la asignación actual de uno de los bienes seleccionados.');
                }

                $detalleOrigen = $asignacionModel->findDetalleActivoPorBienForUpdate($idAsignacionActualBien, $idBien);

                if ($detalleOrigen === false) {
                    throw new RuntimeException(
                        'El bien ' . $bien['codigo_interno']
                        . ' no tiene un detalle de asignación activo identificable y no puede trasladarse. Debe revisarse.'
                    );
                }

                $valor = $bien['costo'] !== null ? $bien['costo'] : $bien['valor_estimado'];

                if ($valor === null) {
                    throw new RuntimeException(
                        'El bien ' . $bien['codigo_interno'] . ' no tiene costo ni valor estimado registrado y no puede trasladarse.'
                    );
                }

                $codigoSicoin = trim((string) ($bien['codigo_sicoin'] ?? ''));
                $codigoMostrado = $codigoSicoin !== '' ? $codigoSicoin : $bien['codigo_interno'];

                if (trim((string) $codigoMostrado) === '') {
                    throw new RuntimeException('El bien seleccionado no tiene un código válido para el traslado.');
                }

                $bienesValidados[$idBien] = [
                    'bien' => $bien,
                    'id_asignacion_actual' => $idAsignacionActualBien,
                    'detalle_origen' => $detalleOrigen,
                    'codigo_mostrado' => $codigoMostrado,
                    'valor' => $valor,
                ];
            }

            // No asumir silenciosamente una ubicación origen común: verificar que todos los bienes
            // seleccionados compartan la misma id_ubicacion_actual. Protege datos históricos/inconsistentes
            // (p. ej. bienes cuya ubicación actual no coincide con la de su responsable).
            $ubicacionesActualesBienes = [];
            foreach ($bienesValidados as $info) {
                $ubicacionesActualesBienes[(int) $info['bien']['id_ubicacion_actual']] = true;
            }

            if (count($ubicacionesActualesBienes) > 1) {
                throw new RuntimeException(
                    'Los bienes seleccionados no comparten una ubicación actual coherente y deben revisarse antes de trasladarse.'
                );
            }

            $idUbicacionOrigen = (int) array_key_first($ubicacionesActualesBienes);

            $ubicacionOrigen = $ubicacionModel->findActivaByIdForUpdate($idUbicacionOrigen);

            if ($ubicacionOrigen === false) {
                throw new RuntimeException('La ubicación actual de los bienes seleccionados no está activa.');
            }

            // 4. Bloquear responsable destino y derivar/bloquear ubicación destino.
            $responsableDestino = $responsableModel->findActivoByIdForUpdate($idResponsableDestino);

            if ($responsableDestino === false) {
                throw new RuntimeException('El responsable destino seleccionado no es válido o está inactivo.');
            }

            $idUbicacionDestino = (int) $responsableDestino['id_ubicacion'];

            $ubicacionDestino = $ubicacionModel->findActivaByIdForUpdate($idUbicacionDestino);

            if ($ubicacionDestino === false) {
                throw new RuntimeException('No se puede trasladar porque la ubicación del responsable destino está inactiva.');
            }

            // 5. Resolver asignación destino: findAsignadaPorResponsableUbicacionForUpdate() solo puede
            // devolver una 'Asignada' que coincida con responsable Y ubicación a la vez — una
            // 'Pendiente' antigua ni siquiera entra como candidata (ver comentario en Asignacion.php);
            // no reutiliza, no bloquea el traslado, y el flujo cae directo a crear una 'Asignada' nueva.
            $asignacionDestino = $asignacionModel->findAsignadaPorResponsableUbicacionForUpdate($idResponsableDestino, $idUbicacionDestino);

            if ($asignacionDestino !== false) {
                $idAsignacionDestino = (int) $asignacionDestino['id_asignacion'];
            } else {
                $anioDestino = (int) substr($fechaMovimiento, 0, 4);
                $numeroAsignacionDestino = $asignacionModel->generarSiguienteNumero($anioDestino);

                $idAsignacionDestino = $asignacionModel->crear([
                    'numero_asignacion' => $numeroAsignacionDestino,
                    'id_responsable' => $idResponsableDestino,
                    'id_ubicacion' => $idUbicacionDestino,
                    'fecha_asignacion' => $fechaMovimiento,
                    'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                    'estado_asignacion' => 'Asignada',
                    'observaciones' => 'Asignación creada automáticamente por Traslado.',
                ]);
            }

            // 6. Tipo de movimiento (búsqueda por catálogo, sin ID fijo).
            $tipoTraslado = $movimientoModel->findTipoPorNombre('Traslado');

            if ($tipoTraslado === false) {
                throw new RuntimeException('No se encontró el tipo de movimiento "Traslado" en el catálogo.');
            }

            // 7. Numerar y crear la cabecera del movimiento.
            $anioMovimiento = (int) substr($fechaMovimiento, 0, 4);
            $numeroMovimiento = $movimientoModel->generarSiguienteNumero($anioMovimiento);

            // fecha_movimiento = fecha administrativa elegida por el usuario + hora real de registro
            // (zona horaria America/Guatemala, fijada globalmente en index.php). Distinto de created_at,
            // que registra el instante exacto de inserción del registro.
            $fechaHoraMovimiento = $fechaMovimiento . ' ' . date('H:i:s');

            $idMovimiento = $movimientoModel->crear([
                'numero_movimiento' => $numeroMovimiento,
                'id_tipo_movimiento' => (int) $tipoTraslado['id_tipo_movimiento'],
                'id_responsable_origen' => $idResponsableOrigen,
                'id_ubicacion_origen' => $idUbicacionOrigen,
                'id_asignacion_destino' => $idAsignacionDestino,
                'id_responsable_destino' => $idResponsableDestino,
                'id_ubicacion_destino' => $idUbicacionDestino,
                'id_usuario_registra' => (int) $_SESSION['id_usuario'],
                'id_usuario_autoriza' => null,
                'fecha_movimiento' => $fechaHoraMovimiento,
                'motivo' => $motivo,
                'observaciones' => $observaciones !== '' ? $observaciones : null,
            ]);

            // 8. Por cada bien: retirar de SU asignación origen específica, incorporar a destino,
            // actualizar espejo, registrar detalle. Cada bien puede provenir de una asignación distinta.
            foreach ($bienesValidados as $idBien => $info) {
                $bien = $info['bien'];
                $detalleOrigen = $info['detalle_origen'];
                $idAsignacionOrigenBien = $info['id_asignacion_actual'];

                $asignacionModel->retirarBien((int) $detalleOrigen['id_detalle_asignacion'], $fechaMovimiento);

                $idDetalleAsignacionDestino = $asignacionModel->agregarBien(
                    $idAsignacionDestino,
                    $idBien,
                    $fechaMovimiento,
                    null
                );

                $espejoActualizado = $bienModel->actualizarAsignacionActual(
                    $idBien,
                    $idAsignacionOrigenBien,
                    $idAsignacionDestino,
                    $idResponsableDestino,
                    $idUbicacionDestino
                );

                if (!$espejoActualizado) {
                    throw new RuntimeException(
                        'No se pudo actualizar el bien ' . $bien['codigo_interno']
                        . ' porque su información cambió durante el proceso. Intente nuevamente.'
                    );
                }

                // Traslado no cambia estado ni condición del bien: se congela el mismo valor como anterior/nuevo.
                $detalleMovimientoModel->insertar([
                    'id_movimiento' => $idMovimiento,
                    'id_bien' => $idBien,
                    'id_detalle_asignacion_origen' => (int) $detalleOrigen['id_detalle_asignacion'],
                    'id_detalle_asignacion_destino' => $idDetalleAsignacionDestino,
                    'codigo_mostrado' => $info['codigo_mostrado'],
                    'valor_movimiento' => $info['valor'],
                    'id_estado_bien_anterior' => (int) $bien['id_estado_bien'],
                    'id_estado_bien_nuevo' => (int) $bien['id_estado_bien'],
                    'condicion_anterior' => $bien['condicion_bien'],
                    'condicion_nueva' => $bien['condicion_bien'],
                    'observaciones' => null,
                ]);
            }

            // 9. Bitácora: una sola entrada por Traslado.
            $descripcionBitacora = sprintf(
                'Se registró el traslado %s de %s/%s a %s/%s con %d bien(es).',
                $numeroMovimiento,
                $responsableOrigen['nombre_completo'],
                $ubicacionOrigen['nombre_ubicacion'],
                $responsableDestino['nombre_completo'],
                $ubicacionDestino['nombre_ubicacion'],
                count($bienesValidados)
            );

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'REGISTRAR_TRASLADO',
                modulo: 'Movimientos',
                resultado: 'exitoso',
                descripcion: $descripcionBitacora,
                tablaAfectada: 'movimientos',
                idRegistroAfectado: $idMovimiento,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $movimientoModel->commit();

            header('Location: index.php?modulo=traslados&accion=ver&id=' . $idMovimiento);
            exit;
        } catch (Throwable $e) {
            if ($movimientoModel->inTransaction()) {
                $movimientoModel->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                error_log('Error al registrar el traslado: ' . $e->getMessage());

                $error = $this->mensajeErrorDuplicado($e)
                    ?? 'No fue posible registrar el traslado. Verifique los datos e intente nuevamente.';
            }

            $this->view('traslados/crear', [
                'responsablesOrigen' => $responsablesOrigen,
                'responsablesDestino' => $responsablesDestino,
                'bienesPorResponsable' => $bienesPorResponsable,
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

        if (str_contains($detalleError, 'uq_movimientos_numero')) {
            return 'No fue posible generar un número único para el traslado. Intente nuevamente.';
        }

        if (str_contains($detalleError, 'uq_detalle_movimiento_bien')) {
            return 'Un bien fue seleccionado más de una vez en el traslado.';
        }

        if (str_contains($detalleError, 'uq_asignaciones_numero')) {
            return 'No fue posible generar un número único para la asignación destino. Intente nuevamente.';
        }

        return null;
    }
}
