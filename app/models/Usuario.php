<?php

require_once __DIR__ . '/../core/Model.php';

class Usuario extends Model
{
    protected string $table = 'usuarios';

    public function findByUsuario(string $usuario): array|false
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.id_rol,
                u.nombre_completo,
                u.usuario,
                u.password_hash,
                u.estado_usuario,
                u.correo,
                u.telefono,
                r.nombre_rol,
                r.estado_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.usuario = :usuario
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':usuario' => $usuario]);
    }

    public function findById(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo,
                estado_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    public function getActivos(): array
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo
            FROM usuarios
            WHERE estado_usuario = 'activo'
            ORDER BY nombre_completo ASC
        ";

        return $this->fetchAll($sql);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findActivoByIdForUpdate(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo,
                estado_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
              AND estado_usuario = 'activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    // Usado exclusivamente para reverificar la contraseña del Administrador autenticado antes de una
    // acción sensible (ej. BajasController::autorizar()) con password_verify(). El hash nunca sale de
    // este método hacia una vista ni se registra en bitácora — el llamador debe descartarlo tras
    // verificar.
    public function findActivoConPasswordById(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo,
                password_hash,
                estado_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
              AND estado_usuario = 'activo'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }
}