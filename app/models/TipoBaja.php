<?php

require_once __DIR__ . '/../core/Model.php';

class TipoBaja extends Model
{
    protected string $table = 'tipos_baja';

    public function getActivos(): array
    {
        $sql = "
            SELECT
                id_tipo_baja,
                nombre_tipo_baja
            FROM tipos_baja
            WHERE estado_tipo_baja = 'activo'
            ORDER BY nombre_tipo_baja ASC
        ";

        return $this->fetchAll($sql);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findActivoByIdForUpdate(int $idTipoBaja): array|false
    {
        $sql = "
            SELECT
                id_tipo_baja,
                nombre_tipo_baja
            FROM tipos_baja
            WHERE id_tipo_baja = :id_tipo_baja
              AND estado_tipo_baja = 'activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_tipo_baja' => $idTipoBaja]);
    }
}
