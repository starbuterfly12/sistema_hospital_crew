<?php

require_once __DIR__ . '/../core/Model.php';

// Capa de agregación de solo lectura para el módulo Reportes. A diferencia del resto de modelos
// (que representan una tabla), este servicio combina varias tablas ya existentes de otros módulos
// SIN modificarlas ni escribir en ellas — confirmado: cero INSERT/UPDATE/DELETE en todo este archivo.
//
// Decisión de diseño (aprobada explícitamente): para los reportes consolidados (Movimientos, Bienes
// con actividad, Resumen) NO se usa un único SQL con múltiples UNION ALL. En su lugar, cada fuente
// (Traslados/Préstamos/Devoluciones/Requisiciones entregadas/Bajas finalizadas/Verificaciones) se
// consulta por separado con su propio SQL parametrizado, y los resultados se normalizan y combinan
// como arrays en PHP (array_merge + usort). Esto evita una sola consulta gigantesca y mantiene cada
// fuente independiente y fácil de revisar/depurar por separado.
class ReportesService extends Model
{
    public const TIPOS_EVENTO = ['Traslado', 'Préstamo', 'Devolución', 'Requisición', 'Baja', 'Verificación física'];

    private const SEPARADOR_VACIO = '—';

    // =========================================================================================
    // FUENTES NORMALIZADAS (una por tipo de evento) — cada una devuelve una lista de arrays con,
    // como mínimo: tipo, id_operacion, id_bien, fecha, codigo_interno, codigo_sicoin, descripcion,
    // responsable, ubicacion, usuario, resumen. Se agregan además id_responsable_filtro/
    // id_ubicacion_filtro/id_usuario_filtro (enteros o null) para que los filtros por responsable/
    // ubicación/usuario del Reporte de Movimientos puedan comparar por ID en vez de por texto ya
    // formateado (que incluye flechas "origen → destino" y no es apto para comparación exacta).
    // =========================================================================================

    private function obtenerTraslados(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                m.id_movimiento,
                m.fecha_movimiento,
                dm.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                m.id_responsable_origen,
                ro.nombre_completo AS responsable_origen,
                m.id_responsable_destino,
                rd.nombre_completo AS responsable_destino,
                m.id_ubicacion_origen,
                uo.nombre_ubicacion AS ubicacion_origen,
                m.id_ubicacion_destino,
                ud.nombre_ubicacion AS ubicacion_destino,
                m.id_usuario_registra,
                usr.nombre_completo AS usuario_registra,
                m.numero_movimiento,
                m.motivo
            FROM movimientos m
            INNER JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
            INNER JOIN detalle_movimiento dm ON dm.id_movimiento = m.id_movimiento
            INNER JOIN bienes b ON dm.id_bien = b.id_bien
            LEFT JOIN responsables ro ON m.id_responsable_origen = ro.id_responsable
            LEFT JOIN responsables rd ON m.id_responsable_destino = rd.id_responsable
            LEFT JOIN ubicaciones uo ON m.id_ubicacion_origen = uo.id_ubicacion
            LEFT JOIN ubicaciones ud ON m.id_ubicacion_destino = ud.id_ubicacion
            LEFT JOIN usuarios usr ON m.id_usuario_registra = usr.id_usuario
            WHERE tm.nombre_movimiento = 'Traslado'
              AND m.fecha_movimiento >= :fecha_desde
              AND m.fecha_movimiento <= :fecha_hasta_fin
        ";

        $filas = $this->fetchAll($sql, [
            ':fecha_desde' => $fechaDesde . ' 00:00:00',
            ':fecha_hasta_fin' => $fechaHasta . ' 23:59:59',
        ]);

        $eventos = [];

        foreach ($filas as $fila) {
            $eventos[] = [
                'tipo' => 'Traslado',
                'id_operacion' => 'TRASLADO:' . $fila['id_movimiento'],
                'id_bien' => (int) $fila['id_bien'],
                'fecha' => (string) $fila['fecha_movimiento'],
                'fecha_es_datetime' => true, // movimientos.fecha_movimiento es DATETIME real
                'codigo_interno' => $fila['codigo_interno'],
                'codigo_sicoin' => $fila['codigo_sicoin'],
                'descripcion' => $fila['descripcion'],
                'responsable' => $this->formatearFlecha($fila['responsable_origen'], $fila['responsable_destino']),
                'ubicacion' => $this->formatearFlecha($fila['ubicacion_origen'], $fila['ubicacion_destino']),
                'id_responsable_filtro' => $this->intOrNull($fila['id_responsable_destino']),
                'id_ubicacion_filtro' => $this->intOrNull($fila['id_ubicacion_destino']),
                'usuario' => $fila['usuario_registra'] ?? self::SEPARADOR_VACIO,
                'id_usuario_filtro' => $this->intOrNull($fila['id_usuario_registra']),
                'resumen' => 'Traslado ' . $fila['numero_movimiento'] . ($fila['motivo'] ? ' — ' . $fila['motivo'] : ''),
            ];
        }

        return $eventos;
    }

