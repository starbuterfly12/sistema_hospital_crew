<?php

require_once __DIR__ . '/../core/Model.php';

class IngresoTraslado extends Model
{
    protected string $table = 'ingreso_traslado';

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO ingreso_traslado (
                id_bien,
                procedencia,
                unidad_ejecutora_origen,
                codigo_unidad_origen,
                numero_acta,
                fecha_acta,
                documento_respaldo,
                observaciones
            ) VALUES (
                :id_bien,
                :procedencia,
                :unidad_ejecutora_origen,
                :codigo_unidad_origen,
                :numero_acta,
                :fecha_acta,
                :documento_respaldo,
                :observaciones
            )
        ";

        $params = [
            ':id_bien' => $datos['id_bien'] ?? null,
            ':procedencia' => $datos['procedencia'] ?? null,
            ':unidad_ejecutora_origen' => $datos['unidad_ejecutora_origen'] ?? null,
            ':codigo_unidad_origen' => $datos['codigo_unidad_origen'] ?? null,
            ':numero_acta' => $datos['numero_acta'] ?? null,
            ':fecha_acta' => $datos['fecha_acta'] ?? null,
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
                id_traslado_ingreso,
                id_bien,
                procedencia,
                unidad_ejecutora_origen,
                codigo_unidad_origen,
                numero_acta,
                fecha_acta,
                documento_respaldo,
                observaciones
            FROM ingreso_traslado
            WHERE id_bien = :id_bien
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }

    public function actualizarPorBienId(int $idBien, array $datos): bool
    {
        $sql = "
            UPDATE ingreso_traslado SET
                procedencia = :procedencia,
                unidad_ejecutora_origen = :unidad_ejecutora_origen,
                codigo_unidad_origen = :codigo_unidad_origen,
                numero_acta = :numero_acta,
                fecha_acta = :fecha_acta,
                documento_respaldo = :documento_respaldo,
                observaciones = :observaciones
            WHERE id_bien = :id_bien
        ";

        $params = [
            ':procedencia' => $datos['procedencia'] ?? null,
            ':unidad_ejecutora_origen' => $datos['unidad_ejecutora_origen'] ?? null,
            ':codigo_unidad_origen' => $datos['codigo_unidad_origen'] ?? null,
            ':numero_acta' => $datos['numero_acta'] ?? null,
            ':fecha_acta' => $datos['fecha_acta'] ?? null,
            ':documento_respaldo' => $datos['documento_respaldo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_bien' => $idBien,
        ];

        $this->query($sql, $params);

        return true;
    }
}
