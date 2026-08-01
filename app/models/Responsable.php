<?php

require_once __DIR__ . '/../core/Model.php';

class Responsable extends Model
{
    protected string $table = 'responsables';

    public function getActivos(): array
    {
        $sql = "
            SELECT
                id_responsable,
                nombre_completo
            FROM responsables
            WHERE estado_responsable = 'activo'
            ORDER BY nombre_completo ASC
        ";

        return $this->fetchAll($sql);
    }
}