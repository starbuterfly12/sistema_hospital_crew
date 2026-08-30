<?php

require_once __DIR__ . '/../core/Model.php';

class VerificacionFisica extends Model
{
    protected string $table = 'verificaciones_fisicas';

    // $datos['id_verificacion_asignacion'] es OPCIONAL: null (o ausente) => verificación individual
    // (comportamiento actual, intacto); un id => verificación registrada dentro de una jornada por
    // asignación. En ambos casos la fila representa la verificación de UN bien y alimenta el mismo
    // historial del bien.
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO verificaciones_fisicas (
                id_bien,
                id_responsable_registrado,
                id_ubicacion_registrada,
                condicion_registrada,
                id_usuario_verifica,
                id_verificacion_asignacion,
                bien_localizado,
                responsable_correcto,
                ubicacion_correcta,
                condicion_observada,
                tiene_diferencias,
                observaciones
            ) VALUES (
                :id_bien,
                :id_responsable_registrado,
                :id_ubicacion_registrada,
                :condicion_registrada,
                :id_usuario_verifica,
                :id_verificacion_asignacion,
                :bien_localizado,
                :responsable_correcto,
                :ubicacion_correcta,
                :condicion_observada,
                :tiene_diferencias,
                :observaciones
            )
        ";

        $params = [
            ':id_bien' => $datos['id_bien'],
            ':id_responsable_registrado' => $datos['id_responsable_registrado'],
            ':id_ubicacion_registrada' => $datos['id_ubicacion_registrada'],
            ':condicion_registrada' => $datos['condicion_registrada'],
            ':id_usuario_verifica' => $datos['id_usuario_verifica'],
            ':id_verificacion_asignacion' => $datos['id_verificacion_asignacion'] ?? null,
            ':bien_localizado' => $datos['bien_localizado'],
            ':responsable_correcto' => $datos['responsable_correcto'],
            ':ubicacion_correcta' => $datos['ubicacion_correcta'],
            ':condicion_observada' => $datos['condicion_observada'],
            ':tiene_diferencias' => $datos['tiene_diferencias'],
            ':observaciones' => $datos['observaciones'],
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // Reconstruye el contexto histórico EXACTO de la verificación a partir de los snapshots
    // (id_responsable_registrado/id_ubicacion_registrada/condicion_registrada vía rr/ur), nunca
    // desde el responsable/ubicación/condición ACTUALES del bien: si el bien cambió después de
    // verificarse, este detalle debe seguir mostrando lo que era cierto en el momento de verificar.
    // Los datos de bienes (b.*) que sí se traen aquí son solo de identificación (código/descripción/
    // marca/modelo/serie) — la tabla no tiene snapshot de esos campos (ver auditoría), así que se
    // muestran desde el bien actual únicamente para identificar el registro, nunca como histórico.
    public function findById(int $idVerificacion): array|false
    {
        $sql = "
            SELECT
                vf.id_verificacion,
                vf.id_bien,
                vf.id_responsable_registrado,
                vf.id_ubicacion_registrada,
                vf.condicion_registrada,
                vf.id_usuario_verifica,
                vf.id_verificacion_asignacion,
                vf.fecha_hora,
                vf.bien_localizado,
                vf.responsable_correcto,
                vf.ubicacion_correcta,
                vf.condicion_observada,
                vf.tiene_diferencias,
                vf.observaciones,
                vf.created_at,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                u.nombre_completo AS usuario_verifica_nombre,
                rr.nombre_completo AS responsable_registrado_nombre,
                ur.nombre_ubicacion AS ubicacion_registrada_nombre,
                va.numero_asignacion AS verificacion_asignacion_numero
            FROM verificaciones_fisicas vf
            INNER JOIN bienes b ON vf.id_bien = b.id_bien
            INNER JOIN usuarios u ON vf.id_usuario_verifica = u.id_usuario
            LEFT JOIN responsables rr ON vf.id_responsable_registrado = rr.id_responsable
            LEFT JOIN ubicaciones ur ON vf.id_ubicacion_registrada = ur.id_ubicacion
            LEFT JOIN verificaciones_asignacion va ON vf.id_verificacion_asignacion = va.id_verificacion_asignacion
            WHERE vf.id_verificacion = :id_verificacion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_verificacion' => $idVerificacion]);
    }

    // Listado general (índice del módulo) y, vía $filtros['id_bien'], también el "historial de
    // verificaciones" de un bien específico enlazado desde bienes/ver.php. Los nombres de
    // responsable/ubicación mostrados (rr/ur) salen de los snapshots, no del responsable/ubicación
    // actuales del bien.
    public function getAll(array $filtros = []): array
    {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['id_bien'])) {
            $condiciones[] = 'vf.id_bien = :id_bien';
            $params[':id_bien'] = (int) $filtros['id_bien'];
        }

