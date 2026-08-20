<?php

require_once __DIR__ . '/../core/Model.php';

class Ubicacion extends Model
{
    protected string $table = 'ubicaciones';

    public function getActivas(): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion
            FROM ubicaciones
            WHERE estado_ubicacion = 'activa'
            ORDER BY nombre_ubicacion ASC
        ";

        return $this->fetchAll($sql);
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            FROM ubicaciones
            ORDER BY nombre_ubicacion ASC
        ";

        return $this->fetchAll($sql);
    }

    public function findById(int $idUbicacion): array|false
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            FROM ubicaciones
            WHERE id_ubicacion = :id_ubicacion
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_ubicacion' => $idUbicacion]);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO ubicaciones (
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            ) VALUES (
                :nombre_ubicacion,
                :tipo_ubicacion,
                :descripcion,
                :estado_ubicacion
            )
        ";

        $params = [
            ':nombre_ubicacion' => $datos['nombre_ubicacion'] ?? null,
            ':tipo_ubicacion' => $datos['tipo_ubicacion'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':estado_ubicacion' => $datos['estado_ubicacion'] ?? 'activa',
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    public function actualizar(int $idUbicacion, array $datos): bool
    {
        $sql = "
            UPDATE ubicaciones
            SET
                nombre_ubicacion = :nombre_ubicacion,
                tipo_ubicacion = :tipo_ubicacion,
                descripcion = :descripcion,
                estado_ubicacion = :estado_ubicacion
            WHERE id_ubicacion = :id_ubicacion
        ";

        $params = [
            ':nombre_ubicacion' => $datos['nombre_ubicacion'] ?? null,
            ':tipo_ubicacion' => $datos['tipo_ubicacion'] ?? null,
            ':descripcion' => $datos['descripcion'] ?? null,
            ':estado_ubicacion' => $datos['estado_ubicacion'] ?? null,
            ':id_ubicacion' => $idUbicacion,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function cambiarEstado(int $idUbicacion, string $estado): bool
    {
        $sql = "
            UPDATE ubicaciones
            SET estado_ubicacion = :estado_ubicacion
            WHERE id_ubicacion = :id_ubicacion
        ";

        $params = [
            ':estado_ubicacion' => $estado,
            ':id_ubicacion' => $idUbicacion,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        if ($excluirId === null) {
            $sql = "
                SELECT id_ubicacion
                FROM ubicaciones
                WHERE nombre_ubicacion = :nombre
                LIMIT 1
            ";

            $params = [':nombre' => $nombre];
        } else {
            $sql = "
                SELECT id_ubicacion
                FROM ubicaciones
                WHERE nombre_ubicacion = :nombre AND id_ubicacion <> :id_ubicacion
                LIMIT 1
            ";

            $params = [
                ':nombre' => $nombre,
                ':id_ubicacion' => $excluirId,
            ];
        }

        return $this->fetchOne($sql, $params) !== false;
    }

    public function buscar(string $termino): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                descripcion,
                estado_ubicacion
            FROM ubicaciones
            WHERE nombre_ubicacion LIKE :termino_nombre
               OR tipo_ubicacion LIKE :termino_tipo
            ORDER BY nombre_ubicacion ASC
        ";

        $like = '%' . $termino . '%';

        return $this->fetchAll($sql, [
            ':termino_nombre' => $like,
            ':termino_tipo' => $like,
        ]);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findActivaByIdForUpdate(int $idUbicacion): array|false
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                estado_ubicacion
            FROM ubicaciones
            WHERE id_ubicacion = :id_ubicacion
              AND estado_ubicacion = 'activa'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_ubicacion' => $idUbicacion]);
    }

    // Id de la ubicación institucional de "Bodega de Almacén" — no "cualquier/la única ubicación
    // tipo Bodega" (en el futuro puede haber varias Bodegas legítimas sin que eso deba impedir
    // registrar bienes). No existe todavía infraestructura de configuración institucional
    // (tabla de parámetros); mientras no exista, se centraliza AQUÍ, en un único punto con nombre
    // explícito, en vez de dispersar el id por controladores/modelos.
    //
    // Se usa el id (no el nombre) porque el id es el dato realmente estable: el nombre de esta
    // misma ubicación ya cambió una vez ("Almacen" -> "Almacen Institucional") sin que el id
    // cambiara, confirmando en la práctica que resolver por nombre textual sería frágil.
    //
    // Confirmado institucionalmente el 2026-08-18: id_ubicacion=2, tipo_ubicacion='Bodega'.
    // PENDIENTE: mover a una configuración institucional real si el sistema llega a incorporarla.
    public const ID_UBICACION_ALMACEN_INSTITUCIONAL = 2;

    // Resuelve específicamente la Bodega de Almacén institucional (ID_UBICACION_ALMACEN_INSTITUCIONAL),
    // no "la única ubicación de tipo Bodega que exista". Devuelve false si esa ubicación no existe,
    // no está activa, o dejó de ser de tipo 'Bodega' (protección ante un cambio de tipo que
    // invalidaría la configuración) — el llamador debe tratarlo como "Almacén no configurado
    // correctamente" y no debe adivinar ni sustituir por otra ubicación.
    public function findAlmacenInstitucional(): array|false
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                estado_ubicacion
            FROM ubicaciones
            WHERE id_ubicacion = :id_ubicacion
              AND tipo_ubicacion = 'Bodega'
              AND estado_ubicacion = 'activa'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_ubicacion' => self::ID_UBICACION_ALMACEN_INSTITUCIONAL]);
    }

    // Id de la ubicación institucional de "Inventario" — de aquí se resuelve el nombre de la
    // Encargada que firma "Autoriza" en la constancia de Requisición (Responsable::findActivoPorUbicacion()
    // sobre este id). Mismo criterio que ID_UBICACION_ALMACEN_INSTITUCIONAL: se fija por id, no por
    // nombre textual, porque el id es el dato estable.
    //
    // Confirmado institucionalmente el 2026-08-18: id_ubicacion=1, tipo_ubicacion='Departamento',
    // responsable activo único en esa ubicación = Evelyn Castañeda de Tejada (Encargada), consistente
    // con el nombre que la usuaria ya dejó como ejemplo real en la plantilla de Requisición.
    public const ID_UBICACION_INVENTARIOS_INSTITUCIONAL = 1;

    // Bodega destino de Bajas: cualquier ubicación activa tipo 'Bodega', no una única fija (a
    // diferencia de ID_UBICACION_ALMACEN_INSTITUCIONAL). Hoy solo existe "Almacén Institucional",
    // pero el selector debe seguir funcionando igual si en el futuro existe más de una.
    public function getBodegasActivas(): array
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion
            FROM ubicaciones
            WHERE tipo_ubicacion = 'Bodega'
              AND estado_ubicacion = 'activa'
            ORDER BY nombre_ubicacion ASC
        ";

        return $this->fetchAll($sql);
    }

    public function findInventariosInstitucional(): array|false
    {
        $sql = "
            SELECT
                id_ubicacion,
                nombre_ubicacion,
                tipo_ubicacion,
                estado_ubicacion
            FROM ubicaciones
            WHERE id_ubicacion = :id_ubicacion
              AND estado_ubicacion = 'activa'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_ubicacion' => self::ID_UBICACION_INVENTARIOS_INSTITUCIONAL]);
    }
}
