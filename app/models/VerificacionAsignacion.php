<?php

require_once __DIR__ . '/../core/Model.php';

// Cabecera/jornada de la "Verificación física por asignación". Cada bien revisado se guarda como
// una fila normal en `verificaciones_fisicas` (historial del bien reutilizado) enlazada por
// `verificaciones_fisicas.id_verificacion_asignacion`; esta tabla solo agrupa la jornada y guarda
// su contexto y su resumen. Esta modalidad NO modifica el bien (igual que la individual).
class VerificacionAsignacion extends Model
{
    protected string $table = 'verificaciones_asignacion';

    // Reglas de elegibilidad de una asignación para ser verificada (Sección 4 del requerimiento):
    // - estado_asignacion = 'Asignada' (única situación que representa carga vigente; 'Pendiente' es
    //   residual del antiguo flujo manual, ver Asignacion::findAsignadaPorResponsableUbicacionForUpdate);
    // - responsable Y ubicación existentes (INNER JOIN);
    // - con al menos un bien actualmente cargado y elegible (misma regla que getBienesVigentes()).
    private const WHERE_BIEN_VIGENTE = "
        da.estado_detalle = 'activo'
        AND eb.nombre_estado = 'Activo'
        AND b.id_asignacion_actual = da.id_asignacion
    ";

    // $q filtra por número de asignación / responsable / ubicación (mismo criterio que
    // Asignacion::buscar(), reducido a lo relevante aquí).
    public function getAsignacionesElegibles(?string $q = null): array
    {
        $condiciones = ["a.estado_asignacion = 'Asignada'"];
        $params = [];

        if ($q !== null && trim($q) !== '') {
            $condiciones[] = '(a.numero_asignacion LIKE :q_numero OR r.nombre_completo LIKE :q_resp OR u.nombre_ubicacion LIKE :q_ubic)';
            $like = '%' . trim($q) . '%';
            $params[':q_numero'] = $like;
            $params[':q_resp'] = $like;
            $params[':q_ubic'] = $like;
        }

        $where = implode(' AND ', $condiciones);

        $sql = "
            SELECT
                a.id_asignacion,
                a.numero_asignacion,
                a.estado_asignacion,
                a.fecha_asignacion,
                a.id_responsable,
                r.nombre_completo AS responsable_nombre,
                a.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion,
                (
                    SELECT COUNT(*)
                    FROM detalle_asignacion da
                    INNER JOIN bienes b ON b.id_bien = da.id_bien
                    INNER JOIN estados_bien eb ON eb.id_estado_bien = b.id_estado_bien
                    WHERE da.id_asignacion = a.id_asignacion
                      AND " . self::WHERE_BIEN_VIGENTE . "
                ) AS cantidad_bienes
            FROM asignaciones a
            INNER JOIN responsables r ON r.id_responsable = a.id_responsable
            INNER JOIN ubicaciones u ON u.id_ubicacion = a.id_ubicacion
            WHERE {$where}
            HAVING cantidad_bienes > 0
            ORDER BY a.numero_asignacion ASC
        ";

        return $this->fetchAll($sql, $params);
    }

    // Una sola asignación, con las mismas reglas de elegibilidad. Devuelve false si no es elegible
    // (no existe, no está 'Asignada', responsable/ubicación inexistentes, o sin bienes vigentes).
    public function findElegible(int $idAsignacion): array|false
    {
        $sql = "
            SELECT
                a.id_asignacion,
                a.numero_asignacion,
                a.estado_asignacion,
                a.fecha_asignacion,
                a.id_responsable,
                r.nombre_completo AS responsable_nombre,
                a.id_ubicacion,
                u.nombre_ubicacion,
                u.tipo_ubicacion,
                (
                    SELECT COUNT(*)
                    FROM detalle_asignacion da
                    INNER JOIN bienes b ON b.id_bien = da.id_bien
                    INNER JOIN estados_bien eb ON eb.id_estado_bien = b.id_estado_bien
                    WHERE da.id_asignacion = a.id_asignacion
                      AND " . self::WHERE_BIEN_VIGENTE . "
                ) AS cantidad_bienes
            FROM asignaciones a
            INNER JOIN responsables r ON r.id_responsable = a.id_responsable
            INNER JOIN ubicaciones u ON u.id_ubicacion = a.id_ubicacion
            WHERE a.id_asignacion = :id_asignacion
              AND a.estado_asignacion = 'Asignada'
            HAVING cantidad_bienes > 0
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_asignacion' => $idAsignacion]);
    }

    // Bienes actualmente cargados y elegibles de una asignación: detalle_asignacion activo, bien
    // Activo y espejo b.id_asignacion_actual coincidente (misma triple condición que usan Traslados/
    // Préstamos/Bajas para "bienes actuales de"). NO incluye retirados, dados de baja ni históricos.
    public function getBienesVigentes(int $idAsignacion): array
    {
        return $this->fetchAll($this->sqlBienesVigentes(false), [':id_asignacion' => $idAsignacion]);
    }

    // Igual que getBienesVigentes() pero con FOR UPDATE: DEBE ejecutarse dentro de una transacción
    // activa. Es la revalidación real del snapshot esperado en el instante de finalizar la jornada
    // — nunca se confía en los IDs enviados por el navegador.
    public function getBienesVigentesForUpdate(int $idAsignacion): array
    {
        return $this->fetchAll($this->sqlBienesVigentes(true), [':id_asignacion' => $idAsignacion]);
    }

