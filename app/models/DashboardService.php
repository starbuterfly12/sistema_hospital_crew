<?php

require_once __DIR__ . '/../core/Model.php';

// Capa de agregación de solo lectura para el Dashboard. Igual que ReportesService, combina tablas
// de otros módulos SIN modificarlas — cero INSERT/UPDATE/DELETE en este archivo. A diferencia de
// ReportesService (que arma listados completos por período), aquí cada método hace COUNT(*) o
// SELECT ... LIMIT acotado: el Dashboard se abre con mucha frecuencia y nunca necesita traer más
// filas de las que realmente va a mostrar.
class DashboardService extends Model
{
    // =========================================================================================
    // Indicadores principales
    // =========================================================================================
    public function getIndicadoresPrincipales(): array
    {
        return [
            'bienes_registrados' => $this->contarBienesRegistrados(),
            'bienes_activos' => $this->contarBienesPorEstado('Activo'),
            'bienes_baja' => $this->contarBienesPorEstado('Baja'),
            'prestamos_activos' => $this->contarPrestamosActivos(),
        ];
    }

    private function contarBienesRegistrados(): int
    {
        return (int) $this->fetchOne('SELECT COUNT(*) AS total FROM bienes')['total'];
    }

    // Compara por nombre_estado (no por id fijo) para no depender de que el id de "Activo"/"Baja"
    // en estados_bien nunca cambie.
    private function contarBienesPorEstado(string $nombreEstado): int
    {
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM bienes b
             INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
             WHERE eb.nombre_estado = :nombre_estado',
            [':nombre_estado' => $nombreEstado]
        );

