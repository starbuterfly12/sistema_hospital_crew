<?php
// Fragmento de contenido: se renderiza dentro de layouts/main.php (ver TarjetasController::generar()).
// La lógica de emisión NO cambia: mismo endpoint (POST a index.php?modulo=tarjetas&accion=generar),
// mismo csrfField(), mismo <select name="id_asignacion"> con las asignaciones en estado 'Asignada'
// (Asignacion::getAsignadas()). La confirmación final usa el modal global #modal-confirm del layout
// (data-confirm), nunca window.confirm(): el <button> visible es type="button" y dispara el envío del
// <form> real mediante requestSubmit().
$asignaciones = $asignaciones ?? [];
$error = $error ?? null;

$mostrar = static function ($valor): string {
    return ($valor !== null && trim((string) $valor) !== '') ? htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') : '—';
};
?>
<div class="page-header">
    <div class="page-header-fila">
        <div>
            <h1 class="page-title">Generar tarjeta de responsabilidad</h1>
            <p class="page-subtitle">Emisión de una tarjeta a partir de una asignación en estado Asignada.</p>
        </div>

        <div class="page-actions">
            <a href="index.php?modulo=tarjetas" class="btn btn-secondary">Volver</a>
        </div>
    </div>
</div>

<?php if ($error !== null && $error !== ''): ?>
    <div class="alert alert-error"><?= $mostrar($error) ?></div>
<?php endif; ?>

<?php if (empty($asignaciones)): ?>
    <div class="card">
        <p class="estado-vacio">No hay asignaciones en estado Asignada disponibles para generar una tarjeta.</p>
    </div>
<?php else: ?>
    <form method="POST" action="index.php?modulo=tarjetas&accion=generar" id="form-generar-tarjeta" class="form-card">
        <?= csrfField() ?>

        <div class="form-section">
            <h2 class="form-section-title">Asignación</h2>
            <div class="form-grid">
                <div class="form-group form-grid-full">
                    <label class="form-label" for="id_asignacion">Asignación <span class="required-mark">*</span></label>
                    <select id="id_asignacion" name="id_asignacion" class="form-control" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($asignaciones as $asignacion): ?>
                            <?php
                                $ubicacionTexto = $asignacion['nombre_ubicacion'] ?? '-';

                                if (!empty($asignacion['tipo_ubicacion'])) {
                                    $ubicacionTexto .= ' - ' . $asignacion['tipo_ubicacion'];
                                }

                                $etiqueta = ($asignacion['numero_asignacion'] ?? '-')
                                    . ' - ' . ($asignacion['responsable_nombre'] ?? '-')
                                    . ' - ' . $ubicacionTexto;
                            ?>
                            <option value="<?= (int) $asignacion['id_asignacion'] ?>"><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-primary"
                data-confirm
                data-confirm-form="form-generar-tarjeta"
                data-confirm-validate-form
                data-confirm-icon="doc" data-confirm-variant="azul"
                data-confirm-title="Confirmar generación"
                data-confirm-text="Se generará una tarjeta de responsabilidad para la asignación seleccionada."
                data-confirm-subtext="¿Desea generar la tarjeta?"
                data-confirm-ok="Generar tarjeta"
                data-confirm-btnclass="btn-primary">
                Generar tarjeta
            </button>
            <a href="index.php?modulo=tarjetas" class="btn btn-secondary">Cancelar</a>
        </div>

        <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Generar tarjeta</button>
    </form>
<?php endif; ?>
