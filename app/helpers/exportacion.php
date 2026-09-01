<?php

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Helper de exportación común para el módulo Reportes: NO se usa en ningún otro módulo (Tarjetas/
// Requisiciones/Préstamos/Traslados/Bajas rellenan sus propias plantillas institucionales .xlsx y
// no tienen relación con este archivo). Reportes no tiene plantilla institucional (confirmado en la
// auditoría), así que el Excel se construye enteramente por código, aquí, una sola vez, en vez de
// repetir el mismo boilerplate en cada uno de los 8 reportes.

if (!function_exists('sanitizarTextoExcel')) {
    // Neutraliza inyección de fórmulas en texto libre proveniente de BD/usuario (observaciones,
    // justificación, motivo, etc.): si el valor empieza con un carácter que Excel podría interpretar
    // como inicio de fórmula, se antepone un apóstrofo para forzarlo a texto literal. NO debe
    // aplicarse a celdas numéricas/fecha (esas se escriben con setCellValue() de tipo numérico
    // directamente, nunca pasan por aquí).
    function sanitizarTextoExcel(?string $valor): string
    {
        $valor = (string) ($valor ?? '');

        if ($valor === '') {
            return $valor;
        }

        if (preg_match('/^[=+\-@\t\r]/', $valor) === 1) {
            return "'" . $valor;
        }

        return $valor;
    }
}

if (!function_exists('nombreArchivoReporte')) {
    // Con rango de fechas: Informe_Movimientos_01-08-2026_al_31-08-2026.xlsx
    // Sin rango (reportes de estado actual, ej. Préstamos sin filtro de fecha): Informe_PrestamosPendientes_Generado_20-08-2026.xlsx
    function nombreArchivoReporte(string $base, ?string $fechaDesde = null, ?string $fechaHasta = null): string
    {
        $nombreBase = 'Informe_' . preg_replace('/[^A-Za-z0-9]/', '', $base);

        if ($fechaDesde !== null && $fechaHasta !== null && isValidIsoDate($fechaDesde) && isValidIsoDate($fechaHasta)) {
            $desde = DateTime::createFromFormat('!Y-m-d', $fechaDesde)->format('d-m-Y');
            $hasta = DateTime::createFromFormat('!Y-m-d', $fechaHasta)->format('d-m-Y');

            return $nombreBase . '_' . $desde . '_al_' . $hasta . '.xlsx';
        }

        return $nombreBase . '_Generado_' . date('d-m-Y') . '.xlsx';
    }
}

// Tipografía y tamaños aprobados en la revisión de impresión — reemplaza el criterio anterior de
// "fuente 12 en todo" (que, combinado con muchas columnas, obligaba a reducir la escala de
// impresión de forma extrema). Arial en toda la hoja; el cuerpo de la tabla en 7pt es lo que
// realmente permite que columnas compactas + FitToWidth=1 sigan siendo legibles.
if (!defined('REPORTES_FUENTE')) {
    define('REPORTES_FUENTE', 'Arial');
    define('REPORTES_TAM_INSTITUCIONAL', 10);
    define('REPORTES_TAM_TITULO', 12);
    define('REPORTES_TAM_META', 9);
    define('REPORTES_TAM_ENCABEZADO_TABLA', 7);
    define('REPORTES_TAM_DATOS', 7);
    define('REPORTES_TAM_TOTALES', 8);
}

if (!function_exists('construirReporteExcel')) {
    /**
     * Construye un Spreadsheet nuevo (sin plantilla) con el formato administrativo uniforme de
     * Reportes: encabezado institucional compacto, título, período/filtros aplicados, generado por,
     * tabla con encabezados + bordes + autofiltro + freeze pane, y fila de totales. SIEMPRE
     * horizontal/Letter, ajustado a una sola página de ANCHO (alto libre, con encabezado de tabla
     * repetido en cada página) — ver revisión de impresión.
     *
     * $columnas: lista de ['clave'=>string,'titulo'=>string,'tipo'=>'texto'|'entero'|'moneda'|'fecha'|'fecha_hora'|'fecha_variable','ancho'=>int opcional]
     *   'fecha_variable' espera que el valor de la fila sea ['valor' => string fecha, 'es_datetime' => bool]
     *   — permite que Movimientos/Bienes con actividad muestren DD/MM/YYYY (sin hora falsa) para
     *   fuentes que solo tienen DATE, y DD/MM/YYYY HH:mm para las que sí tienen DATETIME real.
     * $filas: lista de arrays asociativos, cada uno indexado por 'clave' de columna
     * $metaFiltros: líneas de texto ya formateadas (ej. "Período: 01/08/2026 al 31/08/2026", "Tipo: Traslado")
     * $totales: null, o ['valores' => [clave_columna => suma_numerica, ...]]
     */
    function construirReporteExcel(
        string $titulo,
        array $columnas,
        array $filas,
        array $metaFiltros,
        string $generadoPor,
        string $orientacion = 'landscape',
        ?array $totales = null
    ): Spreadsheet {
        // Ancho de columna (en unidades de caracteres) cuando la definición de columna no trae
        // 'ancho' explícito. Rangos compactos aprobados en la revisión de impresión — nunca AutoSize.
        $anchoPorDefecto = [
            'texto' => 18,
            'entero' => 10,
            'moneda' => 13,
            'fecha' => 12,
            'fecha_hora' => 15,
            'fecha_variable' => 13,
        ];

        // Alineación horizontal por tipo de columna (revisión de impresión): texto a la izquierda,
        // números a la derecha, fechas al centro. Los encabezados de tabla siempre van centrados
        // (ver más abajo), independientemente de esta tabla.
        $alineacionPorTipo = [
            'texto' => Alignment::HORIZONTAL_LEFT,
            'entero' => Alignment::HORIZONTAL_RIGHT,
            'moneda' => Alignment::HORIZONTAL_RIGHT,
            'fecha' => Alignment::HORIZONTAL_CENTER,
            'fecha_hora' => Alignment::HORIZONTAL_CENTER,
            'fecha_variable' => Alignment::HORIZONTAL_CENTER,
        ];

        $spreadsheet = new Spreadsheet();
        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Informe');

        // Fuente base de toda la hoja: Arial, para no tener que fijar la familia celda por celda.
        $spreadsheet->getDefaultStyle()->getFont()->setName(REPORTES_FUENTE)->setSize(REPORTES_TAM_DATOS);

        $fila = 1;

        $hoja->setCellValue('A' . $fila, 'Sistema de Gestión de Bienes');
        $hoja->getStyle('A' . $fila)->getFont()->setName(REPORTES_FUENTE)->setBold(true)->setSize(REPORTES_TAM_INSTITUCIONAL);
        $fila++;

        $hoja->setCellValue('A' . $fila, 'Hospital General de Chiquimula');
        $hoja->getStyle('A' . $fila)->getFont()->setName(REPORTES_FUENTE)->setBold(true)->setSize(REPORTES_TAM_INSTITUCIONAL);
        $fila++;

        $hoja->setCellValue('A' . $fila, 'Departamento de Inventarios');
        $hoja->getStyle('A' . $fila)->getFont()->setName(REPORTES_FUENTE)->setSize(REPORTES_TAM_INSTITUCIONAL);
        $fila++;

        // Título INMEDIATAMENTE debajo del bloque institucional, sin fila en blanco — encabezado
        // compacto.
        $hoja->setCellValue('A' . $fila, $titulo);
        $hoja->getStyle('A' . $fila)->getFont()->setName(REPORTES_FUENTE)->setBold(true)->setSize(REPORTES_TAM_TITULO);
        $fila++;

        foreach ($metaFiltros as $linea) {
            $hoja->setCellValueExplicit('A' . $fila, sanitizarTextoExcel($linea), DataType::TYPE_STRING);
            $hoja->getStyle('A' . $fila)->getFont()->setName(REPORTES_FUENTE)->setSize(REPORTES_TAM_META);
            $fila++;
        }

        $hoja->setCellValueExplicit(
            'A' . $fila,
            sanitizarTextoExcel('Generado por: ' . $generadoPor . ' — ' . date('d/m/Y H:i')),
            DataType::TYPE_STRING
        );
        $hoja->getStyle('A' . $fila)->getFont()->setName(REPORTES_FUENTE)->setSize(REPORTES_TAM_META);
        $fila += 2;

        $filaEncabezado = $fila;
        $ultimaColumna = 'A';
        $columnaActual = 'A';

        foreach ($columnas as $columnaDef) {
            $hoja->setCellValue($columnaActual . $filaEncabezado, $columnaDef['titulo']);
            $ultimaColumna = $columnaActual;
            $columnaActual++;
        }

        $rangoEncabezado = 'A' . $filaEncabezado . ':' . $ultimaColumna . $filaEncabezado;
        $hoja->getStyle($rangoEncabezado)->getFont()->setName(REPORTES_FUENTE)->setBold(true)->setSize(REPORTES_TAM_ENCABEZADO_TABLA);
        $hoja->getStyle($rangoEncabezado)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9D9D9');
        $hoja->getStyle($rangoEncabezado)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $hoja->getStyle($rangoEncabezado)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $fila = $filaEncabezado + 1;

        foreach ($filas as $filaDatos) {
            $columnaActual = 'A';

            foreach ($columnas as $columnaDef) {
                $valor = $filaDatos[$columnaDef['clave']] ?? null;
                $celda = $columnaActual . $fila;

                switch ($columnaDef['tipo']) {
                    case 'moneda':
                        $hoja->setCellValue($celda, $valor !== null ? (float) $valor : null);
                        $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('Q #,##0.00');
                        break;

                    case 'entero':
                        $hoja->setCellValue($celda, $valor !== null ? (int) $valor : null);
                        break;

                    case 'fecha':
                        if ($valor !== null && $valor !== '') {
                            $hoja->setCellValue($celda, ExcelDate::PHPToExcel(new DateTime((string) $valor)));
                            $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('DD/MM/YYYY');
                        }
                        break;

                    case 'fecha_hora':
                        if ($valor !== null && $valor !== '') {
                            $hoja->setCellValue($celda, ExcelDate::PHPToExcel(new DateTime((string) $valor)));
                            $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('DD/MM/YYYY HH:mm');
                        }
                        break;

                    case 'fecha_variable':
                        if (is_array($valor) && $valor['valor'] !== null && $valor['valor'] !== '') {
                            $hoja->setCellValue($celda, ExcelDate::PHPToExcel(new DateTime((string) $valor['valor'])));
                            $hoja->getStyle($celda)->getNumberFormat()->setFormatCode(
                                !empty($valor['es_datetime']) ? 'DD/MM/YYYY HH:mm' : 'DD/MM/YYYY'
                            );
                        }
                        break;

                    default: // texto
                        $hoja->setCellValueExplicit($celda, sanitizarTextoExcel($valor !== null ? (string) $valor : ''), DataType::TYPE_STRING);
                }

                $hoja->getStyle($celda)->getFont()->setName(REPORTES_FUENTE)->setSize(REPORTES_TAM_DATOS);
                $hoja->getStyle($celda)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $hoja->getStyle($celda)->getAlignment()
                    ->setHorizontal($alineacionPorTipo[$columnaDef['tipo']] ?? Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_TOP)
                    ->setWrapText(true);

                $columnaActual++;
            }

            $fila++;
        }

        $filaTotales = $fila;
        $hoja->setCellValueExplicit('A' . $filaTotales, 'Total de registros: ' . count($filas), DataType::TYPE_STRING);
        $hoja->getStyle('A' . $filaTotales)->getFont()->setName(REPORTES_FUENTE)->setBold(true)->setSize(REPORTES_TAM_TOTALES);

        if ($totales !== null && !empty($totales['valores'])) {
            $columnaActual = 'A';

            foreach ($columnas as $columnaDef) {
                if (isset($totales['valores'][$columnaDef['clave']])) {
                    $celda = $columnaActual . $filaTotales;
                    $hoja->setCellValue($celda, (float) $totales['valores'][$columnaDef['clave']]);
                    $hoja->getStyle($celda)->getFont()->setName(REPORTES_FUENTE)->setBold(true)->setSize(REPORTES_TAM_TOTALES);
                    $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('Q #,##0.00');
                }

                $columnaActual++;
            }
        }

        $hoja->setAutoFilter($rangoEncabezado);
        $hoja->freezePane('A' . ($filaEncabezado + 1));

        // Anchos FIJOS y compactos por columna (nunca AutoSize): cada definición de columna puede
        // traer 'ancho' explícito; si no, se usa el valor por defecto según su 'tipo'. wrapText(true)
        // ya aplicado arriba permite que el texto largo aumente el ALTO de fila en vez de ensanchar
        // la columna — Excel calcula esa altura automáticamente al abrir el archivo.
        $columnaActual = 'A';
        foreach ($columnas as $columnaDef) {
            $ancho = $columnaDef['ancho'] ?? ($anchoPorDefecto[$columnaDef['tipo']] ?? $anchoPorDefecto['texto']);
            $hoja->getColumnDimension($columnaActual)->setWidth($ancho);
            $columnaActual++;
        }

        // -----------------------------------------------------------------------------------
        // Configuración de impresión (revisión final): Carta/Letter, SIEMPRE horizontal, ajustada
        // a UNA sola página de ANCHO (FitToWidth=1) con alto libre (FitToHeight=0 = tantas páginas
        // de alto como haga falta) — nunca se fuerza también el alto a una página, que es lo que
        // producía la reducción extrema del criterio anterior. Columnas compactas + fuente 7pt en
        // la tabla son las que hacen que esta única página de ancho siga siendo legible.
        // -----------------------------------------------------------------------------------
        $hoja->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LETTER);
        $hoja->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $hoja->getPageSetup()->setFitToPage(true);
        $hoja->getPageSetup()->setFitToWidth(1);
        $hoja->getPageSetup()->setFitToHeight(0);

        // Área de impresión: únicamente encabezado + filtros + tabla + totales — nunca columnas o
        // filas vacías más allá de lo escrito, para que el ancho disponible se use completo.
        $hoja->getPageSetup()->setPrintArea('A1:' . $ultimaColumna . $filaTotales);

        // Repetir la fila de encabezados de la tabla en cada página si el reporte ocupa varias
        // páginas de alto.
        $hoja->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($filaEncabezado, $filaEncabezado);

        // Márgenes compactos (pulgadas) — nunca cero.
        $hoja->getPageMargins()->setLeft(0.3)->setRight(0.3);
        $hoja->getPageMargins()->setTop(0.45)->setBottom(0.45);
        $hoja->getPageMargins()->setHeader(0.2)->setFooter(0.2);

        return $spreadsheet;
    }
}

if (!function_exists('descargarExcelReporte')) {
    function descargarExcelReporte(Spreadsheet $spreadsheet, string $nombreArchivo): void
    {
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
}
