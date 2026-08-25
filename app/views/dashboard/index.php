<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver DashboardController::index()).
// Los datos reales vienen de DashboardService a través de DashboardController. Si alguna consulta
// falló, el controlador ya deja el valor correspondiente en null / arreglo vacío — aquí solo se
// decide cómo mostrar ese caso, nunca se vuelve a consultar la base de datos desde la vista.

$puedeRegistrar = tieneRol(['Administrador', 'Operativo']);

// Set de íconos SVG inline (trazo, currentColor) solo para esta tarjeta — igual criterio que
// sgbIconoSidebar() en layouts/main.php, pero sin tocar ese archivo (no es de layout global).
if (!function_exists('dashboardIconoAcceso')) {
    function dashboardIconoAcceso(string $clave): string
    {
        $iconos = [
            'bien' => '<path d="M4 7l8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/>',
            'requisicion' => '<path d="M7 3h7l4 4v14H7V3Z"/><path d="M11 17v-6M8 14l3-3 3 3"/>',
            'traslado' => '<path d="M4 7h13l-3-3M20 17H7l3 3"/>',
            'prestamo' => '<path d="M17 3l4 4-4 4"/><path d="M21 7H9"/><path d="M7 21l-4-4 4-4"/><path d="M3 17h12"/>',
            'verificacion' => '<circle cx="10" cy="10" r="6"/><path d="M20 20l-4.35-4.35"/><path d="M7.3 10.2l1.8 1.8L13 8.3"/>',
            'reporte' => '<path d="M5 20V10M11 20V4M17 20v-7"/>',
        ];

        return $iconos[$clave] ?? '';
    }
}

$accesosRapidos = [
    ['etiqueta' => 'Registrar bien', 'href' => 'index.php?modulo=bienes&accion=crear', 'permitido' => $puedeRegistrar, 'icono' => 'bien', 'acento' => 'azul'],
    ['etiqueta' => 'Nueva requisición', 'href' => 'index.php?modulo=requisiciones&accion=crear', 'permitido' => $puedeRegistrar, 'icono' => 'requisicion', 'acento' => 'menta'],
    ['etiqueta' => 'Registrar traslado', 'href' => 'index.php?modulo=traslados&accion=crear', 'permitido' => $puedeRegistrar, 'icono' => 'traslado', 'acento' => 'lila'],
    ['etiqueta' => 'Registrar préstamo', 'href' => 'index.php?modulo=prestamos&accion=crear', 'permitido' => $puedeRegistrar, 'icono' => 'prestamo', 'acento' => 'celeste'],
    ['etiqueta' => 'Verificación física', 'href' => 'index.php?modulo=verificaciones&accion=crear', 'permitido' => $puedeRegistrar, 'icono' => 'verificacion', 'acento' => 'turquesa'],
    ['etiqueta' => 'Generar reporte', 'href' => 'index.php?modulo=reportes', 'permitido' => true, 'icono' => 'reporte', 'acento' => 'institucional'],
];

$indicadores = $indicadores ?? [];
$pendientes = $pendientes ?? [];
$ultimosMovimientos = $ultimosMovimientos ?? [];
$errorMovimientos = $errorMovimientos ?? false;

// null (la consulta falló) y "sin dato real" se muestran igual, como placeholder de presentación —
// nunca se inventa un 0 cuando en realidad no se pudo calcular el valor.
$mostrarNumero = static function ($valor): string {
    return $valor === null ? '—' : (string) (int) $valor;
};

$mostrarCodigoInterno = static function (?string $codigo): string {
    return ($codigo !== null && trim($codigo) !== '') ? htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') : '—';
};

