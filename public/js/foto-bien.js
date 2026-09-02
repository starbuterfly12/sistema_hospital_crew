// Modal compartido "Fotografía del bien" para los procesos operativos (Traslados, Préstamos,
// Devoluciones, Verificación individual y por asignación) + generador de miniatura para las
// tablas de selección que se construyen por JS. Sin librerías, sin CDN.
//
// La lógica de apertura usa delegación en document, así que también funciona con las miniaturas
// que se inyectan dinámicamente. SOLO consulta: nunca modifica la fotografía del bien.
(function () {
    'use strict';

    function escaparHtml(valor) {
        var div = document.createElement('div');
        div.textContent = (valor === null || valor === undefined) ? '' : String(valor);
        return div.innerHTML;
    }

    // HTML de una miniatura clickeable para tablas generadas por JS (selección de bienes en
    // Traslados / Préstamos / Devoluciones). Misma apariencia y disparador [data-foto-bien] que
    // fotoBienThumb() de PHP.
    //   tieneImagen: boolean.  tam: 'sm' | 'md' | 'lg'.
    window.fotoBienThumbHtml = function (idBien, tieneImagen, codigo, descripcion, tam) {
        tam = (tam === 'md' || tam === 'lg') ? tam : 'sm';
        if (!tieneImagen) {
            return '<span class="foto-bien-vacio">Sin fotografía</span>';
        }
        var url = 'index.php?modulo=bienes&accion=imagen&id=' + encodeURIComponent(idBien);
        return '<button type="button" class="foto-bien-thumb foto-bien-thumb--' + tam + '"'
            + ' data-foto-bien data-imagen="' + escaparHtml(url) + '"'
            + ' data-codigo="' + escaparHtml(codigo || '') + '"'
            + ' data-descripcion="' + escaparHtml(descripcion || '') + '"'
            + ' aria-label="Ampliar la fotografía del bien">'
            + '<img src="' + escaparHtml(url) + '" alt="Fotografía del bien" loading="lazy"></button>';
    };

    function iniciar() {
        var modal = document.getElementById('modal-foto-bien');
        if (!modal || modal.dataset.fotoBienInit === '1') { return; }
        modal.dataset.fotoBienInit = '1';

        var img = document.getElementById('modal-foto-bien-img');
        var codigo = document.getElementById('modal-foto-bien-codigo');
        var descWrap = document.getElementById('modal-foto-bien-desc-wrap');
        var desc = document.getElementById('modal-foto-bien-desc');

        function abrir(src, cod, dsc) {
            img.setAttribute('src', src || '');
            codigo.textContent = (cod && cod !== '—') ? cod : '—';
            if (dsc && dsc !== '—') {
                desc.textContent = dsc;
                descWrap.hidden = false;
            } else {
                desc.textContent = '';
                descWrap.hidden = true;
            }
            modal.classList.add('modal-abierto');
        }

        function cerrar() {
            modal.classList.remove('modal-abierto');
            img.setAttribute('src', '');
        }

        document.addEventListener('click', function (evento) {
            var disparador = evento.target.closest('[data-foto-bien]');
            if (disparador) {
                evento.preventDefault();
                abrir(
                    disparador.getAttribute('data-imagen'),
                    disparador.getAttribute('data-codigo'),
                    disparador.getAttribute('data-descripcion')
                );
                return;
            }
            if (evento.target === modal || evento.target.closest('[data-cerrar-modal-foto]')) {
                cerrar();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && modal.classList.contains('modal-abierto')) {
                cerrar();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
