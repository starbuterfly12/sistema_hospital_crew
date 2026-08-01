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
}