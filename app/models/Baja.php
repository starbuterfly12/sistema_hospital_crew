<?php

require_once __DIR__ . '/../core/Model.php';

class Baja extends Model
{
    protected string $table = 'bajas';

    // Nomenclatura oficial de `estado_baja`, confirmada en la revisión funcional del módulo
    // (2026-08-19): la decisión administrativa sobre una solicitud es binaria (Aceptar/Rechazar), sin
    // tercera opción ni "anulada". NO usar 'registrada' ni 'anulada': quedaron descartados.
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_AUTORIZADA = 'autorizada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_FINALIZADA = 'finalizada';

    public const ESTADOS_VALIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_AUTORIZADA,
        self::ESTADO_RECHAZADA,
        self::ESTADO_FINALIZADA,
    ];

    // `bajas` ya NO tiene id_tipo_baja ni motivo: una misma solicitud puede incluir bienes con
    // tipo de baja y justificación distintos, así que ambos viven en detalle_baja (revisión
    // funcional 2026-08-19). La cabecera solo agrupa: Bodega destino, responsable, auxiliar,
    // correlativo, estado y observaciones generales.
    // Incluye auxiliar_encargado aunque el listado normal (bajas/index.php) no lo muestre: la
    // bandeja administrativa de Solicitudes (bajas/solicitudes.php) sí lo necesita y reutiliza esta
    // misma consulta en vez de duplicarla (BajasController::solicitudes()).
    // Filtros opcionales para el listado (por GET, consulta preparada). Sin argumentos se comporta
    // EXACTAMENTE igual que antes (sin WHERE) — así BajasController::solicitudes() la sigue usando tal cual.
    //   $q          -> LIKE sobre numero_baja / responsable del área / Bodega destino / servicio,
    //                  y (EXISTS) sobre código interno o descripción de cualquier bien de la baja.
    //   $estado     -> b.estado_baja exacto ('pendiente' / 'autorizada' / 'rechazada' / 'finalizada').
    //   $idTipoBaja -> baja que incluya al menos un bien con ese tipo (detalle_baja.id_tipo_baja).
    public function getAll(?string $q = null, ?string $estado = null, ?int $idTipoBaja = null): array
    {
        $condiciones = [];
        $params = [];

        if ($q !== null && trim($q) !== '') {
            // Placeholders distintos por ocurrencia: con PDO::ATTR_EMULATE_PREPARES=false un mismo
            // nombre no puede repetirse en la consulta (mismo criterio que los demás listados).
            $condiciones[] = "(
                b.numero_baja LIKE :q_num
                OR r.nombre_completo LIKE :q_resp
                OR u.nombre_ubicacion LIKE :q_bod
                OR ru.nombre_ubicacion LIKE :q_serv
                OR EXISTS (
                    SELECT 1 FROM detalle_baja dbq
                    WHERE dbq.id_baja = b.id_baja
                      AND (dbq.codigo_interno_mostrado LIKE :q_cod OR dbq.descripcion_mostrada LIKE :q_desc)
                )
            )";
            $like = '%' . trim($q) . '%';
            $params[':q_num'] = $like;
            $params[':q_resp'] = $like;
            $params[':q_bod'] = $like;
            $params[':q_serv'] = $like;
            $params[':q_cod'] = $like;
            $params[':q_desc'] = $like;
        }

        if ($estado !== null && trim($estado) !== '') {
            $condiciones[] = "b.estado_baja = :estado";
            $params[':estado'] = trim($estado);
        }

        if ($idTipoBaja !== null && $idTipoBaja > 0) {
            $condiciones[] = "EXISTS (
                SELECT 1 FROM detalle_baja dbt
                WHERE dbt.id_baja = b.id_baja AND dbt.id_tipo_baja = :id_tipo_baja
            )";
            $params[':id_tipo_baja'] = $idTipoBaja;
        }

        $where = $condiciones !== [] ? ' WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "
            SELECT
                b.id_baja,
                b.numero_baja,
                b.id_ubicacion_bodega_destino,
                u.nombre_ubicacion AS ubicacion_bodega_destino,
                b.id_responsable_descarga,
                r.nombre_completo AS responsable_descarga,
                ru.nombre_ubicacion AS ubicacion_responsable_descarga,
                b.id_auxiliar_encargado,
                aux.nombre_completo AS auxiliar_encargado,
                b.estado_baja,
                b.fecha_preparacion,
                b.fecha_autorizacion,
                b.fecha_baja,
                (
                    SELECT COUNT(*)
                    FROM detalle_baja db
                    WHERE db.id_baja = b.id_baja
                ) AS total_bienes
            FROM bajas b
            LEFT JOIN ubicaciones u ON b.id_ubicacion_bodega_destino = u.id_ubicacion
            LEFT JOIN responsables r ON b.id_responsable_descarga = r.id_responsable
            LEFT JOIN ubicaciones ru ON r.id_ubicacion = ru.id_ubicacion
            LEFT JOIN usuarios aux ON b.id_auxiliar_encargado = aux.id_usuario
            {$where}
            ORDER BY b.id_baja DESC
        ";

        return $this->fetchAll($sql, $params);
    }

    // Usado solo para el contador opcional "(N pendientes)" junto al enlace de Solicitudes de baja
    // en el menú Movimientos — visible únicamente para Administrador.
    public function contarPendientes(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM bajas WHERE estado_baja = 'pendiente'";

        $resultado = $this->fetchOne($sql);

        return $resultado !== false ? (int) $resultado['total'] : 0;
    }

    public function findById(int $idBaja): array|false
    {
        $sql = "
            SELECT
                b.id_baja,
                b.numero_baja,
                b.id_ubicacion_bodega_destino,
                u.nombre_ubicacion AS ubicacion_bodega_destino,
                b.id_responsable_descarga,
                r.nombre_completo AS responsable_descarga,
                ru.nombre_ubicacion AS ubicacion_responsable_descarga,
                b.id_auxiliar_encargado,
                aux.nombre_completo AS auxiliar_encargado,
                b.id_usuario_registra,
                reg.nombre_completo AS usuario_registra,
                b.id_usuario_autoriza,
                aut.nombre_completo AS usuario_autoriza,
                b.id_usuario_rechaza,
                rec.nombre_completo AS usuario_rechaza,
                b.fecha_baja,
                b.numero_acta,
                b.fecha_preparacion,
                b.fecha_autorizacion,
                b.fecha_rechazo,
                b.estado_baja,
                b.documento_respaldo,
                b.observaciones,
                b.created_at,
                b.updated_at
            FROM bajas b
            LEFT JOIN ubicaciones u ON b.id_ubicacion_bodega_destino = u.id_ubicacion
            LEFT JOIN responsables r ON b.id_responsable_descarga = r.id_responsable
            LEFT JOIN ubicaciones ru ON r.id_ubicacion = ru.id_ubicacion
            LEFT JOIN usuarios aux ON b.id_auxiliar_encargado = aux.id_usuario
            LEFT JOIN usuarios reg ON b.id_usuario_registra = reg.id_usuario
            LEFT JOIN usuarios aut ON b.id_usuario_autoriza = aut.id_usuario
            LEFT JOIN usuarios rec ON b.id_usuario_rechaza = rec.id_usuario
            WHERE b.id_baja = :id_baja
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_baja' => $idBaja]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    // Usado por editar(), autorizar() y rechazar() para releer y bloquear la cabecera antes de
    // validar su estado_baja actual. finalizar() todavía no está implementado.
    public function findByIdForUpdate(int $idBaja): array|false
    {
        $sql = "
            SELECT
                id_baja,
                numero_baja,
                id_ubicacion_bodega_destino,
                id_responsable_descarga,
                id_auxiliar_encargado,
                id_usuario_registra,
                id_usuario_autoriza,
                documento_respaldo,
                estado_baja
            FROM bajas
            WHERE id_baja = :id_baja
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_baja' => $idBaja]);
    }

    // Lectura mínima para el endpoint autenticado que sirve el documento de respaldo de una Baja
    // (BajasController::verDocumento()). Solo devuelve el id y la ruta relativa guardada; la ruta
    // se resuelve/valida contra el sistema de archivos en el controlador, nunca llega del navegador.
    public function findDocumentoRespaldoPorId(int $idBaja): array|false
    {
        return $this->fetchOne(
            "SELECT id_baja, documento_respaldo FROM bajas WHERE id_baja = :id_baja LIMIT 1",
            [':id_baja' => $idBaja]
        );
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO bajas (
                numero_baja,
                id_ubicacion_bodega_destino,
                id_responsable_descarga,
                id_auxiliar_encargado,
                id_usuario_registra,
                numero_acta,
                estado_baja,
                documento_respaldo,
                observaciones
            ) VALUES (
                :numero_baja,
                :id_ubicacion_bodega_destino,
                :id_responsable_descarga,
                :id_auxiliar_encargado,
                :id_usuario_registra,
                :numero_acta,
                :estado_baja,
                :documento_respaldo,
                :observaciones
            )
        ";

        $params = [
            ':numero_baja' => $datos['numero_baja'],
            ':id_ubicacion_bodega_destino' => $datos['id_ubicacion_bodega_destino'],
            ':id_responsable_descarga' => $datos['id_responsable_descarga'],
            ':id_auxiliar_encargado' => $datos['id_auxiliar_encargado'],
            ':id_usuario_registra' => $datos['id_usuario_registra'],
            ':numero_acta' => $datos['numero_acta'] ?? null,
            ':estado_baja' => self::ESTADO_PENDIENTE,
            ':documento_respaldo' => $datos['documento_respaldo'] ?? null,
            ':observaciones' => $datos['observaciones'] ?? null,
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // Solo debe usarse mientras estado_baja = 'pendiente' (verificado por el llamador antes de
    // invocar). No toca numero_baja, id_usuario_registra, id_auxiliar_encargado ni estado_baja:
    // el Auxiliar de Inventarios es quien preparó la Baja originalmente y no cambia aunque otro
    // usuario edite la solicitud después (regla funcional confirmada 2026-08-19).
    //
    // Tampoco toca documento_respaldo: ese campo no forma parte del flujo de solicitud inicial en
    // esta fase (revisión funcional 2026-08-19) — permanece NULL desde crear() y editar() no lo
    // modifica. La columna sigue existiendo en `bajas` para una etapa administrativa futura.
    public function actualizarCabecera(int $idBaja, array $datos): bool
    {
        $sql = "
            UPDATE bajas
            SET
                id_ubicacion_bodega_destino = :id_ubicacion_bodega_destino,
                id_responsable_descarga = :id_responsable_descarga,
                observaciones = :observaciones,
                updated_at = NOW()
            WHERE id_baja = :id_baja
              AND estado_baja = 'pendiente'
        ";

        $params = [
            ':id_ubicacion_bodega_destino' => $datos['id_ubicacion_bodega_destino'],
            ':id_responsable_descarga' => $datos['id_responsable_descarga'],
            ':observaciones' => $datos['observaciones'] ?? null,
            ':id_baja' => $idBaja,
        ];

        $this->query($sql, $params);

        return true;
    }

    // Solo debe usarse mientras estado_baja = 'pendiente' (verificado por el llamador, bloqueado con
    // findByIdForUpdate(), antes de invocar). NO toca bienes/asignaciones/Tarjeta — solo la decisión
    // administrativa sobre la propia solicitud.
    public function autorizar(int $idBaja, int $idUsuarioAutoriza): bool
    {
        $sql = "
            UPDATE bajas
            SET
                estado_baja = :estado_autorizada,
                id_usuario_autoriza = :id_usuario_autoriza,
                fecha_autorizacion = NOW()
            WHERE id_baja = :id_baja
              AND estado_baja = :estado_pendiente
        ";

        $this->query($sql, [
            ':estado_autorizada' => self::ESTADO_AUTORIZADA,
            ':id_usuario_autoriza' => $idUsuarioAutoriza,
            ':id_baja' => $idBaja,
            ':estado_pendiente' => self::ESTADO_PENDIENTE,
        ]);

        return true;
    }

    // Solo debe usarse mientras estado_baja = 'pendiente' (verificado por el llamador, bloqueado con
    // findByIdForUpdate(), antes de invocar). Decisión mutuamente excluyente con autorizar(): esta
    // consulta nunca toca id_usuario_autoriza/fecha_autorizacion, que permanecen NULL. NO toca
    // bienes/asignaciones/Tarjeta. Sin motivo_rechazo: el proceso aprobado no exige justificación.
    public function rechazar(int $idBaja, int $idUsuarioRechaza): bool
    {
        $sql = "
            UPDATE bajas
            SET
                estado_baja = :estado_rechazada,
                id_usuario_rechaza = :id_usuario_rechaza,
                fecha_rechazo = NOW()
            WHERE id_baja = :id_baja
              AND estado_baja = :estado_pendiente
        ";

        $this->query($sql, [
            ':estado_rechazada' => self::ESTADO_RECHAZADA,
            ':id_usuario_rechaza' => $idUsuarioRechaza,
            ':id_baja' => $idBaja,
            ':estado_pendiente' => self::ESTADO_PENDIENTE,
        ]);

        return true;
    }

    // Solo debe usarse mientras estado_baja = 'autorizada' (verificado por el llamador, bloqueado con
    // findByIdForUpdate(), antes de invocar), y solo DESPUÉS de procesar y retirar todos los bienes
    // de la Baja — nunca antes. No toca id_usuario_autoriza/fecha_autorizacion (se conservan como
    // historial de quién autorizó). $fechaBaja en formato 'Y-m-d' (bajas.fecha_baja es DATE).
    public function finalizar(int $idBaja, string $fechaBaja): bool
    {
        $sql = "
            UPDATE bajas
            SET
                estado_baja = :estado_finalizada,
                fecha_baja = :fecha_baja
            WHERE id_baja = :id_baja
              AND estado_baja = :estado_autorizada
        ";

        $this->query($sql, [
            ':estado_finalizada' => self::ESTADO_FINALIZADA,
            ':fecha_baja' => $fechaBaja,
            ':id_baja' => $idBaja,
            ':estado_autorizada' => self::ESTADO_AUTORIZADA,
        ]);

        return true;
    }

    // Mismo patrón seguro de correlativos que Asignacion::generarSiguienteNumero(): MAX + 1 sobre el
    // consecutivo numérico del año, con formato BAJA-YYYY-NNNNNN (aprobado en la fase de esquema).
    public function generarSiguienteNumero(int $anio): string
    {
        $sql = "
            SELECT MAX(CAST(SUBSTRING(numero_baja, 11) AS UNSIGNED)) AS ultimo_consecutivo
            FROM bajas
            WHERE numero_baja LIKE :prefijo
        ";

        $prefijo = sprintf('BAJA-%04d-%%', $anio);

        $resultado = $this->fetchOne($sql, [':prefijo' => $prefijo]);

        $ultimoConsecutivo = ($resultado !== false && $resultado['ultimo_consecutivo'] !== null)
            ? (int) $resultado['ultimo_consecutivo']
            : 0;

        $siguienteConsecutivo = $ultimoConsecutivo + 1;

        return sprintf('BAJA-%04d-%06d', $anio, $siguienteConsecutivo);
    }
}
