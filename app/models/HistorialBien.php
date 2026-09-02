<?php

require_once __DIR__ . '/../core/Model.php';

// Trazabilidad funcional de UN bien: reconstruye "qué le ha pasado a este bien" a lo largo del
// tiempo a partir de las tablas que YA existen (no crea ninguna tabla ni escribe nada — cero
// INSERT/UPDATE/DELETE en todo el archivo). Distinto de Bitácora (quién hizo qué acción de sistema).
//
// Patrón idéntico a ReportesService: una consulta parametrizada por fuente, cada resultado
// normalizado al mismo array de evento, y luego array_merge + usort en PHP (nunca UNION ALL).
//
// Regla de deduplicación (aprobada): NO se emite un evento por cada fila de detalle_asignacion.
// Cada alta/retiro de detalle_asignacion en este sistema es SIEMPRE consecuencia de uno de los
// eventos funcionales que sí se emiten (Ingreso -> resguardo inicial en Bodega; Requisición
// entregada; Traslado; Baja), y esos eventos ya llevan responsable/ubicación. La asignación manual
// está deshabilitada. El resguardo inicial se muestra dentro del evento "Ingreso". Tampoco se
// emiten las emisiones de Tarjeta de Responsabilidad (son reimpresiones de un documento, no hechos
// del bien) ni las líneas derivadas de REGULARIZACION de SICOIN (su fuente única es historial_sicoin).
class HistorialBien extends Model
{
    // Estructura de cada evento devuelto:
    //   fecha        string 'Y-m-d H:i:s' o 'Y-m-d'  (clave de orden; comparación lexicográfica = cronológica)
    //   es_datetime  bool   (true si la fuente tiene hora real; false si solo DATE)
    //   tipo         string (Ingreso | Requisición | Traslado | Préstamo | Devolución |
    //                        Verificación física | SICOIN | Baja | Modificación de información)
    //   detalles     array|null (solo "Modificación de información": lista de
    //                        ['campo','valor_anterior','valor_nuevo'] para render estructurado)
    //   usuario      string|null (solo "Modificación de información": nombre de quien editó)
    //   titulo       string corto
    //   descripcion  string frase
    //   referencia   string|null  (número de documento)
    //   url_detalle  string|null  (ruta a la ficha del documento origen)
    public function getEventos(int $idBien): array
    {
        $eventos = array_merge(
            $this->eventoIngreso($idBien),
            $this->eventosRequisicion($idBien),
            $this->eventosTraslado($idBien),
            $this->eventosPrestamo($idBien),
            $this->eventosDevolucion($idBien),
            $this->eventosVerificacion($idBien),
            $this->eventosSicoin($idBien),
            $this->eventosBaja($idBien),
            $this->eventosModificacion($idBien)
        );

        // Más reciente primero. Desempate estable por tipo para que dos eventos del mismo instante
        // (p. ej. verificación por asignación) queden en un orden determinista.
        usort($eventos, static function (array $a, array $b): int {
            return ($b['fecha'] <=> $a['fecha'])
                ?: ($b['tipo'] <=> $a['tipo']);
        });

        return $eventos;
    }

    // Tipos presentes en un historial, para el filtro opcional de la vista.
    public function getTiposPresentes(array $eventos): array
    {
        $tipos = [];
        foreach ($eventos as $evento) {
            $tipos[$evento['tipo']] = true;
        }
        return array_keys($tipos);
    }

