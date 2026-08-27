<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver RespaldosController::index()).
// Módulo exclusivo de Administrador. Alcance real intacto: generar (POST+CSRF) + listar + descargar.
// No hay restaurar/eliminar. La confirmación de "Generar respaldo" usa el #modal-confirm global
// (antes window.confirm()); el feedback lo muestra #modal-feedback del layout a partir del flash del
// controlador. La descarga conserva su endpoint y comportamiento (stream de archivo, sin layout).
$mostrar = static function ($value): string {
    return ($value !== null && trim((string) $value) !== '') ? htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') : '—';
};

$respaldos = $respaldos ?? [];

$mostrarGeneradoPor = static function (array $fila): string {
    if (($fila['nombre_completo'] ?? null) !== null && ($fila['usuario'] ?? null) !== null) {
        return htmlspecialchars($fila['nombre_completo'], ENT_QUOTES, 'UTF-8')
            . ' (' . htmlspecialchars($fila['usuario'], ENT_QUOTES, 'UTF-8') . ')';
    }
    return '—';
};

// El archivo puede haber sido borrado manualmente del disco sin tocar la BD — en ese caso se
// muestra "No disponible" en vez de un warning de filesize() sobre un archivo inexistente.
$mostrarTamano = static function (array $fila): string {
    $rutaFisica = resolverRutaFisicaRespaldo($fila['ruta_archivo']);
    if ($rutaFisica === null || !is_file($rutaFisica)) {
        return 'No disponible';
    }
    $tamano = filesize($rutaFisica);
    return $tamano === false ? 'No disponible' : formatearTamanoArchivo($tamano);
};

$mostrarEstado = static function (string $estado): string {
    return $estado === 'generado' ? 'Generado' : htmlspecialchars($estado, ENT_QUOTES, 'UTF-8');
};

$svgDescarga = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 19h16"/></svg>';
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Respaldos</h1>
            <p class="page-subtitle">Generación y descarga de respaldos de la base de datos.</p>
        </div>

        <div class="page-actions">
            <button type="button" class="btn btn-primary"
                data-confirm
                data-confirm-form="form-generar-respaldo"
                data-confirm-icon="doc" data-confirm-variant="azul"
                data-confirm-title="Confirmar generación de respaldo"
                data-confirm-text="Se generará un nuevo respaldo de la base de datos."
                data-confirm-subtext="¿Desea generar el respaldo?"
                data-confirm-ok="Generar respaldo"
                data-confirm-btnclass="btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15l3 3 3-3M12 12v6"/></svg>
                Generar respaldo
            </button>
        </div>
    </div>
</div>

<form method="POST" action="index.php?modulo=respaldos&accion=generar" id="form-generar-respaldo" hidden>
    <?= csrfField() ?>
    <button type="submit" tabindex="-1" aria-hidden="true">Generar respaldo</button>
</form>

<div class="card">
    <?php if (empty($respaldos)): ?>
        <p class="estado-vacio">No hay respaldos disponibles.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-app table-detail-centered table-respaldos">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Archivo</th>
                        <th>Generado por</th>
                        <th>Tamaño</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($respaldos as $respaldo): ?>
                        <tr>
                            <td><?= $mostrar(formatDateTimeSeconds($respaldo['fecha_generacion'] ?? null)) ?></td>
                            <td><?= $mostrar($respaldo['nombre_archivo'] ?? null) ?></td>
                            <td><?= $mostrarGeneradoPor($respaldo) ?></td>
                            <td><?= $mostrarTamano($respaldo) ?></td>
                            <td><?= $mostrarEstado((string) ($respaldo['estado_respaldo'] ?? '')) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a class="table-action-btn table-action-ver" href="index.php?modulo=respaldos&accion=descargar&id=<?= (int) $respaldo['id_respaldo'] ?>">
                                        <?= $svgDescarga ?>
                                        Descargar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
