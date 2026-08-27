<?php

require_once __DIR__ . '/../core/Model.php';

class Movimiento extends Model
{
    protected string $table = 'movimientos';

    // Filtro opcional para el listado de Traslados (por GET, consulta preparada). Sin argumento se
    // comporta EXACTAMENTE igual que antes (sin WHERE).
    //   $q -> LIKE sobre numero_movimiento y los nombres de responsable/ubicación de origen y destino.
    public function getAll(?string $q = null): array
    {
        $where = '';
        $params = [];

        if ($q !== null && trim($q) !== '') {
            // Placeholders distintos por ocurrencia: con PDO::ATTR_EMULATE_PREPARES=false un mismo
            // nombre no puede repetirse en la consulta.
            $where = "
                WHERE m.numero_movimiento LIKE :q_num
                   OR ro.nombre_completo LIKE :q_ro
                   OR rd.nombre_completo LIKE :q_rd
                   OR uo.nombre_ubicacion LIKE :q_uo
                   OR ud.nombre_ubicacion LIKE :q_ud
            ";
            $like = '%' . trim($q) . '%';
            $params[':q_num'] = $like;
            $params[':q_ro'] = $like;
            $params[':q_rd'] = $like;
            $params[':q_uo'] = $like;
            $params[':q_ud'] = $like;
        }

        $sql = "
            SELECT
                m.id_movimiento,
                m.numero_movimiento,
                m.fecha_movimiento,
                ro.nombre_completo AS responsable_origen_nombre,
                uo.nombre_ubicacion AS ubicacion_origen_nombre,
                rd.nombre_completo AS responsable_destino_nombre,
                ud.nombre_ubicacion AS ubicacion_destino_nombre,
                usr.nombre_completo AS usuario_registra_nombre,
                (
                    SELECT COUNT(*)
                    FROM detalle_movimiento dm
                    WHERE dm.id_movimiento = m.id_movimiento
                ) AS cantidad_bienes
            FROM movimientos m
            LEFT JOIN responsables ro ON m.id_responsable_origen = ro.id_responsable
            LEFT JOIN ubicaciones uo ON m.id_ubicacion_origen = uo.id_ubicacion
            LEFT JOIN responsables rd ON m.id_responsable_destino = rd.id_responsable
            LEFT JOIN ubicaciones ud ON m.id_ubicacion_destino = ud.id_ubicacion
            LEFT JOIN usuarios usr ON m.id_usuario_registra = usr.id_usuario
            {$where}
            ORDER BY m.id_movimiento DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_movimiento, 10) AS UNSIGNED)) AS ultimo_consecutivo
            FROM movimientos
            WHERE numero_movimiento LIKE :prefijo
        ";

        $prefijo = sprintf('TRA-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('TRA-%04d-%06d', $anio, $siguienteConsecutivo);
    }

    public function findTipoPorNombre(string $nombreMovimiento): array|false
    {
        $sql = "
            SELECT id_tipo_movimiento, nombre_movimiento, requiere_autorizacion, estado_tipo_movimiento
            FROM tipos_movimiento
            WHERE nombre_movimiento = :nombre_movimiento
              AND estado_tipo_movimiento = 'activo'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':nombre_movimiento' => $nombreMovimiento]);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO movimientos (
                numero_movimiento,
                id_tipo_movimiento,
                id_responsable_origen,
                id_ubicacion_origen,
                id_asignacion_destino,
                id_responsable_destino,
                id_ubicacion_destino,
                id_usuario_registra,
                id_usuario_autoriza,
                fecha_movimiento,
                motivo,
                observaciones
            ) VALUES (
                :numero_movimiento,
                :id_tipo_movimiento,
                :id_responsable_origen,
                :id_ubicacion_origen,
                :id_asignacion_destino,
                :id_responsable_destino,
                :id_ubicacion_destino,
                :id_usuario_registra,
                :id_usuario_autoriza,
                :fecha_movimiento,
                :motivo,
                :observaciones
            )
        ";

        $params = [
            ':numero_movimiento' => $datos['numero_movimiento'],
            ':id_tipo_movimiento' => $datos['id_tipo_movimiento'],
            ':id_responsable_origen' => $datos['id_responsable_origen'],
            ':id_ubicacion_origen' => $datos['id_ubicacion_origen'],
            ':id_asignacion_destino' => $datos['id_asignacion_destino'] ?? null,
            ':id_responsable_destino' => $datos['id_responsable_destino'],
            ':id_ubicacion_destino' => $datos['id_ubicacion_destino'],
            ':id_usuario_registra' => $datos['id_usuario_registra'],
            ':id_usuario_autoriza' => $datos['id_usuario_autoriza'] ?? null,
            ':fecha_movimiento' => $datos['fecha_movimiento'] ?? date('Y-m-d H:i:s'),
            ':motivo' => $datos['motivo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function findById(int $idMovimiento): array|false
    {
        $sql = "
            SELECT
                m.id_movimiento,
                m.numero_movimiento,
                m.id_tipo_movimiento,
                tm.nombre_movimiento,
                m.id_responsable_origen,
                ro.nombre_completo AS responsable_origen_nombre,
                m.id_ubicacion_origen,
                uo.nombre_ubicacion AS ubicacion_origen_nombre,
                m.id_asignacion_destino,
                ad.numero_asignacion AS numero_asignacion_destino,
                m.id_responsable_destino,
                rd.nombre_completo AS responsable_destino_nombre,
                m.id_ubicacion_destino,
                ud.nombre_ubicacion AS ubicacion_destino_nombre,
                m.id_usuario_registra,
                usr.nombre_completo AS usuario_registra_nombre,
                m.id_usuario_autoriza,
                usa.nombre_completo AS usuario_autoriza_nombre,
                m.fecha_movimiento,
                m.motivo,
                m.observaciones,
                m.created_at,
                m.updated_at
            FROM movimientos m
            LEFT JOIN tipos_movimiento tm ON m.id_tipo_movimiento = tm.id_tipo_movimiento
            LEFT JOIN responsables ro ON m.id_responsable_origen = ro.id_responsable
            LEFT JOIN ubicaciones uo ON m.id_ubicacion_origen = uo.id_ubicacion
            LEFT JOIN asignaciones ad ON m.id_asignacion_destino = ad.id_asignacion
            LEFT JOIN responsables rd ON m.id_responsable_destino = rd.id_responsable
            LEFT JOIN ubicaciones ud ON m.id_ubicacion_destino = ud.id_ubicacion
            LEFT JOIN usuarios usr ON m.id_usuario_registra = usr.id_usuario
            LEFT JOIN usuarios usa ON m.id_usuario_autoriza = usa.id_usuario
            WHERE m.id_movimiento = :id_movimiento
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_movimiento' => $idMovimiento]);
    }
}
