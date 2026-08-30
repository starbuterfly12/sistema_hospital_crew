<?php

require_once __DIR__ . '/../core/Model.php';

class Bien extends Model
{
    protected string $table = 'bienes';

    // Catálogo real de `bienes.condicion_bien` (confirmado en BienesController::crear()/editar(),
    // donde hoy sigue duplicado como array literal en 2 sitios más las 2 vistas de Bienes — no se
    // tocan esos 4 usos ya existentes para no rehacer un módulo cerrado). Punto único
    // de reutilización para módulos nuevos que necesiten el mismo catálogo (Préstamos: condición de
    // entrega; Devoluciones: condición de devolución) en vez de que cada uno defina su propia lista.
    public const CONDICIONES_VALIDAS = ['Bueno', 'Regular', 'Malo'];

    // Filtros opcionales para el listado (todos por GET, consulta preparada). Sin argumentos se
    // comporta EXACTAMENTE igual que antes (sin WHERE): las llamadas existentes no cambian.
    //   $q            -> LIKE sobre codigo_interno / codigo_sicoin / descripcion
    //   $idCategoria  -> b.id_categoria exacto
    //   $nombreEstado -> eb.nombre_estado exacto (ej. 'Activo' / 'Baja')
    public function getAll(?string $q = null, ?int $idCategoria = null, ?string $nombreEstado = null): array
    {
        $condiciones = [];
        $params = [];

        if ($q !== null && trim($q) !== '') {
            // Placeholders distintos por ocurrencia: con PDO::ATTR_EMULATE_PREPARES=false un mismo
            // nombre no puede repetirse en la consulta (mismo criterio que Bien::buscar()).
            $condiciones[] = "(b.codigo_interno LIKE :q_codigo OR b.codigo_sicoin LIKE :q_sicoin OR b.descripcion LIKE :q_desc)";
            $like = '%' . trim($q) . '%';
            $params[':q_codigo'] = $like;
            $params[':q_sicoin'] = $like;
            $params[':q_desc'] = $like;
        }

        if ($idCategoria !== null && $idCategoria > 0) {
            $condiciones[] = "b.id_categoria = :id_categoria";
            $params[':id_categoria'] = $idCategoria;
        }

        if ($nombreEstado !== null && trim($nombreEstado) !== '') {
            $condiciones[] = "eb.nombre_estado = :nombre_estado";
            $params[':nombre_estado'] = trim($nombreEstado);
        }

        $where = $condiciones !== [] ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.fecha_ingreso,
                b.costo,
                b.valor_estimado,
                b.id_responsable_actual,
                b.id_ubicacion_actual,
                cb.nombre_categoria,
                eb.nombre_estado,
                r.nombre_completo AS responsable_actual,
                u.nombre_ubicacion AS ubicacion_actual
            FROM bienes b
            LEFT JOIN categorias_bien cb ON b.id_categoria = cb.id_categoria
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN responsables r ON b.id_responsable_actual = r.id_responsable
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            {$where}
            ORDER BY b.id_bien DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO bienes (
                codigo_interno,
                codigo_sicoin,
                descripcion,
                marca,
                modelo,
                serie,
                id_categoria,
                id_estado_bien,
                condicion_bien,
                id_forma_ingreso,
                fecha_ingreso,
                costo,
                valor_estimado,
                id_responsable_actual,
                id_ubicacion_actual,
                observaciones
            ) VALUES (
                :codigo_interno,
                :codigo_sicoin,
                :descripcion,
                :marca,
                :modelo,
                :serie,
                :id_categoria,
                :id_estado_bien,
                :condicion_bien,
                :id_forma_ingreso,
                :fecha_ingreso,
                :costo,
                :valor_estimado,
                :id_responsable_actual,
                :id_ubicacion_actual,
                :observaciones
            )
        ";

        $params = [
            ':codigo_interno' => $datos['codigo_interno'] ?? null,
            ':codigo_sicoin' => $datos['codigo_sicoin'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':marca' => $datos['marca'] ?? null,
            ':modelo' => $datos['modelo'] ?? null,
            ':serie' => $datos['serie'] ?? null,
            ':id_categoria' => $datos['id_categoria'] ?? null,
            ':id_estado_bien' => $datos['id_estado_bien'] ?? null,
            ':condicion_bien' => $datos['condicion_bien'] ?? null,
            ':id_forma_ingreso' => $datos['id_forma_ingreso'] ?? null,
            ':fecha_ingreso' => $datos['fecha_ingreso'] ?? null,
            ':costo' => $datos['costo'] ?? null,
            ':valor_estimado' => $datos['valor_estimado'] ?? null,
            ':id_responsable_actual' => $datos['id_responsable_actual'] ?? null,
            ':id_ubicacion_actual' => $datos['id_ubicacion_actual'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function findById(int $idBien): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.id_categoria,
                b.id_estado_bien,
                b.id_forma_ingreso,
                b.id_responsable_actual,
                b.id_ubicacion_actual,
                b.condicion_bien,
                b.fecha_ingreso,
                b.costo,
                b.valor_estimado,
                b.observaciones,
                b.codigo_qr,
                b.ruta_qr,
                b.created_at,
                b.updated_at,
                cb.nombre_categoria,
                eb.nombre_estado,
                fi.nombre_forma,
                r.nombre_completo AS responsable_actual,
                u.nombre_ubicacion AS ubicacion_actual,
                u.tipo_ubicacion
            FROM bienes b
            LEFT JOIN categorias_bien cb ON b.id_categoria = cb.id_categoria
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN formas_ingreso fi ON b.id_forma_ingreso = fi.id_forma_ingreso
            LEFT JOIN responsables r ON b.id_responsable_actual = r.id_responsable
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            WHERE b.id_bien = :id_bien
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }

    // El `codigo_interno` NO se actualiza aquí a propósito: se define una única vez al registrar el
    // bien (Bien::crear()) y queda bloqueado de forma permanente. Aunque el formulario de edición se
    // manipule para enviar otro valor, esta consulta nunca lo escribe. El `codigo_sicoin` SÍ puede
    // escribirse, pero solo para establecerlo por primera vez cuando estaba en NULL/vacío: el
    // controlador (BienesController::editar()) fuerza el valor existente cuando el bien ya tiene uno.
    public function actualizar(int $idBien, array $datos): bool
    {
        $sql = "
            UPDATE bienes
            SET
                codigo_sicoin = :codigo_sicoin,
                descripcion = :descripcion,
                marca = :marca,
                modelo = :modelo,
                serie = :serie,
                id_categoria = :id_categoria,
                id_estado_bien = :id_estado_bien,
                condicion_bien = :condicion_bien,
                id_forma_ingreso = :id_forma_ingreso,
                fecha_ingreso = :fecha_ingreso,
                costo = :costo,
                valor_estimado = :valor_estimado,
                observaciones = :observaciones,
                updated_at = NOW()
            WHERE id_bien = :id_bien
        ";

        $params = [
            ':codigo_sicoin' => $datos['codigo_sicoin'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':marca' => $datos['marca'] ?? null,
            ':modelo' => $datos['modelo'] ?? null,
            ':serie' => $datos['serie'] ?? null,
            ':id_categoria' => $datos['id_categoria'] ?? null,
            ':id_estado_bien' => $datos['id_estado_bien'] ?? null,
            ':condicion_bien' => $datos['condicion_bien'] ?? null,
            ':id_forma_ingreso' => $datos['id_forma_ingreso'] ?? null,
            ':fecha_ingreso' => $datos['fecha_ingreso'] ?? null,
            ':costo' => $datos['costo'] ?? null,
            ':valor_estimado' => $datos['valor_estimado'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_bien' => $idBien,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function actualizarQr(int $idBien, string $codigoQr, string $rutaQr): bool
    {
        $sql = "
            UPDATE bienes
            SET
                codigo_qr = :codigo_qr,
                ruta_qr = :ruta_qr,
                updated_at = NOW()
            WHERE id_bien = :id_bien
        ";

        $params = [
            ':codigo_qr' => $codigoQr,
            ':ruta_qr' => $rutaQr,
            ':id_bien' => $idBien,
        ];

        $this->query($sql, $params);

        return true;
    }

    // Búsqueda simple para Verificación física: SIN filtro de estado a propósito — un bien en Baja
    // sigue formando parte del inventario histórico y puede existir físicamente en Bodega, así que
    // debe poder localizarse y verificarse igual que uno Activo (a diferencia de
    // getDisponiblesParaAsignacion() y similares, que sí filtran por 'Activo' porque resuelven
    // elegibilidad para un movimiento administrativo, no una simple consulta).
    public function buscar(string $termino): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                eb.nombre_estado,
                r.nombre_completo AS responsable_actual,
                u.nombre_ubicacion AS ubicacion_actual
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN responsables r ON b.id_responsable_actual = r.id_responsable
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            WHERE b.codigo_interno LIKE :termino_codigo
               OR b.codigo_sicoin LIKE :termino_sicoin
               OR b.descripcion LIKE :termino_descripcion
            ORDER BY b.codigo_interno ASC
        ";

        $like = '%' . $termino . '%';

        return $this->fetchAll($sql, [
            ':termino_codigo' => $like,
            ':termino_sicoin' => $like,
            ':termino_descripcion' => $like,
        ]);
    }

    public function getDisponiblesParaAsignacion(): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                u.nombre_ubicacion,
                u.tipo_ubicacion
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            WHERE eb.nombre_estado = 'Activo'
              AND b.id_asignacion_actual IS NULL
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_asignacion da
                    INNER JOIN asignaciones a2 ON a2.id_asignacion = da.id_asignacion
                    WHERE da.id_bien = b.id_bien
                      AND da.estado_detalle = 'activo'
                      AND a2.estado_asignacion = 'Pendiente'
              )
            ORDER BY b.codigo_interno ASC
        ";

        return $this->fetchAll($sql);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findDisponibleParaAsignacion(int $idBien): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND eb.nombre_estado = 'Activo'
              AND b.id_asignacion_actual IS NULL
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_asignacion da
                    INNER JOIN asignaciones a2 ON a2.id_asignacion = da.id_asignacion
                    WHERE da.id_bien = b.id_bien
                      AND da.estado_detalle = 'activo'
                      AND a2.estado_asignacion = 'Pendiente'
              )
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findConfirmableParaAsignacion(int $idBien, int $idAsignacion): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND eb.nombre_estado = 'Activo'
              AND b.id_asignacion_actual IS NULL
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_asignacion da
                    INNER JOIN asignaciones a2 ON a2.id_asignacion = da.id_asignacion
                    WHERE da.id_bien = b.id_bien
                      AND da.estado_detalle = 'activo'
                      AND a2.estado_asignacion = 'Pendiente'
                      AND da.id_asignacion <> :id_asignacion
              )
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_asignacion' => $idAsignacion,
        ]);
    }

