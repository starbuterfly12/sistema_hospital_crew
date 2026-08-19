<?php

require_once __DIR__ . '/../core/Model.php';

class Asignacion extends Model
{
    protected string $table = 'asignaciones';

    public function getAll(): array
    {
        $sql = "
            SELECT
                a.id_asignacion,
                a.numero_asignacion,
                a.id_responsable,
                r.nombre_completo AS responsable_nombre,
                a.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion,
                a.fecha_asignacion,
                a.id_usuario_registra,
                usu.nombre_completo AS usuario_registra_nombre,
                a.estado_asignacion,
                a.observaciones,
                a.created_at,
                a.updated_at
            FROM asignaciones a
            LEFT JOIN responsables r ON a.id_responsable = r.id_responsable
            LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id_ubicacion
            LEFT JOIN usuarios usu ON a.id_usuario_registra = usu.id_usuario
            ORDER BY a.id_asignacion DESC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idAsignacion): array|false
    {
        $sql = "
            SELECT
                a.id_asignacion,
                a.numero_asignacion,
                a.id_responsable,
                r.nombre_completo AS responsable_nombre,
                a.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion,
                a.fecha_asignacion,
                a.id_usuario_registra,
                usu.nombre_completo AS usuario_registra_nombre,
                a.estado_asignacion,
                a.observaciones,
                a.created_at,
                a.updated_at
            FROM asignaciones a
            LEFT JOIN responsables r ON a.id_responsable = r.id_responsable
            LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id_ubicacion
            LEFT JOIN usuarios usu ON a.id_usuario_registra = usu.id_usuario
            WHERE a.id_asignacion = :id_asignacion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_asignacion' => $idAsignacion]);
    }

    public function buscar(string $termino): array
    {
        $sql = "
            SELECT
                a.id_asignacion,
                a.numero_asignacion,
                a.id_responsable,
                r.nombre_completo AS responsable_nombre,
                a.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion,
                a.fecha_asignacion,
                a.id_usuario_registra,
                usu.nombre_completo AS usuario_registra_nombre,
                a.estado_asignacion,
                a.observaciones,
                a.created_at,
                a.updated_at
            FROM asignaciones a
            LEFT JOIN responsables r ON a.id_responsable = r.id_responsable
            LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id_ubicacion
            LEFT JOIN usuarios usu ON a.id_usuario_registra = usu.id_usuario
            WHERE a.numero_asignacion LIKE :termino_numero
               OR r.nombre_completo LIKE :termino_responsable
               OR u.nombre_ubicacion LIKE :termino_ubicacion
               OR a.estado_asignacion LIKE :termino_estado
            ORDER BY a.id_asignacion DESC
        ";

        $like = '%' . $termino . '%';

        return $this->fetchAll($sql, [
            ':termino_numero' => $like,
            ':termino_responsable' => $like,
            ':termino_ubicacion' => $like,
            ':termino_estado' => $like,
        ]);
    }

