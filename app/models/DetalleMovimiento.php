<?php

require_once __DIR__ . '/../core/Model.php';

class DetalleMovimiento extends Model
{
    protected string $table = 'detalle_movimiento';

    public function insertar(array $datos): int
    {
        $sql = "
            INSERT INTO detalle_movimiento (
                id_movimiento,
                id_bien,
                id_detalle_asignacion_origen,
                id_detalle_asignacion_destino,
                codigo_mostrado,
                valor_movimiento,
                id_estado_bien_anterior,
                id_estado_bien_nuevo,
                condicion_anterior,
                condicion_nueva,
                observaciones
            ) VALUES (
                :id_movimiento,
                :id_bien,
                :id_detalle_asignacion_origen,
                :id_detalle_asignacion_destino,
                :codigo_mostrado,
                :valor_movimiento,
                :id_estado_bien_anterior,
                :id_estado_bien_nuevo,
                :condicion_anterior,
                :condicion_nueva,
                :observaciones
            )
        ";

        $params = [
            ':id_movimiento' => $datos['id_movimiento'],
            ':id_bien' => $datos['id_bien'],
            ':id_detalle_asignacion_origen' => $datos['id_detalle_asignacion_origen'],
            ':id_detalle_asignacion_destino' => $datos['id_detalle_asignacion_destino'] ?? null,
            ':codigo_mostrado' => $datos['codigo_mostrado'],
            ':valor_movimiento' => $datos['valor_movimiento'],
            ':id_estado_bien_anterior' => $datos['id_estado_bien_anterior'] ?? null,
            ':id_estado_bien_nuevo' => $datos['id_estado_bien_nuevo'] ?? null,
            ':condicion_anterior' => $datos['condicion_anterior'] ?? null,
            ':condicion_nueva' => $datos['condicion_nueva'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // NOTA: descripcion/marca/modelo/serie se leen aquí de `bienes` (espejo actual), NO de un
    // snapshot histórico — detalle_movimiento no tiene columnas propias para esto (a diferencia de
    // detalle_prestamo/detalle_requisicion, que sí guardan *_mostrado). Si el bien se edita después
    // del traslado, una constancia vuelta a descargar reflejará el dato nuevo, no el vigente al
    // momento del traslado. Comportamiento preexistente para descripcion/serie; marca/modelo se
    // agregan aquí con el mismo criterio dinámico para no introducir una inconsistencia nueva entre
    // las columnas. Ver auditoría 2026-08-19 en TrasladosController antes de considerar un ALTER.
    public function listarPorMovimiento(int $idMovimiento): array
    {
        $sql = "
            SELECT
                dm.id_detalle_movimiento,
                dm.id_movimiento,
                dm.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.imagen_bien,
                dm.id_detalle_asignacion_origen,
                ao.numero_asignacion AS numero_asignacion_origen,
                dm.id_detalle_asignacion_destino,
                dm.codigo_mostrado,
                dm.valor_movimiento,
                dm.id_estado_bien_anterior,
                dm.id_estado_bien_nuevo,
                dm.condicion_anterior,
                dm.condicion_nueva,
                dm.observaciones,
                dm.created_at
            FROM detalle_movimiento dm
            LEFT JOIN bienes b ON dm.id_bien = b.id_bien
            LEFT JOIN detalle_asignacion dao ON dm.id_detalle_asignacion_origen = dao.id_detalle_asignacion
            LEFT JOIN asignaciones ao ON dao.id_asignacion = ao.id_asignacion
            WHERE dm.id_movimiento = :id_movimiento
            ORDER BY dm.id_detalle_movimiento ASC
        ";

        return $this->fetchAll($sql, [':id_movimiento' => $idMovimiento]);
    }

    // Traslado que retiró un bien de este detalle de asignación específico (su detalle ORIGEN).
    // Se resuelve por catálogo (nombre_movimiento), sin hardcodear id_tipo_movimiento.
    public function findSalidaPorDetalleOrigen(int $idDetalleAsignacion): array|false
    {
        $sql = "
            SELECT
                dm.id_detalle_movimiento,
                dm.id_movimiento,
                m.numero_movimiento,
                m.fecha_movimiento,
                dm.id_bien,
                dm.id_detalle_asignacion_origen,
                dm.id_detalle_asignacion_destino,
                dm.codigo_mostrado,
                dm.valor_movimiento
            FROM detalle_movimiento dm
            INNER JOIN movimientos m ON dm.id_movimiento = m.id_movimiento
            INNER JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
            WHERE dm.id_detalle_asignacion_origen = :id_detalle_asignacion
              AND tm.nombre_movimiento = 'Traslado'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_detalle_asignacion' => $idDetalleAsignacion]);
    }

    // Traslado que incorporó un bien a este detalle de asignación específico (su detalle DESTINO).
    public function findEntradaPorDetalleDestino(int $idDetalleAsignacion): array|false
    {
        $sql = "
            SELECT
                dm.id_detalle_movimiento,
                dm.id_movimiento,
                m.numero_movimiento,
                m.fecha_movimiento,
                dm.id_bien,
                dm.id_detalle_asignacion_origen,
                dm.id_detalle_asignacion_destino,
                dm.codigo_mostrado,
                dm.valor_movimiento
            FROM detalle_movimiento dm
            INNER JOIN movimientos m ON dm.id_movimiento = m.id_movimiento
            INNER JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
            WHERE dm.id_detalle_asignacion_destino = :id_detalle_asignacion
              AND tm.nombre_movimiento = 'Traslado'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_detalle_asignacion' => $idDetalleAsignacion]);
    }

    public function existeBienEnMovimiento(int $idMovimiento, int $idBien): bool
    {
        $sql = "
            SELECT id_detalle_movimiento
            FROM detalle_movimiento
            WHERE id_movimiento = :id_movimiento
              AND id_bien = :id_bien
            LIMIT 1
        ";

        return $this->fetchOne($sql, [
            ':id_movimiento' => $idMovimiento,
            ':id_bien' => $idBien,
        ]) !== false;
    }
}
