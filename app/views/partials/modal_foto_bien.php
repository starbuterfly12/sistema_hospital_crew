<?php
// Parcial reutilizable: modal "Fotografía del bien" para los procesos operativos (Traslados,
// Préstamos, Devoluciones, Verificación individual y por asignación). Se abre desde cualquier
// elemento [data-foto-bien] con data-imagen / data-codigo / data-descripcion — sea una miniatura
// de fotoBienThumb() (PHP) o generada por window.fotoBienThumbHtml() (tablas construidas por JS).
// SOLO consulta: nunca modifica la fotografía. Abrir/cerrar/ESC/click-fuera están en
// public/js/foto-bien.js. Incluir UNA sola vez por vista (guardado contra doble require).
if (defined('PARCIAL_MODAL_FOTO_BIEN')) {
    return;
}
define('PARCIAL_MODAL_FOTO_BIEN', true);
?>
<div id="modal-foto-bien" class="modal-overlay">
    <div class="modal-caja modal-caja-qr" role="dialog" aria-modal="true" aria-labelledby="modal-foto-bien-titulo">
        <h2 id="modal-foto-bien-titulo" class="modal-qr-titulo">Fotografía del bien</h2>

        <div class="modal-qr-contenido">
            <img id="modal-foto-bien-img" src="" alt="Fotografía del bien" class="modal-foto-imagen">
            <p class="modal-qr-dato">Código interno: <strong id="modal-foto-bien-codigo">—</strong></p>
            <p class="modal-qr-dato" id="modal-foto-bien-desc-wrap" hidden><span id="modal-foto-bien-desc"></span></p>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-cerrar-modal-foto>Cerrar</button>
        </div>
    </div>
</div>
<script src="<?= url('public/js/foto-bien.js') ?>"></script>