    private function obtenerPrestamos(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                p.id_prestamo,
                p.fecha_prestamo,
                dp.id_bien,
                dp.codigo_interno_mostrado,
                dp.codigo_sicoin_mostrado,
                dp.descripcion_mostrada,
                p.id_responsable_origen,
                p.responsable_origen_mostrado,
                p.id_responsable_destino,
                p.responsable_destino_mostrado,
                p.id_ubicacion_origen,
                p.ubicacion_origen_mostrada,
                p.id_ubicacion_destino,
                p.ubicacion_destino_mostrada,
                p.id_usuario_registra,
                usr.nombre_completo AS usuario_registra,
                p.numero_prestamo,
                p.motivo
            FROM prestamos p
            INNER JOIN detalle_prestamo dp ON dp.id_prestamo = p.id_prestamo
            LEFT JOIN usuarios usr ON p.id_usuario_registra = usr.id_usuario
            WHERE p.fecha_prestamo >= :fecha_desde
              AND p.fecha_prestamo <= :fecha_hasta
        ";

        $filas = $this->fetchAll($sql, [
            ':fecha_desde' => $fechaDesde,
            ':fecha_hasta' => $fechaHasta,
        ]);

        $eventos = [];

        foreach ($filas as $fila) {
            $eventos[] = [
                'tipo' => 'Préstamo',
                'id_operacion' => 'PRESTAMO:' . $fila['id_prestamo'],
                'id_bien' => (int) $fila['id_bien'],
                'fecha' => (string) $fila['fecha_prestamo'] . ' 00:00:00',
                // prestamos.fecha_prestamo es solo DATE: el " 00:00:00" es artificial, únicamente
                // para poder ordenar cronológicamente junto a fuentes con hora real — NUNCA debe
                // mostrarse como si fuera una hora verdadera (por eso fecha_es_datetime = false).
                'fecha_es_datetime' => false,
                'codigo_interno' => $fila['codigo_interno_mostrado'],
                'codigo_sicoin' => $fila['codigo_sicoin_mostrado'],
                'descripcion' => $fila['descripcion_mostrada'],
                'responsable' => $this->formatearFlecha($fila['responsable_origen_mostrado'], $fila['responsable_destino_mostrado']),
                'ubicacion' => $this->formatearFlecha($fila['ubicacion_origen_mostrada'], $fila['ubicacion_destino_mostrada']),
                'id_responsable_filtro' => $this->intOrNull($fila['id_responsable_destino']),
                'id_ubicacion_filtro' => $this->intOrNull($fila['id_ubicacion_destino']),
                'usuario' => $fila['usuario_registra'] ?? self::SEPARADOR_VACIO,
                'id_usuario_filtro' => $this->intOrNull($fila['id_usuario_registra']),
                'resumen' => 'Préstamo ' . $fila['numero_prestamo'] . ($fila['motivo'] ? ' — ' . $fila['motivo'] : ''),
            ];
        }

        return $eventos;
    }

    private function obtenerDevoluciones(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                d.id_devolucion,
                d.fecha_devolucion,
                dd.id_bien,
                dpr.codigo_interno_mostrado,
                dpr.codigo_sicoin_mostrado,
                dpr.descripcion_mostrada,
                p.id_responsable_origen,
                p.responsable_origen_mostrado,
                p.id_responsable_destino,
                p.responsable_destino_mostrado,
                p.id_ubicacion_origen,
                p.ubicacion_origen_mostrada,
                p.id_ubicacion_destino,
                p.ubicacion_destino_mostrada,
                d.id_usuario_recibe,
                usr.nombre_completo AS usuario_recibe,
                d.numero_devolucion,
                p.numero_prestamo,
                d.motivo
            FROM devoluciones d
            INNER JOIN detalle_devolucion dd ON dd.id_devolucion = d.id_devolucion
            INNER JOIN detalle_prestamo dpr ON dd.id_detalle_prestamo = dpr.id_detalle_prestamo
            INNER JOIN prestamos p ON dpr.id_prestamo = p.id_prestamo
            LEFT JOIN usuarios usr ON d.id_usuario_recibe = usr.id_usuario
            WHERE d.fecha_devolucion >= :fecha_desde
              AND d.fecha_devolucion <= :fecha_hasta
        ";

        $filas = $this->fetchAll($sql, [
            ':fecha_desde' => $fechaDesde,
            ':fecha_hasta' => $fechaHasta,
        ]);

        $eventos = [];

        foreach ($filas as $fila) {
            // La devolución regresa el bien del responsable/ubicación TEMPORAL (destino del préstamo)
            // al PERMANENTE (origen del préstamo) — flecha en sentido inverso al Préstamo, resuelta
            // enteramente a través del préstamo relacionado (la propia tabla devoluciones no guarda
            // responsable/ubicación).
            $eventos[] = [
                'tipo' => 'Devolución',
                'id_operacion' => 'DEVOLUCION:' . $fila['id_devolucion'],
                'id_bien' => (int) $fila['id_bien'],
                'fecha' => (string) $fila['fecha_devolucion'] . ' 00:00:00', // devoluciones.fecha_devolucion es solo DATE
                'fecha_es_datetime' => false,
                'codigo_interno' => $fila['codigo_interno_mostrado'],
                'codigo_sicoin' => $fila['codigo_sicoin_mostrado'],
                'descripcion' => $fila['descripcion_mostrada'],
                'responsable' => $this->formatearFlecha($fila['responsable_destino_mostrado'], $fila['responsable_origen_mostrado']),
                'ubicacion' => $this->formatearFlecha($fila['ubicacion_destino_mostrada'], $fila['ubicacion_origen_mostrada']),
                'id_responsable_filtro' => $this->intOrNull($fila['id_responsable_origen']),
                'id_ubicacion_filtro' => $this->intOrNull($fila['id_ubicacion_origen']),
                'usuario' => $fila['usuario_recibe'] ?? self::SEPARADOR_VACIO,
                'id_usuario_filtro' => $this->intOrNull($fila['id_usuario_recibe']),
                'resumen' => 'Devolución ' . $fila['numero_devolucion'] . ' del préstamo ' . $fila['numero_prestamo']
                    . ($fila['motivo'] ? ' — ' . $fila['motivo'] : ''),
            ];
        }

        return $eventos;
    }

