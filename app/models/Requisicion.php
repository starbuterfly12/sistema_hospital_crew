<?php

require_once __DIR__ . '/../core/Model.php';

class Requisicion extends Model
{
    protected string $table = 'requisiciones';

    // Filtros opcionales para el listado (por GET, consulta preparada). Sin argumentos se comporta
    // EXACTAMENTE igual que antes (sin WHERE).
    //   $q      -> LIKE sobre numero_requisicion_sistema / numero_oficio / responsable_solicitante_mostrado
    //              y también sobre cualquier numero_requisicion institucional asociado (EXISTS).
    //   $estado -> r.estado_requisicion exacto ('Pendiente' / 'Autorizada' / 'Entregada' / 'Anulada')
    public function getAll(?string $q = null, ?string $estado = null): array
    {
        $condiciones = [];
        $params = [];

        if ($q !== null && trim($q) !== '') {
            // Placeholders distintos por ocurrencia: con PDO::ATTR_EMULATE_PREPARES=false un mismo
            // nombre no puede repetirse en la consulta.
            $condiciones[] = "(
                r.numero_requisicion_sistema LIKE :q_sistema
                OR r.numero_oficio LIKE :q_oficio
                OR r.responsable_solicitante_mostrado LIKE :q_resp
                OR EXISTS (
                    SELECT 1 FROM numeros_requisicion nrf
                    WHERE nrf.id_requisicion = r.id_requisicion
                      AND nrf.numero_requisicion LIKE :q_inst
                )
            )";
            $like = '%' . trim($q) . '%';
            $params[':q_sistema'] = $like;
            $params[':q_oficio'] = $like;
            $params[':q_resp'] = $like;
            $params[':q_inst'] = $like;
        }

        if ($estado !== null && trim($estado) !== '') {
            $condiciones[] = "r.estado_requisicion = :estado";
            $params[':estado'] = trim($estado);
        }

