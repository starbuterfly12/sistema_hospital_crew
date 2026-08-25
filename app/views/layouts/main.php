<?php

if (!function_exists('sgbModuloActivo')) {
    // Normaliza el modulo real de la URL al item de sidebar que debe resaltarse.
    // Los procesos que hoy viven como modulos propios (traslados, prestamos, devoluciones,
    // bajas, verificaciones) se accederan desde "Movimientos" cuando se migren visualmente;
    // se dejan mapeados desde ya aunque esas vistas todavia no usen este layout.
    function sgbModuloActivo(string $moduloUrl): string
    {
        $gruposMovimientos = ['movimientos', 'traslados', 'prestamos', 'devoluciones', 'bajas', 'verificaciones'];

        if (in_array($moduloUrl, $gruposMovimientos, true)) {
            return 'movimientos';
        }

        return $moduloUrl;
    }
}

if (!function_exists('sgbIconoSidebar')) {
    // Set minimo de iconos SVG inline (trazo, currentColor) para no depender de una libreria externa.
    function sgbIconoSidebar(string $clave): string
    {
        $iconos = [
            'dashboard' => '<path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z"/>',
            'bienes' => '<path d="M4 7l8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/>',
            'requisiciones' => '<path d="M7 3h8l4 4v14H5V3h2Z"/><path d="M9 8h6M9 12h6M9 16h4"/>',
            'responsables' => '<circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/>',
            'ubicaciones' => '<path d="M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/>',
            'asignaciones' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9.5l2 2 4-4.2"/>',
            'movimientos' => '<path d="M4 7h13l-3-3M20 17H7l3 3"/>',
            'tarjetas' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
            'reportes' => '<path d="M5 20V10M11 20V4M17 20v-7"/>',
            'usuarios' => '<circle cx="9" cy="8" r="3"/><path d="M2.5 20c0-3.6 2.9-6.5 6.5-6.5s6.5 2.9 6.5 6.5"/><circle cx="17.5" cy="8.5" r="2.4"/><path d="M16 13.6c2.5.5 4.5 2.7 4.5 5.4"/>',
            'bitacora' => '<path d="M6 3h9l4 4v14H6V3Z"/><path d="M9 9h7M9 13h7M9 17h4"/>',
            'respaldos' => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v12c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/><path d="M5 12c0 1.7 3.1 3 7 3s7-1.3 7-3"/>',
            'logout' => '<path d="M9 4H5v16h4"/><path d="M13 8l4 4-4 4M17 12H9"/>',
            'campana' => '<path d="M6 9a6 6 0 0 1 12 0v5l1.5 3h-15L6 14V9Z"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        ];

        return $iconos[$clave] ?? '';
    }
}

$tituloPagina = trim((string) ($tituloPagina ?? ''));
if ($tituloPagina === '') {
    $tituloPagina = 'Sistema de Gestión de Bienes';
}

$nombreCompletoSesion = (string) ($_SESSION['nombre_completo'] ?? '');
$nombreRolSesion = (string) ($_SESSION['nombre_rol'] ?? '');
$inicialAvatar = mb_strtoupper(mb_substr(trim($nombreCompletoSesion) !== '' ? trim($nombreCompletoSesion) : '?', 0, 1, 'UTF-8'), 'UTF-8');

$moduloUrlActual = strtolower((string) ($_GET['modulo'] ?? ''));
$moduloActivo = sgbModuloActivo($moduloUrlActual);

// Estructura preparada para public/img/logo-institucional.{svg,png}: mientras ese archivo
// institucional no exista, el sidebar usa un bloque de texto discreto que no aparenta ser el logo final.
$logoInstitucionalUrl = null;
foreach (['svg', 'png'] as $extensionLogo) {
    if (is_file(__DIR__ . '/../../../public/img/logo-institucional.' . $extensionLogo)) {
        $logoInstitucionalUrl = 'public/img/logo-institucional.' . $extensionLogo;
        break;
    }
}

$itemsSidebar = [
    ['id' => 'dashboard', 'etiqueta' => 'Panel principal', 'href' => 'index.php?modulo=dashboard', 'icono' => 'dashboard', 'roles' => null],
    ['id' => 'bienes', 'etiqueta' => 'Bienes institucionales', 'href' => 'index.php?modulo=bienes', 'icono' => 'bienes', 'roles' => null],
    ['id' => 'requisiciones', 'etiqueta' => 'Requisiciones', 'href' => 'index.php?modulo=requisiciones', 'icono' => 'requisiciones', 'roles' => null],
    ['id' => 'responsables', 'etiqueta' => 'Responsables', 'href' => 'index.php?modulo=responsables', 'icono' => 'responsables', 'roles' => null],
    ['id' => 'ubicaciones', 'etiqueta' => 'Áreas / ubicaciones', 'href' => 'index.php?modulo=ubicaciones', 'icono' => 'ubicaciones', 'roles' => null],
    ['id' => 'asignaciones', 'etiqueta' => 'Asignaciones', 'href' => 'index.php?modulo=asignaciones', 'icono' => 'asignaciones', 'roles' => null],
    ['id' => 'movimientos', 'etiqueta' => 'Movimientos', 'href' => 'index.php?modulo=movimientos', 'icono' => 'movimientos', 'roles' => null],
    ['id' => 'tarjetas', 'etiqueta' => 'Tarjeta de responsabilidad', 'href' => 'index.php?modulo=tarjetas', 'icono' => 'tarjetas', 'roles' => null],
    ['id' => 'reportes', 'etiqueta' => 'Reportes', 'href' => 'index.php?modulo=reportes', 'icono' => 'reportes', 'roles' => null],
    ['id' => 'usuarios', 'etiqueta' => 'Usuarios', 'href' => 'index.php?modulo=usuarios', 'icono' => 'usuarios', 'roles' => ['Administrador']],
    ['id' => 'bitacora', 'etiqueta' => 'Bitácora', 'href' => 'index.php?modulo=bitacora', 'icono' => 'bitacora', 'roles' => ['Administrador']],
    ['id' => 'respaldos', 'etiqueta' => 'Respaldos', 'href' => 'index.php?modulo=respaldos', 'icono' => 'respaldos', 'roles' => ['Administrador']],
];
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= url('public/css/app.css') ?>">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <?php if ($logoInstitucionalUrl !== null): ?>
                    <img src="<?= htmlspecialchars(url($logoInstitucionalUrl), ENT_QUOTES, 'UTF-8') ?>" alt="Logo institucional" class="sidebar-brand-logo">
                <?php else: ?>
                    <span class="sidebar-brand-placeholder" aria-hidden="true">SGB</span>
                <?php endif; ?>
            </div>

            <nav class="sidebar-nav">
                <?php foreach ($itemsSidebar as $item): ?>
                    <?php if ($item['roles'] !== null && !tieneRol($item['roles'])) : continue; endif; ?>
                    <a
                        href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"
                        class="sidebar-link<?= $moduloActivo === $item['id'] ? ' sidebar-link-activo' : '' ?>"
                    >
                        <span class="sidebar-link-icono" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= sgbIconoSidebar($item['icono']) ?></svg>
                        </span>
                        <span class="sidebar-link-texto"><?= htmlspecialchars($item['etiqueta'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="index.php?modulo=auth&accion=logout" class="sidebar-link sidebar-link-logout">
                    <span class="sidebar-link-icono" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= sgbIconoSidebar('logout') ?></svg>
                    </span>
                    <span class="sidebar-link-texto">Cerrar sesión</span>
                </a>
            </div>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <div class="topbar-saludo">
                    <span class="topbar-saludo-linea">Bienvenido,</span>
                    <span class="topbar-saludo-nombre"><?= htmlspecialchars($nombreCompletoSesion !== '' ? $nombreCompletoSesion : $nombreRolSesion, ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <div class="topbar-acciones">
                    <span class="topbar-campana" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= sgbIconoSidebar('campana') ?></svg>
                    </span>
                    <span class="topbar-avatar" title="<?= htmlspecialchars($nombreCompletoSesion, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($inicialAvatar, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </header>

            <main class="app-content">
                <div class="fondo-decorativo" aria-hidden="true"></div>
                <div class="app-content-inner">
                    <?= $content ?? '' ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