    // Solo Requisiciones realmente ENTREGADAS (estado_requisicion='Entregada' y fecha_entrega no
    // nula) — una Requisición Pendiente/Autorizada/Anulada no representa un movimiento físico real
    // de bienes todavía.
    private function obtenerRequisicionesEntregadas(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                r.id_requisicion,
                r.fecha_entrega,
                dr.id_bien,
                dr.codigo_interno_mostrado,
                dr.codigo_sicoin_mostrado,
                dr.descripcion_mostrada,
                r.id_responsable_solicitante,
                r.responsable_solicitante_mostrado,
                r.id_ubicacion_solicitante,
                r.ubicacion_solicitante_mostrada,
                r.id_usuario_entrega,
                usr.nombre_completo AS usuario_entrega,
                r.numero_requisicion_sistema
            FROM requisiciones r
            INNER JOIN detalle_requisicion dr ON dr.id_requisicion = r.id_requisicion
            LEFT JOIN usuarios usr ON r.id_usuario_entrega = usr.id_usuario
            WHERE r.estado_requisicion = 'Entregada'
              AND r.fecha_entrega IS NOT NULL
              AND r.fecha_entrega >= :fecha_desde
              AND r.fecha_entrega <= :fecha_hasta_fin
        ";

        $filas = $this->fetchAll($sql, [
            ':fecha_desde' => $fechaDesde . ' 00:00:00',
            ':fecha_hasta_fin' => $fechaHasta . ' 23:59:59',
        ]);

        $eventos = [];

        foreach ($filas as $fila) {
            $eventos[] = [
                'tipo' => 'Requisición',
                'id_operacion' => 'REQUISICION:' . $fila['id_requisicion'],
                'id_bien' => (int) $fila['id_bien'],
                'fecha' => (string) $fila['fecha_entrega'],
                'fecha_es_datetime' => true, // requisiciones.fecha_entrega es DATETIME real
                'codigo_interno' => $fila['codigo_interno_mostrado'],
                'codigo_sicoin' => $fila['codigo_sicoin_mostrado'],
                'descripcion' => $fila['descripcion_mostrada'],
                'responsable' => $fila['responsable_solicitante_mostrado'] ?? self::SEPARADOR_VACIO,
                'ubicacion' => $fila['ubicacion_solicitante_mostrada'] ?? self::SEPARADOR_VACIO,
                'id_responsable_filtro' => $this->intOrNull($fila['id_responsable_solicitante']),
                'id_ubicacion_filtro' => $this->intOrNull($fila['id_ubicacion_solicitante']),
                'usuario' => $fila['usuario_entrega'] ?? self::SEPARADOR_VACIO,
                'id_usuario_filtro' => $this->intOrNull($fila['id_usuario_entrega']),
                'resumen' => 'Requisición ' . $fila['numero_requisicion_sistema'],
            ];
        }

        return $eventos;
    }

    // Solo Bajas FINALIZADAS con fecha_baja real — coherente con que fecha_baja es NULL en
    // pendiente/autorizada/rechazada (no representan un retiro físico consumado).
    private function obtenerBajasFinalizadas(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                ba.id_baja,
                ba.fecha_baja,
                db.id_bien,
                db.codigo_interno_mostrado,
                db.codigo_sicoin_mostrado,
                db.descripcion_mostrada,
                db.id_responsable_anterior,
                ra.nombre_completo AS responsable_anterior,
                db.id_ubicacion_anterior,
                ua.nombre_ubicacion AS ubicacion_anterior,
                ba.id_usuario_registra,
                usr.nombre_completo AS usuario_registra,
                ba.numero_baja,
                tb.nombre_tipo_baja
            FROM bajas ba
            INNER JOIN detalle_baja db ON db.id_baja = ba.id_baja
            LEFT JOIN responsables ra ON db.id_responsable_anterior = ra.id_responsable
            LEFT JOIN ubicaciones ua ON db.id_ubicacion_anterior = ua.id_ubicacion
            LEFT JOIN tipos_baja tb ON db.id_tipo_baja = tb.id_tipo_baja
            LEFT JOIN usuarios usr ON ba.id_usuario_registra = usr.id_usuario
            WHERE ba.estado_baja = 'finalizada'
              AND ba.fecha_baja IS NOT NULL
              AND ba.fecha_baja >= :fecha_desde
              AND ba.fecha_baja <= :fecha_hasta
        ";

        $filas = $this->fetchAll($sql, [
            ':fecha_desde' => $fechaDesde,
            ':fecha_hasta' => $fechaHasta,
        ]);

        $eventos = [];

        foreach ($filas as $fila) {
            $eventos[] = [
                'tipo' => 'Baja',
                'id_operacion' => 'BAJA:' . $fila['id_baja'],
                'id_bien' => (int) $fila['id_bien'],
                'fecha' => (string) $fila['fecha_baja'] . ' 00:00:00', // bajas.fecha_baja es solo DATE
                'fecha_es_datetime' => false,
                'codigo_interno' => $fila['codigo_interno_mostrado'],
                'codigo_sicoin' => $fila['codigo_sicoin_mostrado'],
                'descripcion' => $fila['descripcion_mostrada'],
                'responsable' => $fila['responsable_anterior'] ?? self::SEPARADOR_VACIO,
                'ubicacion' => $fila['ubicacion_anterior'] ?? self::SEPARADOR_VACIO,
                'id_responsable_filtro' => $this->intOrNull($fila['id_responsable_anterior']),
                'id_ubicacion_filtro' => $this->intOrNull($fila['id_ubicacion_anterior']),
                'usuario' => $fila['usuario_registra'] ?? self::SEPARADOR_VACIO,
                'id_usuario_filtro' => $this->intOrNull($fila['id_usuario_registra']),
                'resumen' => 'Baja ' . $fila['numero_baja'] . ($fila['nombre_tipo_baja'] ? ' (' . $fila['nombre_tipo_baja'] . ')' : ''),
            ];
        }

        return $eventos;
    }

