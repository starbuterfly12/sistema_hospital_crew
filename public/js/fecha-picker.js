(function () {
    function inicializarSelectoresFecha(idsCampos) {
        if (typeof flatpickr === 'undefined') {
            return;
        }

        if (flatpickr.l10ns && flatpickr.l10ns.es) {
            flatpickr.localize(flatpickr.l10ns.es);
        }

        idsCampos.forEach(function (idCampo) {
            var campo = document.getElementById(idCampo);

            if (!campo) {
                return;
            }

            var instancia = flatpickr(campo, {
                altInput: true,
                altFormat: 'd/m/Y',
                dateFormat: 'Y-m-d',
                allowInput: true,
                locale: 'es'
            });

            var boton = document.querySelector('[data-flatpickr-target="' + idCampo + '"]');

            if (boton) {
                boton.addEventListener('click', function () {
                    instancia.open();
                });
            }
        });
    }

    window.inicializarSelectoresFecha = inicializarSelectoresFecha;
})();
