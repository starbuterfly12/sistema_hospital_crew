<?php

require_once __DIR__ . '/../core/Model.php';

class Bien extends Model
{
    protected string $table = 'bienes';

    public function getAll(): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.fecha_ingreso,
                b.costo,
                b.valor_estimado,
                b.id_responsable_actual,
                b.id_ubicacion_actual,
                cb.nombre_categoria,
                eb.nombre_estado,
                r.nombre_completo AS responsable_actual,
                u.nombre_ubicacion AS ubicacion_actual
            FROM bienes b
            LEFT JOIN categorias_bien cb ON b.id_categoria = cb.id_categoria
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN responsables r ON b.id_responsable_actual = r.id_responsable
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            ORDER BY b.id_bien DESC
        ";

        return $this->fetchAll($sql);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO bienes (
                codigo_interno,
                codigo_sicoin,
                descripcion,
                marca,
                modelo,
                serie,
                id_categoria,
                id_estado_bien,
                condicion_bien,
                id_forma_ingreso,
                fecha_ingreso,
                costo,
                valor_estimado,
                id_responsable_actual,
                id_ubicacion_actual,
                observaciones
            ) VALUES (
                :codigo_interno,
                :codigo_sicoin,
                :descripcion,
                :marca,
                :modelo,
                :serie,
                :id_categoria,
                :id_estado_bien,
                :condicion_bien,
                :id_forma_ingreso,
                :fecha_ingreso,
                :costo,
                :valor_estimado,
                :id_responsable_actual,
                :id_ubicacion_actual,
                :observaciones
            )
        ";

        $params = [
            ':codigo_interno' => $datos['codigo_interno'] ?? null,
            ':codigo_sicoin' => $datos['codigo_sicoin'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':marca' => $datos['marca'] ?? null,
            ':modelo' => $datos['modelo'] ?? null,
            ':serie' => $datos['serie'] ?? null,
            ':id_categoria' => $datos['id_categoria'] ?? null,
            ':id_estado_bien' => $datos['id_estado_bien'] ?? null,
            ':condicion_bien' => $datos['condicion_bien'] ?? null,
            ':id_forma_ingreso' => $datos['id_forma_ingreso'] ?? null,
            ':fecha_ingreso' => $datos['fecha_ingreso'] ?? null,
            ':costo' => $datos['costo'] ?? null,
            ':valor_estimado' => $datos['valor_estimado'] ?? null,
            ':id_responsable_actual' => $datos['id_responsable_actual'] ?? null,
            ':id_ubicacion_actual' => $datos['id_ubicacion_actual'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }
}