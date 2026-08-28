<?php

require_once __DIR__ . '/../core/Model.php';

class DetalleBaja extends Model
{
    protected string $table = 'detalle_baja';

    // Mapeo definitivo confirmado con personal del Hospital (2026-08-19): la columna "No. de Bien" de
    // la plantilla institucional storage/templates/bajas.xlsx se llena desde codigo_interno_mostrado
    // (snapshot de bienes.codigo_interno al momento de agregar el bien a la Baja), NUNCA desde
    // codigo_sicoin_mostrado. SICOIN se conserva únicamente como dato interno/histórico y no sustituye
    // al código interno en ese campo. El generador XLSX de Bajas todavía no está implementado (se
    // difiere a la fase de Autorizar/Finalizar); este comentario deja fijado el mapeo para esa fase.
    //
    // id_tipo_baja y justificacion viven AQUÍ, no en `bajas`: una misma solicitud puede incluir bienes
    // con tipo de baja y justificación distintos (revisión funcional 2026-08-19, ver
    // database/2026_08_19_bajas_snapshots_correlativo.sql).
    public function insertar(array $datos): int
    {
        $sql = "
            INSERT INTO detalle_baja (
                id_baja,
                id_bien,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                marca_mostrada,
                modelo_mostrado,
                serie_mostrada,
                valor_mostrado,
                id_tipo_baja,
                justificacion,
                id_responsable_anterior,
                id_ubicacion_anterior,
                id_estado_bien_anterior,
                id_estado_bien_nuevo,
                imagen_bien,
                observaciones
            ) VALUES (
                :id_baja,
                :id_bien,
                :codigo_interno_mostrado,
                :codigo_sicoin_mostrado,
                :descripcion_mostrada,
                :marca_mostrada,
                :modelo_mostrado,
                :serie_mostrada,
                :valor_mostrado,
                :id_tipo_baja,
                :justificacion,
                :id_responsable_anterior,
                :id_ubicacion_anterior,
                :id_estado_bien_anterior,
                :id_estado_bien_nuevo,
                :imagen_bien,
                :observaciones
            )
        ";

        $params = [
            ':id_baja' => $datos['id_baja'],
            ':id_bien' => $datos['id_bien'],
            ':codigo_interno_mostrado' => $datos['codigo_interno_mostrado'],
            ':codigo_sicoin_mostrado' => $datos['codigo_sicoin_mostrado'] ?? null,
            ':descripcion_mostrada' => $datos['descripcion_mostrada'],
            ':marca_mostrada' => $datos['marca_mostrada'] ?? null,
            ':modelo_mostrado' => $datos['modelo_mostrado'] ?? null,
            ':serie_mostrada' => $datos['serie_mostrada'] ?? null,
            ':valor_mostrado' => $datos['valor_mostrado'],
            ':id_tipo_baja' => $datos['id_tipo_baja'],
            ':justificacion' => $datos['justificacion'],
            ':id_responsable_anterior' => $datos['id_responsable_anterior'] ?? null,
            ':id_ubicacion_anterior' => $datos['id_ubicacion_anterior'] ?? null,
            ':id_estado_bien_anterior' => $datos['id_estado_bien_anterior'],
            ':id_estado_bien_nuevo' => $datos['id_estado_bien_nuevo'],
            ':imagen_bien' => $datos['imagen_bien'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // Lectura mínima para el endpoint autenticado que sirve la foto de un bien de la baja
    // (BajasController::verFoto()). Identifica la foto por el PK del detalle (id_detalle_baja); una
    // fila = un bien de una baja = a lo sumo una imagen_bien. Devuelve también id_baja por si el
    // llamador quiere trazabilidad. La ruta se resuelve/valida contra el filesystem en el controlador.
    public function findImagenPorId(int $idDetalleBaja): array|false
    {
        return $this->fetchOne(
            "SELECT id_detalle_baja, id_baja, imagen_bien FROM detalle_baja WHERE id_detalle_baja = :id LIMIT 1",
            [':id' => $idDetalleBaja]
        );
    }

    // Solo debe usarse mientras la baja dueña está 'pendiente' (verificado por el llamador). No hay
    // estado_detalle en esta tabla (a diferencia de detalle_requisicion): un bien quitado de una Baja
    // Pendiente se DELETE real, nunca se marca "anulado" — así lo definió la usuaria explícitamente.
    public function eliminar(int $idDetalleBaja): bool
    {
        $sql = "DELETE FROM detalle_baja WHERE id_detalle_baja = :id_detalle_baja";

        $this->query($sql, [':id_detalle_baja' => $idDetalleBaja]);

        return true;
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Verificación adicional de concurrencia (mismo criterio que
    // DetalleRequisicion::existeReservaActivaPorBienForUpdate()): confirma, en el instante exacto de
    // guardar, que el bien no quedó atrapado en otra Baja pendiente/autorizada creada en paralelo.
    // $idBajaExcluir permite reutilizarla al editar una Baja Pendiente sin que se detecte a sí misma.
    public function existeEnBajaVigentePorBienForUpdate(int $idBien, int $idBajaExcluir = 0): bool
    {
        $sql = "
            SELECT db.id_detalle_baja
            FROM detalle_baja db
            INNER JOIN bajas ba ON db.id_baja = ba.id_baja
            WHERE db.id_bien = :id_bien
              AND ba.estado_baja IN ('pendiente', 'autorizada')
              AND ba.id_baja <> :id_baja_excluir
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_baja_excluir' => $idBajaExcluir,
        ]) !== false;
    }

    public function listarPorBaja(int $idBaja): array
    {
        $sql = "
            SELECT
                db.id_detalle_baja,
                db.id_baja,
                db.id_bien,
                db.codigo_interno_mostrado,
                db.codigo_sicoin_mostrado,
                db.descripcion_mostrada,
                db.marca_mostrada,
                db.modelo_mostrado,
                db.serie_mostrada,
                db.valor_mostrado,
                db.id_tipo_baja,
                tb.nombre_tipo_baja,
                db.justificacion,
                db.id_responsable_anterior,
                ra.nombre_completo AS responsable_anterior,
                db.id_ubicacion_anterior,
                ua.nombre_ubicacion AS ubicacion_anterior,
                db.id_estado_bien_anterior,
                eba.nombre_estado AS estado_bien_anterior,
                db.id_estado_bien_nuevo,
                ebn.nombre_estado AS estado_bien_nuevo,
                db.imagen_bien,
                db.observaciones
            FROM detalle_baja db
            LEFT JOIN tipos_baja tb ON db.id_tipo_baja = tb.id_tipo_baja
            LEFT JOIN responsables ra ON db.id_responsable_anterior = ra.id_responsable
            LEFT JOIN ubicaciones ua ON db.id_ubicacion_anterior = ua.id_ubicacion
            LEFT JOIN estados_bien eba ON db.id_estado_bien_anterior = eba.id_estado_bien
            LEFT JOIN estados_bien ebn ON db.id_estado_bien_nuevo = ebn.id_estado_bien
            WHERE db.id_baja = :id_baja
            ORDER BY db.id_detalle_baja ASC
        ";

        return $this->fetchAll($sql, [':id_baja' => $idBaja]);
    }

    // Solo debe usarse mientras la baja dueña está 'pendiente'. Reemplaza únicamente imagen_bien de un
    // detalle ya existente (ej. al reemplazar la foto de un bien que permanece en la Baja al editar);
    // el borrado físico del archivo anterior es responsabilidad del llamador (eliminarImagenBien()).
    public function actualizarImagen(int $idDetalleBaja, ?string $rutaImagen): bool
    {
        $sql = "
            UPDATE detalle_baja
            SET imagen_bien = :imagen_bien
            WHERE id_detalle_baja = :id_detalle_baja
        ";

        $this->query($sql, [
            ':imagen_bien' => $rutaImagen,
            ':id_detalle_baja' => $idDetalleBaja,
        ]);

        return true;
    }

    // Solo debe usarse mientras la baja dueña está 'pendiente'. Actualiza tipo de baja y
    // justificación de un bien que se CONSERVA en la Baja al editar (si el bien se quita/agrega, se
    // usan eliminar()/insertar() en su lugar, no este método).
    public function actualizarTipoJustificacion(int $idDetalleBaja, int $idTipoBaja, string $justificacion): bool
    {
        $sql = "
            UPDATE detalle_baja
            SET
                id_tipo_baja = :id_tipo_baja,
                justificacion = :justificacion
            WHERE id_detalle_baja = :id_detalle_baja
        ";

        $this->query($sql, [
            ':id_tipo_baja' => $idTipoBaja,
            ':justificacion' => $justificacion,
            ':id_detalle_baja' => $idDetalleBaja,
        ]);

        return true;
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Usado por BajasController::finalizar() para bloquear todos los detalles de la Baja mientras se
    // procesan sus bienes. Sin joins de nombres (no es para mostrar en pantalla, solo para iterar y
    // bloquear durante la transacción).
    public function listarPorBajaForUpdate(int $idBaja): array
    {
        $sql = "
            SELECT
                id_detalle_baja,
                id_baja,
                id_bien,
                codigo_interno_mostrado,
                codigo_sicoin_mostrado,
                descripcion_mostrada,
                marca_mostrada,
                modelo_mostrado,
                serie_mostrada,
                valor_mostrado,
                id_tipo_baja,
                justificacion,
                id_responsable_anterior,
                id_ubicacion_anterior,
                id_estado_bien_anterior,
                id_estado_bien_nuevo,
                imagen_bien,
                observaciones
            FROM detalle_baja
            WHERE id_baja = :id_baja
            ORDER BY id_detalle_baja ASC
            FOR UPDATE
        ";

        return $this->fetchAll($sql, [':id_baja' => $idBaja]);
    }

    // Resuelve si un detalle_asignacion específico fue retirado definitivamente por una Baja ya
    // FINALIZADA (una Baja solo 'autorizada' todavía no retiró nada — ver
    // BajasController::finalizar()). A diferencia de DetalleRequisicion/DetallePrestamo, detalle_baja
    // no guarda una columna propia id_detalle_asignacion_origen; se correlaciona por id_bien porque,
    // por regla de negocio, un bien solo puede tener UNA Baja finalizada en toda su historia — una vez
    // finalizada, bienes.id_estado_bien pasa a 'Baja' y ese bien deja de ser elegible para cualquier
    // Baja nueva (Bien::getElegiblesParaBajaPorResponsable() exige estado 'Activo') — así que la
    // correlación id_bien + estado_baja='finalizada' es unívoca en la práctica. Usado exclusivamente
    // por TarjetasController::resolverSalidaDetalle() (lectura, sin bloqueo).
    public function findSalidaPorDetalleOrigen(int $idDetalleAsignacion): array|false
    {
        $sql = "
            SELECT
                db.id_detalle_baja,
                db.id_baja,
                ba.numero_baja,
                ba.fecha_baja,
                db.id_bien,
                COALESCE(NULLIF(db.codigo_sicoin_mostrado, ''), db.codigo_interno_mostrado) AS codigo_mostrado,
                db.descripcion_mostrada,
                db.modelo_mostrado,
                db.serie_mostrada,
                db.valor_mostrado
            FROM detalle_baja db
            INNER JOIN bajas ba ON db.id_baja = ba.id_baja
            INNER JOIN detalle_asignacion da ON da.id_bien = db.id_bien
            WHERE da.id_detalle_asignacion = :id_detalle_asignacion
              AND ba.estado_baja = 'finalizada'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_detalle_asignacion' => $idDetalleAsignacion]);
    }
}
