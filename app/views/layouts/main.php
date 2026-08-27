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

// Mientras ese archivo institucional no exista, el sidebar usa un bloque de texto discreto que no
// aparenta ser el logo final. logoInstitucionalUrl() (app/helpers/url.php) ya incluye cache-busting
// por filemtime(), así que $logoInstitucionalUrl aquí es una URL completa, no una ruta relativa.
$logoInstitucionalUrl = logoInstitucionalUrl();

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
    <link rel="stylesheet" href="<?= assetUrl('public/css/app.css') ?>">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <?php if ($logoInstitucionalUrl !== null): ?>
                    <img src="<?= htmlspecialchars($logoInstitucionalUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo institucional" class="sidebar-brand-logo">
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

    <?php
        // Feedback global (POST → redirect → GET): un ÚNICO modal reutilizable para todo el sistema.
        // getFlash() consume el mensaje una sola vez; al recargar ya no vuelve a aparecer.
        $sgbFlash = function_exists('getFlash') ? getFlash() : null;
    ?>
    <?php if ($sgbFlash !== null): ?>
        <?php
            $sgbFlashTipo = in_array($sgbFlash['tipo'], ['success', 'error', 'warning', 'info'], true) ? $sgbFlash['tipo'] : 'info';
            $sgbFlashIconos = [
                'success' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
                'error'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>',
                'warning' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                'info'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            ];
        ?>
        <div id="modal-feedback" class="modal-overlay modal-abierto" data-modal-feedback>
            <div class="modal-caja modal-feedback" role="dialog" aria-modal="true" aria-labelledby="modal-feedback-titulo">
                <div class="modal-feedback-icono modal-feedback-icono--<?= $sgbFlashTipo ?>" aria-hidden="true"><?= $sgbFlashIconos[$sgbFlashTipo] ?></div>
                <h2 id="modal-feedback-titulo" class="modal-feedback-titulo"><?= htmlspecialchars($sgbFlash['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if (trim($sgbFlash['mensaje']) !== ''): ?>
                    <p class="modal-feedback-mensaje"><?= htmlspecialchars($sgbFlash['mensaje'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="modal-feedback-actions">
                    <button type="button" class="btn btn-primary" data-cerrar-modal-feedback>Aceptar</button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('modal-feedback');
                if (!modal) { return; }

                var btnAceptar = modal.querySelector('[data-cerrar-modal-feedback]');

                function cerrar() {
                    modal.classList.remove('modal-abierto');
                }

                if (btnAceptar) {
                    btnAceptar.addEventListener('click', cerrar);
                    try { btnAceptar.focus(); } catch (e) {}
                }

                modal.addEventListener('click', function (evento) {
                    if (evento.target === modal) { cerrar(); }
                });

                document.addEventListener('keydown', function (evento) {
                    if (evento.key === 'Escape' && modal.classList.contains('modal-abierto')) {
                        cerrar();
                    }
                });
            })();
        </script>
    <?php endif; ?>

    <?php
        // Modal de CONFIRMACIÓN global y reutilizable (previo a una acción sensible). Distinto del
        // modal de FEEDBACK de arriba (resultado posterior). Se controla por atributos data-confirm-*
        // en el botón disparador, que debe apuntar a un <form> real (normalmente oculto) mediante
        // data-confirm-form. Soporta un paso previo opcional con textarea (data-confirm-input) y una
        // segunda pantalla de confirmación (data-confirm-step2-*). Nunca ejecuta el POST hasta que la
        // persona pulsa el botón final del modal.
    ?>
    <div id="modal-confirm" class="modal-overlay" data-modal-confirm>
        <div class="modal-caja modal-confirm" role="dialog" aria-modal="true" aria-labelledby="modal-confirm-titulo">
            <div id="modal-confirm-icono" class="modal-confirm-icono modal-confirm-icono--azul" aria-hidden="true"></div>
            <h2 id="modal-confirm-titulo" class="modal-confirm-titulo">Confirmar</h2>
            <p id="modal-confirm-texto" class="modal-confirm-texto"></p>
            <div id="modal-confirm-campo" class="modal-confirm-campo" hidden>
                <label id="modal-confirm-campo-label" class="form-label" for="modal-confirm-textarea"></label>
                <textarea id="modal-confirm-textarea" class="form-control" rows="3"></textarea>
            </div>
            <p id="modal-confirm-subtexto" class="modal-confirm-sub" hidden></p>
            <div class="modal-confirm-acciones">
                <button type="button" class="btn btn-secondary" data-modal-confirm-cancel>Cancelar</button>
                <button type="button" class="btn btn-primary" id="modal-confirm-ok">Continuar</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var overlay = document.getElementById('modal-confirm');
            if (!overlay) { return; }

            var elIcono  = document.getElementById('modal-confirm-icono');
            var elTitulo = document.getElementById('modal-confirm-titulo');
            var elTexto  = document.getElementById('modal-confirm-texto');
            var elSub    = document.getElementById('modal-confirm-subtexto');
            var wrapCampo = document.getElementById('modal-confirm-campo');
            var lblCampo  = document.getElementById('modal-confirm-campo-label');
            var txtCampo  = document.getElementById('modal-confirm-textarea');
            var btnOk     = document.getElementById('modal-confirm-ok');

            var ICONOS = {
                check:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>',
                alerta: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
                doc:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>'
            };

            var trigger = null;
            var paso = 1;
            var valores = {};   // targetId -> texto guardado (persiste entre aperturas)

            function at(nombre, def) {
                if (!trigger) { return def || ''; }
                var v = trigger.getAttribute(nombre);
                return (v === null || v === '') ? (def || '') : v;
            }
            function tiene(nombre) {
                return trigger && trigger.getAttribute(nombre) !== null;
            }

            function pintar(o) {
                elIcono.className = 'modal-confirm-icono modal-confirm-icono--' + (o.variant || 'azul');
                elIcono.innerHTML = ICONOS[o.icon] || ICONOS.check;
                elTitulo.textContent = o.title || 'Confirmar';
                elTexto.textContent = o.text || '';
                elTexto.hidden = !o.text;
                elSub.textContent = o.subtext || '';
                elSub.hidden = !o.subtext;
                btnOk.textContent = o.ok || 'Continuar';
                btnOk.className = 'btn ' + (o.btnclass || 'btn-primary');
            }

            function abrirPaso1() {
                paso = 1;
                pintar({
                    variant: at('data-confirm-variant', 'azul'),
                    icon: at('data-confirm-icon', 'check'),
                    title: at('data-confirm-title', 'Confirmar'),
                    text: at('data-confirm-text'),
                    subtext: at('data-confirm-subtext'),
                    ok: at('data-confirm-ok', 'Continuar'),
                    btnclass: at('data-confirm-btnclass', 'btn-primary')
                });
                if (tiene('data-confirm-input')) {
                    wrapCampo.hidden = false;
                    lblCampo.textContent = at('data-confirm-input-label', 'Detalle');
                    txtCampo.value = valores[at('data-confirm-input-target')] || '';
                    txtCampo.required = true;
                } else {
                    wrapCampo.hidden = true;
                    txtCampo.required = false;
                }
                overlay.classList.add('modal-abierto');
                (tiene('data-confirm-input') ? txtCampo : btnOk).focus();
            }

            function abrirPaso2() {
                paso = 2;
                wrapCampo.hidden = true;
                pintar({
                    variant: at('data-confirm-step2-variant', at('data-confirm-variant', 'azul')),
                    icon: at('data-confirm-step2-icon', at('data-confirm-icon', 'check')),
                    title: at('data-confirm-step2-title', 'Confirmar'),
                    text: at('data-confirm-step2-text'),
                    subtext: at('data-confirm-step2-subtext'),
                    ok: at('data-confirm-step2-ok', 'Confirmar'),
                    btnclass: at('data-confirm-step2-btnclass', at('data-confirm-btnclass', 'btn-primary'))
                });
                btnOk.focus();
            }

            function cerrar() {
                overlay.classList.remove('modal-abierto');
                // El modal es reutilizable: al cerrarse se reinicia el estado del paso con textarea
                // para que la próxima confirmación (simple o de otro trigger) no lo herede visible.
                paso = 1;
                wrapCampo.hidden = true;
                txtCampo.required = false;
            }

            function enviar() {
                if (!trigger) { cerrar(); return; }
                var form = document.getElementById(at('data-confirm-form'));
                if (!form) { cerrar(); return; }
                var destinoId = at('data-confirm-input-target');
                if (destinoId) {
                    var destino = document.getElementById(destinoId);
                    if (destino) { destino.value = valores[destinoId] || txtCampo.value || ''; }
                }
                form.__confirmado = true;
                cerrar();
                if (typeof form.requestSubmit === 'function') { form.requestSubmit(); }
                else { form.submit(); }
            }

            btnOk.addEventListener('click', function () {
                if (paso === 1 && tiene('data-confirm-input')) {
                    if (typeof txtCampo.reportValidity === 'function' && !txtCampo.reportValidity()) { return; }
                    if (txtCampo.value.trim() === '') { txtCampo.focus(); return; }
                    valores[at('data-confirm-input-target')] = txtCampo.value;
                    abrirPaso2();
                    return;
                }
                if (paso === 1 && tiene('data-confirm-step2-title')) {
                    abrirPaso2();
                    return;
                }
                enviar();
            });

            document.addEventListener('click', function (evento) {
                var t = evento.target.closest('[data-confirm]');
                if (t) {
                    evento.preventDefault();
                    trigger = t;

                    var idCampoPrevio = t.getAttribute('data-confirm-require-field');
                    if (idCampoPrevio) {
                        var campoPrevio = document.getElementById(idCampoPrevio);
                        if (campoPrevio && campoPrevio.value.trim() === '') {
                            campoPrevio.focus();
                            if (typeof campoPrevio.reportValidity === 'function') { campoPrevio.reportValidity(); }
                            return;
                        }
                    }

                    if (t.getAttribute('data-confirm-validate-form') !== null) {
                        var fv = document.getElementById(t.getAttribute('data-confirm-form'));
                        if (fv && typeof fv.checkValidity === 'function' && !fv.checkValidity()) {
                            if (typeof fv.reportValidity === 'function') { fv.reportValidity(); }
                            return;
                        }
                    }

                    abrirPaso1();
                    return;
                }

                if (evento.target === overlay || evento.target.closest('[data-modal-confirm-cancel]')) {
                    cerrar();
                }
            });

            document.addEventListener('keydown', function (evento) {
                if (evento.key === 'Escape' && overlay.classList.contains('modal-abierto')) { cerrar(); }
            });

            // Enter / submit programático en un formulario objetivo: enrutarlo por el modal mientras
            // no se haya confirmado explícitamente en él.
            var vistos = {};
            document.querySelectorAll('[data-confirm][data-confirm-form]').forEach(function (t) {
                var idForm = t.getAttribute('data-confirm-form');
                if (vistos[idForm]) { return; }
                vistos[idForm] = 1;
                var f = document.getElementById(idForm);
                if (!f) { return; }
                f.addEventListener('submit', function (evento) {
                    if (f.__confirmado) { return; }
                    evento.preventDefault();
                    t.click();
                });
            });
        })();
    </script>
</body>
</html>
