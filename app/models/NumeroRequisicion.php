<?php

require_once __DIR__ . '/../core/Model.php';

class NumeroRequisicion extends Model
{
    protected string $table = 'numeros_requisicion';

    public function insertar(int $idRequisicion, string $numeroRequisicion, string $fechaRequisicion): int
    {
        $sql = "
            INSERT INTO numeros_requisicion (
                id_requisicion,
                numero_requisicion,
                fecha_requisicion
            ) VALUES (
                :id_requisicion,
                :numero_requisicion,
                :fecha_requisicion
            )
        ";

        $this->query($sql, [
            ':id_requisicion' => $idRequisicion,
            ':numero_requisicion' => $numeroRequisicion,
            ':fecha_requisicion' => $fechaRequisicion,
        ]);

        return (int) $this->lastInsertId();
    }

    public function listarPorRequisicion(int $idRequisicion): array
    {
        $sql = "
            SELECT
                id_numero_requisicion,
                id_requisicion,
                numero_requisicion,
                fecha_requisicion
            FROM numeros_requisicion
            WHERE id_requisicion = :id_requisicion
            ORDER BY id_numero_requisicion ASC
        ";

        return $this->fetchAll($sql, [':id_requisicion' => $idRequisicion]);
    }

    // Usado en editar(): mientras la requisición está Pendiente, el conjunto completo de números se
    // reemplaza (DELETE + reinsertar en el orden enviado) en vez de diffear fila por fila — más simple
    // y seguro porque el orden y el contenido son enteramente reemplazables mientras no hay autorización.
    public function eliminarPorRequisicion(int $idRequisicion): bool
    {
        $sql = "DELETE FROM numeros_requisicion WHERE id_requisicion = :id_requisicion";

        $this->query($sql, [':id_requisicion' => $idRequisicion]);

        return true;
    }
}
