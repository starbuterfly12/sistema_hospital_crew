// qr-scanner.js — Escaneo de código QR con la cámara para LOCALIZAR un bien en la Verificación
// física INDIVIDUAL (no en la verificación por asignación). Sin CDN: usa jsQR vendorizado
// (public/vendor/jsqr/jsQR.js), que debe cargarse ANTES que este archivo.
//
// La cámara es únicamente otra forma de localizar el bien. Al detectar un QR válido del sistema,
// se redirige al MISMO flujo que la búsqueda manual:
//     index.php?modulo=verificaciones&accion=crear&id_bien=<id>
// Nunca se navega al contenido del QR, nunca se ejecuta texto capturado, no se escribe en BD ni en
// bitácora, y el stream de la cámara se detiene SIEMPRE (éxito, cancelación, error o cierre).
//
// Si no hay soporte de cámara (navegador antiguo, o acceso por HTTP no seguro desde otra máquina),
// el modal muestra un mensaje amable y la búsqueda manual sigue intacta.
(function () {
    'use strict';

    // --- Interpretación SEGURA del contenido del QR ------------------------------------------------
    // Nuestros QR (BienesController::generarArchivoQr) codifican SIEMPRE la URL estable del bien:
    //     http(s)://.../index.php?modulo=bienes&accion=ver&id=<id_bien>
    // Sólo se extrae el id numérico de ESE patrón. Cualquier otro texto (otra web, WiFi, texto
    // suelto, etc.) devuelve null -> "no corresponde a un bien registrado".
    function interpretarQr(texto) {
        if (typeof texto !== 'string') { return null; }
        var t = texto.trim();
        if (t === '' || t.length > 512) { return null; }

        var m = t.match(/[?&]modulo=bienes(?:&|&amp;)accion=ver(?:&|&amp;)id=(\d{1,9})(?:[&#]|$)/i);
        if (!m) { return null; }

        var id = parseInt(m[1], 10);
        return (id > 0 && id < 1000000000) ? id : null;
    }

    // --- Mensaje humano por tipo de fallo de cámara (NUNCA se muestran nombres técnicos) ----------
    function mensajeErrorCamara(nombre) {
        switch (nombre) {
            case 'NotAllowedError':
            case 'SecurityError':
            case 'PermissionDeniedError':
                return 'No se pudo acceder a la cámara. Puede intentarlo nuevamente o localizar el bien manualmente.';
            case 'NotFoundError':
            case 'DevicesNotFoundError':
            case 'OverconstrainedError':
                return 'No se encontró una cámara disponible. Puede localizar el bien manualmente.';
            default:
                return 'La cámara no está disponible en este navegador o conexión. Puede localizar el bien manualmente.';
        }
    }

    function camaraSoportada() {
        return !!(navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function');
    }

    function iniciar() {
        var modal = document.getElementById('modal-qr-scanner');
        if (!modal || modal.dataset.qrScannerInit === '1') { return; }

        var disparadores = document.querySelectorAll('[data-abrir-qr-scanner]');
        if (!disparadores.length) { return; }

        modal.dataset.qrScannerInit = '1';

        var video = document.getElementById('qr-scanner-video');
        var canvas = document.getElementById('qr-scanner-canvas');
        var estado = document.getElementById('qr-scanner-estado');
        var mensaje = document.getElementById('qr-scanner-mensaje');
        var btnReintentar = document.getElementById('qr-scanner-reintentar');
        var ctx = canvas ? canvas.getContext('2d', { willReadFrequently: true }) : null;
        var destinoBase = modal.getAttribute('data-destino-base') || 'index.php?modulo=verificaciones&accion=crear';

        var stream = null;
        var rafId = null;
        var ultimoScan = 0;
        var bloqueado = false; // procesa una única detección hasta completar / reintentar
        var activo = false;

        function setEstado(texto) {
            if (estado) { estado.textContent = texto || ''; }
        }

        function setMensaje(texto) {
            if (!mensaje) { return; }
            mensaje.textContent = texto || '';
            mensaje.hidden = !texto;
        }

        function mostrarReintentar(mostrar) {
            if (btnReintentar) { btnReintentar.hidden = !mostrar; }
        }

        // Punto único para apagar la cámara. Idempotente: se puede llamar varias veces sin efectos.
        function detenerCamara() {
            activo = false;

            if (rafId !== null) {
                try { cancelAnimationFrame(rafId); } catch (e) { /* noop */ }
                rafId = null;
            }

            if (stream) {
                try {
                    stream.getTracks().forEach(function (track) { track.stop(); });
                } catch (e) { /* la cámara ya podría estar liberada */ }
                stream = null;
            }

            if (video) {
                try { video.pause(); } catch (e) { /* noop */ }
                try { video.srcObject = null; } catch (e) { /* noop */ }
            }
        }

        function cerrar() {
            detenerCamara();
            modal.classList.remove('modal-abierto');
        }

        function bucle() {
            if (!activo) { return; }
            rafId = requestAnimationFrame(bucle);

            if (bloqueado || !ctx) { return; }

            var ahora = Date.now();
            if (ahora - ultimoScan < 180) { return; } // ~5 lecturas/seg, suficiente y sin recalentar
            ultimoScan = ahora;

            if (!video || video.readyState < 2 || !video.videoWidth) { return; }

            var w = video.videoWidth;
            var h = video.videoHeight;
            canvas.width = w;
            canvas.height = h;

            var datos;
            try {
                ctx.drawImage(video, 0, 0, w, h);
                datos = ctx.getImageData(0, 0, w, h);
            } catch (e) {
                return; // frame aún no disponible; se reintenta en el siguiente ciclo
            }

            var resultado = null;
            try {
                if (typeof window.jsQR === 'function') {
                    resultado = window.jsQR(datos.data, w, h, { inversionAttempts: 'dontInvert' });
                }
            } catch (e) {
                resultado = null;
            }

            if (resultado && resultado.data) {
                procesarDeteccion(resultado.data);
            }
        }

        function procesarDeteccion(texto) {
            if (bloqueado) { return; }
            bloqueado = true; // una sola vez hasta completar / reintentar (evita lecturas repetidas)

            var id = interpretarQr(texto);

            if (id === null) {
                // QR ajeno al sistema: NO se navega a nada. Se ofrece reintentar sin recargar.
                setEstado('');
                setMensaje('El código QR escaneado no corresponde a un bien registrado.');
                mostrarReintentar(true);
                return;
            }

            // QR válido del sistema -> apagar cámara y entrar al MISMO flujo que la selección manual.
            // Si el bien ya no existe / no es verificable, el propio flujo actual mostrará su mensaje.
            setEstado('Bien identificado. Abriendo…');
            setMensaje('');
            detenerCamara();

            var sep = destinoBase.indexOf('?') === -1 ? '?' : '&';
            window.location.assign(destinoBase + sep + 'id_bien=' + encodeURIComponent(String(id)));
        }

        function reintentar() {
            setMensaje('');
            mostrarReintentar(false);
            setEstado(activo ? 'Buscando código QR…' : '');
            bloqueado = false;

            if (!activo) {
                abrir(); // la cámara se había detenido tras un error: se vuelve a pedir
            }
        }

        function abrir() {
            setMensaje('');
            mostrarReintentar(false);
            setEstado('');
            modal.classList.add('modal-abierto');

            if (!camaraSoportada()) {
                setMensaje(mensajeErrorCamara(''));
                return;
            }

            setEstado('Solicitando acceso a la cámara…');

            navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' } },
                audio: false
            }).then(function (s) {
                stream = s;
                if (video) {
                    video.setAttribute('playsinline', '');
                    video.muted = true;
                    video.srcObject = s;
                    var p = video.play();
                    if (p && typeof p.catch === 'function') { p.catch(function () { /* autoplay */ }); }
                }
                bloqueado = false;
                activo = true;
                setEstado('Buscando código QR…');
                rafId = requestAnimationFrame(bucle);
            }).catch(function (err) {
                detenerCamara();
                setEstado('');
                setMensaje(mensajeErrorCamara(err && err.name ? err.name : ''));
            });
        }

        Array.prototype.forEach.call(disparadores, function (btn) {
            btn.addEventListener('click', function () {
                try {
                    abrir();
                } catch (e) {
                    detenerCamara();
                    setEstado('');
                    setMensaje(mensajeErrorCamara(''));
                }
            });
        });

        if (btnReintentar) {
            btnReintentar.addEventListener('click', reintentar);
        }

        modal.addEventListener('click', function (evento) {
            if (evento.target === modal || (evento.target.closest && evento.target.closest('[data-cerrar-qr-scanner]'))) {
                cerrar();
            }
        });

        document.addEventListener('keydown', function (evento) {
            if (evento.key === 'Escape' && modal.classList.contains('modal-abierto')) {
                cerrar();
            }
        });

        // Salir de la página (navegación, cerrar pestaña) también libera la cámara.
        window.addEventListener('pagehide', detenerCamara);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