    private function obtenerVerificaciones(string $fechaDesde, string $fechaHasta): array
    {
        $sql = "
            SELECT
                vf.id_verificacion,
                vf.fecha_hora,
                vf.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                vf.id_responsable_registrado,
                rr.nombre_completo AS responsable_registrado,
                vf.id_ubicacion_registrada,
                ur.nombre_ubicacion AS ubicacion_registrada,
                vf.id_usuario_verifica,
                usr.nombre_completo AS usuario_verifica,
                vf.tiene_diferencias
            FROM verificaciones_fisicas vf
            INNER JOIN bienes b ON vf.id_bien = b.id_bien
            LEFT JOIN responsables rr ON vf.id_responsable_registrado = rr.id_responsable
            LEFT JOIN ubicaciones ur ON vf.id_ubicacion_registrada = ur.id_ubicacion
            LEFT JOIN usuarios usr ON vf.id_usuario_verifica = usr.id_usuario
            WHERE vf.fecha_hora >= :fecha_desde
              AND vf.fecha_hora <= :fecha_hasta_fin
        ";

        $filas = $this->fetchAll($sql, [
            ':fecha_desde' => $fechaDesde . ' 00:00:00',
            ':fecha_hasta_fin' => $fechaHasta . ' 23:59:59',
        ]);

        $eventos = [];

        foreach ($filas as $fila) {
            $eventos[] = [
                'tipo' => 'Verificación física',
                'id_operacion' => 'VERIFICACION:' . $fila['id_verificacion'],
                'id_bien' => (int) $fila['id_bien'],
                'fecha' => (string) $fila['fecha_hora'],
                'fecha_es_datetime' => true, // verificaciones_fisicas.fecha_hora es DATETIME real
                'codigo_interno' => $fila['codigo_interno'],
                'codigo_sicoin' => $fila['codigo_sicoin'],
                'descripcion' => $fila['descripcion'],
                'responsable' => $fila['responsable_registrado'] ?? self::SEPARADOR_VACIO,
                'ubicacion' => $fila['ubicacion_registrada'] ?? self::SEPARADOR_VACIO,
                'id_responsable_filtro' => $this->intOrNull($fila['id_responsable_registrado']),
                'id_ubicacion_filtro' => $this->intOrNull($fila['id_ubicacion_registrada']),
                'usuario' => $fila['usuario_verifica'] ?? self::SEPARADOR_VACIO,
                'id_usuario_filtro' => $this->intOrNull($fila['id_usuario_verifica']),
                'resumen' => 'Verificación física — ' . ((int) $fila['tiene_diferencias'] === 1 ? 'Con diferencias' : 'Sin diferencias'),
            ];
        }

        return $eventos;
    }

    // Punto único de combinación de las 6 fuentes — usado por Movimientos, Bienes con actividad y
    // Resumen de movimientos, para no repetir la consolidación tres veces. array_merge + usort en
    // PHP (nunca UNION ALL en SQL — ver comentario de cabecera de la clase).
    public function getEventosNormalizados(string $fechaDesde, string $fechaHasta): array
    {
        $eventos = array_merge(
            $this->obtenerTraslados($fechaDesde, $fechaHasta),
            $this->obtenerPrestamos($fechaDesde, $fechaHasta),
            $this->obtenerDevoluciones($fechaDesde, $fechaHasta),
            $this->obtenerRequisicionesEntregadas($fechaDesde, $fechaHasta),
            $this->obtenerBajasFinalizadas($fechaDesde, $fechaHasta),
            $this->obtenerVerificaciones($fechaDesde, $fechaHasta)
        );

        usort($eventos, static function (array $a, array $b): int {
            return $b['fecha'] <=> $a['fecha'];
        });

        return $eventos;
    }

    // =========================================================================================
    // REPORTE 1 — Movimientos por período
    // =========================================================================================
    public function getMovimientosPorPeriodo(array $filtros): array
    {
        $eventos = $this->getEventosNormalizados($filtros['fecha_desde'], $filtros['fecha_hasta']);

        return array_values(array_filter($eventos, static function (array $evento) use ($filtros): bool {
            if (($filtros['tipo'] ?? '') !== '' && $evento['tipo'] !== $filtros['tipo']) {
                return false;
            }

            if (($filtros['id_responsable'] ?? 0) > 0 && $evento['id_responsable_filtro'] !== (int) $filtros['id_responsable']) {
                return false;
            }

            if (($filtros['id_ubicacion'] ?? 0) > 0 && $evento['id_ubicacion_filtro'] !== (int) $filtros['id_ubicacion']) {
                return false;
            }

            if (($filtros['id_usuario'] ?? 0) > 0 && $evento['id_usuario_filtro'] !== (int) $filtros['id_usuario']) {
                return false;
            }

            return true;
        }));
    }

