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
}