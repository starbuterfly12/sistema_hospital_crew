<?php

require_once __DIR__ . '/../core/Model.php';

class Prestamo extends Model
{
    protected string $table = 'prestamos';

    public function getAll(): array
    {
        $sql = "
            SELECT
                p.id_prestamo,
                p.numero_prestamo,
                p.tipo_prestamo,
                p.numero_oficio,
                p.responsable_origen_mostrado,
                p.responsable_destino_mostrado,
                p.fecha_prestamo,
                p.fecha_devolucion_estimada,
                p.estado_prestamo,
                (
                    p.estado_prestamo IN ('activo', 'parcial')
                    AND p.fecha_devolucion_estimada < CURDATE()
                ) AS vencido
            FROM prestamos p
            ORDER BY p.id_prestamo DESC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idPrestamo): array|false
    {
        $sql = "
            SELECT
                p.id_prestamo,
                p.numero_prestamo,
                p.tipo_prestamo,
                p.numero_oficio,
                p.id_responsable_origen,
                p.responsable_origen_mostrado,
                p.id_responsable_destino,
                p.responsable_destino_mostrado,
                p.id_ubicacion_origen,
                p.ubicacion_origen_mostrada,
                p.id_ubicacion_destino,
                p.ubicacion_destino_mostrada,
                p.fecha_prestamo,
                p.fecha_devolucion_estimada,
                p.id_usuario_registra,
                usr.nombre_completo AS usuario_registra_nombre,
                p.motivo,
                p.estado_prestamo,
                p.observaciones,
                p.created_at,
                p.updated_at,
                (
                    p.estado_prestamo IN ('activo', 'parcial')
                    AND p.fecha_devolucion_estimada < CURDATE()
                ) AS vencido
            FROM prestamos p
            LEFT JOIN usuarios usr ON p.id_usuario_registra = usr.id_usuario
            WHERE p.id_prestamo = :id_prestamo
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_prestamo' => $idPrestamo]);
    }

    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_prestamo, 10) AS UNSIGNED)) AS ultimo_consecutivo
            FROM prestamos
            WHERE numero_prestamo LIKE :prefijo
        ";

        $prefijo = sprintf('PRE-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('PRE-%04d-%06d', $anio, $siguienteConsecutivo);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO prestamos (
                numero_prestamo,
                tipo_prestamo,
                numero_oficio,
                id_responsable_origen,
                responsable_origen_mostrado,
                id_responsable_destino,
                responsable_destino_mostrado,
                id_ubicacion_origen,
                ubicacion_origen_mostrada,
                id_ubicacion_destino,
                ubicacion_destino_mostrada,
                fecha_prestamo,
                fecha_devolucion_estimada,
                id_usuario_registra,
                motivo,
                observaciones
            ) VALUES (
                :numero_prestamo,
                :tipo_prestamo,
                :numero_oficio,
                :id_responsable_origen,
                :responsable_origen_mostrado,
                :id_responsable_destino,
                :responsable_destino_mostrado,
                :id_ubicacion_origen,
                :ubicacion_origen_mostrada,
                :id_ubicacion_destino,
                :ubicacion_destino_mostrada,
                :fecha_prestamo,
                :fecha_devolucion_estimada,
                :id_usuario_registra,
                :motivo,
                :observaciones
            )
        ";

        $params = [
            ':numero_prestamo' => $datos['numero_prestamo'],
            ':tipo_prestamo' => $datos['tipo_prestamo'],
            ':numero_oficio' => $datos['numero_oficio'],
            ':id_responsable_origen' => $datos['id_responsable_origen'],
            ':responsable_origen_mostrado' => $datos['responsable_origen_mostrado'],
            ':id_responsable_destino' => $datos['id_responsable_destino'],
            ':responsable_destino_mostrado' => $datos['responsable_destino_mostrado'],
            ':id_ubicacion_origen' => $datos['id_ubicacion_origen'],
            ':ubicacion_origen_mostrada' => $datos['ubicacion_origen_mostrada'],
            ':id_ubicacion_destino' => $datos['id_ubicacion_destino'],
            ':ubicacion_destino_mostrada' => $datos['ubicacion_destino_mostrada'],
            ':fecha_prestamo' => $datos['fecha_prestamo'],
            ':fecha_devolucion_estimada' => $datos['fecha_devolucion_estimada'],
            ':id_usuario_registra' => $datos['id_usuario_registra'],
            ':motivo' => $datos['motivo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }
}
