<?php

require_once __DIR__ . '/../core/Model.php';

class Responsable extends Model
{
    protected string $table = 'responsables';

    public function getActivos(): array
    {
        $sql = "
            SELECT
                r.id_responsable,
                r.nombre_completo,
                r.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM responsables r
            JOIN ubicaciones u ON r.id_ubicacion = u.id_ubicacion
            WHERE r.estado_responsable = 'activo'
            ORDER BY r.nombre_completo ASC
        ";

        return $this->fetchAll($sql);
    }

    public function getActivosConBienesAsignados(): array
    {
        $sql = "
            SELECT
                r.id_responsable,
                r.nombre_completo,
                r.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM responsables r
            JOIN ubicaciones u ON r.id_ubicacion = u.id_ubicacion
            WHERE r.estado_responsable = 'activo'
              AND EXISTS (
                    SELECT 1
                    FROM bienes b
                    WHERE b.id_responsable_actual = r.id_responsable
              )
            ORDER BY r.nombre_completo ASC
        ";

        return $this->fetchAll($sql);
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                r.id_responsable,
                r.nombre_completo,
                r.cargo,
                r.profesion,
                r.nit,
                r.telefono,
                r.id_ubicacion,
                r.estado_responsable,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM responsables r
            LEFT JOIN ubicaciones u ON r.id_ubicacion = u.id_ubicacion
            ORDER BY r.nombre_completo ASC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idResponsable): array|false
    {
        $sql = "
            SELECT
                r.id_responsable,
                r.nombre_completo,
                r.cargo,
                r.profesion,
                r.nit,
                r.telefono,
                r.id_ubicacion,
                r.estado_responsable,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM responsables r
            LEFT JOIN ubicaciones u ON r.id_ubicacion = u.id_ubicacion
            WHERE r.id_responsable = :id_responsable
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_responsable' => $idResponsable]);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO responsables (
                nombre_completo,
                cargo,
                profesion,
                nit,
                telefono,
                id_ubicacion,
                estado_responsable
            ) VALUES (
                :nombre_completo,
                :cargo,
                :profesion,
                :nit,
                :telefono,
                :id_ubicacion,
                :estado_responsable
            )
        ";

        $params = [
            ':nombre_completo' => $datos['nombre_completo'] ?? null,
            ':cargo' => $datos['cargo'] ?? null,
            ':profesion' => $datos['profesion'] ?? null,
            ':nit' => $datos['nit'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':id_ubicacion' => $datos['id_ubicacion'] ?? null,
            ':estado_responsable' => $datos['estado_responsable'] ?? 'activo',
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function actualizar(int $idResponsable, array $datos): bool
    {
        $sql = "
            UPDATE responsables
            SET
                nombre_completo = :nombre_completo,
                cargo = :cargo,
                profesion = :profesion,
                nit = :nit,
                telefono = :telefono,
                id_ubicacion = :id_ubicacion,
                estado_responsable = :estado_responsable
            WHERE id_responsable = :id_responsable
        ";

        $params = [
            ':nombre_completo' => $datos['nombre_completo'] ?? null,
            ':cargo' => $datos['cargo'] ?? null,
            ':profesion' => $datos['profesion'] ?? null,
            ':nit' => $datos['nit'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':id_ubicacion' => $datos['id_ubicacion'] ?? null,
            ':estado_responsable' => $datos['estado_responsable'] ?? null,
            ':id_responsable' => $idResponsable,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function cambiarEstado(int $idResponsable, string $estado): bool
    {
        $sql = "
            UPDATE responsables
            SET estado_responsable = :estado_responsable
            WHERE id_responsable = :id_responsable
        ";

        $params = [
            ':estado_responsable' => $estado,
            ':id_responsable' => $idResponsable,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function existeNit(string $nit, ?int $excluirId = null): bool
    {
        if ($excluirId === null) {
            $sql = "
                SELECT id_responsable
                FROM responsables
                WHERE nit = :nit
                LIMIT 1
            ";

            $params = [':nit' => $nit];
        } else {
            $sql = "
                SELECT id_responsable
                FROM responsables
                WHERE nit = :nit AND id_responsable <> :id_responsable
                LIMIT 1
            ";

            $params = [
                ':nit' => $nit,
                ':id_responsable' => $excluirId,
            ];
        }

        return $this->fetchOne($sql, $params) !== false;
    }

    public function buscar(string $termino): array
    {
        $sql = "
            SELECT
                r.id_responsable,
                r.nombre_completo,
                r.cargo,
                r.profesion,
                r.nit,
                r.telefono,
                r.id_ubicacion,
                r.estado_responsable,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM responsables r
            LEFT JOIN ubicaciones u ON r.id_ubicacion = u.id_ubicacion
            WHERE r.nombre_completo LIKE :termino_nombre
               OR r.nit LIKE :termino_nit
            ORDER BY r.nombre_completo ASC
        ";

        $like = '%' . $termino . '%';

        return $this->fetchAll($sql, [
            ':termino_nombre' => $like,
            ':termino_nit' => $like,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findActivoByIdForUpdate(int $idResponsable): array|false
    {
        $sql = "
            SELECT
                id_responsable,
                nombre_completo,
                id_ubicacion,
                estado_responsable
            FROM responsables
            WHERE id_responsable = :id_responsable
              AND estado_responsable = 'activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_responsable' => $idResponsable]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findByIdForUpdate(int $idResponsable): array|false
    {
        $sql = "
            SELECT
                id_responsable,
                nombre_completo,
                cargo,
                nit,
                id_ubicacion,
                estado_responsable
            FROM responsables
            WHERE id_responsable = :id_responsable
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_responsable' => $idResponsable]);
    }
}
