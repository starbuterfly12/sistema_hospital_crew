<?php
require_once APP_ROOT . '/app/core/Model.php';

class Bitacora extends Model
{
    /**
     * Registra una entrada de bitacora.
     *
     * @param int|null    $idUsuario         Usuario relacionado; NULL si el intento no corresponde a un usuario existente.
     * @param string|null $usuarioIntentado  Nombre de usuario ingresado en el intento (exista o no en el sistema).
     * @param string      $accion            Ej: "Inicio de sesion", "Cierre de sesion".
     * @param string      $modulo            Modulo del sistema, ej: "Autenticacion".
     * @param string      $resultado         Ej: "exitoso", "fallido", "denegado", "error".
     * @param string|null $descripcion       Detalle adicional de la accion.
     * @param string|null $tablaAfectada     Tabla afectada, cuando aplique.
     * @param int|null    $idRegistroAfectado Id del registro afectado, cuando aplique.
     */
    public function registrar(
        ?int $idUsuario,
        ?string $usuarioIntentado,
        string $accion,
        string $modulo,
        string $resultado,
        ?string $descripcion,
        ?string $tablaAfectada = null,
        ?int $idRegistroAfectado = null
    ): void {
        $sql = "INSERT INTO bitacora
                    (id_usuario, usuario_intentado, accion, modulo, resultado,
                     descripcion, fecha_hora, tabla_afectada, id_registro_afectado, ip_origen)
                VALUES
                    (:id_usuario, :usuario_intentado, :accion, :modulo, :resultado,
                     :descripcion, NOW(), :tabla_afectada, :id_registro_afectado, :ip_origen)";

        $this->query($sql, [
            'id_usuario'           => $idUsuario,
            'usuario_intentado'    => $usuarioIntentado,
            'accion'               => $accion,
            'modulo'               => $modulo,
            'resultado'            => $resultado,
            'descripcion'          => $descripcion,
            'tabla_afectada'       => $tablaAfectada,
            'id_registro_afectado' => $idRegistroAfectado,
            'ip_origen'            => clientIp(),
        ]);
    }
}