// Mismo criterio de truncado seguro para UTF-8 que ya usa bitacora/index.php: corta con mb_substr y
// escapa DESPUÉS de truncar, para no cortar a mitad de una entidad HTML.
$truncar = static function (?string $texto, int $limite = 45): string {
    $texto = (string) ($texto ?? '');
    if (mb_strlen($texto, 'UTF-8') <= $limite) {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(mb_substr($texto, 0, $limite, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '…';
};
?>
<div class="page-header">
    <h1 class="page-title">Panel principal</h1>
    <p class="page-subtitle">Resumen general del sistema de gestión de bienes.</p>
</div>

<div class="dashboard-indicadores">
    <div class="card">
        <div class="dashboard-indicador-valor"><?= $mostrarNumero($indicadores['bienes_registrados'] ?? null) ?></div>
        <div class="dashboard-indicador-etiqueta">Bienes registrados</div>
    </div>
    <div class="card">
        <div class="dashboard-indicador-valor"><?= $mostrarNumero($indicadores['bienes_activos'] ?? null) ?></div>
        <div class="dashboard-indicador-etiqueta">Bienes activos</div>
    </div>
    <div class="card">
        <div class="dashboard-indicador-valor"><?= $mostrarNumero($indicadores['bienes_baja'] ?? null) ?></div>
        <div class="dashboard-indicador-etiqueta">Bienes de baja</div>
    </div>
    <div class="card">
        <div class="dashboard-indicador-valor"><?= $mostrarNumero($indicadores['prestamos_activos'] ?? null) ?></div>
        <div class="dashboard-indicador-etiqueta">Préstamos activos</div>
    </div>
</div>

<div class="dashboard-pendientes">
    <div class="dashboard-pendiente-card">
        <div class="dashboard-pendiente-valor"><?= $mostrarNumero($pendientes['bajas_pendientes'] ?? null) ?></div>
        <div class="dashboard-pendiente-etiqueta">Bajas pendientes de autorización</div>
    </div>
    <div class="dashboard-pendiente-card">
        <div class="dashboard-pendiente-valor"><?= $mostrarNumero($pendientes['prestamos_vencidos'] ?? null) ?></div>
        <div class="dashboard-pendiente-etiqueta">Préstamos vencidos</div>
    </div>
    <div class="dashboard-pendiente-card">
        <div class="dashboard-pendiente-valor"><?= $mostrarNumero($pendientes['proximos_vencer'] ?? null) ?></div>
        <div class="dashboard-pendiente-etiqueta">Próximos a vencer</div>
    </div>
    <div class="dashboard-pendiente-card">
        <div class="dashboard-pendiente-valor"><?= $mostrarNumero($pendientes['bienes_sin_sicoin'] ?? null) ?></div>
        <div class="dashboard-pendiente-etiqueta">Bienes sin SICOIN</div>
    </div>
</div>

<div class="dashboard-inferior">
    <div class="card">
        <h2 class="dashboard-panel-titulo">Últimos movimientos registrados</h2>
        <?php if ($errorMovimientos): ?>
            <p class="dashboard-vacio">No fue posible cargar los movimientos en este momento.</p>
        <?php elseif (empty($ultimosMovimientos)): ?>
            <p class="dashboard-vacio">No hay movimientos registrados.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-app dashboard-movimientos-tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Código interno</th>
                            <th>Descripción del bien</th>
                            <th>Movimiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosMovimientos as $evento): ?>
                            <tr>
                                <td><?= htmlspecialchars(formatDate($evento['fecha']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $mostrarCodigoInterno($evento['codigo_interno']) ?></td>
                                <td><?= $truncar($evento['descripcion']) ?></td>
                                <td><?= htmlspecialchars($evento['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($evento['estado'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="dashboard-panel-titulo">Accesos rápidos</h2>
        <div class="dashboard-accesos">
            <?php foreach ($accesosRapidos as $acceso): ?>
                <?php if (!$acceso['permitido']) : continue; endif; ?>
                <a
                    class="dashboard-acceso-link dashboard-acceso-<?= htmlspecialchars($acceso['acento'], ENT_QUOTES, 'UTF-8') ?>"
                    href="<?= htmlspecialchars($acceso['href'], ENT_QUOTES, 'UTF-8') ?>"
                >
                    <span class="dashboard-acceso-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= dashboardIconoAcceso($acceso['icono']) ?></svg>
                    </span>
                    <span class="dashboard-acceso-texto"><?= htmlspecialchars($acceso['etiqueta'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
