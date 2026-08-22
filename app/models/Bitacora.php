<?php

require_once __DIR__ . '/../core/Model.php';

class Bitacora extends Model
{
    protected string $table = 'bitacora';

    public function registrar(
        ?int $idUsuario,
        string $accion,
        string $modulo,
        string $resultado,
        ?string $descripcion,
        ?string $tablaAfectada = null,
        ?int $idRegistroAfectado = null,
        ?string $ipOrigen = null,
        ?string $usuarioIntentado = null
    ): bool {
        $sql = "
            INSERT INTO bitacora (
                id_usuario,
                usuario_intentado,
                accion,
                modulo,
                resultado,
                descripcion,
                tabla_afectada,
                id_registro_afectado,
                ip_origen
            ) VALUES (
                :id_usuario,
                :usuario_intentado,
                :accion,
                :modulo,
                :resultado,
                :descripcion,
                :tabla_afectada,
                :id_registro_afectado,
                :ip_origen
            )
        ";

        $this->query($sql, [
            'id_usuario' => $idUsuario,
            'usuario_intentado' => $usuarioIntentado,
            'accion' => $accion,
            'modulo' => $modulo,
            'resultado' => $resultado,
            'descripcion' => $descripcion,
            'tabla_afectada' => $tablaAfectada,
            'id_registro_afectado' => $idRegistroAfectado,
            'ip_origen' => $ipOrigen,
        ]);

        return true;
    }

    // =========================================================================================
    // Módulo Bitácora — consulta (a partir de aquí, todo nuevo). registrar() de arriba NO se
    // modificó: sigue siendo el único punto de escritura, ya usado por múltiples módulos.
    // =========================================================================================

    // WHERE/params compartidos por getAll() y contar() para que ambas consultas midan exactamente
    // el mismo conjunto de filas — evita que el total mostrado no coincida con lo realmente listado.
    // $filtros admite: fecha_desde, fecha_hasta ('Y-m-d'), buscar, modulo, resultado ('' = sin filtro).
    private function construirWhere(array $filtros): array
    {
        $condiciones = [];
        $params = [];

        $fechaDesde = trim((string) ($filtros['fecha_desde'] ?? ''));
        if ($fechaDesde !== '') {
            $condiciones[] = 'b.fecha_hora >= :fecha_desde';
            $params[':fecha_desde'] = $fechaDesde;
        }

        // Límite EXCLUSIVO del día siguiente: incluye todo el día "hasta" sin dejar fuera los
        // registros posteriores a las 00:00:00 de ese día (fecha_hora es DATETIME, no DATE).
        $fechaHasta = trim((string) ($filtros['fecha_hasta'] ?? ''));
        if ($fechaHasta !== '') {
            $condiciones[] = 'b.fecha_hora < DATE_ADD(:fecha_hasta, INTERVAL 1 DAY)';
            $params[':fecha_hasta'] = $fechaHasta;
        }

        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $condiciones[] = '(
                u.nombre_completo LIKE :buscar_nombre
                OR u.usuario LIKE :buscar_usuario
                OR b.usuario_intentado LIKE :buscar_intentado
                OR b.accion LIKE :buscar_accion
                OR b.descripcion LIKE :buscar_descripcion
            )';
            $like = '%' . $buscar . '%';
            $params[':buscar_nombre'] = $like;
            $params[':buscar_usuario'] = $like;
            $params[':buscar_intentado'] = $like;
            $params[':buscar_accion'] = $like;
            $params[':buscar_descripcion'] = $like;
        }

        $modulo = trim((string) ($filtros['modulo'] ?? ''));
        if ($modulo !== '') {
            $condiciones[] = 'b.modulo = :modulo';
            $params[':modulo'] = $modulo;
        }

        $resultado = (string) ($filtros['resultado'] ?? '');
        if (in_array($resultado, ['exitoso', 'fallido'], true)) {
            $condiciones[] = 'b.resultado = :resultado';
            $params[':resultado'] = $resultado;
        }

        $where = $condiciones !== [] ? ('WHERE ' . implode(' AND ', $condiciones)) : '';

        return [$where, $params];
    }

    // LEFT JOIN usuarios (nunca INNER): preserva filas cuyo usuario ya está inactivo, y filas con
    // id_usuario NULL (intentos de login con usuario_intentado). $pagina es 1-based.
    public function getAll(array $filtros, int $pagina, int $porPagina): array
    {
        [$where, $params] = $this->construirWhere($filtros);

        // LIMIT/OFFSET van como enteros ya controlados directamente en el SQL (no como parámetro
        // PDO): con PDO::ATTR_EMULATE_PREPARES=false, algunos drivers no aceptan bien placeholders
        // ahí. Son seguros porque nunca contienen texto libre — max(1, ...) los fuerza a enteros
        // positivos antes de interpolarlos; ningún otro valor del usuario se concatena en el SQL.
        $porPagina = max(1, $porPagina);
        $pagina = max(1, $pagina);
        $limite = $porPagina;
        $offset = ($pagina - 1) * $porPagina;

        $sql = "
            SELECT
                b.id_bitacora,
                b.id_usuario,
                b.usuario_intentado,
                b.accion,
                b.modulo,
                b.resultado,
                b.descripcion,
                b.fecha_hora,
                u.nombre_completo,
                u.usuario
            FROM bitacora b
            LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
            {$where}
            ORDER BY b.fecha_hora DESC, b.id_bitacora DESC
            LIMIT {$limite} OFFSET {$offset}
        ";

        return $this->fetchAll($sql, $params);
    }

    public function contar(array $filtros): int
    {
        [$where, $params] = $this->construirWhere($filtros);

        $sql = "
            SELECT COUNT(*) AS total
            FROM bitacora b
            LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
            {$where}
        ";

        $fila = $this->fetchOne($sql, $params);

        return $fila !== false ? (int) $fila['total'] : 0;
    }

    public function findById(int $idBitacora): array|false
    {
        $sql = "
            SELECT
                b.id_bitacora,
                b.id_usuario,
                b.usuario_intentado,
                b.accion,
                b.modulo,
                b.resultado,
                b.descripcion,
                b.fecha_hora,
                b.tabla_afectada,
                b.id_registro_afectado,
                b.ip_origen,
                u.nombre_completo,
                u.usuario
            FROM bitacora b
            LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
            WHERE b.id_bitacora = :id_bitacora
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_bitacora' => $idBitacora]);
    }

    public function getModulosDistinct(): array
    {
        $sql = 'SELECT DISTINCT modulo FROM bitacora ORDER BY modulo ASC';

        return array_column($this->fetchAll($sql), 'modulo');
    }
}