    // ---------------------------------------------------------------------------------------
    // INGRESO — 1 evento. Incluye forma de ingreso, procedencia/proveedor, código interno, SICOIN
    // vigente en ese momento (reconstruido) y el resguardo inicial (primer detalle_asignacion).
    //
    // Los datos MUTABLES del ingreso (fecha de ingreso, proveedor, entidad donante, procedencia,
    // unidad ejecutora de origen) salen del SNAPSHOT congelado `ingreso_bien_original` cuando existe
    // — así una edición posterior desde "Modificar bien" NO reescribe este evento histórico; el
    // cambio se ve aparte, en el evento "Modificación de información". COALESCE(snapshot, valor vivo)
    // deja como fallback el comportamiento anterior si por algún dato antiguo faltara el snapshot
    // (tras la migración de backfill todo bien tiene uno, así que el fallback es excepcional).
    // NO se congelan aquí: codigo_interno (inmutable), forma de ingreso (bloqueada), SICOIN
    // ("al ingreso" ya se reconstruye de historial_sicoin) ni el resguardo inicial (detalle_asignacion).
    // ---------------------------------------------------------------------------------------
    private function eventoIngreso(int $idBien): array
    {
        $sql = "
            SELECT
                b.codigo_interno,
                b.codigo_sicoin,
                COALESCE(ibo.fecha_ingreso_original, b.fecha_ingreso) AS fecha_ingreso,
                fi.nombre_forma,
                COALESCE(ibo.proveedor_original, ic.proveedor) AS compra_proveedor,
                COALESCE(ibo.entidad_donante_original, idn.entidad_donante) AS donacion_entidad,
                COALESCE(ibo.procedencia_donacion_original, idn.procedencia) AS donacion_procedencia,
                COALESCE(ibo.procedencia_traslado_original, itr.procedencia) AS traslado_procedencia,
                COALESCE(ibo.unidad_ejecutora_origen_original, itr.unidad_ejecutora_origen) AS traslado_unidad,
                (SELECT COUNT(*) FROM historial_sicoin h WHERE h.id_bien = b.id_bien) AS n_hist_sicoin,
                (
                    SELECT h.sicoin_anterior
                    FROM historial_sicoin h
                    WHERE h.id_bien = b.id_bien
                    ORDER BY h.fecha_cambio ASC, h.id_historial_sicoin ASC
                    LIMIT 1
                ) AS sicoin_anterior_primero
            FROM bienes b
            LEFT JOIN formas_ingreso fi ON b.id_forma_ingreso = fi.id_forma_ingreso
            LEFT JOIN ingreso_compra ic ON ic.id_bien = b.id_bien
            LEFT JOIN ingreso_donacion idn ON idn.id_bien = b.id_bien
            LEFT JOIN ingreso_traslado itr ON itr.id_bien = b.id_bien
            LEFT JOIN ingreso_bien_original ibo ON ibo.id_bien = b.id_bien
            WHERE b.id_bien = :id_bien
            LIMIT 1
        ";

        $fila = $this->fetchOne($sql, [':id_bien' => $idBien]);

        if ($fila === false) {
            return [];
        }

        $forma = trim((string) ($fila['nombre_forma'] ?? ''));
        $formaLower = $this->quitarAcentos(mb_strtolower($forma, 'UTF-8'));

        $procedencia = null;
        if ($formaLower === 'compra') {
            $procedencia = trim((string) ($fila['compra_proveedor'] ?? '')) ?: null;
        } elseif ($formaLower === 'donacion') {
            $procedencia = trim((string) ($fila['donacion_entidad'] ?? '')) ?: (trim((string) ($fila['donacion_procedencia'] ?? '')) ?: null);
        } elseif ($formaLower === 'traslado') {
            $procedencia = trim((string) ($fila['traslado_unidad'] ?? '')) ?: (trim((string) ($fila['traslado_procedencia'] ?? '')) ?: null);
        }

        // SICOIN vigente al ingreso: si nunca hubo cambio en historial_sicoin, el actual es el mismo
        // de siempre; si hubo cambios, el valor previo al primero es el que existía al ingresar.
        $sicoinIngreso = ((int) ($fila['n_hist_sicoin'] ?? 0) > 0)
            ? ($fila['sicoin_anterior_primero'] ?? null)
            : ($fila['codigo_sicoin'] ?? null);
        $sicoinIngreso = ($sicoinIngreso !== null && trim((string) $sicoinIngreso) !== '') ? trim((string) $sicoinIngreso) : null;

        $resguardo = $this->fetchOne(
            "
                SELECT a.numero_asignacion, r.nombre_completo AS responsable, u.nombre_ubicacion AS ubicacion
                FROM detalle_asignacion da
                INNER JOIN asignaciones a ON da.id_asignacion = a.id_asignacion
                LEFT JOIN responsables r ON a.id_responsable = r.id_responsable
                LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id_ubicacion
                WHERE da.id_bien = :id_bien
                ORDER BY da.fecha_agregado ASC, da.id_detalle_asignacion ASC
                LIMIT 1
            ",
            [':id_bien' => $idBien]
        );

        $partes = [];
        $partes[] = 'Forma de ingreso: ' . ($forma !== '' ? $forma : '—') . '.';
        if ($procedencia !== null) {
            $partes[] = ($formaLower === 'compra' ? 'Proveedor: ' : 'Procedencia: ') . $procedencia . '.';
        }
        $partes[] = 'SICOIN al ingreso: ' . ($sicoinIngreso ?? 'sin SICOIN') . '.';
        if ($resguardo !== false) {
            $partes[] = 'Resguardo inicial: ' . ($resguardo['responsable'] ?? '—')
                . ' — ' . ($resguardo['ubicacion'] ?? '—')
                . ($resguardo['numero_asignacion'] ? ' (' . $resguardo['numero_asignacion'] . ')' : '') . '.';
        }

        return [[
            'fecha' => (string) $fila['fecha_ingreso'],
            'es_datetime' => false,
            'tipo' => 'Ingreso',
            'titulo' => 'Ingreso del bien',
            'descripcion' => implode(' ', $partes),
            'referencia' => (string) $fila['codigo_interno'],
            'url_detalle' => null,
        ]];
    }

