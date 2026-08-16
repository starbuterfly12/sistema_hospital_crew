<?php

require_once __DIR__ . '/../core/Controller.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TarjetasController extends Controller
{
    private const OPERACIONES_POR_HOJA = 21;
    private const FILA_PRIMERA_OPERACION = 11;
    private const FILA_ARRASTRE = 10;

    public function index(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $tarjetaModel = $this->model('TarjetaResponsabilidad');
        $tarjetas = $tarjetaModel->getAll();

        $this->view('tarjetas/index', [
            'tarjetas' => $tarjetas,
        ]);
    }

    public function generar(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        requireRole(['Administrador', 'Operativo']);

        $asignacionModel = $this->model('Asignacion');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view('tarjetas/generar', [
                'asignaciones' => $asignacionModel->getAsignadas(),
                'error' => null,
            ]);
            return;
        }

        verifyCsrf();

        $idAsignacion = (int) ($_POST['id_asignacion'] ?? 0);

        if ($idAsignacion <= 0) {
            $this->view('tarjetas/generar', [
                'asignaciones' => $asignacionModel->getAsignadas(),
                'error' => 'Debe seleccionar una asignación.',
            ]);
            return;
        }

        $responsableModel = $this->model('Responsable');
        $ubicacionModel = $this->model('Ubicacion');
        $tarjetaModel = $this->model('TarjetaResponsabilidad');
        $detalleTarjetaModel = $this->model('DetalleTarjetaResponsabilidad');
        $historialSicoinModel = $this->model('HistorialSicoin');
        $detalleMovimientoModel = $this->model('DetalleMovimiento');
        $bitacoraModel = $this->model('Bitacora');

        try {
            $tarjetaModel->beginTransaction();

            $asignacion = $asignacionModel->findByIdForUpdate($idAsignacion);

            if ($asignacion === false) {
                $tarjetaModel->rollBack();

                $this->view('tarjetas/generar', [
                    'asignaciones' => $asignacionModel->getAsignadas(),
                    'error' => 'Asignación no encontrada.',
                ]);
                return;
            }

            if ($asignacion['estado_asignacion'] !== 'Asignada') {
                $tarjetaModel->rollBack();

                $this->view('tarjetas/generar', [
                    'asignaciones' => $asignacionModel->getAsignadas(),
                    'error' => 'Solo se pueden generar tarjetas para asignaciones en estado Asignada.',
                ]);
                return;
            }

            $idResponsable = (int) $asignacion['id_responsable'];
            $idUbicacion = (int) $asignacion['id_ubicacion'];

            $responsable = $responsableModel->findByIdForUpdate($idResponsable);

            if ($responsable === false) {
                $tarjetaModel->rollBack();

                $this->view('tarjetas/generar', [
                    'asignaciones' => $asignacionModel->getAsignadas(),
                    'error' => 'No se encontró el responsable de la asignación.',
                ]);
                return;
            }

            $ubicacion = $ubicacionModel->findById($idUbicacion);

            if ($ubicacion === false) {
                $tarjetaModel->rollBack();

                $this->view('tarjetas/generar', [
                    'asignaciones' => $asignacionModel->getAsignadas(),
                    'error' => 'No se encontró la ubicación de la asignación.',
                ]);
                return;
            }

            $detalles = $asignacionModel->getDetallesParaTarjeta($idAsignacion);

            if (empty($detalles)) {
                $tarjetaModel->rollBack();

                $this->view('tarjetas/generar', [
                    'asignaciones' => $asignacionModel->getAsignadas(),
                    'error' => 'La asignación no tiene bienes ni historial para generar una tarjeta de responsabilidad.',
                ]);
                return;
            }

            $anio = (int) date('Y');
            $fechaEmision = date('Y-m-d');
            $numeroTarjeta = $tarjetaModel->generarSiguienteNumero($anio);

            $idTarjeta = $tarjetaModel->crear([
                'numero_tarjeta' => $numeroTarjeta,
                'id_asignacion' => $idAsignacion,
                'id_responsable' => $idResponsable,
                'responsable_nombre' => $responsable['nombre_completo'],
                'responsable_nit' => $responsable['nit'],
                'responsable_cargo' => $responsable['cargo'],
                'id_ubicacion' => $idUbicacion,
                'ubicacion_nombre' => $ubicacion['nombre_ubicacion'],
                'fecha_emision' => $fechaEmision,
                'estado_tarjeta' => 'Emitida',
                'id_usuario_genera' => (int) $_SESSION['id_usuario'],
            ]);

            $eventos = [];

            foreach ($detalles as $detalle) {
                $idDetalleAsignacion = (int) $detalle['id_detalle_asignacion'];

                $historialBien = $historialSicoinModel->getPorBien((int) $detalle['id_bien']);
                $entrada = $detalleMovimientoModel->findEntradaPorDetalleDestino($idDetalleAsignacion);
                $salida = $detalleMovimientoModel->findSalidaPorDetalleOrigen($idDetalleAsignacion);

                array_push($eventos, ...$this->construirEventosDetalle($detalle, $historialBien, $entrada, $salida));
            }

            usort($eventos, function (array $a, array $b): int {
                return $a['clave_fecha'] <=> $b['clave_fecha']
                    ?: $a['clave_grupo'] <=> $b['clave_grupo']
                    ?: $a['clave_agrupador'] <=> $b['clave_agrupador']
                    ?: $a['clave_suborden'] <=> $b['clave_suborden'];
            });

            $saldo = 0.0;
            $orden = 1;

            foreach ($eventos as $evento) {
                $saldo = round($saldo + $evento['debe'] - $evento['haber'], 2);

                $detalleTarjetaModel->insertar([
                    'id_tarjeta_responsabilidad' => $idTarjeta,
                    'id_bien' => $evento['id_bien'],
                    'tipo_operacion' => $evento['tipo_operacion'],
                    'fecha_operacion' => $evento['fecha_operacion'],
                    'codigo_mostrado' => $evento['codigo_mostrado'],
                    'descripcion_mostrada' => $evento['descripcion_mostrada'],
                    'cantidad' => $evento['cantidad'],
                    'costo_unitario' => $evento['costo_unitario'],
                    'debe' => $evento['debe'],
                    'haber' => $evento['haber'],
                    'saldo_resultante' => $saldo,
                    'orden_linea' => $orden,
                    'observaciones' => $evento['observaciones'],
                    'id_detalle_movimiento' => $evento['id_detalle_movimiento'],
                    'id_detalle_asignacion' => $evento['id_detalle_asignacion'],
                ]);

                $orden++;
            }

            $bitacoraModel->registrar(
                idUsuario: (int) $_SESSION['id_usuario'],
                accion: 'GENERAR_TARJETA_RESPONSABILIDAD',
                modulo: 'Tarjetas',
                resultado: 'exitoso',
                descripcion: 'Se generó la tarjeta ' . $numeroTarjeta . ' de la asignación '
                    . $asignacion['numero_asignacion'] . ' para ' . $responsable['nombre_completo']
                    . ' con ' . count($eventos) . ' operación(es).',
                tablaAfectada: 'tarjetas_responsabilidad',
                idRegistroAfectado: $idTarjeta,
                ipOrigen: $_SERVER['REMOTE_ADDR'] ?? null,
                usuarioIntentado: null
            );

            $tarjetaModel->commit();

            header('Location: index.php?modulo=tarjetas&accion=ver&id=' . $idTarjeta);
            exit;
        } catch (Throwable $e) {
            if ($tarjetaModel->inTransaction()) {
                $tarjetaModel->rollBack();
            }

            error_log('Error al generar la tarjeta de responsabilidad: ' . $e->getMessage());

            $error = $this->mensajeErrorDuplicado($e)
                ?? 'No fue posible generar la tarjeta de responsabilidad. Verifique los datos e intente nuevamente.';

            $this->view('tarjetas/generar', [
                'asignaciones' => $asignacionModel->getAsignadas(),
                'error' => $error,
            ]);
            return;
        }
    }

    public function ver(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idTarjeta = (int) ($_GET['id'] ?? 0);

        if ($idTarjeta <= 0) {
            echo 'Tarjeta no válida.';
            return;
        }

        $tarjetaModel = $this->model('TarjetaResponsabilidad');
        $tarjeta = $tarjetaModel->findById($idTarjeta);

        if ($tarjeta === false) {
            echo 'Tarjeta no encontrada.';
            return;
        }

        $detalleTarjetaModel = $this->model('DetalleTarjetaResponsabilidad');
        $operaciones = $detalleTarjetaModel->listarPorTarjeta($idTarjeta);

        $this->view('tarjetas/ver', [
            'tarjeta' => $tarjeta,
            'operaciones' => $operaciones,
        ]);
    }

    public function descargarExcel(): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php');
            exit;
        }

        $idTarjeta = (int) ($_GET['id'] ?? 0);

        if ($idTarjeta <= 0) {
            echo 'Tarjeta no válida.';
            return;
        }

        $tarjetaModel = $this->model('TarjetaResponsabilidad');
        $tarjeta = $tarjetaModel->findById($idTarjeta);

        if ($tarjeta === false) {
            echo 'Tarjeta no encontrada.';
            return;
        }

        $detalleTarjetaModel = $this->model('DetalleTarjetaResponsabilidad');
        $operaciones = $detalleTarjetaModel->listarPorTarjeta($idTarjeta);

        if (empty($operaciones)) {
            echo 'Esta tarjeta no tiene operaciones registradas.';
            return;
        }

        $spreadsheet = $this->generarWorkbookTarjeta($tarjeta, $operaciones);

        $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $tarjeta['numero_tarjeta']) . '.xlsx';

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
     * Reconstruye la tarjeta como workbook XLSX a partir de la plantilla maestra,
     * SIN modificarla: se carga en memoria, se clonan hojas dentro de esa copia
     * en memoria, y se retorna el objeto Spreadsheet ya listo para escribir.
     */
    private function generarWorkbookTarjeta(array $tarjeta, array $operaciones): Spreadsheet
    {
        $rutaPlantilla = dirname(__DIR__, 2) . '/storage/templates/tarjetas_responsabilidad.xlsx';

        if (!is_file($rutaPlantilla)) {
            throw new RuntimeException('No se encontró la plantilla maestra de tarjetas de responsabilidad.');
        }

        $spreadsheet = IOFactory::load($rutaPlantilla);

        $plantillaPrimera = $spreadsheet->getSheet(0);
        $plantillaContinuacion = $spreadsheet->getSheet(1);

        $totalOperaciones = count($operaciones);
        $numeroHojas = (int) max(1, ceil($totalOperaciones / self::OPERACIONES_POR_HOJA));

        $hojasGeneradas = [];

        for ($numeroHoja = 1; $numeroHoja <= $numeroHojas; $numeroHoja++) {
            $origen = $numeroHoja === 1 ? $plantillaPrimera : $plantillaContinuacion;
            $hoja = clone $origen;
            $hoja->setTitle('Hoja ' . $numeroHoja);
            $spreadsheet->addSheet($hoja);
            $hojasGeneradas[] = $hoja;
        }

        // Elimina las hojas originales de la plantilla (quedaron primero en el workbook cargado).
        while ($spreadsheet->getSheetCount() > count($hojasGeneradas)) {
            $spreadsheet->removeSheetByIndex(0);
        }

        $spreadsheet->setActiveSheetIndex(0);

        foreach ($hojasGeneradas as $indice => $hoja) {
            $numeroHoja = $indice + 1;
            $esPrimera = $numeroHoja === 1;
            $esUltima = $numeroHoja === $numeroHojas;

            $this->prepararHojaTarjeta($hoja, $tarjeta);

            $offset = ($numeroHoja - 1) * self::OPERACIONES_POR_HOJA;
            $operacionesHoja = array_slice($operaciones, $offset, self::OPERACIONES_POR_HOJA);
            $cantidadEnHoja = count($operacionesHoja);

            if (!$esPrimera) {
                $saldoAnterior = (float) $operaciones[$offset - 1]['saldo_resultante'];
                $hoja->setCellValue('D' . self::FILA_ARRASTRE, 'VIENEN');
                $hoja->setCellValue('G' . self::FILA_ARRASTRE, $saldoAnterior);
                $hoja->getStyle('D' . self::FILA_ARRASTRE)->getFont()->setBold(true);
                $hoja->getStyle('G' . self::FILA_ARRASTRE)->getFont()->setBold(true);
            }

            $fila = self::FILA_PRIMERA_OPERACION;

            foreach ($operacionesHoja as $operacion) {
                $hoja->setCellValue(
                    'A' . $fila,
                    ExcelDate::PHPToExcel(new DateTime((string) $operacion['fecha_operacion']))
                );

                if ($operacion['codigo_mostrado'] !== null && $operacion['codigo_mostrado'] !== '') {
                    $hoja->setCellValueExplicit('B' . $fila, (string) $operacion['codigo_mostrado'], DataType::TYPE_STRING);
                }

                $hoja->setCellValue('C' . $fila, (int) $operacion['cantidad']);
                $hoja->setCellValueExplicit('D' . $fila, (string) $operacion['descripcion_mostrada'], DataType::TYPE_STRING);
                $hoja->setCellValue('E' . $fila, (float) $operacion['debe']);
                $hoja->setCellValue('F' . $fila, (float) $operacion['haber']);
                $hoja->setCellValue('G' . $fila, (float) $operacion['saldo_resultante']);

                $fila++;
            }

            $filaCierre = self::FILA_PRIMERA_OPERACION + $cantidadEnHoja;

            if ($esUltima) {
                $ultimaOperacion = end($operacionesHoja);
                $hoja->setCellValue('D' . $filaCierre, 'ULTIMA LINEA');
                $hoja->setCellValue('G' . $filaCierre, (float) $ultimaOperacion['saldo_resultante']);
                $hoja->getStyle('D' . $filaCierre)->getFont()->setBold(true);
                $hoja->getStyle('G' . $filaCierre)->getFont()->setBold(true);
            } else {
                $hoja->setCellValue('D' . $filaCierre, 'VAN');
                $hoja->getStyle('D' . $filaCierre)->getFont()->setBold(true);
            }
        }

        return $spreadsheet;
    }

    /**
     * Limpia los datos de muestra heredados del clon de la plantilla y escribe
     * el encabezado congelado de la emisión. No toca estilos, merges, bordes
     * ni configuración de página — solo valores de celda.
     */
    private function prepararHojaTarjeta(Worksheet $hoja, array $tarjeta): void
    {
        $hoja->setCellValueExplicit('C4', (string) $tarjeta['responsable_nombre'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('F4', (string) $tarjeta['responsable_nit'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('C6', (string) $tarjeta['ubicacion_nombre'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('F6', (string) $tarjeta['responsable_cargo'], DataType::TYPE_STRING);

        $hoja->setCellValue('D' . self::FILA_ARRASTRE, null);
        $hoja->setCellValue('G' . self::FILA_ARRASTRE, null);

        $ultimaFilaOperacion = self::FILA_PRIMERA_OPERACION + self::OPERACIONES_POR_HOJA - 1;

        for ($fila = self::FILA_PRIMERA_OPERACION; $fila <= $ultimaFilaOperacion; $fila++) {
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $columna) {
                $hoja->setCellValue($columna . $fila, null);
            }
        }

        $filaCierre = $ultimaFilaOperacion + 1;
        $hoja->setCellValue('D' . $filaCierre, null);
        $hoja->setCellValue('G' . $filaCierre, null);
    }

    /**
     * Construye los eventos históricos de UN detalle de asignación dentro de una emisión:
     * evento inicial (ALTA o TRASLADO_ENTRADA, mutuamente excluyentes), regularizaciones SICOIN
     * acotadas a la vigencia de este detalle, y evento final TRASLADO_SALIDA si aplica.
     * No inserta nada: solo devuelve la colección en memoria, cada evento con sus propias
     * claves de orden global (ver generar()).
     *
     * $entrada / $salida: filas de detalle_movimiento (o false) ya resueltas por el llamador
     * vía DetalleMovimiento::findEntradaPorDetalleDestino()/findSalidaPorDetalleOrigen().
     */
    private function construirEventosDetalle(array $detalle, array $historialBien, array|false $entrada, array|false $salida): array
    {
        $idBien = (int) $detalle['id_bien'];
        $idDetalleAsignacion = (int) $detalle['id_detalle_asignacion'];
        $fechaAgregado = (string) $detalle['fecha_agregado'];
        $fechaRetirado = $detalle['fecha_retirado'] !== null ? (string) $detalle['fecha_retirado'] : null;
        $descripcionMostrada = $detalle['descripcion'];
        $codigoInterno = (string) $detalle['codigo_interno'];

        $valorBien = $detalle['costo'] ?? $detalle['valor_estimado'] ?? null;

        if ($valorBien === null) {
            throw new RuntimeException(
                'El bien con id ' . $idBien . ' no tiene costo ni valor estimado registrado.'
            );
        }

        $valorBien = round((float) $valorBien, 2);

        $entroPorTraslado = $entrada !== false;
        $salioPorTraslado = $salida !== false;

        $eventos = [];

        if ($entroPorTraslado) {
            // Detalle DESTINO de un Traslado: NO se genera ALTA. El código y el valor son
            // exclusivamente el snapshot congelado en detalle_movimiento (puntos 10-11).
            $fechaEntradaCompleta = (string) $entrada['fecha_movimiento'];
            $fechaEntradaFecha = substr($fechaEntradaCompleta, 0, 10);
            $valorEntrada = round((float) $entrada['valor_movimiento'], 2);

            $eventos[] = [
                'id_bien' => $idBien,
                'id_detalle_asignacion' => $idDetalleAsignacion,
                'id_detalle_movimiento' => (int) $entrada['id_detalle_movimiento'],
                'tipo_operacion' => 'TRASLADO_ENTRADA',
                'fecha_operacion' => $fechaEntradaFecha,
                'codigo_mostrado' => $entrada['codigo_mostrado'],
                'descripcion_mostrada' => $descripcionMostrada,
                'cantidad' => 1,
                'costo_unitario' => $valorEntrada,
                'debe' => $valorEntrada,
                'haber' => 0.00,
                'observaciones' => null,
                'clave_fecha' => $fechaEntradaCompleta,
                'clave_grupo' => 0,
                'clave_agrupador' => $idDetalleAsignacion,
                'clave_suborden' => 0,
            ];
        } else {
            // ALTA normal: detalle que entró por asignación directa (sin cambios respecto
            // al generador anterior).
            $codigoMostradoAlta = $detalle['codigo_sicoin'];

            foreach ($historialBien as $evento) {
                if (substr((string) $evento['fecha_cambio'], 0, 10) >= $fechaAgregado) {
                    $codigoMostradoAlta = $evento['sicoin_anterior'];
                    break;
                }
            }

            if ($codigoMostradoAlta === null || $codigoMostradoAlta === '') {
                $codigoMostradoAlta = $codigoInterno;
            }

            $eventos[] = [
                'id_bien' => $idBien,
                'id_detalle_asignacion' => $idDetalleAsignacion,
                'id_detalle_movimiento' => null,
                'tipo_operacion' => 'ALTA',
                'fecha_operacion' => $fechaAgregado,
                'codigo_mostrado' => $codigoMostradoAlta,
                'descripcion_mostrada' => $descripcionMostrada,
                'cantidad' => 1,
                'costo_unitario' => $valorBien,
                'debe' => $valorBien,
                'haber' => 0.00,
                'observaciones' => null,
                'clave_fecha' => $fechaAgregado,
                'clave_grupo' => 0,
                'clave_agrupador' => $idDetalleAsignacion,
                'clave_suborden' => 0,
            ];
        }

        // Regularizaciones SICOIN acotadas a la vigencia de ESTE detalle (evita duplicar un
        // mismo cambio de SICOIN entre dos responsables cuando hubo Traslado de por medio).
        // Límite inferior EXCLUSIVO cuando el detalle entró por Traslado: el día de
        // TRASLADO_ENTRADA ya "pertenece" a esta etapa desde ese evento, pero un cambio de
        // SICOIN fechado ese mismo día se considera ocurrido antes de la entrada y se atribuye
        // a la etapa anterior (cuyo límite superior es inclusivo — ver abajo). Así, un mismo día
        // de transición nunca queda cubierto por ambas etapas a la vez.
        foreach ($historialBien as $evento) {
            $sicoinAnterior = $evento['sicoin_anterior'];
            $sicoinNuevo = $evento['sicoin_nuevo'];
            $fechaCambioCompleta = (string) $evento['fecha_cambio'];
            $fechaCambioFecha = substr($fechaCambioCompleta, 0, 10);

            $esRegularizacionSicoin = ($sicoinAnterior === null || $sicoinAnterior === '')
                && $sicoinNuevo !== null
                && $sicoinNuevo !== '';

            if (!$esRegularizacionSicoin) {
                continue;
            }

            $dentroLimiteInferior = $entroPorTraslado
                ? ($fechaCambioFecha > $fechaAgregado)
                : ($fechaCambioFecha >= $fechaAgregado);

            $dentroLimiteSuperior = $fechaRetirado === null || $fechaCambioFecha <= $fechaRetirado;

            if (!$dentroLimiteInferior || !$dentroLimiteSuperior) {
                continue;
            }

            $idHistorialSicoin = (int) $evento['id_historial_sicoin'];

            $eventos[] = [
                'id_bien' => $idBien,
                'id_detalle_asignacion' => $idDetalleAsignacion,
                'id_detalle_movimiento' => null,
                'tipo_operacion' => 'REGULARIZACION_SALIDA',
                'fecha_operacion' => $fechaCambioFecha,
                'codigo_mostrado' => $codigoInterno,
                'descripcion_mostrada' => $descripcionMostrada,
                'cantidad' => 1,
                'costo_unitario' => $valorBien,
                'debe' => 0.00,
                'haber' => $valorBien,
                'observaciones' => null,
                'clave_fecha' => $fechaCambioCompleta,
                'clave_grupo' => 1,
                'clave_agrupador' => $idHistorialSicoin,
                'clave_suborden' => 0,
            ];

            $eventos[] = [
                'id_bien' => $idBien,
                'id_detalle_asignacion' => $idDetalleAsignacion,
                'id_detalle_movimiento' => null,
                'tipo_operacion' => 'REGULARIZACION_ENTRADA',
                'fecha_operacion' => $fechaCambioFecha,
                'codigo_mostrado' => $sicoinNuevo,
                'descripcion_mostrada' => $descripcionMostrada,
                'cantidad' => 1,
                'costo_unitario' => $valorBien,
                'debe' => $valorBien,
                'haber' => 0.00,
                'observaciones' => null,
                'clave_fecha' => $fechaCambioCompleta,
                'clave_grupo' => 1,
                'clave_agrupador' => $idHistorialSicoin,
                'clave_suborden' => 1,
            ];
        }

        if ($salioPorTraslado) {
            // Detalle que salió por Traslado: evento final, siempre después de cualquier
            // evento previo del mismo día (clave_grupo = 2, la más alta de este detalle).
            $fechaSalidaCompleta = (string) $salida['fecha_movimiento'];
            $fechaSalidaFecha = substr($fechaSalidaCompleta, 0, 10);
            $valorSalida = round((float) $salida['valor_movimiento'], 2);

            $eventos[] = [
                'id_bien' => $idBien,
                'id_detalle_asignacion' => $idDetalleAsignacion,
                'id_detalle_movimiento' => (int) $salida['id_detalle_movimiento'],
                'tipo_operacion' => 'TRASLADO_SALIDA',
                'fecha_operacion' => $fechaSalidaFecha,
                'codigo_mostrado' => $salida['codigo_mostrado'],
                'descripcion_mostrada' => $descripcionMostrada,
                'cantidad' => 1,
                'costo_unitario' => $valorSalida,
                'debe' => 0.00,
                'haber' => $valorSalida,
                'observaciones' => null,
                'clave_fecha' => $fechaSalidaCompleta,
                'clave_grupo' => 2,
                'clave_agrupador' => $idDetalleAsignacion,
                'clave_suborden' => 0,
            ];
        }

        return $eventos;
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

        if (str_contains($detalleError, 'uq_tarjetas_responsabilidad_numero')) {
            return 'No fue posible generar un número único para la tarjeta. Intente nuevamente.';
        }

        return null;
    }
}
