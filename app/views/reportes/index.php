<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver ReportesController::index()).
// Hub del módulo Reportes — solo enlaces a los 7 reportes reales existentes. Módulo de solo consulta:
// los tres roles pueden ver y exportar todos los reportes (no hay requireRole en el controlador).
$reportes = [
    [
        'href' => 'index.php?modulo=reportes&accion=movimientos',
        'acento' => 'azul',
        'titulo' => 'Movimientos por período',
        'texto' => 'Traslados, préstamos, devoluciones, requisiciones, bajas y verificaciones en una línea de tiempo.',
        'icono' => '<path d="M4 7h13l-3-3M20 17H7l3 3"/>',
    ],
    [
        'href' => 'index.php?modulo=reportes&accion=bienesActividad',
        'acento' => 'turquesa',
        'titulo' => 'Bienes con actividad',
        'texto' => 'Bienes con al menos un evento en el período: cantidad, primer y último evento.',
        'icono' => '<path d="M4 7l8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/>',
    ],
    [
        'href' => 'index.php?modulo=reportes&accion=prestamos',
        'acento' => 'lila',
        'titulo' => 'Préstamos pendientes o vencidos',
        'texto' => 'Préstamos activos o parcialmente devueltos, con días de vencimiento cuando aplica.',
        'icono' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ],
    [
        'href' => 'index.php?modulo=reportes&accion=bajas',
        'acento' => 'rosa',
        'titulo' => 'Bajas por período',
        'texto' => 'Bajas finalizadas u otros estados, con su tipo, valor y bodega destino.',
        'icono' => '<path d="M3 7l1.6-3h14.8L21 7"/><rect x="3" y="7" width="18" height="13" rx="1"/><path d="M10 12h4"/>',
    ],
    [
        'href' => 'index.php?modulo=reportes&accion=verificaciones',
        'acento' => 'menta',
        'titulo' => 'Verificaciones con diferencias',
        'texto' => 'Verificaciones físicas registradas, con los datos congelados al momento de verificar.',
        'icono' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M8.4 11l2 2 3.4-3.4"/>',
    ],
    [
        'href' => 'index.php?modulo=reportes&accion=ingresos',
        'acento' => 'ambar',
        'titulo' => 'Ingresos de bienes',
        'texto' => 'Bienes ingresados en el período, con su forma de ingreso y procedencia/proveedor.',
        'icono' => '<path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 21h16"/>',
    ],
    [
        'href' => 'index.php?modulo=reportes&accion=resumen',
        'acento' => 'azul',
        'titulo' => 'Resumen de movimientos',
        'texto' => 'Cantidad de operaciones y de bienes involucrados por tipo, dentro de un período.',
        'icono' => '<path d="M5 20V10M11 20V4M17 20v-7"/>',
    ],
];
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Informes</h1>
            <p class="page-subtitle">Consulta y generación de informes del sistema.</p>
        </div>
    </div>
</div>

<div class="module-grid">
    <?php foreach ($reportes as $reporte): ?>
        <a class="module-card module-card-<?= htmlspecialchars($reporte['acento'], ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($reporte['href'], ENT_QUOTES, 'UTF-8') ?>">
            <span class="module-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $reporte['icono'] ?></svg>
            </span>

            <h2 class="module-card-title"><?= htmlspecialchars($reporte['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>

            <p class="module-card-text"><?= htmlspecialchars($reporte['texto'], ENT_QUOTES, 'UTF-8') ?></p>

            <span class="module-card-arrow" aria-hidden="true">
                Abrir
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </span>
        </a>
    <?php endforeach; ?>
</div>
