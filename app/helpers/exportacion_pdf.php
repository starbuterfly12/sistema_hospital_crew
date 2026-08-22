<?php

use Dompdf\Dompdf;
use Dompdf\Options;

// Helper de exportación PDF común para el módulo Reportes — hermano de exportacion.php (Excel), NO
// se usa en ningún otro módulo. Centraliza configuración de Dompdf, HTML base, CSS común, encabezado
// institucional, filtros, tabla y totales, para que los 7 reportes NO tengan 7 implementaciones
// completas independientes: cada exportarXPdf() en ReportesController solo arma columnas/meta y
// llama a construirPdfReporte() + descargarPdfReporte(), igual que ya hacen con el Excel aprobado.
//
// Fuente: se usa DejaVu Sans (no Arial). Dompdf no distribuye Arial de forma portable — solo la
// resuelve mediante un alias interno a la fuente núcleo Helvetica (no embebida, métricas menos
// confiables en Dompdf). DejaVu Sans SÍ viene embebida con el paquete (vendor/dompdf/dompdf/lib/fonts)
// y soporta acentos/ñ sin depender de archivos externos — es la alternativa portable que exige la
// revisión funcional cuando Arial no está garantizada.

if (!defined('REPORTES_PDF_FUENTE')) {
    define('REPORTES_PDF_FUENTE', 'DejaVu Sans');
    define('REPORTES_PDF_TAM_INSTITUCIONAL', 9);
    define('REPORTES_PDF_TAM_TITULO', 12);
    define('REPORTES_PDF_TAM_META', 8);
    define('REPORTES_PDF_TAM_ENCABEZADO_TABLA', 7);
    define('REPORTES_PDF_TAM_DATOS', 7);
    define('REPORTES_PDF_TAM_TOTALES', 8);
}

