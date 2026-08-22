<?php

require_once __DIR__ . '/../core/Model.php';

class Rol extends Model
{
    protected string $table = 'roles';

    public function getActivos(): array
    {
        $sql = "
            SELECT id_rol, nombre_rol
            FROM roles
            WHERE estado_rol = 'activo'
            ORDER BY nombre_rol ASC
        ";

        return $this->fetchAll($sql);
    }

    // No filtra por estado_rol: el rol Administrador debe seguir siendo resoluble para las reglas de
    // protección de Usuarios (ver UsuariosController) incluso en el caso extremo de que ese rol se
    // hubiera inactivado.
    public function obtenerIdPorNombre(string $nombreRol): ?int
    {
        $sql = "
            SELECT id_rol
            FROM roles
            WHERE nombre_rol = :nombre_rol
            LIMIT 1
        ";

        $fila = $this->fetchOne($sql, [':nombre_rol' => $nombreRol]);

        return $fila !== false ? (int) $fila['id_rol'] : null;
    }
}