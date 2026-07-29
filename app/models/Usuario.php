<?php
require_once APP_ROOT . '/app/core/Model.php';

class Usuario extends Model
{
    /**
     * Busca un usuario por su nombre de usuario, incluyendo el rol.
     * Se trae el registro exista o no el estado activo, para poder
     * distinguir "no existe" de "existe pero inactivo/bloqueado".
     */
    public function findByUsuario(string $usuario): array|false
    {
        $sql = "SELECT u.id_usuario, u.id_rol, u.nombre_completo, u.usuario,
                       u.password_hash, u.estado_usuario, u.correo, u.telefono,
                       r.nombre_rol, r.estado_rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.id_rol
                WHERE u.usuario = :usuario
                LIMIT 1";

        return $this->fetchOne($sql, ['usuario' => $usuario]);
    }

    public function touchUltimoAcceso(int $idUsuario): void
    {
        $this->query(
            "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id",
            ['id' => $idUsuario]
        );
    }
}