    public function existeNumero(string $numeroAsignacion): bool
    {
        $sql = "
            SELECT id_asignacion
            FROM asignaciones
            WHERE numero_asignacion = :numero_asignacion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':numero_asignacion' => $numeroAsignacion]) !== false;
    }

    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_asignacion, 10) AS UNSIGNED)) AS ultimo_consecutivo
            FROM asignaciones
            WHERE numero_asignacion LIKE :prefijo
        ";

        $prefijo = sprintf('ASG-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('ASG-%04d-%06d', $anio, $siguienteConsecutivo);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO asignaciones (
                numero_asignacion,
                id_responsable,
                id_ubicacion,
                fecha_asignacion,
                id_usuario_registra,
                estado_asignacion,
                observaciones
            ) VALUES (
                :numero_asignacion,
                :id_responsable,
                :id_ubicacion,
                :fecha_asignacion,
                :id_usuario_registra,
                :estado_asignacion,
                :observaciones
            )
        ";

        $params = [
            ':numero_asignacion' => $datos['numero_asignacion'] ?? null,
            ':id_responsable' => $datos['id_responsable'] ?? null,
            ':id_ubicacion' => $datos['id_ubicacion'] ?? null,
            ':fecha_asignacion' => $datos['fecha_asignacion'] ?? null,
            ':id_usuario_registra' => $datos['id_usuario_registra'] ?? null,
            ':estado_asignacion' => $datos['estado_asignacion'] ?? 'Pendiente',
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function getBienesPorAsignacion(int $idAsignacion): array
    {
        $sql = "
            SELECT
                da.id_detalle_asignacion,
                da.id_asignacion,
                da.id_bien,
                da.fecha_agregado,
                da.fecha_retirado,
                da.estado_detalle,
                da.observaciones AS observaciones_detalle,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien
            FROM detalle_asignacion da
            LEFT JOIN bienes b ON da.id_bien = b.id_bien
            WHERE da.id_asignacion = :id_asignacion
            ORDER BY da.id_detalle_asignacion ASC
        ";

        return $this->fetchAll($sql, [':id_asignacion' => $idAsignacion]);
    }

    public function existeBienEnAsignacion(int $idAsignacion, int $idBien): bool
    {
        $sql = "
            SELECT id_detalle_asignacion
            FROM detalle_asignacion
            WHERE id_asignacion = :id_asignacion AND id_bien = :id_bien
            LIMIT 1
        ";

        return $this->fetchOne($sql, [
            ':id_asignacion' => $idAsignacion,
            ':id_bien' => $idBien,
        ]) !== false;
    }

    public function agregarBien(int $idAsignacion, int $idBien, string $fechaAgregado, ?string $observaciones = null): int
    {
        $sql = "
            INSERT INTO detalle_asignacion (
                id_asignacion,
                id_bien,
                fecha_agregado,
                estado_detalle,
                observaciones
            ) VALUES (
                :id_asignacion,
                :id_bien,
                :fecha_agregado,
                'activo',
                :observaciones
            )
        ";

        $this->query($sql, [
            ':id_asignacion' => $idAsignacion,
            ':id_bien' => $idBien,
            ':fecha_agregado' => $fechaAgregado,
            ':observaciones' => $observaciones,
        ]);

        return (int) $this->lastInsertId();
    }

    // Usar únicamente mientras la asignación cabecera sigue en estado Pendiente: el bien nunca
    // llegó a estar realmente asignado, así que se elimina el detalle en vez de marcarlo
    // 'retirado' (ese estado se reserva para un bien que sí estuvo asignado y luego se retiró).
    public function eliminarDetallePendiente(int $idDetalleAsignacion): bool
    {
        $sql = "
            DELETE FROM detalle_asignacion
            WHERE id_detalle_asignacion = :id_detalle_asignacion
              AND estado_detalle = 'activo'
        ";

        $this->query($sql, [
            ':id_detalle_asignacion' => $idDetalleAsignacion,
        ]);

        return true;
    }

    public function retirarBien(int $idDetalleAsignacion, string $fechaRetirado): bool
    {
        $sql = "
            UPDATE detalle_asignacion
            SET
                estado_detalle = 'retirado',
                fecha_retirado = :fecha_retirado
            WHERE id_detalle_asignacion = :id_detalle_asignacion
              AND estado_detalle = 'activo'
        ";

        $this->query($sql, [
            ':fecha_retirado' => $fechaRetirado,
            ':id_detalle_asignacion' => $idDetalleAsignacion,
        ]);

        return true;
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findByIdForUpdate(int $idAsignacion): array|false
    {
        $sql = "
            SELECT
                id_asignacion,
                numero_asignacion,
                estado_asignacion,
                id_responsable,
                id_ubicacion
            FROM asignaciones
            WHERE id_asignacion = :id_asignacion
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_asignacion' => $idAsignacion]);
    }

    public function actualizar(int $idAsignacion, array $datos): bool
    {
        $sql = "
            UPDATE asignaciones
            SET
                id_responsable = :id_responsable,
                id_ubicacion = :id_ubicacion,
                fecha_asignacion = :fecha_asignacion,
                observaciones = :observaciones
            WHERE id_asignacion = :id_asignacion
              AND estado_asignacion = 'Pendiente'
        ";

        $this->query($sql, [
            ':id_responsable' => $datos['id_responsable'] ?? null,
            ':id_ubicacion' => $datos['id_ubicacion'] ?? null,
            ':fecha_asignacion' => $datos['fecha_asignacion'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_asignacion' => $idAsignacion,
        ]);

        return true;
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findDetalleByIdForUpdate(int $idDetalleAsignacion): array|false
    {
        $sql = "
            SELECT
                id_detalle_asignacion,
                id_asignacion,
                id_bien,
                fecha_agregado,
                fecha_retirado,
                estado_detalle,
                observaciones
            FROM detalle_asignacion
            WHERE id_detalle_asignacion = :id_detalle_asignacion
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_detalle_asignacion' => $idDetalleAsignacion]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function getDetallesActivosForUpdate(int $idAsignacion): array
    {
        $sql = "
            SELECT
                id_detalle_asignacion,
                id_asignacion,
                id_bien,
                estado_detalle
            FROM detalle_asignacion
            WHERE id_asignacion = :id_asignacion
              AND estado_detalle = 'activo'
            ORDER BY id_bien ASC
            FOR UPDATE
        ";

        return $this->fetchAll($sql, [':id_asignacion' => $idAsignacion]);
    }

    public function confirmar(int $idAsignacion): bool
    {
        $sql = "
            UPDATE asignaciones
            SET estado_asignacion = 'Asignada'
            WHERE id_asignacion = :id_asignacion
              AND estado_asignacion = 'Pendiente'
        ";

        $this->query($sql, [':id_asignacion' => $idAsignacion]);

        return true;
    }

    public function getAsignadas(): array
    {
        $sql = "
            SELECT
                a.id_asignacion,
                a.numero_asignacion,
                a.id_responsable,
                a.id_ubicacion,
                r.nombre_completo AS responsable_nombre,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM asignaciones a
            LEFT JOIN responsables r ON a.id_responsable = r.id_responsable
            LEFT JOIN ubicaciones u ON a.id_ubicacion = u.id_ubicacion
            WHERE a.estado_asignacion = 'Asignada'
            ORDER BY a.numero_asignacion ASC
        ";

        return $this->fetchAll($sql);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Localiza el detalle activo exacto de un bien dentro de una asignación específica, sin asumir
    // cuál es esa asignación de antemano (se recalcula server-side a partir de b.id_asignacion_actual).
    public function findDetalleActivoPorBienForUpdate(int $idAsignacion, int $idBien): array|false
    {
        $sql = "
            SELECT
                id_detalle_asignacion,
                id_asignacion,
                id_bien,
                estado_detalle
            FROM detalle_asignacion
            WHERE id_asignacion = :id_asignacion
              AND id_bien = :id_bien
              AND estado_detalle = 'activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_asignacion' => $idAsignacion,
            ':id_bien' => $idBien,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findVigentePorResponsableForUpdate(int $idResponsable): array|false
    {
        $sql = "
            SELECT
                id_asignacion,
                numero_asignacion,
                id_ubicacion,
                estado_asignacion
            FROM asignaciones
            WHERE id_responsable = :id_responsable
              AND estado_asignacion IN ('Pendiente', 'Asignada')
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_responsable' => $idResponsable]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Incluye detalles activos Y retirados: una emisión histórica de Tarjeta debe poder reconstruir
    // también los bienes que ya salieron de la asignación (p. ej. por Traslado), no solo los actuales.
    public function getDetallesParaTarjeta(int $idAsignacion): array
    {
        $sql = "
            SELECT
                da.id_detalle_asignacion,
                da.id_bien,
                da.fecha_agregado,
                da.fecha_retirado,
                da.estado_detalle,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.modelo,
                b.serie,
                b.costo,
                b.valor_estimado
            FROM detalle_asignacion da
            INNER JOIN bienes b ON da.id_bien = b.id_bien
            WHERE da.id_asignacion = :id_asignacion
            ORDER BY da.fecha_agregado ASC, da.id_detalle_asignacion ASC
            FOR UPDATE
        ";

        return $this->fetchAll($sql, [':id_asignacion' => $idAsignacion]);
    }

    public function existeAsignacionVigentePorResponsable(
        int $idResponsable,
        ?int $excluirIdAsignacion = null
    ): bool {
        $sql = "
            SELECT id_asignacion
            FROM asignaciones
            WHERE id_responsable = :id_responsable
              AND estado_asignacion IN ('Pendiente', 'Asignada')
        ";

        $params = [':id_responsable' => $idResponsable];

        if ($excluirIdAsignacion !== null) {
            $sql .= " AND id_asignacion <> :excluir_id_asignacion";
            $params[':excluir_id_asignacion'] = $excluirIdAsignacion;
        }

        $sql .= " LIMIT 1";

        return $this->fetchOne($sql, $params) !== false;
    }
}
