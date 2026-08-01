<?php

require_once __DIR__ . '/../core/Model.php';

class FormaIngreso extends Model
{
    protected string $table = 'formas_ingreso';

    public function getActivas(): array
    {
        $sql = "
            SELECT
                id_forma_ingreso,
                nombre_forma
            FROM formas_ingreso
            WHERE estado_forma = 'activa'
            ORDER BY nombre_forma ASC
        ";

        return $this->fetchAll($sql);
    }
}
