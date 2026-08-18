<?php

require_once __DIR__ . '/../core/Model.php';

class DetallePrestamo extends Model
{
    protected string $table = 'detalle_prestamo';

    public function insertar(array $datos): int
    {
        $sql = "
            INSERT INTO detalle_prestamo (
                id_prestamo,
                id_bien,
                id_detalle_asignacion_origen,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                serie_mostrada,
                valor_prestamo,
                condicion_entrega,
                observaciones
            ) VALUES (
                :id_prestamo,
                :id_bien,
                :id_detalle_asignacion_origen,
                :codigo_interno_mostrado,
                :codigo_sicoin_mostrado,
                :descripcion_mostrada,
                :serie_mostrada,
                :valor_prestamo,
                :condicion_entrega,
                :observaciones
            )
        ";

        $params = [
            ':id_prestamo' => $datos['id_prestamo'],
            ':id_bien' => $datos['id_bien'],
            ':id_detalle_asignacion_origen' => $datos['id_detalle_asignacion_origen'],
            ':codigo_interno_mostrado' => $datos['codigo_interno_mostrado'],
            ':codigo_sicoin_mostrado' => $datos['codigo_sicoin_mostrado'] ?? null,
            ':descripcion_mostrada' => $datos['descripcion_mostrada'],
            ':serie_mostrada' => $datos['serie_mostrada'] ?? null,
            ':valor_prestamo' => $datos['valor_prestamo'],
            ':condicion_entrega' => $datos['condicion_entrega'],
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function listarPorPrestamo(int $idPrestamo): array
    {
        $sql = "
            SELECT
                dp.id_detalle_prestamo,
                dp.id_prestamo,
                dp.id_bien,
                dp.id_detalle_asignacion_origen,
                dp.codigo_interno_mostrado,
                dp.codigo_sicoin_mostrado,
                dp.descripcion_mostrada,
                dp.serie_mostrada,
                dp.valor_prestamo,
                dp.condicion_entrega,
                dp.estado_detalle,
                dp.observaciones
            FROM detalle_prestamo dp
            WHERE dp.id_prestamo = :id_prestamo
            ORDER BY dp.id_detalle_prestamo ASC
        ";

        return $this->fetchAll($sql, [':id_prestamo' => $idPrestamo]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // La protección real de concurrencia depende de que el bien ya esté bloqueado (FOR UPDATE) antes
    // de llamar este método: dos transacciones que intenten prestar el mismo bien se serializan en ese
    // lock, y la segunda —ya después del commit de la primera— sí encuentra aquí la fila recién creada
    // (lectura de bloqueo sobre el dato más reciente, no una instantánea MVCC).
    public function existePrestamoActivoPorBienForUpdate(int $idBien): bool
    {
        $sql = "
            SELECT id_detalle_prestamo
            FROM detalle_prestamo
            WHERE id_bien = :id_bien
              AND estado_detalle = 'prestado'
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]) !== false;
    }
}
