// app.js — JS global ligero del nuevo layout. Por ahora solo contiene el redimensionado manual de
// columnas de tabla; se inicializa ÚNICAMENTE en <table class="table-resizable">, nunca en todas
// las tablas del sistema (ver .table-app en app.css para el resto de listados).
(function () {
    function inicializarTabla(tabla) {
        var encabezados = tabla.querySelectorAll('thead th');

        encabezados.forEach(function (encabezado) {
            // A propósito NO se fija ningún ancho aquí (ni se lee getBoundingClientRect al cargar):
            // el ancho inicial "cómodo" de cada columna ya lo define app.css por nth-child. Fijarlo
            // en JS al cargar congelaba el ancho YA comprimido que el navegador calculaba antes de
            // que existiera ese CSS, partiendo los encabezados. El primer style.width en línea recién
            // se aplica cuando la usuaria empieza a arrastrar (ver mousedown más abajo).
            var handle = document.createElement('span');
            handle.className = 'table-resize-handle';
            handle.setAttribute('aria-hidden', 'true');
            encabezado.appendChild(handle);

            var anchoAlIniciarArrastre = 0;
            var xAlIniciarArrastre = 0;

            function alMover(evento) {
                var minimo = parseInt(window.getComputedStyle(encabezado).minWidth, 10) || 80;
                var delta = evento.clientX - xAlIniciarArrastre;
                var nuevoAncho = Math.max(minimo, anchoAlIniciarArrastre + delta);
                encabezado.style.width = nuevoAncho + 'px';
            }

            function alSoltar() {
                handle.classList.remove('resize-activo');
                document.removeEventListener('mousemove', alMover);
                document.removeEventListener('mouseup', alSoltar);
            }

            handle.addEventListener('mousedown', function (evento) {
                evento.preventDefault();
                // Ancho YA renderizado en ese momento (el cómodo definido por CSS la primera vez,
                // o el que dejó un arrastre anterior) — nunca uno recalculado de antemano.
                anchoAlIniciarArrastre = encabezado.getBoundingClientRect().width;
                xAlIniciarArrastre = evento.clientX;
                handle.classList.add('resize-activo');
                document.addEventListener('mousemove', alMover);
                document.addEventListener('mouseup', alSoltar);
            });
        });
    }

    function inicializarTablasRedimensionables() {
        document.querySelectorAll('table.table-resizable').forEach(inicializarTabla);
    }

    document.addEventListener('DOMContentLoaded', inicializarTablasRedimensionables);
})();