    // ---------------------------------------------------------------------------------------
    // REQUISICIÓN — solo entregadas (fecha_entrega DATETIME). Una por detalle_requisicion.
    // ---------------------------------------------------------------------------------------
    private function eventosRequisicion(int $idBien): array
    {
        $sql = "
            SELECT
                r.id_requisicion,
                r.numero_requisicion_sistema,
                r.fecha_entrega,
                r.responsable_solicitante_mostrado,
                r.ubicacion_solicitante_mostrada
            FROM detalle_requisicion dr
            INNER JOIN requisiciones r ON dr.id_requisicion = r.id_requisicion
            WHERE dr.id_bien = :id_bien
              AND r.estado_requisicion = 'Entregada'
              AND r.fecha_entrega IS NOT NULL
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $eventos[] = [
                'fecha' => (string) $fila['fecha_entrega'],
                'es_datetime' => true,
                'tipo' => 'Requisición',
                'titulo' => 'Entregado por requisición',
                'descripcion' => 'Entregado mediante la requisición ' . $fila['numero_requisicion_sistema'] . '. '
                    . 'Responsable: ' . ($fila['responsable_solicitante_mostrado'] ?? '—') . '. '
                    . 'Ubicación: ' . ($fila['ubicacion_solicitante_mostrada'] ?? '—') . '.',
                'referencia' => (string) $fila['numero_requisicion_sistema'],
                'url_detalle' => 'index.php?modulo=requisiciones&accion=ver&id=' . (int) $fila['id_requisicion'],
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // TRASLADO — detalle_movimiento de movimientos tipo "Traslado" (fecha_movimiento DATETIME).
    // ---------------------------------------------------------------------------------------
    private function eventosTraslado(int $idBien): array
    {
        $sql = "
            SELECT
                m.id_movimiento,
                m.numero_movimiento,
                m.fecha_movimiento,
                ro.nombre_completo AS responsable_origen,
                rd.nombre_completo AS responsable_destino,
                uo.nombre_ubicacion AS ubicacion_origen,
                ud.nombre_ubicacion AS ubicacion_destino,
                dm.condicion_anterior,
                dm.condicion_nueva
            FROM detalle_movimiento dm
            INNER JOIN movimientos m ON dm.id_movimiento = m.id_movimiento
            INNER JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
            LEFT JOIN responsables ro ON m.id_responsable_origen = ro.id_responsable
            LEFT JOIN responsables rd ON m.id_responsable_destino = rd.id_responsable
            LEFT JOIN ubicaciones uo ON m.id_ubicacion_origen = uo.id_ubicacion
            LEFT JOIN ubicaciones ud ON m.id_ubicacion_destino = ud.id_ubicacion
            WHERE dm.id_bien = :id_bien
              AND tm.nombre_movimiento = 'Traslado'
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $desc = 'Trasladado de ' . ($fila['responsable_origen'] ?? '—') . ' (' . ($fila['ubicacion_origen'] ?? '—') . ')'
                . ' a ' . ($fila['responsable_destino'] ?? '—') . ' (' . ($fila['ubicacion_destino'] ?? '—') . ').';

            $condAnt = trim((string) ($fila['condicion_anterior'] ?? ''));
            $condNue = trim((string) ($fila['condicion_nueva'] ?? ''));
            if ($condAnt !== '' && $condNue !== '' && $condAnt !== $condNue) {
                $desc .= ' Condición: ' . $condAnt . ' → ' . $condNue . '.';
            }

            $eventos[] = [
                'fecha' => (string) $fila['fecha_movimiento'],
                'es_datetime' => true,
                'tipo' => 'Traslado',
                'titulo' => 'Traslado',
                'descripcion' => $desc,
                'referencia' => (string) $fila['numero_movimiento'],
                'url_detalle' => 'index.php?modulo=traslados&accion=ver&id=' . (int) $fila['id_movimiento'],
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // PRÉSTAMO — detalle_prestamo (fecha_prestamo DATE).
    // ---------------------------------------------------------------------------------------
    private function eventosPrestamo(int $idBien): array
    {
        $sql = "
            SELECT
                p.id_prestamo,
                p.numero_prestamo,
                p.fecha_prestamo,
                p.estado_prestamo,
                p.responsable_origen_mostrado,
                p.responsable_destino_mostrado,
                p.ubicacion_destino_mostrada,
                dp.condicion_entrega
            FROM detalle_prestamo dp
            INNER JOIN prestamos p ON dp.id_prestamo = p.id_prestamo
            WHERE dp.id_bien = :id_bien
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $eventos[] = [
                'fecha' => (string) $fila['fecha_prestamo'],
                'es_datetime' => false,
                'tipo' => 'Préstamo',
                'titulo' => 'Préstamo',
                'descripcion' => 'Prestado por ' . ($fila['responsable_origen_mostrado'] ?? '—')
                    . ' a ' . ($fila['responsable_destino_mostrado'] ?? '—')
                    . ' (' . ($fila['ubicacion_destino_mostrada'] ?? '—') . ').'
                    . ' Condición de entrega: ' . ($fila['condicion_entrega'] ?? '—') . '.'
                    . ' Estado del préstamo: ' . ($fila['estado_prestamo'] ?? '—') . '.',
                'referencia' => (string) $fila['numero_prestamo'],
                'url_detalle' => 'index.php?modulo=prestamos&accion=ver&id=' . (int) $fila['id_prestamo'],
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // DEVOLUCIÓN — detalle_devolucion (fecha_devolucion DATE).
    // ---------------------------------------------------------------------------------------
    private function eventosDevolucion(int $idBien): array
    {
        $sql = "
            SELECT
                d.id_devolucion,
                d.numero_devolucion,
                d.fecha_devolucion,
                d.estado_devolucion,
                dd.condicion_devolucion,
                p.numero_prestamo
            FROM detalle_devolucion dd
            INNER JOIN devoluciones d ON dd.id_devolucion = d.id_devolucion
            LEFT JOIN prestamos p ON d.id_prestamo = p.id_prestamo
            WHERE dd.id_bien = :id_bien
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $eventos[] = [
                'fecha' => (string) $fila['fecha_devolucion'],
                'es_datetime' => false,
                'tipo' => 'Devolución',
                'titulo' => 'Devolución',
                'descripcion' => 'Devolución ' . $fila['numero_devolucion']
                    . ($fila['numero_prestamo'] ? ' del préstamo ' . $fila['numero_prestamo'] : '') . '. '
                    . 'Condición de devolución: ' . ($fila['condicion_devolucion'] ?? '—') . '. '
                    . 'Estado: ' . ($fila['estado_devolucion'] ?? '—') . '.',
                'referencia' => (string) $fila['numero_devolucion'],
                'url_detalle' => 'index.php?modulo=devoluciones&accion=ver&id=' . (int) $fila['id_devolucion'],
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // VERIFICACIÓN FÍSICA — verificaciones_fisicas (fecha_hora DATETIME). Individual / Por asignación.
    // ---------------------------------------------------------------------------------------
    private function eventosVerificacion(int $idBien): array
    {
        $sql = "
            SELECT
                vf.id_verificacion,
                vf.fecha_hora,
                vf.bien_localizado,
                vf.tiene_diferencias,
                vf.id_verificacion_asignacion,
                va.numero_asignacion AS verificacion_asignacion_numero
            FROM verificaciones_fisicas vf
            LEFT JOIN verificaciones_asignacion va ON vf.id_verificacion_asignacion = va.id_verificacion_asignacion
            WHERE vf.id_bien = :id_bien
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $porAsignacion = $fila['id_verificacion_asignacion'] !== null;
            $localizado = (int) $fila['bien_localizado'] === 1;
            $conDiferencias = (int) $fila['tiene_diferencias'] === 1;

            $desc = 'Verificación ' . ($porAsignacion ? 'por asignación' : 'individual') . '. '
                . 'Localizado: ' . ($localizado ? 'Sí' : 'No') . '. '
                . 'Resultado: ' . ($conDiferencias ? 'Con diferencias' : 'Sin diferencias') . '.';
            if ($porAsignacion && $fila['verificacion_asignacion_numero']) {
                $desc .= ' Jornada de la asignación ' . $fila['verificacion_asignacion_numero'] . '.';
            }

            $eventos[] = [
                'fecha' => (string) $fila['fecha_hora'],
                'es_datetime' => true,
                'tipo' => 'Verificación física',
                'titulo' => 'Verificación física',
                'descripcion' => $desc,
                'referencia' => $porAsignacion ? ($fila['verificacion_asignacion_numero'] ?: null) : null,
                'url_detalle' => 'index.php?modulo=verificaciones&accion=ver&id=' . (int) $fila['id_verificacion'],
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // SICOIN — historial_sicoin (fecha_cambio DATETIME). "Sin SICOIN → 123456", etc.
    // ---------------------------------------------------------------------------------------
    private function eventosSicoin(int $idBien): array
    {
        $sql = "
            SELECT id_historial_sicoin, fecha_cambio, sicoin_anterior, sicoin_nuevo
            FROM historial_sicoin
            WHERE id_bien = :id_bien
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $anterior = ($fila['sicoin_anterior'] !== null && trim((string) $fila['sicoin_anterior']) !== '')
                ? trim((string) $fila['sicoin_anterior'])
                : 'Sin SICOIN';

            $eventos[] = [
                'fecha' => (string) $fila['fecha_cambio'],
                'es_datetime' => true,
                'tipo' => 'SICOIN',
                'titulo' => 'Registro / cambio de SICOIN',
                'descripcion' => 'Código SICOIN: ' . $anterior . ' → ' . $fila['sicoin_nuevo'] . '.',
                'referencia' => (string) $fila['sicoin_nuevo'],
                'url_detalle' => null,
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // BAJA — detalle_baja + bajas. Se emite una por cada baja que incluyó el bien, con su estado
    // real. Fecha según el estado: finalizada -> fecha_baja (DATE, con respaldo en fecha_preparacion);
    // rechazada -> fecha_rechazo (DATETIME); pendiente/autorizada -> fecha_preparacion (DATETIME).
    // ---------------------------------------------------------------------------------------
    private function eventosBaja(int $idBien): array
    {
        $sql = "
            SELECT
                ba.id_baja,
                ba.numero_baja,
                ba.estado_baja,
                ba.fecha_baja,
                ba.fecha_preparacion,
                ba.fecha_rechazo,
                tb.nombre_tipo_baja,
                db.justificacion
            FROM detalle_baja db
            INNER JOIN bajas ba ON db.id_baja = ba.id_baja
            LEFT JOIN tipos_baja tb ON db.id_tipo_baja = tb.id_tipo_baja
            WHERE db.id_bien = :id_bien
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $estado = (string) $fila['estado_baja'];

            if ($estado === 'finalizada') {
                $fecha = (string) ($fila['fecha_baja'] ?: $fila['fecha_preparacion']);
                $esDatetime = $fila['fecha_baja'] ? false : true;
                $titulo = 'Baja del bien';
            } elseif ($estado === 'rechazada') {
                $fecha = (string) ($fila['fecha_rechazo'] ?: $fila['fecha_preparacion']);
                $esDatetime = true;
                $titulo = 'Solicitud de baja rechazada';
            } else {
                $fecha = (string) $fila['fecha_preparacion'];
                $esDatetime = true;
                $titulo = 'Solicitud de baja en proceso';
            }

            $justificacion = trim((string) ($fila['justificacion'] ?? ''));
            if (mb_strlen($justificacion, 'UTF-8') > 160) {
                $justificacion = mb_substr($justificacion, 0, 160, 'UTF-8') . '…';
            }

            $eventos[] = [
                'fecha' => $fecha,
                'es_datetime' => $esDatetime,
                'tipo' => 'Baja',
                'titulo' => $titulo,
                'descripcion' => 'Baja ' . $fila['numero_baja'] . '. '
                    . 'Tipo: ' . ($fila['nombre_tipo_baja'] ?? '—') . '. '
                    . 'Estado: ' . $estado . '.'
                    . ($justificacion !== '' ? ' Justificación: ' . $justificacion : ''),
                'referencia' => (string) $fila['numero_baja'],
                'url_detalle' => 'index.php?modulo=bajas&accion=ver&id=' . (int) $fila['id_baja'],
            ];
        }

        return $eventos;
    }

    // ---------------------------------------------------------------------------------------
    // MODIFICACIÓN DE INFORMACIÓN — historial_modificaciones_bien (fecha_hora DATETIME). Un evento
    // por `grupo_cambio` (un guardado de "Modificar bien"), con todos los campos que cambiaron en
    // ese guardado. NO incluye SICOIN (su fuente es historial_sicoin -> eventosSicoin()) ni estado
    // del bien (deja de ser editable desde Modificar). Consulta propia, mismo patrón self-contained
    // que el resto de este modelo (no llama a HistorialModificacionBien).
    // ---------------------------------------------------------------------------------------
    private function eventosModificacion(int $idBien): array
    {
        $sql = "
            SELECT
                h.grupo_cambio,
                h.campo,
                h.valor_anterior,
                h.valor_nuevo,
                h.fecha_hora,
                u.nombre_completo AS usuario_nombre
            FROM historial_modificaciones_bien h
            LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
            WHERE h.id_bien = :id_bien
            ORDER BY h.fecha_hora ASC, h.grupo_cambio ASC, h.id_historial_modificacion ASC
        ";

        $grupos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $clave = (string) $fila['grupo_cambio'];

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'fecha' => (string) $fila['fecha_hora'],
                    'usuario' => trim((string) ($fila['usuario_nombre'] ?? '')),
                    'lineas' => [],
                    'detalles' => [],
                ];
            }

            $anterior = ($fila['valor_anterior'] !== null && trim((string) $fila['valor_anterior']) !== '')
                ? (string) $fila['valor_anterior']
                : 'Sin registro';
            $nuevo = ($fila['valor_nuevo'] !== null && trim((string) $fila['valor_nuevo']) !== '')
                ? (string) $fila['valor_nuevo']
                : 'Sin registro';

            $grupos[$clave]['lineas'][] = $fila['campo'] . ': ' . $anterior . ' → ' . $nuevo . '.';
            $grupos[$clave]['detalles'][] = [
                'campo' => (string) $fila['campo'],
                'valor_anterior' => $anterior,
                'valor_nuevo' => $nuevo,
            ];
        }

        $eventos = [];
        foreach ($grupos as $grupo) {
            $totalCampos = count($grupo['detalles']);
            $eventos[] = [
                'fecha' => $grupo['fecha'],
                'es_datetime' => true,
                'tipo' => 'Modificación de información',
                'titulo' => 'Modificación de información',
                'descripcion' => ($totalCampos === 1 ? 'Se modificó 1 campo del bien.' : 'Se modificaron ' . $totalCampos . ' campos del bien.')
                    . ($grupo['usuario'] !== '' ? ' Modificado por ' . $grupo['usuario'] . '.' : ''),
                'detalles' => $grupo['detalles'],
                'usuario' => $grupo['usuario'] !== '' ? $grupo['usuario'] : null,
                'referencia' => null,
                'url_detalle' => null,
            ];
        }

        return $eventos;
    }

    private function quitarAcentos(string $texto): string
    {
        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $texto
        );
    }
}
