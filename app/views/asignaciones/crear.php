<!DOCTYPE html>
<html lang="es-GT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar asignación</title>
    <link rel="stylesheet" href="<?= url('public/vendor/flatpickr/flatpickr.min.css') ?>">
    <style>
        .campo-fecha {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-calendario {
            cursor: pointer;
            line-height: 1;
        }
    </style>
</head>
<body>
    <?php
        $responsables = $responsables ?? [];
        $error = $error ?? null;
        $datosFormulario = $datosFormulario ?? [];
    ?>

    <?php if (!empty($error)): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h1>Registrar asignación</h1>

    <form method="POST">
        <?= csrfField() ?>

        <div>
            <label for="id_responsable">Responsable *</label>
            <select id="id_responsable" name="id_responsable" required>
                <option value="">Seleccione</option>
                <?php foreach ($responsables as $responsable): ?>
                    <option
                        value="<?= (int) $responsable['id_responsable'] ?>"
                        data-ubicacion="<?= htmlspecialchars($responsable['nombre_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-tipo-ubicacion="<?= htmlspecialchars($responsable['tipo_ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        <?= ((int) ($datosFormulario['id_responsable'] ?? 0) === (int) $responsable['id_responsable']) ? ' selected' : '' ?>
                    ><?= htmlspecialchars($responsable['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="ubicacion_derivada">Área / ubicación</label>
            <input
                type="text"
                id="ubicacion_derivada"
                readonly
            >
        </div>

        <div>
            <label for="fecha_asignacion">Fecha de asignación *</label>
            <div class="campo-fecha">
                <input
                    type="text"
                    id="fecha_asignacion"
                    name="fecha_asignacion"
                    value="<?= htmlspecialchars($datosFormulario['fecha_asignacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="DD/MM/AAAA"
                    required
                >
                <button type="button" class="btn-calendario" data-flatpickr-target="fecha_asignacion" aria-label="Abrir calendario">📅</button>
            </div>
        </div>

        <div>
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones"><?= htmlspecialchars($datosFormulario['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div>
            <button type="submit">Registrar asignación</button>
            <a href="index.php?modulo=asignaciones">Cancelar</a>
        </div>
    </form>

    <script>
        (function () {
            var selectResponsable = document.getElementById('id_responsable');
            var campoUbicacion = document.getElementById('ubicacion_derivada');

            function actualizarUbicacion() {
                var opcion = selectResponsable.options[selectResponsable.selectedIndex];

                if (!opcion || !opcion.value) {
                    campoUbicacion.value = 'Seleccione un responsable';
                    return;
                }

                var nombreUbicacion = opcion.getAttribute('data-ubicacion') || '';
                var tipoUbicacion = opcion.getAttribute('data-tipo-ubicacion') || '';

                if (nombreUbicacion === '') {
                    campoUbicacion.value = 'Ubicación no disponible';
                    return;
                }

                campoUbicacion.value = tipoUbicacion !== ''
                    ? nombreUbicacion + ' — ' + tipoUbicacion
                    : nombreUbicacion;
            }

            selectResponsable.addEventListener('change', actualizarUbicacion);
            actualizarUbicacion();
        })();
    </script>

    <script src="<?= url('public/vendor/flatpickr/flatpickr.min.js') ?>"></script>
    <script src="<?= url('public/vendor/flatpickr/l10n/es.js') ?>"></script>
    <script src="<?= url('public/js/fecha-picker.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            inicializarSelectoresFecha(['fecha_asignacion']);
        });
    </script>
</body>
</html>
