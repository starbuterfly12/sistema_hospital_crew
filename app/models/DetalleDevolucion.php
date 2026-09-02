<?php

require_once __DIR__ . '/../core/Model.php';

class DetalleDevolucion extends Model
{
    protected string $table = 'detalle_devolucion';

    public function insertar(array $datos): int
    {
        $sql = "
            INSERT INTO detalle_devolucion (
                id_devolucion,
                id_detalle_prestamo,
                id_bien,
                condicion_devolucion,
                observaciones
            ) VALUES (
                :id_devolucion,
                :id_detalle_prestamo,
                :id_bien,
                :condicion_devolucion,
                :observaciones
            )
        ";

        $params = [
            ':id_devolucion' => $datos['id_devolucion'],
            ':id_detalle_prestamo' => $datos['id_detalle_prestamo'],
            ':id_bien' => $datos['id_bien'],
            ':condicion_devolucion' => $datos['condicion_devolucion'],
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function listarPorDevolucion(int $idDevolucion): array
    {
        $sql = "
            SELECT
                dd.id_detalle_devolucion,
                dd.id_devolucion,
                dd.id_detalle_prestamo,
                dd.id_bien,
                dd.condicion_devolucion,
                dd.observaciones,
                dp.codigo_interno_mostrado,
                dp.codigo_sicoin_mostrado,
                dp.descripcion_mostrada,
                dp.serie_mostrada,
                dp.modelo_mostrado,
                dp.valor_prestamo,
                dp.condicion_entrega,
                b.imagen_bien
            FROM detalle_devolucion dd
            INNER JOIN detalle_prestamo dp ON dp.id_detalle_prestamo = dd.id_detalle_prestamo
            LEFT JOIN bienes b ON b.id_bien = dd.id_bien
            WHERE dd.id_devolucion = :id_devolucion
            ORDER BY dd.id_detalle_devolucion ASC
        ";

        return $this->fetchAll($sql, [':id_devolucion' => $idDevolucion]);
    }
}