        return (int) $fila['total'];
    }

    // "Activo" = todavía tiene bienes con estado_detalle='prestado' pendientes de devolver. Mismo
    // criterio ya usado en ReportesService::getPrestamosPendientesVencidos(): estado_prestamo IN
    // ('activo', 'parcial'); 'finalizado' es el único estado terminal (ya no cuenta).
    private function contarPrestamosActivos(): int
    {
        $fila = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM prestamos WHERE estado_prestamo IN ('activo', 'parcial')"
        );

        return (int) $fila['total'];
    }

    // =========================================================================================
    // Pendientes de supervisión
    // =========================================================================================
    public function getPendientesSupervision(): array
    {
        return [
            'bajas_pendientes' => $this->contarBajasPendientes(),
            'prestamos_vencidos' => $this->contarPrestamosVencidos(),
            'proximos_vencer' => $this->contarPrestamosProximosAVencer(),
            'bienes_sin_sicoin' => $this->contarBienesSinSicoin(),
        ];
    }

    private function contarBajasPendientes(): int
    {
        $fila = $this->fetchOne("SELECT COUNT(*) AS total FROM bajas WHERE estado_baja = 'pendiente'");

        return (int) $fila['total'];
    }

    // Vencido = todavía activo/parcial (bienes pendientes de devolver) Y su fecha estimada de
    // devolución ya pasó respecto a HOY. CURDATE() del servidor de BD: confirmado en la misma zona
    // horaria configurada en PHP (America/Guatemala) al momento de esta implementación.
    private function contarPrestamosVencidos(): int
    {
        $fila = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM prestamos
             WHERE estado_prestamo IN ('activo', 'parcial')
               AND fecha_devolucion_estimada < CURDATE()"
        );

        return (int) $fila['total'];
    }

    // Próximos a vencer = activo/parcial con fecha estimada de devolución entre HOY y HOY+3 días,
    // ambos extremos incluidos. No se solapa con "vencidos" (ese usa "<", este usa ">=").
    private function contarPrestamosProximosAVencer(): int
    {
        $fila = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM prestamos
             WHERE estado_prestamo IN ('activo', 'parcial')
               AND fecha_devolucion_estimada >= CURDATE()
               AND fecha_devolucion_estimada <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
        );

        return (int) $fila['total'];
    }

    private function contarBienesSinSicoin(): int
    {
        $fila = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM bienes WHERE codigo_sicoin IS NULL OR TRIM(codigo_sicoin) = ''"
        );

        return (int) $fila['total'];
    }

    // =========================================================================================
    // Últimos movimientos — versión acotada del mismo enfoque de ReportesService::
    // getEventosNormalizados() ("una fuente por tipo, normalizar, combinar en PHP"), pero cada
    // fuente se consulta con ORDER BY + LIMIT en vez de un rango de fechas completo: así nunca se
    // trae el historial entero solo para quedarse con 5 filas. Los tipos y las reglas de qué cuenta
    // como movimiento (solo Requisiciones 'Entregada', solo Bajas 'finalizada') son exactamente las
    // mismas que ya usa Reportes > Movimientos por período.
    // =========================================================================================
    public function getUltimosMovimientos(int $limite = 5): array
    {
        // LIMIT va como entero ya controlado directamente en el SQL (mismo patrón que
        // Bitacora::getAll()): max(1, ...) fuerza un entero positivo antes de interpolarlo.
        $limite = max(1, $limite);

        $eventos = array_merge(
            $this->ultimosTraslados($limite),
            $this->ultimosPrestamos($limite),
            $this->ultimasDevoluciones($limite),
            $this->ultimasRequisicionesEntregadas($limite),
            $this->ultimasBajasFinalizadas($limite),
            $this->ultimasVerificaciones($limite)
        );

        usort($eventos, static fn(array $a, array $b): int => $b['fecha'] <=> $a['fecha']);

        return array_slice($eventos, 0, $limite);
    }

    // movimientos no tiene ninguna columna de estado (ver DESCRIBE movimientos): un Traslado
    // registrado es, en sí mismo, la constancia de un movimiento ya ejecutado. No hay un estado
    // funcional confiable que mostrar aquí, así que se deja '—' en vez de inventar uno.
    private function ultimosTraslados(int $limite): array
    {
        $sql = "
            SELECT m.fecha_movimiento AS fecha, b.codigo_interno, b.codigo_sicoin, b.descripcion
            FROM movimientos m
            INNER JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
            INNER JOIN detalle_movimiento dm ON dm.id_movimiento = m.id_movimiento
            INNER JOIN bienes b ON dm.id_bien = b.id_bien
            WHERE tm.nombre_movimiento = 'Traslado'
            ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC
            LIMIT {$limite}
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql) as $fila) {
            $eventos[] = $this->normalizar('Traslado', $fila['fecha'], true, $fila, '—');
        }

        return $eventos;
    }

    private function ultimosPrestamos(int $limite): array
    {
        $sql = "
            SELECT p.fecha_prestamo AS fecha, dp.codigo_interno_mostrado AS codigo_interno,
                dp.codigo_sicoin_mostrado AS codigo_sicoin, dp.descripcion_mostrada AS descripcion,
                p.estado_prestamo
            FROM prestamos p
            INNER JOIN detalle_prestamo dp ON dp.id_prestamo = p.id_prestamo
            ORDER BY p.fecha_prestamo DESC, p.id_prestamo DESC
            LIMIT {$limite}
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql) as $fila) {
            $eventos[] = $this->normalizar('Préstamo', $fila['fecha'], false, $fila, ucfirst((string) $fila['estado_prestamo']));
        }

        return $eventos;
    }

    private function ultimasDevoluciones(int $limite): array
    {
        // Igual que ReportesService::obtenerDevoluciones(): el bien se identifica a través del
        // detalle_prestamo relacionado (snapshot al momento del préstamo), no de la tabla bienes
        // actual — detalle_devolucion no guarda código/descripción propios.
        $sql = "
            SELECT d.fecha_devolucion AS fecha, dpr.codigo_interno_mostrado AS codigo_interno,
                dpr.codigo_sicoin_mostrado AS codigo_sicoin, dpr.descripcion_mostrada AS descripcion,
                d.estado_devolucion
            FROM devoluciones d
            INNER JOIN detalle_devolucion dd ON dd.id_devolucion = d.id_devolucion
            INNER JOIN detalle_prestamo dpr ON dd.id_detalle_prestamo = dpr.id_detalle_prestamo
            ORDER BY d.fecha_devolucion DESC, d.id_devolucion DESC
            LIMIT {$limite}
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql) as $fila) {
            $eventos[] = $this->normalizar('Devolución', $fila['fecha'], false, $fila, ucfirst((string) $fila['estado_devolucion']));
        }

        return $eventos;
    }

    // Misma regla que ReportesService::obtenerRequisicionesEntregadas(): solo 'Entregada' representa
    // un movimiento físico real de bienes.
    private function ultimasRequisicionesEntregadas(int $limite): array
    {
        $sql = "
            SELECT r.fecha_entrega AS fecha, dr.codigo_interno_mostrado AS codigo_interno,
                dr.codigo_sicoin_mostrado AS codigo_sicoin, dr.descripcion_mostrada AS descripcion,
                r.estado_requisicion
            FROM requisiciones r
            INNER JOIN detalle_requisicion dr ON dr.id_requisicion = r.id_requisicion
            WHERE r.estado_requisicion = 'Entregada' AND r.fecha_entrega IS NOT NULL
            ORDER BY r.fecha_entrega DESC, r.id_requisicion DESC
            LIMIT {$limite}
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql) as $fila) {
            $eventos[] = $this->normalizar('Requisición', $fila['fecha'], true, $fila, (string) $fila['estado_requisicion']);
        }

        return $eventos;
    }

    // Misma regla que ReportesService::obtenerBajasFinalizadas(): solo 'finalizada' con fecha_baja
    // real representa un retiro físico consumado.
    private function ultimasBajasFinalizadas(int $limite): array
    {
        $sql = "
            SELECT ba.fecha_baja AS fecha, db.codigo_interno_mostrado AS codigo_interno,
                db.codigo_sicoin_mostrado AS codigo_sicoin, db.descripcion_mostrada AS descripcion,
                ba.estado_baja
            FROM bajas ba
            INNER JOIN detalle_baja db ON db.id_baja = ba.id_baja
            WHERE ba.estado_baja = 'finalizada' AND ba.fecha_baja IS NOT NULL
            ORDER BY ba.fecha_baja DESC, ba.id_baja DESC
            LIMIT {$limite}
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql) as $fila) {
            $eventos[] = $this->normalizar('Baja', $fila['fecha'], false, $fila, ucfirst((string) $fila['estado_baja']));
        }

        return $eventos;
    }

    private function ultimasVerificaciones(int $limite): array
    {
        $sql = "
            SELECT vf.fecha_hora AS fecha, b.codigo_interno, b.codigo_sicoin, b.descripcion, vf.tiene_diferencias
            FROM verificaciones_fisicas vf
            INNER JOIN bienes b ON vf.id_bien = b.id_bien
            ORDER BY vf.fecha_hora DESC, vf.id_verificacion DESC
            LIMIT {$limite}
        ";

        $eventos = [];
        foreach ($this->fetchAll($sql) as $fila) {
            $estado = (int) $fila['tiene_diferencias'] === 1 ? 'Con diferencias' : 'Sin diferencias';
            $eventos[] = $this->normalizar('Verificación física', $fila['fecha'], true, $fila, $estado);
        }

        return $eventos;
    }

    // Fechas DATE-only reciben " 00:00:00" únicamente para poder compararse cronológicamente junto a
    // fuentes con hora real (mismo truco que ReportesService) — el Dashboard nunca muestra hora,
    // así que esto no afecta lo que ve la usuaria, solo el orden interno.
    private function normalizar(string $tipo, string $fecha, bool $fechaEsDatetime, array $fila, string $estado): array
    {
        return [
            'tipo' => $tipo,
            'fecha' => $fechaEsDatetime ? $fecha : $fecha . ' 00:00:00',
            'codigo_interno' => $fila['codigo_interno'],
            'codigo_sicoin' => $fila['codigo_sicoin'],
            'descripcion' => $fila['descripcion'],
            'estado' => $estado,
        ];
    }
}
