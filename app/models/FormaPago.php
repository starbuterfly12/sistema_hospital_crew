<?php

require_once __DIR__ . '/../core/Model.php';

// Catálogo de formas de pago para bienes ingresados por Compra (ingreso_compra.id_forma_pago).
// Solo lectura en esta fase: no hay CRUD administrativo; las opciones nuevas se agregan por SQL
// al catálogo hasta que se decida si merece módulo propio. Mismo patrón que FormaIngreso.
class FormaPago extends Model
{
    protected string $table = 'formas_pago';

    public function getActivas(): array
    {
        $sql = "
            SELECT
                id_forma_pago,
                nombre_forma_pago
            FROM formas_pago
            WHERE estado_forma_pago = 'activa'
            ORDER BY nombre_forma_pago ASC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idFormaPago): array|false
    {
        $sql = "
            SELECT
                id_forma_pago,
                nombre_forma_pago,
                estado_forma_pago
            FROM formas_pago
            WHERE id_forma_pago = :id_forma_pago
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_forma_pago' => $idFormaPago]);
    }
}
