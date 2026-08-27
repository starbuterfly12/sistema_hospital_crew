<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver TarjetasController::ver()).
// Ficha de solo lectura de una tarjeta de responsabilidad ya emitida — mismos datos ($tarjeta /
// $operaciones) y mismo endpoint de descarga (accion=descargar_excel, respuesta XLSX sin layout).
// Sin acciones POST: la emisión es inmutable. Importes con formato Q vía formatearQuetzales().
$tarjeta = $tarjeta ?? [];
$operaciones = $operaciones ?? [];
$idTarjeta = (int) ($tarjeta['id_tarjeta_responsabilidad'] ?? 0);
$estado = $tarjeta['estado_tarjeta'] ?? '';

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};

$claseBadgeEstado = static function (?string $estado): string {
    return match ($estado) {
        'Emitida' => 'badge badge-exito',
        'Anulada' => 'badge badge-error',
        default => 'badge',
    };
};

// Misma regla de composición que RequisicionesController/PrestamosController::
// construirDescripcionCompleta(): descripción + "modelo: X" + "serie: Y" solo cuando esos datos
// existen (detalle_tarjeta_responsabilidad puede no traer modelo/serie en emisiones antiguas).
$descripcionCompleta = static function (array $operacion): string {
    $partes = [trim((string) ($operacion['descripcion_mostrada'] ?? ''))];

    $modelo = trim((string) ($operacion['modelo_mostrado'] ?? ''));
    if ($modelo !== '') {
        $partes[] = 'modelo: ' . $modelo;
    }

    $serie = trim((string) ($operacion['serie_mostrada'] ?? ''));
    if ($serie !== '') {
        $partes[] = 'serie: ' . $serie;
    }

    return implode(', ', array_filter($partes, static fn ($p) => $p !== ''));
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Detalle de tarjeta de responsabilidad</h1>
            <p class="page-subtitle">Consulta de la información registrada de la tarjeta emitida.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=tarjetas" class="btn btn-secondary">Volver</a>
            <a href="index.php?modulo=tarjetas&accion=descargar_excel&id=<?= $idTarjeta ?>" class="btn btn-azul-suave">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>
                Descargar tarjeta
            </a>
        </div>
    </div>
</div>

<div class="detail-identidad">
    <p class="detail-identidad-codigo"><?= $mostrar($tarjeta['numero_tarjeta'] ?? null) ?></p>
    <p class="detail-identidad-descripcion"><?= $mostrar($tarjeta['responsable_nombre'] ?? null) ?></p>
</div>

<div class="detail-card">
    <div class="detail-section">
        <h2 class="form-section-title">Datos generales</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Número de tarjeta</span>
                <span class="detail-value"><?= $mostrar($tarjeta['numero_tarjeta'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="<?= $claseBadgeEstado($estado) ?>"><?= $mostrar($estado ?: null) ?></span></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Responsable</span>
                <span class="detail-value"><?= $mostrar($tarjeta['responsable_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">NIT</span>
                <span class="detail-value"><?= $mostrar($tarjeta['responsable_nit'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Cargo</span>
                <span class="detail-value"><?= $mostrar($tarjeta['responsable_cargo'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ubicación</span>
                <span class="detail-value"><?= $mostrar($tarjeta['ubicacion_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Asignación de referencia</span>
                <span class="detail-value"><?= $mostrar($tarjeta['numero_asignacion'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de emisión</span>
                <span class="detail-value"><?= $mostrar(formatDate($tarjeta['fecha_emision'] ?? null)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Generada por</span>
                <span class="detail-value"><?= $mostrar($tarjeta['usuario_genera_nombre'] ?? null) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de registro</span>
                <span class="detail-value"><?= $mostrar(formatDateTime($tarjeta['created_at'] ?? null)) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h2 class="form-section-title">Operaciones</h2>
        <?php if (empty($operaciones)): ?>
            <p class="estado-vacio">Esta tarjeta no tiene operaciones registradas.</p>
        <?php else: ?>
            <?php $totalDebe = 0; $totalHaber = 0; ?>
            <div class="table-responsive">
                <table class="table-app table-detail-centered table-resizable table-tarjetas-detalle">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Fecha</th>
                            <th>SICOIN</th>
                            <th>Cantidad</th>
                            <th>Descripción</th>
                            <th>Debe</th>
                            <th>Haber</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($operaciones as $operacion): ?>
                            <?php
                                $totalDebe += (float) ($operacion['debe'] ?? 0);
                                $totalHaber += (float) ($operacion['haber'] ?? 0);
                                $codigoMostrado = $operacion['codigo_mostrado'] ?? null;
                                $codigoTexto = ($codigoMostrado !== null && $codigoMostrado !== '')
                                    ? $mostrar($codigoMostrado)
                                    : 'Sin SICOIN';
                            ?>
                            <tr>
                                <td><?= (int) ($operacion['orden_linea'] ?? 0) ?></td>
                                <td><?= $mostrar(formatDate($operacion['fecha_operacion'] ?? null)) ?></td>
                                <td><?= $codigoTexto ?></td>
                                <td><?= (int) ($operacion['cantidad'] ?? 0) ?></td>
                                <td><?= $mostrar($descripcionCompleta($operacion)) ?></td>
                                <td><?= formatearQuetzales($operacion['debe'] ?? null) ?></td>
                                <td><?= formatearQuetzales($operacion['haber'] ?? null) ?></td>
                                <td><?= formatearQuetzales($operacion['saldo_resultante'] ?? null) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5"><strong>Totales</strong></td>
                            <td><strong><?= formatearQuetzales($totalDebe) ?></strong></td>
                            <td><strong><?= formatearQuetzales($totalHaber) ?></strong></td>
                            <td><strong><?= formatearQuetzales(round($totalDebe - $totalHaber, 2)) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= url('public/js/app.js') ?>"></script>
