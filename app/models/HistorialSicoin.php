<?php

require_once __DIR__ . '/../core/Model.php';

class HistorialSicoin extends Model
{
    protected string $table = 'historial_sicoin';

    public function registrar(array $datos): int
    {
        $sql = "
            INSERT INTO historial_sicoin (
                id_bien,
                sicoin_anterior,
                sicoin_nuevo,
                fecha_cambio,
                id_usuario
            ) VALUES (
                :id_bien,
                :sicoin_anterior,
                :sicoin_nuevo,
                :fecha_cambio,
                :id_usuario
            )
        ";

        $params = [
            ':id_bien' => $datos['id_bien'],
            ':sicoin_anterior' => $datos['sicoin_anterior'] ?? null,
            ':sicoin_nuevo' => $datos['sicoin_nuevo'],
            ':fecha_cambio' => $datos['fecha_cambio'],
            ':id_usuario' => $datos['id_usuario'],
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function getPorBien(int $idBien): array
    {
        $sql = "
            SELECT
                h.id_historial_sicoin,
                h.id_bien,
                h.sicoin_anterior,
                h.sicoin_nuevo,
                h.fecha_cambio,
                h.id_usuario,
                u.nombre_completo AS usuario_nombre,
                h.created_at
            FROM historial_sicoin h
            LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
            WHERE h.id_bien = :id_bien
            ORDER BY h.fecha_cambio ASC, h.id_historial_sicoin ASC
        ";

        return $this->fetchAll($sql, [':id_bien' => $idBien]);
    }
}
