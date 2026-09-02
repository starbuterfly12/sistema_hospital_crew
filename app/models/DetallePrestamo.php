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
                modelo_mostrado,
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
                :modelo_mostrado,
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
            ':modelo_mostrado' => $datos['modelo_mostrado'] ?? null,
            ':valor_prestamo' => $datos['valor_prestamo'],
            ':condicion_entrega' => $datos['condicion_entrega'],
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // Incluye el snapshot de devolución (si el detalle ya fue devuelto) vía LEFT JOIN — usado por
    // "Ver préstamo" para mostrar Pendiente/Devuelto, fecha de devolución y condición de devolución
    // sin necesidad de una segunda consulta. Un detalle 'prestado' simplemente trae esas columnas NULL.
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
                dp.modelo_mostrado,
                dp.valor_prestamo,
                dp.condicion_entrega,
                dp.estado_detalle,
                dp.observaciones,
                dd.condicion_devolucion,
                dd.observaciones AS observaciones_devolucion,
                dev.id_devolucion,
                dev.numero_devolucion,
                dev.fecha_devolucion,
                b.imagen_bien
            FROM detalle_prestamo dp
            LEFT JOIN detalle_devolucion dd ON dd.id_detalle_prestamo = dp.id_detalle_prestamo
            LEFT JOIN devoluciones dev ON dev.id_devolucion = dd.id_devolucion
            LEFT JOIN bienes b ON b.id_bien = dp.id_bien
            WHERE dp.id_prestamo = :id_prestamo
            ORDER BY dp.id_detalle_prestamo ASC
        ";

        return $this->fetchAll($sql, [':id_prestamo' => $idPrestamo]);
    }

    // Bienes de un préstamo todavía pendientes de devolución — precarga informativa (sin lock) para
    // el formulario de Devoluciones/crear(). La validación real y definitiva ocurre dentro de la
    // transacción vía findPendienteForUpdate().
    public function listarPendientesPorPrestamo(int $idPrestamo): array
    {
        $sql = "
            SELECT
                dp.id_detalle_prestamo,
                dp.id_bien,
                dp.codigo_interno_mostrado,
                dp.codigo_sicoin_mostrado,
                dp.descripcion_mostrada,
                dp.serie_mostrada,
                dp.modelo_mostrado,
                dp.valor_prestamo,
                dp.condicion_entrega,
                b.imagen_bien
            FROM detalle_prestamo dp
            LEFT JOIN bienes b ON b.id_bien = dp.id_bien
            WHERE dp.id_prestamo = :id_prestamo
              AND dp.estado_detalle = 'prestado'
            ORDER BY dp.id_detalle_prestamo ASC
        ";

        return $this->fetchAll($sql, [':id_prestamo' => $idPrestamo]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto,
    // y solo después de bloquear la cabecera del préstamo con Prestamo::findByIdForUpdate() (ver
    // comentario allí sobre por qué el orden importa para el conteo de pendientes). Exige que el
    // detalle pertenezca exactamente a ese préstamo y siga 'prestado': un id_detalle_prestamo de
    // otro préstamo, o ya devuelto, no se encuentra aquí y el llamador debe rechazar la operación.
    public function findPendienteForUpdate(int $idDetallePrestamo, int $idPrestamo): array|false
    {
        $sql = "
            SELECT
                id_detalle_prestamo,
                id_prestamo,
                id_bien,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                serie_mostrada,
                modelo_mostrado,
                valor_prestamo,
                condicion_entrega,
                estado_detalle
            FROM detalle_prestamo
            WHERE id_detalle_prestamo = :id_detalle_prestamo
              AND id_prestamo = :id_prestamo
              AND estado_detalle = 'prestado'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_detalle_prestamo' => $idDetallePrestamo,
            ':id_prestamo' => $idPrestamo,
        ]);
    }

    public function marcarDevuelto(int $idDetallePrestamo): bool
    {
        $sql = "
            UPDATE detalle_prestamo
            SET estado_detalle = 'devuelto'
            WHERE id_detalle_prestamo = :id_detalle_prestamo
        ";

        $this->query($sql, [':id_detalle_prestamo' => $idDetallePrestamo]);

        return true;
    }

    // Debe ejecutarse dentro de la misma transacción, después de bloquear la cabecera del préstamo
    // (ver Prestamo::findByIdForUpdate()): con esa cabecera bloqueada, ninguna otra transacción puede
    // estar devolviendo detalles de este mismo préstamo en paralelo, así que este conteo (tomado antes
    // de marcar nada como devuelto) es seguro para calcular cuántos quedarán pendientes después de
    // restarle la cantidad que se está devolviendo ahora mismo.
    public function contarPendientesPorPrestamo(int $idPrestamo): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM detalle_prestamo
            WHERE id_prestamo = :id_prestamo
              AND estado_detalle = 'prestado'
        ";

        $resultado = $this->fetchOne($sql, [':id_prestamo' => $idPrestamo]);

        return $resultado !== false ? (int) $resultado['total'] : 0;
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