    // =========================================================================================
    // REPORTE 2 — Bienes con actividad en un período (una fila por bien, agregando los mismos
    // eventos normalizados del Reporte 1 — no se repite ninguna consulta).
    // =========================================================================================
    public function getBienesConActividad(array $filtros): array
    {
        $eventos = $this->getEventosNormalizados($filtros['fecha_desde'], $filtros['fecha_hasta']);

        if (($filtros['tipo'] ?? '') !== '') {
            $eventos = array_filter($eventos, static fn(array $e): bool => $e['tipo'] === $filtros['tipo']);
        }

        $agrupado = [];

        foreach ($eventos as $evento) {
            $idBien = $evento['id_bien'];

            if (!isset($agrupado[$idBien])) {
                $agrupado[$idBien] = [
                    'id_bien' => $idBien,
                    'codigo_interno' => $evento['codigo_interno'],
                    'codigo_sicoin' => $evento['codigo_sicoin'],
                    'descripcion' => $evento['descripcion'],
                    'eventos' => 0,
                    'primer_evento' => $evento['fecha'],
                    'primer_evento_es_datetime' => $evento['fecha_es_datetime'],
                    'ultimo_evento' => $evento['fecha'],
                    'ultimo_evento_es_datetime' => $evento['fecha_es_datetime'],
                    'tipos' => [],
                ];
            }

            $agrupado[$idBien]['eventos']++;

            // min()/max() por separado (no directamente sobre el array) para poder arrastrar el flag
            // fecha_es_datetime del evento que realmente ganó la comparación — un bien puede tener
            // su primer evento en una fuente DATE-only y su último en una fuente DATETIME real.
            if ($evento['fecha'] < $agrupado[$idBien]['primer_evento']) {
                $agrupado[$idBien]['primer_evento'] = $evento['fecha'];
                $agrupado[$idBien]['primer_evento_es_datetime'] = $evento['fecha_es_datetime'];
            }

            if ($evento['fecha'] > $agrupado[$idBien]['ultimo_evento']) {
                $agrupado[$idBien]['ultimo_evento'] = $evento['fecha'];
                $agrupado[$idBien]['ultimo_evento_es_datetime'] = $evento['fecha_es_datetime'];
            }

            $agrupado[$idBien]['tipos'][$evento['tipo']] = true;
        }

        $filas = array_values($agrupado);

        foreach ($filas as &$filaBien) {
            $filaBien['tipos'] = implode(', ', array_keys($filaBien['tipos']));
        }
        unset($filaBien);

        usort($filas, static fn(array $a, array $b): int => $b['ultimo_evento'] <=> $a['ultimo_evento']);

        return $filas;
    }

