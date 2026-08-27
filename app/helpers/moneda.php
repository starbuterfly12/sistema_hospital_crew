<?php

if (!function_exists('formatearQuetzales')) {
    // Formato monetario institucional para TODA la interfaz: prefijo "Q ", separador de miles y
    // siempre 2 decimales. Es SOLO presentación — la BD y el backend siguen trabajando con el número
    // plano (ej. 2500.00). Nunca debe usarse dentro del value de un <input> editable (ahí el backend
    // espera el número): para esos casos se usa el componente CSS .input-money con la Q como prefijo
    // visual externo.
    //
    //   2500      -> "Q 2,500.00"
    //   0 / "0"   -> "Q 0.00"        (un valor real de cero SÍ es dinero)
    //   null / "" -> "—"             (el dato no existe: no se inventa "Q 0.00")
    function formatearQuetzales($valor): string
    {
        if ($valor === null || $valor === '' || (is_string($valor) && trim($valor) === '')) {
            return '—';
        }

        if (!is_numeric($valor)) {
            return '—';
        }

        return 'Q ' . number_format((float) $valor, 2);
    }
}