        if (!empty($filtros['id_responsable_registrado'])) {
            $condiciones[] = 'vf.id_responsable_registrado = :id_responsable_registrado';
            $params[':id_responsable_registrado'] = (int) $filtros['id_responsable_registrado'];
        }

        if (!empty($filtros['id_ubicacion_registrada'])) {
            $condiciones[] = 'vf.id_ubicacion_registrada = :id_ubicacion_registrada';
            $params[':id_ubicacion_registrada'] = (int) $filtros['id_ubicacion_registrada'];
        }

        if (isset($filtros['localizado']) && $filtros['localizado'] !== '') {
            $condiciones[] = 'vf.bien_localizado = :bien_localizado';
            $params[':bien_localizado'] = (int) $filtros['localizado'];
        }

        if (isset($filtros['con_diferencias']) && $filtros['con_diferencias'] !== '') {
            $condiciones[] = 'vf.tiene_diferencias = :tiene_diferencias';
            $params[':tiene_diferencias'] = (int) $filtros['con_diferencias'];
        }

        // Tipo de verificación: 'individual' (sin jornada) o 'asignacion' (dentro de una jornada
        // por asignación). Se deriva de vf.id_verificacion_asignacion, sin columna de tipo aparte.
        if (isset($filtros['tipo']) && $filtros['tipo'] === 'individual') {
            $condiciones[] = 'vf.id_verificacion_asignacion IS NULL';
        } elseif (isset($filtros['tipo']) && $filtros['tipo'] === 'asignacion') {
            $condiciones[] = 'vf.id_verificacion_asignacion IS NOT NULL';
        }

        if (!empty($filtros['busqueda'])) {
            $condiciones[] = '(b.codigo_interno LIKE :busqueda_codigo OR b.codigo_sicoin LIKE :busqueda_sicoin OR b.descripcion LIKE :busqueda_descripcion)';
            $like = '%' . $filtros['busqueda'] . '%';
            $params[':busqueda_codigo'] = $like;
            $params[':busqueda_sicoin'] = $like;
            $params[':busqueda_descripcion'] = $like;
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "
            SELECT
                vf.id_verificacion,
                vf.id_bien,
                vf.fecha_hora,
                vf.bien_localizado,
                vf.tiene_diferencias,
                vf.id_verificacion_asignacion,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                rr.nombre_completo AS responsable_registrado_nombre,
                ur.nombre_ubicacion AS ubicacion_registrada_nombre,
                va.numero_asignacion AS verificacion_asignacion_numero
            FROM verificaciones_fisicas vf
            INNER JOIN bienes b ON vf.id_bien = b.id_bien
            LEFT JOIN responsables rr ON vf.id_responsable_registrado = rr.id_responsable
            LEFT JOIN ubicaciones ur ON vf.id_ubicacion_registrada = ur.id_ubicacion
            LEFT JOIN verificaciones_asignacion va ON vf.id_verificacion_asignacion = va.id_verificacion_asignacion
            {$where}
            ORDER BY vf.fecha_hora DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    // Envoltorio de getAll() con un único filtro, para llamadores que solo necesitan el historial
    // completo de un bien sin construir el array de filtros a mano.
    public function findByBien(int $idBien): array
    {
        return $this->getAll(['id_bien' => $idBien]);
    }

    public function getUltimaPorBien(int $idBien): array|false
    {
        $sql = "
            SELECT
                vf.id_verificacion,
                vf.fecha_hora,
                vf.bien_localizado,
                vf.tiene_diferencias
            FROM verificaciones_fisicas vf
            WHERE vf.id_bien = :id_bien
            ORDER BY vf.fecha_hora DESC
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }
}
