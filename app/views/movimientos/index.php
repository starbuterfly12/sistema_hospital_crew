<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver MovimientosController::index()).
// Panel/hub de navegación del módulo Movimientos. Solo enlaces a rutas reales existentes — sin tablas,
// historial, estadísticas ni filtros. El único dato dinámico es el contador de solicitudes de baja
// pendientes, que ya venía en la vista anterior y solo se calcula/ muestra para Administrador.
$totalBajasPendientes = (int) ($totalBajasPendientes ?? 0);
$esAdministrador = tieneRol(['Administrador']);

$tarjetas = [
    [
        'href' => 'index.php?modulo=traslados',
        'acento' => 'azul',
        'titulo' => 'Traslados',
        'texto' => 'Gestione el traslado de bienes entre responsables y ubicaciones.',
        'icono' => '<path d="M4 7h13l-3-3M20 17H7l3 3"/>',
    ],
    [
        'href' => 'index.php?modulo=prestamos',
        'acento' => 'lila',
        'titulo' => 'Préstamos',
        'texto' => 'Registre préstamos temporales de bienes institucionales.',
        'icono' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ],
    [
        'href' => 'index.php?modulo=devoluciones',
        'acento' => 'menta',
        'titulo' => 'Devoluciones',
        'texto' => 'Registre devoluciones parciales o totales de préstamos.',
        'icono' => '<path d="M9 14L4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 5 5v1"/>',
    ],
    [
        'href' => 'index.php?modulo=bajas',
        'acento' => 'rosa',
        'titulo' => 'Bajas',
        'texto' => 'Consulte y gestione los procesos de baja de bienes.',
        'icono' => '<path d="M3 7l1.6-3h14.8L21 7"/><rect x="3" y="7" width="18" height="13" rx="1"/><path d="M10 12h4"/>',
    ],
    [
        'href' => 'index.php?modulo=verificaciones',
        'acento' => 'turquesa',
        'titulo' => 'Verificación física',
        'texto' => 'Registre y consulte verificaciones físicas de bienes.',
        'icono' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M8.4 11l2 2 3.4-3.4"/>',
    ],
];

if ($esAdministrador) {
    $tarjetas[] = [
        'href' => 'index.php?modulo=bajas&accion=solicitudes',
        'acento' => 'ambar',
        'titulo' => 'Solicitudes de baja',
        'texto' => 'Consulte y gestione las solicitudes de baja pendientes y procesadas.',
        'icono' => '<path d="M7 3h8l4 4v14H5V3h2Z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        'contador' => $totalBajasPendientes,
    ];
}
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Movimientos</h1>
            <p class="page-subtitle">Gestión de los movimientos y procesos relacionados con los bienes institucionales.</p>
        </div>
    </div>
</div>

<div class="module-grid">
    <?php foreach ($tarjetas as $tarjeta): ?>
        <a class="module-card module-card-<?= htmlspecialchars($tarjeta['acento'], ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($tarjeta['href'], ENT_QUOTES, 'UTF-8') ?>">
            <span class="module-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= $tarjeta['icono'] ?></svg>
            </span>

            <h2 class="module-card-title">
                <?= htmlspecialchars($tarjeta['titulo'], ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($tarjeta['contador']) && (int) $tarjeta['contador'] > 0): ?>
                    <span class="badge badge-pendiente"><?= (int) $tarjeta['contador'] ?> <?= ((int) $tarjeta['contador'] === 1) ? 'pendiente' : 'pendientes' ?></span>
                <?php endif; ?>
            </h2>

            <p class="module-card-text"><?= htmlspecialchars($tarjeta['texto'], ENT_QUOTES, 'UTF-8') ?></p>

            <span class="module-card-arrow" aria-hidden="true">
                Abrir
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </span>
        </a>
    <?php endforeach; ?>
</div>
