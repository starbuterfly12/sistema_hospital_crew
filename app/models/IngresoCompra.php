<?php

require_once __DIR__ . '/../core/Model.php';

class IngresoCompra extends Model
{
    protected string $table = 'ingreso_compra';

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO ingreso_compra (
                id_bien,
                proveedor,
                numero_factura,
                serie_factura,
                fecha_factura,
                numero_liquidacion,
                forma_compra,
                tiene_garantia,
                tiempo_garantia,
                documento_respaldo,
                observaciones
            ) VALUES (
                :id_bien,
                :proveedor,
                :numero_factura,
                :serie_factura,
                :fecha_factura,
                :numero_liquidacion,
                :forma_compra,
                :tiene_garantia,
                :tiempo_garantia,
                :documento_respaldo,
                :observaciones
            )
        ";

        $params = [
            ':id_bien' => $datos['id_bien'] ?? null,
            ':proveedor' => $datos['proveedor'] ?? null,
            ':numero_factura' => $datos['numero_factura'] ?? null,
            ':serie_factura' => $datos['serie_factura'] ?? null,
            ':fecha_factura' => $datos['fecha_factura'] ?? null,
            ':numero_liquidacion' => $datos['numero_liquidacion'] ?? null,
            ':forma_compra' => $datos['forma_compra'] ?? null,
            ':tiene_garantia' => isset($datos['tiene_garantia'])
                ? (int) $datos['tiene_garantia']
                : 0,
            ':tiempo_garantia' => $datos['tiempo_garantia'] ?? null,
            ':documento_respaldo' => $datos['documento_respaldo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function findByBienId(int $idBien): array|false
    {
        $sql = "
            SELECT
                id_compra,
                id_bien,
                proveedor,
                numero_factura,
                serie_factura,
                fecha_factura,
                numero_liquidacion,
                forma_compra,
                tiene_garantia,
                tiempo_garantia,
                documento_respaldo,
                observaciones
            FROM ingreso_compra
            WHERE id_bien = :id_bien
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }

    public function actualizarPorBienId(int $idBien, array $datos): bool
    {
        $tieneGarantia = isset($datos['tiene_garantia']) ? (int) $datos['tiene_garantia'] : 0;

        $sql = "
            UPDATE ingreso_compra SET
                proveedor = :proveedor,
                numero_factura = :numero_factura,
                serie_factura = :serie_factura,
                fecha_factura = :fecha_factura,
                numero_liquidacion = :numero_liquidacion,
                forma_compra = :forma_compra,
                tiene_garantia = :tiene_garantia,
                tiempo_garantia = :tiempo_garantia,
                documento_respaldo = :documento_respaldo,
                observaciones = :observaciones
            WHERE id_bien = :id_bien
        ";

        $params = [
            ':proveedor' => $datos['proveedor'] ?? null,
            ':numero_factura' => $datos['numero_factura'] ?? null,
            ':serie_factura' => $datos['serie_factura'] ?? null,
            ':fecha_factura' => $datos['fecha_factura'] ?? null,
            ':numero_liquidacion' => $datos['numero_liquidacion'] ?? null,
            ':forma_compra' => $datos['forma_compra'] ?? null,
            ':tiene_garantia' => $tieneGarantia,
            ':tiempo_garantia' => $tieneGarantia === 1 ? ($datos['tiempo_garantia'] ?? null) : null,
            ':documento_respaldo' => $datos['documento_respaldo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_bien' => $idBien,
        ];

        $this->query($sql, $params);

        return true;
    }
}
