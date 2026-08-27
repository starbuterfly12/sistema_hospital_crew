<?php

require_once __DIR__ . '/../core/Model.php';

class TarjetaResponsabilidad extends Model
{
    protected string $table = 'tarjetas_responsabilidad';

    // $q      -> búsqueda libre (LIKE) sobre número de tarjeta / responsable / ubicación / número de
    //            asignación. Placeholders distintos por ocurrencia (PDO::ATTR_EMULATE_PREPARES=false).
    // $estado -> t.estado_tarjeta exacto ('Emitida' / 'Anulada').
    // Sin argumentos devuelve el listado completo (comportamiento previo, única consumidora index()).
    public function getAll(?string $q = null, ?string $estado = null): array
    {
        $condiciones = [];
        $params = [];

        if ($q !== null && trim($q) !== '') {
            $condiciones[] = "(
                t.numero_tarjeta LIKE :q_num
                OR t.responsable_nombre LIKE :q_resp
                OR t.ubicacion_nombre LIKE :q_ubi
                OR a.numero_asignacion LIKE :q_asig
            )";
            $like = '%' . trim($q) . '%';
            $params[':q_num'] = $like;
            $params[':q_resp'] = $like;
            $params[':q_ubi'] = $like;
            $params[':q_asig'] = $like;
        }

        if ($estado !== null && trim($estado) !== '') {
            $condiciones[] = "t.estado_tarjeta = :estado";
            $params[':estado'] = trim($estado);
        }

        $where = $condiciones !== [] ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "
            SELECT
                t.id_tarjeta_responsabilidad,
                t.numero_tarjeta,
                t.id_asignacion,
                a.numero_asignacion,
                t.responsable_nombre,
                t.ubicacion_nombre,
                t.fecha_emision,
                t.estado_tarjeta
            FROM tarjetas_responsabilidad t
            LEFT JOIN asignaciones a ON t.id_asignacion = a.id_asignacion
            {$where}
            ORDER BY t.id_tarjeta_responsabilidad DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    public function findById(int $idTarjetaResponsabilidad): array|false
    {
        $sql = "
            SELECT
                t.id_tarjeta_responsabilidad,
                t.numero_tarjeta,
                t.id_asignacion,
                a.numero_asignacion,
                t.id_responsable,
                t.responsable_nombre,
                t.responsable_nit,
                t.responsable_cargo,
                t.id_ubicacion,
                t.ubicacion_nombre,
                t.fecha_emision,
                t.estado_tarjeta,
                t.id_usuario_genera,
                usu.nombre_completo AS usuario_genera_nombre,
                t.created_at,
                t.updated_at
            FROM tarjetas_responsabilidad t
            LEFT JOIN asignaciones a ON t.id_asignacion = a.id_asignacion
            LEFT JOIN usuarios usu ON t.id_usuario_genera = usu.id_usuario
            WHERE t.id_tarjeta_responsabilidad = :id_tarjeta_responsabilidad
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_tarjeta_responsabilidad' => $idTarjetaResponsabilidad]);
    }

    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_tarjeta, 9) AS UNSIGNED)) AS ultimo_consecutivo
            FROM tarjetas_responsabilidad
            WHERE numero_tarjeta LIKE :prefijo
        ";

        $prefijo = sprintf('TR-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('TR-%04d-%06d', $anio, $siguienteConsecutivo);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO tarjetas_responsabilidad (
                numero_tarjeta,
                id_asignacion,
                id_responsable,
                responsable_nombre,
                responsable_nit,
                responsable_cargo,
                id_ubicacion,
                ubicacion_nombre,
                fecha_emision,
                estado_tarjeta,
                id_usuario_genera
            ) VALUES (
                :numero_tarjeta,
                :id_asignacion,
                :id_responsable,
                :responsable_nombre,
                :responsable_nit,
                :responsable_cargo,
                :id_ubicacion,
                :ubicacion_nombre,
                :fecha_emision,
                :estado_tarjeta,
                :id_usuario_genera
            )
        ";

        $params = [
            ':numero_tarjeta' => $datos['numero_tarjeta'],
            ':id_asignacion' => $datos['id_asignacion'],
            ':id_responsable' => $datos['id_responsable'],
            ':responsable_nombre' => $datos['responsable_nombre'],
            ':responsable_nit' => $datos['responsable_nit'],
            ':responsable_cargo' => $datos['responsable_cargo'],
            ':id_ubicacion' => $datos['id_ubicacion'],
            ':ubicacion_nombre' => $datos['ubicacion_nombre'],
            ':fecha_emision' => $datos['fecha_emision'],
            ':estado_tarjeta' => $datos['estado_tarjeta'] ?? 'Emitida',
            ':id_usuario_genera' => $datos['id_usuario_genera'],
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }
}
