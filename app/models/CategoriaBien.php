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
}