    // =========================================================================================
    // REPORTE 3 — Bienes sin responsable/asignación con anomalía operativa real. Por defecto solo
    // bienes Activo (un bien de Baja sin responsable NO es anomalía, es lo esperado). Detecta:
    // sin responsable, sin asignación, asignación sin detalle activo resolvible, y detalle activo
    // que no coincide con la asignación actual del bien — independientemente del estado, porque
    // esas dos últimas SÍ son incoherencias estructurales reales pase lo que pase con el estado.
    // =========================================================================================
    public function getBienesSinAsignacion(array $filtros): array
    {
        $condiciones = [];
        $params = [];

        if (empty($filtros['incluir_otros_estados'])) {
            $condiciones[] = "eb.nombre_estado = 'Activo'";
        }

        if (!empty($filtros['id_categoria'])) {
            $condiciones[] = 'b.id_categoria = :id_categoria';
            $params[':id_categoria'] = (int) $filtros['id_categoria'];
        }

        if (!empty($filtros['condicion'])) {
            $condiciones[] = 'b.condicion_bien = :condicion';
            $params[':condicion'] = $filtros['condicion'];
        }

        if (!empty($filtros['id_ubicacion'])) {
            $condiciones[] = 'b.id_ubicacion_actual = :id_ubicacion';
            $params[':id_ubicacion'] = (int) $filtros['id_ubicacion'];
        }

        if (!empty($filtros['fecha_ingreso_desde'])) {
            $condiciones[] = 'b.fecha_ingreso >= :fecha_ingreso_desde';
            $params[':fecha_ingreso_desde'] = $filtros['fecha_ingreso_desde'];
        }

        if (!empty($filtros['fecha_ingreso_hasta'])) {
            $condiciones[] = 'b.fecha_ingreso <= :fecha_ingreso_hasta';
            $params[':fecha_ingreso_hasta'] = $filtros['fecha_ingreso_hasta'];
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "
            SELECT
                b.id_bien, b.codigo_interno, b.codigo_sicoin, b.descripcion,
                eb.nombre_estado, cb.nombre_categoria, b.condicion_bien,
                u.nombre_ubicacion AS ubicacion_actual, b.fecha_ingreso,
                b.id_responsable_actual, b.id_asignacion_actual,
                da.id_detalle_asignacion AS detalle_activo_id,
                da.id_asignacion AS detalle_activo_id_asignacion
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN categorias_bien cb ON b.id_categoria = cb.id_categoria
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            LEFT JOIN detalle_asignacion da ON da.id_bien = b.id_bien AND da.estado_detalle = 'activo'
            {$where}
            ORDER BY b.codigo_interno ASC
        ";

        $filas = $this->fetchAll($sql, $params);
        $resultado = [];

        foreach ($filas as $fila) {
            $esActivo = $fila['nombre_estado'] === 'Activo';
            $anomalias = [];

            if ($esActivo && $fila['id_responsable_actual'] === null) {
                $anomalias[] = 'Sin responsable';
            }

            if ($esActivo && $fila['id_asignacion_actual'] === null) {
                $anomalias[] = 'Sin asignación';
            }

            if ($fila['id_asignacion_actual'] !== null && $fila['detalle_activo_id'] === null) {
                $anomalias[] = 'Asignación actual sin detalle activo resolvible';
            }

            if ($fila['id_asignacion_actual'] === null && $fila['detalle_activo_id'] !== null) {
                $anomalias[] = 'Tiene detalle de asignación activo sin asignación actual registrada';
            }

            if (
                $fila['id_asignacion_actual'] !== null
                && $fila['detalle_activo_id_asignacion'] !== null
                && (int) $fila['id_asignacion_actual'] !== (int) $fila['detalle_activo_id_asignacion']
            ) {
                $anomalias[] = 'El detalle de asignación activo no coincide con la asignación actual del bien';
            }

            if ($anomalias === []) {
                continue;
            }

            $fila['tipo_anomalia'] = implode('; ', $anomalias);
            $resultado[] = $fila;
        }

        return $resultado;
    }

    // =========================================================================================
    // REPORTE 4 — Préstamos pendientes o vencidos
    // =========================================================================================
    public function getPrestamosPendientesVencidos(array $filtros): array
    {
        $condiciones = ["p.estado_prestamo IN ('activo', 'parcial')"];
        $params = [];

        if (!empty($filtros['estado']) && in_array($filtros['estado'], ['activo', 'parcial'], true)) {
            $condiciones = ['p.estado_prestamo = :estado'];
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = 'p.fecha_prestamo >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = 'p.fecha_prestamo <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['id_responsable_destino'])) {
            $condiciones[] = 'p.id_responsable_destino = :id_responsable_destino';
            $params[':id_responsable_destino'] = (int) $filtros['id_responsable_destino'];
        }

        if (isset($filtros['vencido']) && $filtros['vencido'] !== '') {
            $condiciones[] = $filtros['vencido'] === '1'
                ? 'p.fecha_devolucion_estimada < CURDATE()'
                : 'p.fecha_devolucion_estimada >= CURDATE()';
        }

        $where = implode(' AND ', $condiciones);

        $sql = "
            SELECT
                p.id_prestamo, p.numero_prestamo, p.responsable_destino_mostrado,
                p.fecha_prestamo, p.fecha_devolucion_estimada, p.estado_prestamo,
                (
                    SELECT COUNT(*)
                    FROM detalle_prestamo dp
                    WHERE dp.id_prestamo = p.id_prestamo AND dp.estado_detalle = 'prestado'
                ) AS bienes_pendientes
            FROM prestamos p
            WHERE {$where}
            ORDER BY p.fecha_devolucion_estimada ASC
        ";

        $filas = $this->fetchAll($sql, $params);
        $hoy = date('Y-m-d');

        foreach ($filas as &$fila) {
            $vencido = $fila['fecha_devolucion_estimada'] < $hoy;
            $fila['vencido'] = $vencido;
            $fila['dias_vencido'] = $vencido
                ? (int) ((strtotime($hoy) - strtotime($fila['fecha_devolucion_estimada'])) / 86400)
                : 0;
        }
        unset($fila);

        return $filas;
    }

