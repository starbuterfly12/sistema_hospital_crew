<?php

require_once __DIR__ . '/../core/Model.php';

class Devolucion extends Model
{
    protected string $table = 'devoluciones';

    public function getAll(): array
    {
        $sql = "
            SELECT
                dev.id_devolucion,
                dev.numero_devolucion,
                dev.id_prestamo,
                p.numero_prestamo,
                p.tipo_prestamo,
                p.responsable_destino_mostrado,
                dev.fecha_devolucion,
                dev.estado_devolucion,
                p.estado_prestamo,
                usr.nombre_completo AS usuario_recibe_nombre,
                (
                    SELECT COUNT(*)
                    FROM detalle_devolucion dd
                    WHERE dd.id_devolucion = dev.id_devolucion
                ) AS cantidad_bienes
            FROM devoluciones dev
            INNER JOIN prestamos p ON p.id_prestamo = dev.id_prestamo
            LEFT JOIN usuarios usr ON usr.id_usuario = dev.id_usuario_recibe
            ORDER BY dev.id_devolucion DESC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idDevolucion): array|false
    {
        $sql = "
            SELECT
                dev.id_devolucion,
                dev.numero_devolucion,
                dev.id_prestamo,
                p.numero_prestamo,
                p.tipo_prestamo,
                p.responsable_origen_mostrado,
                p.ubicacion_origen_mostrada,
                p.responsable_destino_mostrado,
                p.ubicacion_destino_mostrada,
                p.estado_prestamo,
                dev.id_usuario_recibe,
                usr.nombre_completo AS usuario_recibe_nombre,
                dev.fecha_devolucion,
                dev.estado_devolucion,
                dev.motivo,
                dev.observaciones,
                dev.created_at,
                dev.updated_at
            FROM devoluciones dev
            INNER JOIN prestamos p ON p.id_prestamo = dev.id_prestamo
            LEFT JOIN usuarios usr ON usr.id_usuario = dev.id_usuario_recibe
            WHERE dev.id_devolucion = :id_devolucion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_devolucion' => $idDevolucion]);
    }

    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_devolucion, 10) AS UNSIGNED)) AS ultimo_consecutivo
            FROM devoluciones
            WHERE numero_devolucion LIKE :prefijo
        ";

        $prefijo = sprintf('DEV-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('DEV-%04d-%06d', $anio, $siguienteConsecutivo);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO devoluciones (
                numero_devolucion,
                id_prestamo,
                id_usuario_recibe,
                fecha_devolucion,
                estado_devolucion,
                motivo,
                observaciones
            ) VALUES (
                :numero_devolucion,
                :id_prestamo,
                :id_usuario_recibe,
                :fecha_devolucion,
                :estado_devolucion,
                :motivo,
                :observaciones
            )
        ";

        $params = [
            ':numero_devolucion' => $datos['numero_devolucion'],
            ':id_prestamo' => $datos['id_prestamo'],
            ':id_usuario_recibe' => $datos['id_usuario_recibe'],
            ':fecha_devolucion' => $datos['fecha_devolucion'],
            ':estado_devolucion' => $datos['estado_devolucion'],
            ':motivo' => $datos['motivo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }
}
