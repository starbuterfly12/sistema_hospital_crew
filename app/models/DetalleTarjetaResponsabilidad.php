<?php

require_once __DIR__ . '/../core/Model.php';

class DetalleTarjetaResponsabilidad extends Model
{
    protected string $table = 'detalle_tarjeta_responsabilidad';

    public function insertar(array $datos): int
    {
        $sql = "
            INSERT INTO detalle_tarjeta_responsabilidad (
                id_tarjeta_responsabilidad,
                id_bien,
                tipo_operacion,
                fecha_operacion,
                codigo_mostrado,
                descripcion_mostrada,
                modelo_mostrado,
                serie_mostrada,
                cantidad,
                costo_unitario,
                debe,
                haber,
                saldo_resultante,
                orden_linea,
                observaciones,
                id_detalle_movimiento,
                id_detalle_asignacion
            ) VALUES (
                :id_tarjeta_responsabilidad,
                :id_bien,
                :tipo_operacion,
                :fecha_operacion,
                :codigo_mostrado,
                :descripcion_mostrada,
                :modelo_mostrado,
                :serie_mostrada,
                :cantidad,
                :costo_unitario,
                :debe,
                :haber,
                :saldo_resultante,
                :orden_linea,
                :observaciones,
                :id_detalle_movimiento,
                :id_detalle_asignacion
            )
        ";

        $params = [
            ':id_tarjeta_responsabilidad' => $datos['id_tarjeta_responsabilidad'],
            ':id_bien' => $datos['id_bien'],
            ':tipo_operacion' => $datos['tipo_operacion'],
            ':fecha_operacion' => $datos['fecha_operacion'],
            ':codigo_mostrado' => $datos['codigo_mostrado'] ?? null,
            ':descripcion_mostrada' => $datos['descripcion_mostrada'],
            ':modelo_mostrado' => $datos['modelo_mostrado'] ?? null,
            ':serie_mostrada' => $datos['serie_mostrada'] ?? null,
            ':cantidad' => $datos['cantidad'],
            ':costo_unitario' => $datos['costo_unitario'] ?? null,
            ':debe' => $datos['debe'],
            ':haber' => $datos['haber'],
            ':saldo_resultante' => $datos['saldo_resultante'],
            ':orden_linea' => $datos['orden_linea'],
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_detalle_movimiento' => $datos['id_detalle_movimiento'] ?? null,
            ':id_detalle_asignacion' => $datos['id_detalle_asignacion'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function listarPorTarjeta(int $idTarjetaResponsabilidad): array
    {
        $sql = "
            SELECT
                id_detalle_tarjeta,
                id_tarjeta_responsabilidad,
                id_bien,
                tipo_operacion,
                fecha_operacion,
                codigo_mostrado,
                descripcion_mostrada,
                modelo_mostrado,
                serie_mostrada,
                cantidad,
                costo_unitario,
                debe,
                haber,
                saldo_resultante,
                orden_linea,
                observaciones,
                id_detalle_movimiento,
                id_detalle_asignacion
            FROM detalle_tarjeta_responsabilidad
            WHERE id_tarjeta_responsabilidad = :id_tarjeta_responsabilidad
            ORDER BY orden_linea ASC
        ";

        return $this->fetchAll($sql, [':id_tarjeta_responsabilidad' => $idTarjetaResponsabilidad]);
    }
}
