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

    // Resuelve un estado del catálogo por nombre exacto (ej. 'Baja') en vez de hardcodear su id: el
    // catálogo es pequeño y estable, pero fijar el id aquí lo haría frágil ante cualquier reordenamiento.
    public function findByNombre(string $nombreEstado): array|false
    {
        $sql = "
            SELECT
                id_estado_bien,
                nombre_estado,
                estado_registro
            FROM estados_bien
            WHERE nombre_estado = :nombre_estado
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':nombre_estado' => $nombreEstado]);
    }
}
