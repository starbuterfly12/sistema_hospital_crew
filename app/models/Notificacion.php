<?php

require_once __DIR__ . '/../core/Model.php';

/**
 * Notificaciones internas del sistema (tabla `notificaciones`, ver
 * database/2026_08_27_notificaciones.sql).
 *
 * Se CREAN en el momento exacto de un evento real del flujo (registro / autorización / rechazo
 * de Requisiciones y Solicitudes de baja) y SIEMPRE después de que la operación principal ya
 * confirmó su transacción — nunca dentro de ella: una notificación que no se puede insertar no
 * debe tumbar la operación que la originó (ver el try/catch de cada controlador llamador).
 *
 * `titulo` y `mensaje` se guardan como texto plano (nunca HTML) y se escapan al renderizar.
 * `url_destino` la construye SIEMPRE el servidor (rutas internas index.php?...), nunca viene del
 * usuario; aun así el controlador que redirige la revalida como interna antes de usarla.
 */
class Notificacion extends Model
{
    protected string $table = 'notificaciones';

    private const LIMITE_PANEL = 10;

    /**
     * Inserta una notificación para un único usuario. Devuelve el id creado.
     */
    public function crear(int $idUsuario, string $titulo, string $mensaje, string $urlDestino): int
    {
        $sql = "
            INSERT INTO notificaciones (id_usuario, titulo, mensaje, url_destino)
            VALUES (:id_usuario, :titulo, :mensaje, :url_destino)
        ";

        $this->query($sql, [
            ':id_usuario' => $idUsuario,
            ':titulo' => mb_substr(trim($titulo), 0, 150),
            ':mensaje' => mb_substr(trim($mensaje), 0, 255),
            ':url_destino' => mb_substr(trim($urlDestino), 0, 255),
        ]);

        return (int) $this->lastInsertId();
    }

    /**
     * Inserta la MISMA notificación para varios usuarios (ej. todos los Administradores activos
     * cuando entra una solicitud pendiente). Ignora ids <= 0 y duplicados en la lista recibida.
     */
    public function crearParaVarios(array $idsUsuarios, string $titulo, string $mensaje, string $urlDestino): int
    {
        $creadas = 0;

        foreach (array_unique(array_map('intval', $idsUsuarios)) as $idUsuario) {
            if ($idUsuario <= 0) {
                continue;
            }

            $this->crear($idUsuario, $titulo, $mensaje, $urlDestino);
            $creadas++;
        }

        return $creadas;
    }

    /**
     * Cantidad de notificaciones NO leídas del usuario — alimenta el badge de la campana.
     */
    public function contarNoLeidas(int $idUsuario): int
    {
        $fila = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM notificaciones WHERE id_usuario = :id_usuario AND leida = 0',
            [':id_usuario' => $idUsuario]
        );

        return $fila !== false ? (int) $fila['total'] : 0;
    }

    /**
     * Últimas notificaciones del usuario (leídas y no leídas), más recientes primero.
     * $limite se acota a LIMITE_PANEL para no volcar cientos en cada render del layout.
     */
    public function ultimasPara(int $idUsuario, int $limite = self::LIMITE_PANEL): array
    {
        $limite = max(1, min($limite, self::LIMITE_PANEL));

        // LIMIT interpolado como entero ya saneado (mismo criterio que Bitacora::getAll): con
        // EMULATE_PREPARES=false algunos drivers no aceptan placeholder en LIMIT.
        $sql = "
            SELECT id_notificacion, titulo, mensaje, url_destino, leida, fecha_creacion, fecha_lectura
            FROM notificaciones
            WHERE id_usuario = :id_usuario
            ORDER BY fecha_creacion DESC, id_notificacion DESC
            LIMIT {$limite}
        ";

        return $this->fetchAll($sql, [':id_usuario' => $idUsuario]);
    }

    /**
     * Devuelve la notificación SOLO si pertenece al usuario indicado. Base de la protección de
     * ownership: un usuario nunca puede abrir/marcar una notificación de otro cambiando el id.
     */
    public function findDeUsuario(int $idNotificacion, int $idUsuario): array|false
    {
        return $this->fetchOne(
            'SELECT id_notificacion, id_usuario, titulo, mensaje, url_destino, leida, fecha_creacion, fecha_lectura
             FROM notificaciones
             WHERE id_notificacion = :id_notificacion AND id_usuario = :id_usuario
             LIMIT 1',
            [':id_notificacion' => $idNotificacion, ':id_usuario' => $idUsuario]
        );
    }

    /**
     * Marca una notificación como leída, acotado al propietario. Idempotente: no reescribe
     * fecha_lectura si ya estaba leída.
     */
    public function marcarLeida(int $idNotificacion, int $idUsuario): bool
    {
        $this->query(
            'UPDATE notificaciones
                SET leida = 1, fecha_lectura = NOW()
             WHERE id_notificacion = :id_notificacion AND id_usuario = :id_usuario AND leida = 0',
            [':id_notificacion' => $idNotificacion, ':id_usuario' => $idUsuario]
        );

        return true;
    }

    /**
     * Marca TODAS las no leídas del usuario. Afecta únicamente a sus propias filas.
     * Devuelve cuántas se marcaron.
     */
    public function marcarTodasLeidas(int $idUsuario): int
    {
        $stmt = $this->query(
            'UPDATE notificaciones
                SET leida = 1, fecha_lectura = NOW()
             WHERE id_usuario = :id_usuario AND leida = 0',
            [':id_usuario' => $idUsuario]
        );

        return $stmt->rowCount();
    }
}