        $where = $condiciones !== [] ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "
            SELECT
                r.id_requisicion,
                r.numero_requisicion_sistema,
                r.numero_oficio,
                r.responsable_solicitante_mostrado,
                r.ubicacion_solicitante_mostrada,
                r.estado_requisicion,
                r.created_at,
                (
                    SELECT COUNT(*)
                    FROM detalle_requisicion dr
                    WHERE dr.id_requisicion = r.id_requisicion
                      AND dr.estado_detalle <> 'anulado'
                ) AS total_bienes,
                (
                    SELECT GROUP_CONCAT(nr.numero_requisicion ORDER BY nr.id_numero_requisicion ASC SEPARATOR ', ')
                    FROM numeros_requisicion nr
                    WHERE nr.id_requisicion = r.id_requisicion
                ) AS numeros_institucionales
            FROM requisiciones r
            {$where}
            ORDER BY r.id_requisicion DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    public function findById(int $idRequisicion): array|false
    {
        $sql = "
            SELECT
                r.id_requisicion,
                r.numero_requisicion_sistema,
                r.numero_oficio,
                r.id_responsable_solicitante,
                r.responsable_solicitante_mostrado,
                r.id_ubicacion_solicitante,
                r.ubicacion_solicitante_mostrada,
                r.id_usuario_registra,
                usr.nombre_completo AS usuario_registra_nombre,
                r.id_usuario_autoriza,
                usa.nombre_completo AS usuario_autoriza_nombre,
                r.fecha_autorizacion,
                r.id_usuario_entrega,
                uent.nombre_completo AS usuario_entrega_nombre,
                r.fecha_entrega,
                r.estado_requisicion,
                r.motivo_anulacion,
                r.observaciones,
                r.created_at,
                r.updated_at
            FROM requisiciones r
            LEFT JOIN usuarios usr ON r.id_usuario_registra = usr.id_usuario
            LEFT JOIN usuarios usa ON r.id_usuario_autoriza = usa.id_usuario
            LEFT JOIN usuarios uent ON r.id_usuario_entrega = uent.id_usuario
            WHERE r.id_requisicion = :id_requisicion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_requisicion' => $idRequisicion]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findByIdForUpdate(int $idRequisicion): array|false
    {
        $sql = "
            SELECT
                id_requisicion,
                numero_requisicion_sistema,
                numero_oficio,
                id_responsable_solicitante,
                responsable_solicitante_mostrado,
                id_ubicacion_solicitante,
                ubicacion_solicitante_mostrada,
                estado_requisicion
            FROM requisiciones
            WHERE id_requisicion = :id_requisicion
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_requisicion' => $idRequisicion]);
    }

    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_requisicion_sistema, 10) AS UNSIGNED)) AS ultimo_consecutivo
            FROM requisiciones
            WHERE numero_requisicion_sistema LIKE :prefijo
        ";

        $prefijo = sprintf('REQ-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('REQ-%04d-%06d', $anio, $siguienteConsecutivo);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO requisiciones (
                numero_requisicion_sistema,
                numero_oficio,
                id_responsable_solicitante,
                responsable_solicitante_mostrado,
                id_ubicacion_solicitante,
                ubicacion_solicitante_mostrada,
                id_usuario_registra,
                estado_requisicion,
                observaciones
            ) VALUES (
                :numero_requisicion_sistema,
                :numero_oficio,
                :id_responsable_solicitante,
                :responsable_solicitante_mostrado,
                :id_ubicacion_solicitante,
                :ubicacion_solicitante_mostrada,
                :id_usuario_registra,
                'Pendiente',
                :observaciones
            )
        ";

        $params = [
            ':numero_requisicion_sistema' => $datos['numero_requisicion_sistema'],
            ':numero_oficio' => $datos['numero_oficio'],
            ':id_responsable_solicitante' => $datos['id_responsable_solicitante'],
            ':responsable_solicitante_mostrado' => $datos['responsable_solicitante_mostrado'],
            ':id_ubicacion_solicitante' => $datos['id_ubicacion_solicitante'],
            ':ubicacion_solicitante_mostrada' => $datos['ubicacion_solicitante_mostrada'],
            ':id_usuario_registra' => $datos['id_usuario_registra'],
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // Solo debe llamarse con la cabecera ya bloqueada (findByIdForUpdate) y confirmada en Pendiente.
    public function actualizarCabecera(int $idRequisicion, array $datos): bool
    {
        $sql = "
            UPDATE requisiciones
            SET
                numero_oficio = :numero_oficio,
                id_responsable_solicitante = :id_responsable_solicitante,
                responsable_solicitante_mostrado = :responsable_solicitante_mostrado,
                id_ubicacion_solicitante = :id_ubicacion_solicitante,
                ubicacion_solicitante_mostrada = :ubicacion_solicitante_mostrada,
                observaciones = :observaciones,
                updated_at = NOW()
            WHERE id_requisicion = :id_requisicion
              AND estado_requisicion = 'Pendiente'
        ";

        $this->query($sql, [
            ':numero_oficio' => $datos['numero_oficio'],
            ':id_responsable_solicitante' => $datos['id_responsable_solicitante'],
            ':responsable_solicitante_mostrado' => $datos['responsable_solicitante_mostrado'],
            ':id_ubicacion_solicitante' => $datos['id_ubicacion_solicitante'],
            ':ubicacion_solicitante_mostrada' => $datos['ubicacion_solicitante_mostrada'],
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_requisicion' => $idRequisicion,
        ]);

        return true;
    }

    public function autorizar(int $idRequisicion, int $idUsuarioAutoriza): bool
    {
        $sql = "
            UPDATE requisiciones
            SET
                estado_requisicion = 'Autorizada',
                id_usuario_autoriza = :id_usuario_autoriza,
                fecha_autorizacion = NOW()
            WHERE id_requisicion = :id_requisicion
              AND estado_requisicion = 'Pendiente'
        ";

        $this->query($sql, [
            ':id_usuario_autoriza' => $idUsuarioAutoriza,
            ':id_requisicion' => $idRequisicion,
        ]);

        return true;
    }

    public function confirmarEntrega(int $idRequisicion, int $idUsuarioEntrega): bool
    {
        $sql = "
            UPDATE requisiciones
            SET
                estado_requisicion = 'Entregada',
                id_usuario_entrega = :id_usuario_entrega,
                fecha_entrega = NOW()
            WHERE id_requisicion = :id_requisicion
              AND estado_requisicion = 'Autorizada'
        ";

        $this->query($sql, [
            ':id_usuario_entrega' => $idUsuarioEntrega,
            ':id_requisicion' => $idRequisicion,
        ]);

        return true;
    }

    public function anular(int $idRequisicion, string $motivoAnulacion): bool
    {
        $sql = "
            UPDATE requisiciones
            SET
                estado_requisicion = 'Anulada',
                motivo_anulacion = :motivo_anulacion
            WHERE id_requisicion = :id_requisicion
              AND estado_requisicion IN ('Pendiente', 'Autorizada')
        ";

        $this->query($sql, [
            ':motivo_anulacion' => $motivoAnulacion,
            ':id_requisicion' => $idRequisicion,
        ]);

        return true;
    }
}