if (!function_exists('escaparHtmlPdf')) {
    // Equivalente a e() para el helper PDF, que trabaja fuera de las vistas: nunca debe insertarse
    // HTML crudo proveniente de BD/filtros (texto libre: descripción, observaciones, procedencia...).
    function escaparHtmlPdf($valor): string
    {
        return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatMonedaPdf')) {
    // Misma fuente monetaria aprobada (Q #,##0.00) que el Excel, en texto plano para HTML/PDF.
    function formatMonedaPdf($valor): string
    {
        if ($valor === null || $valor === '') {
            return '-';
        }

        return 'Q ' . number_format((float) $valor, 2);
    }
}

if (!function_exists('nombreArchivoReportePdf')) {
    // Misma convención de nombre que nombreArchivoReporte() (Excel), con extensión .pdf. Se duplica
    // aquí en vez de tocar exportacion.php — helper Excel aprobado, no se modifica.
    function nombreArchivoReportePdf(string $base, ?string $fechaDesde = null, ?string $fechaHasta = null): string
    {
        $nombreBase = 'Reporte_' . preg_replace('/[^A-Za-z0-9]/', '', $base);

        if ($fechaDesde !== null && $fechaHasta !== null && isValidIsoDate($fechaDesde) && isValidIsoDate($fechaHasta)) {
            $desde = DateTime::createFromFormat('!Y-m-d', $fechaDesde)->format('d-m-Y');
            $hasta = DateTime::createFromFormat('!Y-m-d', $fechaHasta)->format('d-m-Y');

            return $nombreBase . '_' . $desde . '_al_' . $hasta . '.pdf';
        }

        return $nombreBase . '_Generado_' . date('d-m-Y') . '.pdf';
    }
}

if (!function_exists('reportesPdfEstilos')) {
    function reportesPdfEstilos(): string
    {
        $fuente = REPORTES_PDF_FUENTE;

        return '
            @page { margin: 26px 22px; }
            * { box-sizing: border-box; }
            body { font-family: \'' . $fuente . '\', sans-serif; font-size: ' . REPORTES_PDF_TAM_DATOS . 'pt; color: #111111; margin: 0; padding: 0; }
            p { margin: 0 0 2px 0; }
            .institucional { font-size: ' . REPORTES_PDF_TAM_INSTITUCIONAL . 'pt; font-weight: bold; }
            .institucional.dept { font-weight: normal; }
            .titulo-reporte { font-size: ' . REPORTES_PDF_TAM_TITULO . 'pt; font-weight: bold; margin-top: 4px; }
            .meta-filtros { font-size: ' . REPORTES_PDF_TAM_META . 'pt; }
            .generado { font-size: ' . REPORTES_PDF_TAM_META . 'pt; margin-bottom: 6px; }
            table.tabla-reporte { table-layout: fixed; width: 100%; border-collapse: collapse; margin-top: 4px; }
            table.tabla-reporte th, table.tabla-reporte td {
                border: 0.5pt solid #666666;
                padding: 2px 3px;
                vertical-align: top;
                word-wrap: break-word;
                overflow-wrap: break-word;
                font-size: ' . REPORTES_PDF_TAM_DATOS . 'pt;
            }
            table.tabla-reporte thead th {
                font-weight: bold;
                background-color: #d9d9d9;
                text-align: center;
                font-size: ' . REPORTES_PDF_TAM_ENCABEZADO_TABLA . 'pt;
            }
            table.tabla-reporte tr { page-break-inside: avoid; }
            .col-texto { text-align: left; }
            .col-entero, .col-moneda { text-align: right; }
            .col-fecha, .col-fecha_hora, .col-fecha_variable { text-align: center; }
            .mensaje-vacio { font-size: ' . REPORTES_PDF_TAM_META . 'pt; margin-top: 8px; }
            .resumen-totales { font-size: ' . REPORTES_PDF_TAM_TOTALES . 'pt; font-weight: bold; margin-top: 4px; }
        ';
    }
}

if (!function_exists('reportesPdfFormatearValor')) {
    // Mismas reglas de fecha aprobadas (formatDate/formatDateTime/formatFechaSegunTipo, de
    // app/helpers/fecha.php): DATE -> DD/MM/AAAA, DATETIME real -> DD/MM/AAAA HH:mm, sin "00:00"
    // artificial. 'fecha_variable' espera ['valor'=>string, 'es_datetime'=>bool], igual que en Excel.
    function reportesPdfFormatearValor($valor, string $tipo): string
    {
        switch ($tipo) {
            case 'moneda':
                return escaparHtmlPdf(formatMonedaPdf($valor));

            case 'entero':
                return $valor !== null ? (string) (int) $valor : '-';

            case 'fecha':
                return $valor !== null && $valor !== '' ? escaparHtmlPdf(formatDate((string) $valor)) : '-';

            case 'fecha_hora':
                return $valor !== null && $valor !== '' ? escaparHtmlPdf(formatDateTime((string) $valor)) : '-';

            case 'fecha_variable':
                if (is_array($valor) && ($valor['valor'] ?? null) !== null && $valor['valor'] !== '') {
                    return escaparHtmlPdf(formatFechaSegunTipo((string) $valor['valor'], !empty($valor['es_datetime'])));
                }

                return '-';

            default: // texto
                return $valor !== null && $valor !== '' ? escaparHtmlPdf((string) $valor) : '-';
        }
    }
}

if (!function_exists('construirPdfReporte')) {
    /**
     * Construye un Dompdf con el mismo formato administrativo uniforme de Reportes que el Excel:
     * encabezado institucional, título, período/filtros aplicados, generado por, tabla con
     * encabezados repetidos (<thead>) + totales. SIEMPRE Carta/Letter horizontal, una sola página de
     * ancho (ancho de columnas en % fijo, nunca automático), alto libre.
     *
     * $columnas: lista de ['clave'=>string,'titulo'=>string,'tipo'=>'texto'|'entero'|'moneda'|'fecha'|'fecha_hora'|'fecha_variable','ancho'=>int en % — la suma de todas debe dar 100]
     * $filas: lista de arrays asociativos, cada uno indexado por 'clave' de columna
     * $metaFiltros: líneas de texto ya formateadas (ej. "Período: 01/08/2026 al 31/08/2026", "Tipo: Traslado")
     * $totales: null, o ['valores' => [clave_columna => suma_numerica, ...]] — únicamente para reportes
     *   con total monetario aprobado (Bajas/Ingresos); se muestra como línea aparte bajo la tabla.
     */
    function construirPdfReporte(
        string $titulo,
        array $columnas,
        array $filas,
        array $metaFiltros,
        string $generadoPor,
        ?array $totales = null
    ): Dompdf {
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . reportesPdfEstilos() . '</style></head><body>';

        $html .= '<p class="institucional">Sistema de Gestión de Bienes</p>';
        $html .= '<p class="institucional">Hospital General de Chiquimula</p>';
        $html .= '<p class="institucional dept">Departamento de Inventarios</p>';
        $html .= '<p class="titulo-reporte">' . escaparHtmlPdf($titulo) . '</p>';

        foreach ($metaFiltros as $linea) {
            $html .= '<p class="meta-filtros">' . escaparHtmlPdf($linea) . '</p>';
        }

        $html .= '<p class="generado">Generado por: ' . escaparHtmlPdf($generadoPor) . ' — ' . date('d/m/Y H:i') . '</p>';

        if (empty($filas)) {
            $html .= '<p class="mensaje-vacio">No se encontraron registros para los criterios seleccionados.</p>';
        } else {
            $html .= '<table class="tabla-reporte"><colgroup>';
            $anchoPorDefecto = round(100 / count($columnas), 2);
            foreach ($columnas as $columnaDef) {
                $ancho = $columnaDef['ancho'] ?? $anchoPorDefecto;
                $html .= '<col style="width:' . $ancho . '%;">';
            }
            $html .= '</colgroup><thead><tr>';

            foreach ($columnas as $columnaDef) {
                $html .= '<th>' . escaparHtmlPdf($columnaDef['titulo']) . '</th>';
            }

            $html .= '</tr></thead><tbody>';

            foreach ($filas as $filaDatos) {
                $html .= '<tr>';

                foreach ($columnas as $columnaDef) {
                    $valor = $filaDatos[$columnaDef['clave']] ?? null;
                    $texto = reportesPdfFormatearValor($valor, $columnaDef['tipo']);
                    $html .= '<td class="col-' . $columnaDef['tipo'] . '">' . $texto . '</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '<p class="resumen-totales">Total de registros: ' . count($filas) . '</p>';

        if ($totales !== null && !empty($totales['valores'])) {
            $titulosPorClave = [];
            foreach ($columnas as $columnaDef) {
                $titulosPorClave[$columnaDef['clave']] = $columnaDef['titulo'];
            }

            foreach ($totales['valores'] as $clave => $suma) {
                $etiqueta = $titulosPorClave[$clave] ?? $clave;
                $html .= '<p class="resumen-totales">' . escaparHtmlPdf($etiqueta) . ' (total): ' . formatMonedaPdf($suma) . '</p>';
            }
        }

        $html .= '</body></html>';

        $options = new Options();
        $options->setDefaultFont(REPORTES_PDF_FUENTE);
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        return $dompdf;
    }
}

if (!function_exists('descargarPdfReporte')) {
    function descargarPdfReporte(Dompdf $dompdf, string $nombreArchivo): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($nombreArchivo, ['Attachment' => true]);
        exit;
    }
}