    private function sqlBienesVigentes(bool $forUpdate): string
    {
        $suffix = $forUpdate ? 'FOR UPDATE' : '';

        return "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.id_responsable_actual,
                b.id_ubicacion_actual,
                r.nombre_completo AS responsable_actual,
                u.nombre_ubicacion AS ubicacion_actual,
                da.id_detalle_asignacion
            FROM detalle_asignacion da
            INNER JOIN bienes b ON b.id_bien = da.id_bien
            INNER JOIN estados_bien eb ON eb.id_estado_bien = b.id_estado_bien
            LEFT JOIN responsables r ON r.id_responsable = b.id_responsable_actual
            LEFT JOIN ubicaciones u ON u.id_ubicacion = b.id_ubicacion_actual
            WHERE da.id_asignacion = :id_asignacion
              AND " . self::WHERE_BIEN_VIGENTE . "
            ORDER BY b.codigo_interno ASC
            {$suffix}
        ";
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO verificaciones_asignacion (
                id_asignacion,
                numero_asignacion,
                id_responsable_registrado,
                id_ubicacion_registrada,
                id_usuario_verifica,
                total_esperado,
                total_revisado,
                total_localizados,
                total_no_localizados,
                total_con_diferencias,
                total_sin_diferencias,
                porcentaje_localizacion,
                porcentaje_sin_diferencias,
                observaciones
            ) VALUES (
                :id_asignacion,
                :numero_asignacion,
                :id_responsable_registrado,
                :id_ubicacion_registrada,
                :id_usuario_verifica,
                :total_esperado,
                :total_revisado,
                :total_localizados,
                :total_no_localizados,
                :total_con_diferencias,
                :total_sin_diferencias,
                :porcentaje_localizacion,
                :porcentaje_sin_diferencias,
                :observaciones
            )
        ";

        $this->query($sql, [
            ':id_asignacion' => $datos['id_asignacion'],
            ':numero_asignacion' => $datos['numero_asignacion'],
            ':id_responsable_registrado' => $datos['id_responsable_registrado'],
            ':id_ubicacion_registrada' => $datos['id_ubicacion_registrada'],
            ':id_usuario_verifica' => $datos['id_usuario_verifica'],
            ':total_esperado' => $datos['total_esperado'],
            ':total_revisado' => $datos['total_revisado'],
            ':total_localizados' => $datos['total_localizados'],
            ':total_no_localizados' => $datos['total_no_localizados'],
            ':total_con_diferencias' => $datos['total_con_diferencias'],
            ':total_sin_diferencias' => $datos['total_sin_diferencias'],
            ':porcentaje_localizacion' => $datos['porcentaje_localizacion'],
            ':porcentaje_sin_diferencias' => $datos['porcentaje_sin_diferencias'],
            ':observaciones' => $datos['observaciones'] ?? null,
        ]);

        return (int) $this->lastInsertId();
    }

    // Cabecera de una jornada + contexto. numero_asignacion / responsable / ubicación salen de los
    // SNAPSHOTS de esta tabla, no del estado actual de la asignación (que pudo cambiar después).
    // Se expone además el estado ACTUAL de la asignación (a.estado_asignacion) solo para informar,
    // en la vista de detalle, si la asignación cambió tras la jornada.
    public function findById(int $idVerificacionAsignacion): array|false
    {
        $sql = "
            SELECT
                va.id_verificacion_asignacion,
                va.id_asignacion,
                va.numero_asignacion,
                va.id_responsable_registrado,
                va.id_ubicacion_registrada,
                va.id_usuario_verifica,
                va.fecha_hora,
                va.total_esperado,
                va.total_revisado,
                va.total_localizados,
                va.total_no_localizados,
                va.total_con_diferencias,
                va.total_sin_diferencias,
                va.porcentaje_localizacion,
                va.porcentaje_sin_diferencias,
                va.observaciones,
                va.created_at,
                rr.nombre_completo AS responsable_registrado_nombre,
                ur.nombre_ubicacion AS ubicacion_registrada_nombre,
                us.nombre_completo AS usuario_verifica_nombre,
                a.estado_asignacion AS asignacion_estado_actual
            FROM verificaciones_asignacion va
            LEFT JOIN responsables rr ON rr.id_responsable = va.id_responsable_registrado
            LEFT JOIN ubicaciones ur ON ur.id_ubicacion = va.id_ubicacion_registrada
            LEFT JOIN usuarios us ON us.id_usuario = va.id_usuario_verifica
            LEFT JOIN asignaciones a ON a.id_asignacion = va.id_asignacion
            WHERE va.id_verificacion_asignacion = :id
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id' => $idVerificacionAsignacion]);
    }

    // Verificaciones individuales (una por bien) generadas dentro de una jornada, con la
    // identificación del bien y los datos observados. Snapshots (rr/ur) desde verificaciones_fisicas.
    public function getBienesRevisados(int $idVerificacionAsignacion): array
    {
        $sql = "
            SELECT
                vf.id_verificacion,
                vf.id_bien,
                vf.bien_localizado,
                vf.responsable_correcto,
                vf.ubicacion_correcta,
                vf.condicion_registrada,
                vf.condicion_observada,
                vf.tiene_diferencias,
                vf.observaciones,
                vf.fecha_hora,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                rr.nombre_completo AS responsable_registrado_nombre,
                ur.nombre_ubicacion AS ubicacion_registrada_nombre
            FROM verificaciones_fisicas vf
            INNER JOIN bienes b ON b.id_bien = vf.id_bien
            LEFT JOIN responsables rr ON rr.id_responsable = vf.id_responsable_registrado
            LEFT JOIN ubicaciones ur ON ur.id_ubicacion = vf.id_ubicacion_registrada
            WHERE vf.id_verificacion_asignacion = :id
            ORDER BY b.codigo_interno ASC
        ";

        return $this->fetchAll($sql, [':id' => $idVerificacionAsignacion]);
    }
}
