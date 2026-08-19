<?php

require_once __DIR__ . '/../core/Model.php';

class DetalleRequisicion extends Model
{
    protected string $table = 'detalle_requisicion';

    public function insertar(array $datos): int
    {
        $sql = "
            INSERT INTO detalle_requisicion (
                id_requisicion,
                id_bien,
                id_detalle_asignacion_origen,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                serie_mostrada,
                modelo_mostrado,
                valor_mostrado,
                estado_detalle,
                observaciones
            ) VALUES (
                :id_requisicion,
                :id_bien,
                :id_detalle_asignacion_origen,
                :codigo_interno_mostrado,
                :codigo_sicoin_mostrado,
                :descripcion_mostrada,
                :serie_mostrada,
                :modelo_mostrado,
                :valor_mostrado,
                :estado_detalle,
                :observaciones
            )
        ";

        $params = [
            ':id_requisicion' => $datos['id_requisicion'],
            ':id_bien' => $datos['id_bien'],
            ':id_detalle_asignacion_origen' => $datos['id_detalle_asignacion_origen'],
            ':codigo_interno_mostrado' => $datos['codigo_interno_mostrado'],
            ':codigo_sicoin_mostrado' => $datos['codigo_sicoin_mostrado'] ?? null,
            ':descripcion_mostrada' => $datos['descripcion_mostrada'],
            ':serie_mostrada' => $datos['serie_mostrada'] ?? null,
            ':modelo_mostrado' => $datos['modelo_mostrado'] ?? null,
            ':valor_mostrado' => $datos['valor_mostrado'],
            ':estado_detalle' => $datos['estado_detalle'] ?? 'pendiente',
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function listarPorRequisicion(int $idRequisicion): array
    {
        $sql = "
            SELECT
                id_detalle_requisicion,
                id_requisicion,
                id_bien,
                id_detalle_asignacion_origen,
                id_detalle_asignacion_destino,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                serie_mostrada,
                modelo_mostrado,
                valor_mostrado,
                estado_detalle,
                observaciones
            FROM detalle_requisicion
            WHERE id_requisicion = :id_requisicion
            ORDER BY id_detalle_requisicion ASC
        ";

        return $this->fetchAll($sql, [':id_requisicion' => $idRequisicion]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function getPorRequisicionYEstadoForUpdate(int $idRequisicion, string $estadoDetalle): array
    {
        $sql = "
            SELECT
                id_detalle_requisicion,
                id_requisicion,
                id_bien,
                id_detalle_asignacion_origen,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                serie_mostrada,
                valor_mostrado,
                estado_detalle
            FROM detalle_requisicion
            WHERE id_requisicion = :id_requisicion
              AND estado_detalle = :estado_detalle
            ORDER BY id_detalle_requisicion ASC
            FOR UPDATE
        ";

        return $this->fetchAll($sql, [
            ':id_requisicion' => $idRequisicion,
            ':estado_detalle' => $estadoDetalle,
        ]);
    }

    // Solo debe usarse mientras la cabecera está Pendiente: el bien nunca fue reservado ni entregado,
    // así que se elimina el detalle en vez de marcarlo 'anulado' — mismo criterio que
    // Asignacion::eliminarDetallePendiente().
    public function eliminarDetallePendiente(int $idDetalleRequisicion): bool
    {
        $sql = "
            DELETE FROM detalle_requisicion
            WHERE id_detalle_requisicion = :id_detalle_requisicion
              AND estado_detalle = 'pendiente'
        ";

        $this->query($sql, [':id_detalle_requisicion' => $idDetalleRequisicion]);

        return true;
    }

    public function marcarReservados(int $idRequisicion): bool
    {
        $sql = "
            UPDATE detalle_requisicion
            SET estado_detalle = 'reservado'
            WHERE id_requisicion = :id_requisicion
              AND estado_detalle = 'pendiente'
        ";

        $this->query($sql, [':id_requisicion' => $idRequisicion]);

        return true;
    }

    public function marcarAnulados(int $idRequisicion): bool
    {
        $sql = "
            UPDATE detalle_requisicion
            SET estado_detalle = 'anulado'
            WHERE id_requisicion = :id_requisicion
              AND estado_detalle IN ('pendiente', 'reservado')
        ";

        $this->query($sql, [':id_requisicion' => $idRequisicion]);

        return true;
    }

    public function marcarEntregado(int $idDetalleRequisicion, int $idDetalleAsignacionDestino): bool
    {
        $sql = "
            UPDATE detalle_requisicion
            SET
                estado_detalle = 'entregado',
                id_detalle_asignacion_destino = :id_detalle_asignacion_destino
            WHERE id_detalle_requisicion = :id_detalle_requisicion
              AND estado_detalle = 'reservado'
        ";

        $this->query($sql, [
            ':id_detalle_asignacion_destino' => $idDetalleAsignacionDestino,
            ':id_detalle_requisicion' => $idDetalleRequisicion,
        ]);

        return true;
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Usado al autorizar/registrar: bloquea cualquier fila 'reservado' de OTRA requisición sobre el
    // mismo bien, para serializar autorizaciones concurrentes que compitan por el mismo bien.
    public function existeReservaActivaPorBienForUpdate(int $idBien, int $idRequisicionExcluir): bool
    {
        $sql = "
            SELECT id_detalle_requisicion
            FROM detalle_requisicion
            WHERE id_bien = :id_bien
              AND estado_detalle = 'reservado'
              AND id_requisicion <> :id_requisicion_excluir
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_requisicion_excluir' => $idRequisicionExcluir,
        ]) !== false;
    }

    // Requisición que retiró un bien de este detalle de asignación específico (su detalle ORIGEN de
    // Bodega). Simétrico a DetalleMovimiento::findSalidaPorDetalleOrigen() — usado por
    // TarjetasController para reconstruir el descargo (HABER) cuando el bien salió por Requisición
    // en vez de por Traslado. codigo_mostrado se resuelve con el mismo criterio SICOIN-o-interno que
    // usa el resto del sistema (COALESCE), ya que detalle_requisicion guarda ambos códigos por
    // separado en vez de uno solo ya resuelto.
    public function findSalidaPorDetalleOrigen(int $idDetalleAsignacion): array|false
    {
        $sql = "
            SELECT
                dr.id_detalle_requisicion,
                dr.id_requisicion,
                r.numero_requisicion_sistema,
                r.fecha_entrega,
                dr.id_bien,
                dr.id_detalle_asignacion_origen,
                dr.id_detalle_asignacion_destino,
                COALESCE(NULLIF(dr.codigo_sicoin_mostrado, ''), dr.codigo_interno_mostrado) AS codigo_mostrado,
                dr.descripcion_mostrada,
                dr.modelo_mostrado,
                dr.serie_mostrada,
                dr.valor_mostrado
            FROM detalle_requisicion dr
            INNER JOIN requisiciones r ON dr.id_requisicion = r.id_requisicion
            WHERE dr.id_detalle_asignacion_origen = :id_detalle_asignacion
              AND dr.estado_detalle = 'entregado'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_detalle_asignacion' => $idDetalleAsignacion]);
    }

    // Requisición que incorporó un bien a este detalle de asignación específico (su detalle DESTINO).
    // Simétrico a DetalleMovimiento::findEntradaPorDetalleDestino().
    public function findEntradaPorDetalleDestino(int $idDetalleAsignacion): array|false
    {
        $sql = "
            SELECT
                dr.id_detalle_requisicion,
                dr.id_requisicion,
                r.numero_requisicion_sistema,
                r.fecha_entrega,
                dr.id_bien,
                dr.id_detalle_asignacion_origen,
                dr.id_detalle_asignacion_destino,
                COALESCE(NULLIF(dr.codigo_sicoin_mostrado, ''), dr.codigo_interno_mostrado) AS codigo_mostrado,
                dr.descripcion_mostrada,
                dr.modelo_mostrado,
                dr.serie_mostrada,
                dr.valor_mostrado
            FROM detalle_requisicion dr
            INNER JOIN requisiciones r ON dr.id_requisicion = r.id_requisicion
            WHERE dr.id_detalle_asignacion_destino = :id_detalle_asignacion
              AND dr.estado_detalle = 'entregado'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_detalle_asignacion' => $idDetalleAsignacion]);
    }
}
