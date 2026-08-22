<?php

require_once __DIR__ . '/../core/Model.php';

class Usuario extends Model
{
    protected string $table = 'usuarios';

    public function findByUsuario(string $usuario): array|false
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.id_rol,
                u.nombre_completo,
                u.usuario,
                u.password_hash,
                u.estado_usuario,
                u.correo,
                u.telefono,
                r.nombre_rol,
                r.estado_rol
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.usuario = :usuario
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':usuario' => $usuario]);
    }

    public function findById(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo,
                estado_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    public function getActivos(): array
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo
            FROM usuarios
            WHERE estado_usuario = 'activo'
            ORDER BY nombre_completo ASC
        ";

        return $this->fetchAll($sql);
    }

    // Debe ejecutarse dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findActivoByIdForUpdate(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo,
                estado_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
              AND estado_usuario = 'activo'
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    // Usado exclusivamente para reverificar la contraseña del Administrador autenticado antes de una
    // acción sensible (ej. BajasController::autorizar()) con password_verify(). El hash nunca sale de
    // este método hacia una vista ni se registra en bitácora — el llamador debe descartarlo tras
    // verificar.
    public function findActivoConPasswordById(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                id_usuario,
                nombre_completo,
                password_hash,
                estado_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario
              AND estado_usuario = 'activo'
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    // =========================================================================================
    // Módulo Usuarios — administración (a partir de aquí, todo nuevo; los métodos de arriba ya eran
    // usados por otros módulos y no se modificaron).
    // =========================================================================================

    // Listado con filtros opcionales para app/views/usuarios/index.php. Nunca incluye password_hash.
    public function getAll(array $filtros = []): array
    {
        $condiciones = [];
        $params = [];

        $q = trim((string) ($filtros['q'] ?? ''));
        if ($q !== '') {
            $condiciones[] = '(u.nombre_completo LIKE :q_nombre OR u.usuario LIKE :q_usuario)';
            $params[':q_nombre'] = '%' . $q . '%';
            $params[':q_usuario'] = '%' . $q . '%';
        }

        $idRol = (int) ($filtros['id_rol'] ?? 0);
        if ($idRol > 0) {
            $condiciones[] = 'u.id_rol = :id_rol';
            $params[':id_rol'] = $idRol;
        }

        $estado = (string) ($filtros['estado'] ?? '');
        if (in_array($estado, ['activo', 'inactivo'], true)) {
            $condiciones[] = 'u.estado_usuario = :estado';
            $params[':estado'] = $estado;
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        $sql = "
            SELECT
                u.id_usuario,
                u.nombre_completo,
                u.usuario,
                u.correo,
                u.estado_usuario,
                u.ultimo_acceso,
                r.nombre_rol
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            {$where}
            ORDER BY u.nombre_completo ASC
        ";

        return $this->fetchAll($sql, $params);
    }

    // Detalle completo para ver.php/editar.php — deliberadamente SIN password_hash.
    public function findByIdCompleto(int $idUsuario): array|false
    {
        $sql = "
            SELECT
                u.id_usuario,
                u.id_rol,
                u.nombre_completo,
                u.usuario,
                u.correo,
                u.telefono,
                u.estado_usuario,
                u.ultimo_acceso,
                u.created_at,
                u.updated_at,
                r.nombre_rol,
                r.estado_rol
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.id_usuario = :id_usuario
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    // Uso interno exclusivo de las reglas de protección de Administrador (UsuariosController): incluye
    // password_hash, que NUNCA debe pasar de ahí hacia una vista ni hacia bitácora. Debe ejecutarse
    // dentro de una transacción activa para que el bloqueo FOR UPDATE tenga efecto.
    public function findByIdForUpdate(int $idUsuario): array|false
    {
        $sql = "
            SELECT id_usuario, id_rol, estado_usuario, password_hash
            FROM usuarios
            WHERE id_usuario = :id_usuario
            LIMIT 1
            FOR UPDATE
        ";

        return $this->fetchOne($sql, [':id_usuario' => $idUsuario]);
    }

    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO usuarios (
                nombre_completo,
                usuario,
                correo,
                telefono,
                id_rol,
                password_hash,
                estado_usuario
            ) VALUES (
                :nombre_completo,
                :usuario,
                :correo,
                :telefono,
                :id_rol,
                :password_hash,
                :estado_usuario
            )
        ";

        $params = [
            ':nombre_completo' => $datos['nombre_completo'] ?? null,
            ':usuario' => $datos['usuario'] ?? null,
            ':correo' => $datos['correo'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':id_rol' => $datos['id_rol'] ?? null,
            ':password_hash' => $datos['password_hash'] ?? null,
            ':estado_usuario' => $datos['estado_usuario'] ?? 'activo',
        ];

        $this->query($sql, $params);

        return (int) $this->lastInsertId();
    }

    // Nunca toca password_hash — el cambio de contraseña es una acción separada (cambiarPassword()).
    public function actualizar(int $idUsuario, array $datos): bool
    {
        $sql = "
            UPDATE usuarios
            SET
                nombre_completo = :nombre_completo,
                usuario = :usuario,
                correo = :correo,
                telefono = :telefono,
                id_rol = :id_rol,
                estado_usuario = :estado_usuario
            WHERE id_usuario = :id_usuario
        ";

        $params = [
            ':nombre_completo' => $datos['nombre_completo'] ?? null,
            ':usuario' => $datos['usuario'] ?? null,
            ':correo' => $datos['correo'] ?? null,
            ':telefono' => $datos['telefono'] ?? null,
            ':id_rol' => $datos['id_rol'] ?? null,
            ':estado_usuario' => $datos['estado_usuario'] ?? null,
            ':id_usuario' => $idUsuario,
        ];

        $this->query($sql, $params);

        return true;
    }

    public function cambiarEstado(int $idUsuario, string $estado): bool
    {
        $sql = "
            UPDATE usuarios
            SET estado_usuario = :estado_usuario
            WHERE id_usuario = :id_usuario
        ";

        $this->query($sql, [
            ':estado_usuario' => $estado,
            ':id_usuario' => $idUsuario,
        ]);

        return true;
    }

    // $passwordHash ya debe venir calculado con password_hash() — este método nunca hashea, solo
    // persiste, para que quede clarísimo en el controlador dónde ocurre el hasheo.
    public function cambiarPassword(int $idUsuario, string $passwordHash): bool
    {
        $sql = "
            UPDATE usuarios
            SET password_hash = :password_hash
            WHERE id_usuario = :id_usuario
        ";

        $this->query($sql, [
            ':password_hash' => $passwordHash,
            ':id_usuario' => $idUsuario,
        ]);

        return true;
    }

    public function existeUsuario(string $usuario, ?int $excluirId = null): bool
    {
        if ($excluirId === null) {
            $sql = 'SELECT id_usuario FROM usuarios WHERE usuario = :usuario LIMIT 1';
            $params = [':usuario' => $usuario];
        } else {
            $sql = 'SELECT id_usuario FROM usuarios WHERE usuario = :usuario AND id_usuario <> :id_usuario LIMIT 1';
            $params = [':usuario' => $usuario, ':id_usuario' => $excluirId];
        }

        return $this->fetchOne($sql, $params) !== false;
    }

    public function existeCorreo(string $correo, ?int $excluirId = null): bool
    {
        if ($excluirId === null) {
            $sql = 'SELECT id_usuario FROM usuarios WHERE correo = :correo LIMIT 1';
            $params = [':correo' => $correo];
        } else {
            $sql = 'SELECT id_usuario FROM usuarios WHERE correo = :correo AND id_usuario <> :id_usuario LIMIT 1';
            $params = [':correo' => $correo, ':id_usuario' => $excluirId];
        }

        return $this->fetchOne($sql, $params) !== false;
    }

    // Cuenta Administradores activos cuyo password_hash es un formato que password_verify() puede
    // evaluar (ver hashEsFuncional()) — es decir, Administradores que realmente PUEDEN autenticarse
    // hoy. La cuenta "sistema" (hash no soportado, ver auditoría M11-A) queda excluida del conteo sin
    // que su nombre de usuario esté hardcodeado en ningún lado: se excluye porque su hash no es
    // funcional, no porque se llame "sistema". $idExcluir permite excluir además al usuario objetivo
    // de una operación en curso (para poder sumarlo de vuelta ya evaluado con su estado hipotético).
    //
    // También exige roles.estado_rol='activo': AuthController::login() rechaza el acceso cuando el
    // ROL está inactivo (no solo cuando el usuario lo está), así que un Administrador cuyo rol se
    // hubiera inactivado tampoco puede autenticarse hoy y no debe contar aquí como "funcional".
    public function contarAdministradoresActivosFuncionales(?int $idExcluir = null): int
    {
        $sql = "
            SELECT u.password_hash
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE r.nombre_rol = 'Administrador'
              AND r.estado_rol = 'activo'
              AND u.estado_usuario = 'activo'
        " . ($idExcluir !== null ? ' AND u.id_usuario <> :id_excluir' : '');

        $params = $idExcluir !== null ? [':id_excluir' => $idExcluir] : [];

        $contador = 0;
        foreach ($this->fetchAll($sql, $params) as $fila) {
            if (self::hashEsFuncional($fila['password_hash'])) {
                $contador++;
            }
        }

        return $contador;
    }

    // true si password_verify() puede evaluar este hash (algoritmo reconocido por PHP). Un hash con
    // formato no soportado (ver auditoría M11-A, cuenta "sistema") siempre devuelve false aquí — nunca
    // puede autenticar de todos modos, así que no debe contar como Administrador "con acceso al
    // sistema" para la regla de último Administrador.
    public static function hashEsFuncional(string $hash): bool
    {
        return password_get_info($hash)['algo'] !== null;
    }

    public function actualizarUltimoAcceso(int $idUsuario): bool
    {
        $sql = "
            UPDATE usuarios
            SET ultimo_acceso = NOW()
            WHERE id_usuario = :id_usuario
        ";

        $this->query($sql, [':id_usuario' => $idUsuario]);

        return true;
    }
}