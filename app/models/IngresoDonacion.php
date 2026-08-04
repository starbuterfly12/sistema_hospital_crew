<?php

require_once __DIR__ . '/../core/Model.php';

class IngresoDonacion extends Model
{
    protected string $table = 'ingreso_donacion';

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO ingreso_donacion (
                id_bien,
                procedencia,
                entidad_donante,
                numero_acta,
                fecha_acta,
                documento_respaldo,
                observaciones
            ) VALUES (
                :id_bien,
                :procedencia,
                :entidad_donante,
                :numero_acta,
                :fecha_acta,
                :documento_respaldo,
                :observaciones
            )
        ";

        $params = [
            ':id_bien' => $datos['id_bien'] ?? null,
            ':procedencia' => $datos['procedencia'] ?? null,
            ':entidad_donante' => $datos['entidad_donante'] ?? null,
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
                id_donacion,
                id_bien,
                procedencia,
                entidad_donante,
                numero_acta,
                fecha_acta,
                documento_respaldo,
                observaciones
            FROM ingreso_donacion
            WHERE id_bien = :id_bien
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }

    public function actualizarPorBienId(int $idBien, array $datos): bool
    {
        $sql = "
            UPDATE ingreso_donacion SET
                procedencia = :procedencia,
                entidad_donante = :entidad_donante,
                numero_acta = :numero_acta,
                fecha_acta = :fecha_acta,
                documento_respaldo = :documento_respaldo,
                observaciones = :observaciones
            WHERE id_bien = :id_bien
        ";

        $params = [
            ':procedencia' => $datos['procedencia'] ?? null,
            ':entidad_donante' => $datos['entidad_donante'] ?? null,
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
