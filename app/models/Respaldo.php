<?php

require_once __DIR__ . '/../core/Model.php';

class Respaldo extends Model
{
    protected string $table = 'respaldos';

    public function registrar(array $datos): int|false
    {
        $sql = "
            INSERT INTO respaldos (
                tipo_respaldo,
                nombre_archivo,
                ruta_archivo,
                estado_respaldo,
                id_usuario_genera,
                observaciones
            ) VALUES (
                :tipo_respaldo,
                :nombre_archivo,
                :ruta_archivo,
                :estado_respaldo,
                :id_usuario_genera,
                :observaciones
            )
        ";

        $this->query($sql, [
            ':tipo_respaldo' => $datos['tipo_respaldo'],
            ':nombre_archivo' => $datos['nombre_archivo'],
            ':ruta_archivo' => $datos['ruta_archivo'],
            ':estado_respaldo' => $datos['estado_respaldo'],
            ':id_usuario_genera' => $datos['id_usuario_genera'],
            ':observaciones' => $datos['observaciones'] ?? null,
        ]);

        $id = $this->lastInsertId();

        return $id !== '' ? (int) $id : false;
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                r.id_respaldo,
                r.tipo_respaldo,
                r.nombre_archivo,
                r.ruta_archivo,
                r.fecha_generacion,
                r.estado_respaldo,
                r.id_usuario_genera,
                r.observaciones,
                u.nombre_completo,
                u.usuario
            FROM respaldos r
            LEFT JOIN usuarios u ON r.id_usuario_genera = u.id_usuario
            ORDER BY r.fecha_generacion DESC, r.id_respaldo DESC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idRespaldo): array|false
    {
        $sql = "
            SELECT
                r.id_respaldo,
                r.tipo_respaldo,
                r.nombre_archivo,
                r.ruta_archivo,
                r.fecha_generacion,
                r.estado_respaldo,
                r.id_usuario_genera,
                r.observaciones,
                u.nombre_completo,
                u.usuario
            FROM respaldos r
            LEFT JOIN usuarios u ON r.id_usuario_genera = u.id_usuario
            WHERE r.id_respaldo = :id_respaldo
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_respaldo' => $idRespaldo]);
    }
}