    // =========================================================================================
    // REPORTE 5 — Bajas por período. Regla documentada explícitamente (aprobada): cuando el
    // filtro de estado es 'finalizada' (default), el rango de fechas se aplica sobre fecha_baja
    // (la única fecha real de retiro consumado). Para cualquier otro estado (o 'todos'), fecha_baja
    // puede ser NULL, así que el rango se aplica sobre fecha_preparacion (fecha de trámite, siempre
    // poblada) — nunca se inventa una fecha_baja que no existe.
    // =========================================================================================
    public function getBajasPorPeriodo(array $filtros): array
    {
        $estado = $filtros['estado'] ?? 'finalizada';
        $columnaFecha = $estado === 'finalizada' ? 'ba.fecha_baja' : 'ba.fecha_preparacion';

        $condiciones = [];
        $params = [];

        if ($estado !== '' && in_array($estado, ['pendiente', 'autorizada', 'rechazada', 'finalizada'], true)) {
            $condiciones[] = 'ba.estado_baja = :estado';
            $params[':estado'] = $estado;
        }

        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = "{$columnaFecha} >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = "{$columnaFecha} <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['id_responsable_anterior'])) {
            $condiciones[] = 'db.id_responsable_anterior = :id_responsable_anterior';
            $params[':id_responsable_anterior'] = (int) $filtros['id_responsable_anterior'];
        }

        if (!empty($filtros['id_ubicacion_anterior'])) {
            $condiciones[] = 'db.id_ubicacion_anterior = :id_ubicacion_anterior';
            $params[':id_ubicacion_anterior'] = (int) $filtros['id_ubicacion_anterior'];
        }

        if (!empty($filtros['id_tipo_baja'])) {
            $condiciones[] = 'db.id_tipo_baja = :id_tipo_baja';
            $params[':id_tipo_baja'] = (int) $filtros['id_tipo_baja'];
        }

        if (!empty($filtros['id_ubicacion_bodega_destino'])) {
            $condiciones[] = 'ba.id_ubicacion_bodega_destino = :id_ubicacion_bodega_destino';
            $params[':id_ubicacion_bodega_destino'] = (int) $filtros['id_ubicacion_bodega_destino'];
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "
            SELECT
                ba.id_baja, ba.numero_baja, ba.fecha_preparacion, ba.fecha_autorizacion, ba.fecha_baja, ba.estado_baja,
                db.id_bien, db.codigo_interno_mostrado, db.codigo_sicoin_mostrado, db.descripcion_mostrada,
                db.valor_mostrado, tb.nombre_tipo_baja,
                ra.nombre_completo AS responsable_anterior, ua.nombre_ubicacion AS ubicacion_anterior,
                ub.nombre_ubicacion AS bodega_destino
            FROM bajas ba
            INNER JOIN detalle_baja db ON db.id_baja = ba.id_baja
            LEFT JOIN tipos_baja tb ON db.id_tipo_baja = tb.id_tipo_baja
            LEFT JOIN responsables ra ON db.id_responsable_anterior = ra.id_responsable
            LEFT JOIN ubicaciones ua ON db.id_ubicacion_anterior = ua.id_ubicacion
            LEFT JOIN ubicaciones ub ON ba.id_ubicacion_bodega_destino = ub.id_ubicacion
            {$where}
            ORDER BY ba.fecha_baja DESC, ba.fecha_preparacion DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    // =========================================================================================
    // REPORTE 6 — Verificaciones con diferencias (usa exclusivamente los snapshots históricos
    // id_responsable_registrado/id_ubicacion_registrada/condicion_registrada, nunca el estado
    // actual del bien).
    // =========================================================================================
    public function getVerificacionesConDiferencias(array $filtros): array
    {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = 'vf.fecha_hora >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'] . ' 00:00:00';
        }

        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = 'vf.fecha_hora <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        $resultado = $filtros['resultado'] ?? '1';
        if ($resultado !== '') {
            $condiciones[] = 'vf.tiene_diferencias = :tiene_diferencias';
            $params[':tiene_diferencias'] = (int) $resultado;
        }

        if (isset($filtros['localizado']) && $filtros['localizado'] !== '') {
            $condiciones[] = 'vf.bien_localizado = :bien_localizado';
            $params[':bien_localizado'] = (int) $filtros['localizado'];
        }

        if (!empty($filtros['id_responsable_registrado'])) {
            $condiciones[] = 'vf.id_responsable_registrado = :id_responsable_registrado';
            $params[':id_responsable_registrado'] = (int) $filtros['id_responsable_registrado'];
        }

        if (!empty($filtros['id_ubicacion_registrada'])) {
            $condiciones[] = 'vf.id_ubicacion_registrada = :id_ubicacion_registrada';
            $params[':id_ubicacion_registrada'] = (int) $filtros['id_ubicacion_registrada'];
        }

        if (!empty($filtros['condicion_registrada'])) {
            $condiciones[] = 'vf.condicion_registrada = :condicion_registrada';
            $params[':condicion_registrada'] = $filtros['condicion_registrada'];
        }

        if (!empty($filtros['condicion_observada'])) {
            $condiciones[] = 'vf.condicion_observada = :condicion_observada';
            $params[':condicion_observada'] = $filtros['condicion_observada'];
        }

        if (!empty($filtros['id_usuario_verifica'])) {
            $condiciones[] = 'vf.id_usuario_verifica = :id_usuario_verifica';
            $params[':id_usuario_verifica'] = (int) $filtros['id_usuario_verifica'];
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "
            SELECT
                vf.id_verificacion, vf.fecha_hora, vf.id_bien,
                b.codigo_interno, b.descripcion,
                rr.nombre_completo AS responsable_registrado,
                ur.nombre_ubicacion AS ubicacion_registrada,
                vf.condicion_registrada, vf.bien_localizado, vf.responsable_correcto, vf.ubicacion_correcta,
                vf.condicion_observada, vf.tiene_diferencias, vf.observaciones,
                usr.nombre_completo AS usuario_verifica
            FROM verificaciones_fisicas vf
            INNER JOIN bienes b ON vf.id_bien = b.id_bien
            LEFT JOIN responsables rr ON vf.id_responsable_registrado = rr.id_responsable
            LEFT JOIN ubicaciones ur ON vf.id_ubicacion_registrada = ur.id_ubicacion
            LEFT JOIN usuarios usr ON vf.id_usuario_verifica = usr.id_usuario
            {$where}
            ORDER BY vf.fecha_hora DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    // =========================================================================================
    // REPORTE 7 — Ingresos de bienes por período
    // =========================================================================================
    public function getIngresos(array $filtros): array
    {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = 'b.fecha_ingreso >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = 'b.fecha_ingreso <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['id_forma_ingreso'])) {
            $condiciones[] = 'b.id_forma_ingreso = :id_forma_ingreso';
            $params[':id_forma_ingreso'] = (int) $filtros['id_forma_ingreso'];
        }

        if (!empty($filtros['id_categoria'])) {
            $condiciones[] = 'b.id_categoria = :id_categoria';
            $params[':id_categoria'] = (int) $filtros['id_categoria'];
        }

        if (!empty($filtros['id_estado_bien'])) {
            $condiciones[] = 'b.id_estado_bien = :id_estado_bien';
            $params[':id_estado_bien'] = (int) $filtros['id_estado_bien'];
        }

        if (!empty($filtros['procedencia'])) {
            // BUG REAL detectado y corregido (revisión de usabilidad): antes se buscaba solo en
            // idn.entidad_donante para Donación, ignorando idn.procedencia — pero la columna que
            // realmente se MUESTRA usa entidad_donante si no está vacío y, si lo está, cae a
            // procedencia (ver el foreach de normalización más abajo). Con datos reales, buscar por
            // el texto genérico de procedencia (ej. "Donación institucional") daba 0 resultados
            // aunque esa fuera la procedencia real de varias donaciones. El CASE aquí replica EXACTAMENTE
            // la misma prioridad que el PHP de abajo, para que el filtro busque sobre el mismo valor
            // normalizado que se muestra en la tabla — nunca sobre un campo distinto al mostrado.
            $condiciones[] = "(
                ic.proveedor LIKE :procedencia_1
                OR (CASE WHEN idn.entidad_donante IS NOT NULL AND idn.entidad_donante <> '' THEN idn.entidad_donante ELSE idn.procedencia END) LIKE :procedencia_2
                OR itr.procedencia LIKE :procedencia_3
            )";
            $like = '%' . trim($filtros['procedencia']) . '%';
            $params[':procedencia_1'] = $like;
            $params[':procedencia_2'] = $like;
            $params[':procedencia_3'] = $like;
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "
            SELECT
                b.id_bien, b.fecha_ingreso, b.codigo_interno, b.codigo_sicoin, b.descripcion,
                fi.nombre_forma, cb.nombre_categoria, b.costo, b.valor_estimado, eb.nombre_estado,
                ic.proveedor AS compra_proveedor,
                idn.entidad_donante AS donacion_entidad, idn.procedencia AS donacion_procedencia,
                itr.procedencia AS traslado_procedencia
            FROM bienes b
            LEFT JOIN formas_ingreso fi ON b.id_forma_ingreso = fi.id_forma_ingreso
            LEFT JOIN categorias_bien cb ON b.id_categoria = cb.id_categoria
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN ingreso_compra ic ON ic.id_bien = b.id_bien
            LEFT JOIN ingreso_donacion idn ON idn.id_bien = b.id_bien
            LEFT JOIN ingreso_traslado itr ON itr.id_bien = b.id_bien
            {$where}
            ORDER BY b.fecha_ingreso DESC
        ";

        $filas = $this->fetchAll($sql, $params);

        foreach ($filas as &$fila) {
            $fila['valor'] = $fila['costo'] !== null ? $fila['costo'] : $fila['valor_estimado'];

            if ($fila['compra_proveedor'] !== null && $fila['compra_proveedor'] !== '') {
                $fila['procedencia'] = $fila['compra_proveedor'];
            } elseif ($fila['donacion_entidad'] !== null && $fila['donacion_entidad'] !== '') {
                $fila['procedencia'] = $fila['donacion_entidad'];
            } elseif ($fila['donacion_procedencia'] !== null && $fila['donacion_procedencia'] !== '') {
                $fila['procedencia'] = $fila['donacion_procedencia'];
            } elseif ($fila['traslado_procedencia'] !== null && $fila['traslado_procedencia'] !== '') {
                $fila['procedencia'] = $fila['traslado_procedencia'];
            } else {
                $fila['procedencia'] = null;
            }
        }
        unset($fila);

        return $filas;
    }

    // =========================================================================================
    // REPORTE 8 — Resumen de movimientos por período. Operaciones = id_operacion ÚNICOS por tipo
    // (no COUNT(*) de filas por bien). Bienes involucrados = id_bien ÚNICOS por tipo. Reutiliza los
    // mismos eventos normalizados de Movimientos/Bienes con actividad.
    // =========================================================================================
    public function getResumenMovimientos(array $filtros): array
    {
        $eventos = $this->getEventosNormalizados($filtros['fecha_desde'], $filtros['fecha_hasta']);

        $porTipo = [];
        foreach (self::TIPOS_EVENTO as $tipo) {
            $porTipo[$tipo] = ['operaciones' => [], 'bienes' => []];
        }

        $totalOperaciones = [];
        $totalBienes = [];

        foreach ($eventos as $evento) {
            $porTipo[$evento['tipo']]['operaciones'][$evento['id_operacion']] = true;
            $porTipo[$evento['tipo']]['bienes'][$evento['id_bien']] = true;
            $totalOperaciones[$evento['id_operacion']] = true;
            $totalBienes[$evento['id_bien']] = true;
        }

        $filas = [];
        foreach ($porTipo as $tipo => $datos) {
            $filas[] = [
                'tipo' => $tipo,
                'operaciones' => count($datos['operaciones']),
                'bienes_involucrados' => count($datos['bienes']),
            ];
        }

        return [
            'filas' => $filas,
            'total_operaciones' => count($totalOperaciones),
            'total_bienes_unicos' => count($totalBienes),
        ];
    }

    // =========================================================================================
    // Utilidades privadas
    // =========================================================================================
    private function formatearFlecha(?string $origen, ?string $destino): string
    {
        $origenTexto = ($origen !== null && $origen !== '') ? $origen : self::SEPARADOR_VACIO;
        $destinoTexto = ($destino !== null && $destino !== '') ? $destino : self::SEPARADOR_VACIO;

        return $origenTexto . ' → ' . $destinoTexto;
    }

    private function intOrNull($valor): ?int
    {
        return $valor !== null ? (int) $valor : null;
    }
}
