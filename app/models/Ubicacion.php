<?php

require_once __DIR__ . '/../core/Model.php';

class Ubicacion extends Model
{
    protected string $table = 'ubicaciones';

    public function getActivas(): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion
            FROM ubicaciones
            WHERE estado_ubicacion = 'activa'
            ORDER BY nombre_ubicacion ASC
        ";

        return $this->fetchAll($sql);
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            FROM ubicaciones
            ORDER BY nombre_ubicacion ASC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idUbicacion): array|false
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            FROM ubicaciones
            WHERE id_ubicacion = :id_ubicacion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_ubicacion' => $idUbicacion]);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO ubicaciones (
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            ) VALUES (
                :nombre_ubicacion,
                :tipo_ubicacion,
                :descripcion,
                :estado_ubicacion
            )
        ";

        $params = [
            ':nombre_ubicacion' => $datos['nombre_ubicacion'] ?? null,
            ':tipo_ubicacion' => $datos['tipo_ubicacion'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':estado_ubicacion' => $datos['estado_ubicacion'] ?? 'activa',
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function actualizar(int $idUbicacion, array $datos): bool
    {
        $sql = "
            UPDATE ubicaciones
            SET
                nombre_ubicacion = :nombre_ubicacion,
                tipo_ubicacion = :tipo_ubicacion,
                descripcion = :descripcion,
                estado_ubicacion = :estado_ubicacion
            WHERE id_ubicacion = :id_ubicacion
        ";

        $params = [
            ':nombre_ubicacion' => $datos['nombre_ubicacion'] ?? null,
            ':tipo_ubicacion' => $datos['tipo_ubicacion'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':estado_ubicacion' => $datos['estado_ubicacion'] ?? null,
            ':id_ubicacion' => $idUbicacion,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function cambiarEstado(int $idUbicacion, string $estado): bool
    {
        $sql = "
            UPDATE ubicaciones
            SET estado_ubicacion = :estado_ubicacion
            WHERE id_ubicacion = :id_ubicacion
        ";

        $params = [
            ':estado_ubicacion' => $estado,
            ':id_ubicacion' => $idUbicacion,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        if ($excluirId === null) {
            $sql = "
                SELECT id_ubicacion
                FROM ubicaciones
                WHERE nombre_ubicacion = :nombre
                LIMIT 1
            ";

            $params = [':nombre' => $nombre];
        } else {
            $sql = "
                SELECT id_ubicacion
                FROM ubicaciones
                WHERE nombre_ubicacion = :nombre AND id_ubicacion <> :id_ubicacion
                LIMIT 1
            ";

            $params = [
                ':nombre' => $nombre,
                ':id_ubicacion' => $excluirId,
            ];
        }

        return $this->fetchOne($sql, $params) !== false;
    }

    public function buscar(string $termino): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            FROM ubicaciones
            WHERE nombre_ubicacion LIKE :termino_nombre
               OR tipo_ubicacion LIKE :termino_tipo
            ORDER BY nombre_ubicacion ASC
        ";

        $like = '%' . $termino . '%';

        return $this->fetchAll($sql, [
            ':termino_nombre' => $like,
            ':termino_tipo' => $like,
        ]);
    }
}
