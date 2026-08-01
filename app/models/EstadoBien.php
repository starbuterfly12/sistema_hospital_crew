<?php

require_once __DIR__ . '/../core/Model.php';

class EstadoBien extends Model
{
    protected string $table = 'estados_bien';

    public function getActivos(): array
    {
        $sql = "
            SELECT
                id_estado_bien,
                nombre_estado
            FROM estados_bien
            WHERE estado_registro = 'activo'
            ORDER BY nombre_estado ASC
        ";

        return $this->fetchAll($sql);
    }
}