    public function asignarActual(int $idBien, int $idAsignacion, int $idResponsable, int $idUbicacion): bool
    {
        $sql = "
            UPDATE bienes
            SET
                id_asignacion_actual = :id_asignacion,
                id_responsable_actual = :id_responsable,
                id_ubicacion_actual = :id_ubicacion
            WHERE id_bien = :id_bien
              AND id_asignacion_actual IS NULL
        ";

        $this->query($sql, [
            ':id_asignacion' => $idAsignacion,
            ':id_responsable' => $idResponsable,
            ':id_ubicacion' => $idUbicacion,
            ':id_bien' => $idBien,
        ]);

        return true;
    }

    // Bienes actuales de un responsable, independientemente de la asignación administrativa
    // (histórica) de la que provengan. Solo incluye bienes con estado Activo y con un detalle de
    // asignación activo resolvible (INNER JOIN): si no puede identificarse su detalle vigente,
    // el bien queda excluido y no aparece como trasladable. Excluye además bienes con una Baja
    // pendiente/autorizada en curso (mismo criterio que getElegiblesParaBajaPorResponsable()): un
    // bien con solicitud de Baja activa no debe aparecer como trasladable hasta que esa Baja se
    // resuelva (rechazada no bloquea; finalizada ya deja de ser 'Activo' y queda fuera por sí sola).
    public function getActualesPorResponsable(int $idResponsable): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                a.numero_asignacion,
                b.id_ubicacion_actual,
                u.nombre_ubicacion
            FROM bienes b
            INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            INNER JOIN asignaciones a ON b.id_asignacion_actual = a.id_asignacion
            INNER JOIN detalle_asignacion da
                ON da.id_asignacion = b.id_asignacion_actual
               AND da.id_bien = b.id_bien
               AND da.estado_detalle = 'activo'
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            WHERE b.id_responsable_actual = :id_responsable
              AND eb.nombre_estado = 'Activo'
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_baja db
                    INNER JOIN bajas ba ON db.id_baja = ba.id_baja
                    WHERE db.id_bien = b.id_bien
                      AND ba.estado_baja IN ('pendiente', 'autorizada')
              )
            ORDER BY a.numero_asignacion ASC, b.codigo_interno ASC
        ";

        return $this->fetchAll($sql, [':id_responsable' => $idResponsable]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // No asume una asignación origen fija: el bien puede provenir de cualquier asignación siempre
    // que su responsable actual coincida con el responsable origen seleccionado. Excluye bienes con
    // una Baja pendiente/autorizada en curso (mismo criterio que
    // Bien::getElegiblesParaBajaPorResponsable() / findElegibleParaBajaForUpdate()) — validación
    // obligatoria aquí porque esta es la consulta que realmente bloquea y confirma el traslado; no
    // basta con excluirlo solo del listado del formulario.
    public function findActualParaTrasladoForUpdate(int $idBien, int $idResponsableOrigen): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                b.id_responsable_actual,
                b.id_ubicacion_actual
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND b.id_responsable_actual = :id_responsable_origen
              AND b.id_asignacion_actual IS NOT NULL
              AND eb.nombre_estado = 'Activo'
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_baja db
                    INNER JOIN bajas ba ON db.id_baja = ba.id_baja
                    WHERE db.id_bien = b.id_bien
                      AND ba.estado_baja IN ('pendiente', 'autorizada')
              )
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_responsable_origen' => $idResponsableOrigen,
        ]);
    }

    // Actualiza el espejo del bien en un Traslado: a diferencia de asignarActual() (primera asignación,
    // exige id_asignacion_actual IS NULL), aquí el bien YA tiene asignación y se exige que coincida
    // exactamente con el origen esperado para detectar concurrencia (0 filas afectadas = ya cambió).
    public function actualizarAsignacionActual(
        int $idBien,
        int $idAsignacionOrigenEsperada,
        int $idAsignacionNueva,
        int $idResponsableNuevo,
        int $idUbicacionNueva
    ): bool {
        $sql = "
            UPDATE bienes
            SET
                id_asignacion_actual = :id_asignacion_nueva,
                id_responsable_actual = :id_responsable_nuevo,
                id_ubicacion_actual = :id_ubicacion_nueva
            WHERE id_bien = :id_bien
              AND id_asignacion_actual = :id_asignacion_origen_esperada
        ";

        $statement = $this->query($sql, [
            ':id_asignacion_nueva' => $idAsignacionNueva,
            ':id_responsable_nuevo' => $idResponsableNuevo,
            ':id_ubicacion_nueva' => $idUbicacionNueva,
            ':id_bien' => $idBien,
            ':id_asignacion_origen_esperada' => $idAsignacionOrigenEsperada,
        ]);

        return $statement->rowCount() === 1;
    }

    // Bienes actuales de un responsable que además no tienen ningún préstamo activo pendiente de
    // devolución. Lectura informativa (sin lock) para precargar el formulario de Préstamo; la
    // validación real y definitiva ocurre dentro de la transacción vía findPrestableForUpdate() +
    // DetallePrestamo::existePrestamoActivoPorBienForUpdate().
    public function getPrestablesPorResponsable(int $idResponsable): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                a.numero_asignacion,
                b.id_ubicacion_actual,
                u.nombre_ubicacion
            FROM bienes b
            INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            INNER JOIN asignaciones a ON b.id_asignacion_actual = a.id_asignacion
            INNER JOIN detalle_asignacion da
                ON da.id_asignacion = b.id_asignacion_actual
               AND da.id_bien = b.id_bien
               AND da.estado_detalle = 'activo'
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            WHERE b.id_responsable_actual = :id_responsable
              AND eb.nombre_estado = 'Activo'
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_prestamo dp
                    WHERE dp.id_bien = b.id_bien
                      AND dp.estado_detalle = 'prestado'
              )
            ORDER BY a.numero_asignacion ASC, b.codigo_interno ASC
        ";

        return $this->fetchAll($sql, [':id_responsable' => $idResponsable]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Método propio de Préstamo (no reutiliza findActualParaTrasladoForUpdate) para mantener
    // independencia semántica entre ambos procesos, aunque hoy la condición base sea la misma: no
    // valida "sin préstamo activo" aquí a propósito, eso se resuelve aparte con
    // DetallePrestamo::existePrestamoActivoPorBienForUpdate() para no mezclar una consulta gigantesca.
    public function findPrestableForUpdate(int $idBien, int $idResponsableOrigen): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                b.id_responsable_actual,
                b.id_ubicacion_actual
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND b.id_responsable_actual = :id_responsable_origen
              AND b.id_asignacion_actual IS NOT NULL
              AND eb.nombre_estado = 'Activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_responsable_origen' => $idResponsableOrigen,
        ]);
    }

    // Bienes actualmente bajo el responsable/ubicación de Bodega de Almacén, con detalle_asignacion
    // activo resolvible, que no estén reservados por otra Requisición ya Autorizada (fila en
    // detalle_requisicion con estado_detalle='reservado'). Una Requisición Pendiente NO reserva
    // (estado_detalle='pendiente'), por eso no se filtra ese estado aquí — puede aparecer en varias
    // requisiciones Pendientes a la vez, tal como fue definido explícitamente para este módulo.
    public function getDisponiblesEnAlmacenParaRequisicion(int $idResponsableAlmacen, int $idUbicacionAlmacen): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_asignacion_actual,
                a.numero_asignacion
            FROM bienes b
            INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            INNER JOIN asignaciones a ON b.id_asignacion_actual = a.id_asignacion
            INNER JOIN detalle_asignacion da
                ON da.id_asignacion = b.id_asignacion_actual
               AND da.id_bien = b.id_bien
               AND da.estado_detalle = 'activo'
            WHERE b.id_responsable_actual = :id_responsable_almacen
              AND b.id_ubicacion_actual = :id_ubicacion_almacen
              AND eb.nombre_estado = 'Activo'
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_requisicion dr
                    WHERE dr.id_bien = b.id_bien
                      AND dr.estado_detalle = 'reservado'
              )
            ORDER BY b.codigo_interno ASC
        ";

        return $this->fetchAll($sql, [
            ':id_responsable_almacen' => $idResponsableAlmacen,
            ':id_ubicacion_almacen' => $idUbicacionAlmacen,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Usado por Requisiciones (registrar/editar/autorizar/confirmar entrega) para reconfirmar, en el
    // instante exacto de cada operación, que el bien sigue Activo y sigue bajo el responsable/
    // ubicación de Bodega de Almacén — no se confía en el estado leído al mostrar el formulario.
    public function findActualParaRequisicionForUpdate(int $idBien, int $idResponsableAlmacen, int $idUbicacionAlmacen): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                b.id_responsable_actual,
                b.id_ubicacion_actual
            FROM bienes b
            LEFT JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND b.id_responsable_actual = :id_responsable_almacen
              AND b.id_ubicacion_actual = :id_ubicacion_almacen
              AND b.id_asignacion_actual IS NOT NULL
              AND eb.nombre_estado = 'Activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_responsable_almacen' => $idResponsableAlmacen,
            ':id_ubicacion_almacen' => $idUbicacionAlmacen,
        ]);
    }

    // Bienes elegibles para Baja de un responsable: Activos, con detalle_asignacion activo
    // resolvible, sin préstamo activo, sin reserva de Requisición autorizada, y sin estar ya en otra
    // Baja vigente (pendiente/autorizada). $idBajaExcluir permite reutilizar esta misma consulta al
    // editar una Baja Pendiente (excluye la propia baja de la validación "ya está en otra baja").
    public function getElegiblesParaBajaPorResponsable(int $idResponsable, int $idBajaExcluir = 0): array
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.condicion_bien,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                b.id_responsable_actual,
                b.id_ubicacion_actual,
                u.nombre_ubicacion
            FROM bienes b
            INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            INNER JOIN asignaciones a ON b.id_asignacion_actual = a.id_asignacion
            INNER JOIN detalle_asignacion da
                ON da.id_asignacion = b.id_asignacion_actual
               AND da.id_bien = b.id_bien
               AND da.estado_detalle = 'activo'
            LEFT JOIN ubicaciones u ON b.id_ubicacion_actual = u.id_ubicacion
            WHERE b.id_responsable_actual = :id_responsable
              AND eb.nombre_estado = 'Activo'
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_prestamo dp
                    WHERE dp.id_bien = b.id_bien
                      AND dp.estado_detalle = 'prestado'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_requisicion dr
                    WHERE dr.id_bien = b.id_bien
                      AND dr.estado_detalle = 'reservado'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_baja db
                    INNER JOIN bajas ba ON db.id_baja = ba.id_baja
                    WHERE db.id_bien = b.id_bien
                      AND ba.estado_baja IN ('pendiente', 'autorizada')
                      AND ba.id_baja <> :id_baja_excluir
              )
            ORDER BY b.codigo_interno ASC
        ";

        return $this->fetchAll($sql, [
            ':id_responsable' => $idResponsable,
            ':id_baja_excluir' => $idBajaExcluir,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Mismas condiciones que getElegiblesParaBajaPorResponsable(), para revalidar en el instante exacto
    // de guardar (crear/editar) que el bien sigue siendo elegible — no se confía en lo mostrado en el
    // formulario.
    public function findElegibleParaBajaForUpdate(int $idBien, int $idResponsable, int $idBajaExcluir = 0): array|false
    {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.codigo_sicoin,
                b.descripcion,
                b.marca,
                b.modelo,
                b.serie,
                b.costo,
                b.valor_estimado,
                b.id_estado_bien,
                b.id_asignacion_actual,
                b.id_responsable_actual,
                b.id_ubicacion_actual
            FROM bienes b
            INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND b.id_responsable_actual = :id_responsable
              AND b.id_asignacion_actual IS NOT NULL
              AND eb.nombre_estado = 'Activo'
              AND EXISTS (
                    SELECT 1
                    FROM detalle_asignacion da
                    WHERE da.id_asignacion = b.id_asignacion_actual
                      AND da.id_bien = b.id_bien
                      AND da.estado_detalle = 'activo'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_prestamo dp
                    WHERE dp.id_bien = b.id_bien
                      AND dp.estado_detalle = 'prestado'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_requisicion dr
                    WHERE dr.id_bien = b.id_bien
                      AND dr.estado_detalle = 'reservado'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_baja db
                    INNER JOIN bajas ba ON db.id_baja = ba.id_baja
                    WHERE db.id_bien = b.id_bien
                      AND ba.estado_baja IN ('pendiente', 'autorizada')
                      AND ba.id_baja <> :id_baja_excluir
              )
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_responsable' => $idResponsable,
            ':id_baja_excluir' => $idBajaExcluir,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Revalida, en el instante exacto de finalizar (nunca antes), que el bien sigue exactamente en
    // las condiciones esperadas: mismo responsable y ubicación que el snapshot de detalle_baja,
    // Activo, con asignación vigente y detalle_asignacion activo resolvible, sin préstamo activo, sin
    // reserva de Requisición, y sin estar en otra Baja pendiente/autorizada distinta de la propia
    // (mismas exclusiones que findElegibleParaBajaForUpdate(), reaplicadas por si algo cambió entre
    // Autorizar y Finalizar). No se confía en el snapshot histórico para decidir el estado actual.
    public function findParaFinalizarBajaForUpdate(
        int $idBien,
        int $idResponsableEsperado,
        int $idUbicacionEsperada,
        int $idBajaExcluir
    ): array|false {
        $sql = "
            SELECT
                b.id_bien,
                b.codigo_interno,
                b.id_estado_bien,
                b.id_asignacion_actual,
                b.id_responsable_actual,
                b.id_ubicacion_actual
            FROM bienes b
            INNER JOIN estados_bien eb ON b.id_estado_bien = eb.id_estado_bien
            WHERE b.id_bien = :id_bien
              AND b.id_responsable_actual = :id_responsable_esperado
              AND b.id_ubicacion_actual = :id_ubicacion_esperada
              AND b.id_asignacion_actual IS NOT NULL
              AND eb.nombre_estado = 'Activo'
              AND EXISTS (
                    SELECT 1
                    FROM detalle_asignacion da
                    WHERE da.id_asignacion = b.id_asignacion_actual
                      AND da.id_bien = b.id_bien
                      AND da.estado_detalle = 'activo'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_prestamo dp
                    WHERE dp.id_bien = b.id_bien
                      AND dp.estado_detalle = 'prestado'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_requisicion dr
                    WHERE dr.id_bien = b.id_bien
                      AND dr.estado_detalle = 'reservado'
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM detalle_baja db2
                    INNER JOIN bajas ba2 ON db2.id_baja = ba2.id_baja
                    WHERE db2.id_bien = b.id_bien
                      AND ba2.estado_baja IN ('pendiente', 'autorizada')
                      AND ba2.id_baja <> :id_baja_excluir
              )
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [
            ':id_bien' => $idBien,
            ':id_responsable_esperado' => $idResponsableEsperado,
            ':id_ubicacion_esperada' => $idUbicacionEsperada,
            ':id_baja_excluir' => $idBajaExcluir,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa. Aplica el retiro definitivo por Baja: estado
    // Baja, sin responsable, sin asignación, ubicación = Bodega destino de la propia Baja. Nunca crea
    // asignación ni busca responsable de esa Bodega — es solo su ubicación física final.
    // $idAsignacionOrigenEsperada exige coincidencia exacta (mismo criterio de concurrencia que
    // actualizarAsignacionActual()): 0 filas afectadas = el bien cambió durante el proceso.
    public function finalizarBaja(
        int $idBien,
        int $idAsignacionOrigenEsperada,
        int $idEstadoBienBaja,
        int $idUbicacionBodegaDestino
    ): bool {
        $sql = "
            UPDATE bienes
            SET
                id_estado_bien = :id_estado_bien_baja,
                id_responsable_actual = NULL,
                id_asignacion_actual = NULL,
                id_ubicacion_actual = :id_ubicacion_bodega_destino
            WHERE id_bien = :id_bien
              AND id_asignacion_actual = :id_asignacion_origen_esperada
        ";

        $statement = $this->query($sql, [
            ':id_estado_bien_baja' => $idEstadoBienBaja,
            ':id_ubicacion_bodega_destino' => $idUbicacionBodegaDestino,
            ':id_bien' => $idBien,
            ':id_asignacion_origen_esperada' => $idAsignacionOrigenEsperada,
        ]);

        return $statement->rowCount() === 1;
    }
}