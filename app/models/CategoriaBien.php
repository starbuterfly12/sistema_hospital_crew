<?php

require_once __DIR__ . '/../core/Model.php';

class CategoriaBien extends Model
{
    protected string $table = 'categorias_bien';

    public function getActivas(): array
    {
        $sql = "
            SELECT
                id_categoria,
                nombre_categoria
            FROM categorias_bien
            WHERE estado_categoria = 'activa'
            ORDER BY nombre_categoria ASC
        ";

        return $this->fetchAll($sql);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO categorias_bien (
                nombre_categoria,
                descripcion,
                estado_categoria
            ) VALUES (
                :nombre_categoria,
                :descripcion,
                :estado_categoria
            )
        ";

        $params = [
            ':nombre_categoria' => $datos['nombre_categoria'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':estado_categoria' => 'activa',
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        if ($excluirId === null) {
            $sql = "
                SELECT id_categoria
                FROM categorias_bien
                WHERE nombre_categoria = :nombre
                LIMIT 1
            ";

            $params = [':nombre' => $nombre];
        } else {
            $sql = "
                SELECT id_categoria
                FROM categorias_bien
                WHERE nombre_categoria = :nombre AND id_categoria <> :id_categoria
                LIMIT 1
            ";

            $params = [
                ':nombre' => $nombre,
                ':id_categoria' => $excluirId,
            ];
        }

        return $this->fetchOne($sql, $params) !== false;
    }
}
