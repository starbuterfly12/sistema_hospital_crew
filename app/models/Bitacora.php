<?php

require_once __DIR__ . '/../core/Model.php';

class Bitacora extends Model
{
    protected string $table = 'bitacora';

    public function registrar(
        ?int $idUsuario,
        string $accion,
        string $modulo,
        string $resultado,
        ?string $descripcion,
        ?string $tablaAfectada = null,
        ?int $idRegistroAfectado = null,
        ?string $ipOrigen = null,
        ?string $usuarioIntentado = null
    ): bool {
        $sql = "
            INSERT INTO bitacora (
                id_usuario,
                usuario_intentado,
                accion,
                modulo,
                resultado,
                descripcion,
                tabla_afectada,
                id_registro_afectado,
                ip_origen
            ) VALUES (
                :id_usuario,
                :usuario_intentado,
                :accion,
                :modulo,
                :resultado,
                :descripcion,
                :tabla_afectada,
                :id_registro_afectado,
                :ip_origen
            )
        ";

        $this->query($sql, [
            'id_usuario' => $idUsuario,
            'usuario_intentado' => $usuarioIntentado,
            'accion' => $accion,
            'modulo' => $modulo,
            'resultado' => $resultado,
            'descripcion' => $descripcion,
            'tabla_afectada' => $tablaAfectada,
            'id_registro_afectado' => $idRegistroAfectado,
            'ip_origen' => $ipOrigen,
        ]);

        return true;
    }
}
