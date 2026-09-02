<?php

require_once __DIR__ . '/../core/Model.php';

// Colección ACUMULATIVA de documentos de respaldo del ingreso de un bien (tabla `documentos_bien`,
// 0..N filas por bien). Reemplaza a la columna única `ingreso_*.documento_respaldo` (que queda como
// legado): los documentos ya NO se sustituyen al modificar el bien — se agregan y se conservan como
// evidencia documental e histórica.
//
// Append-only por lógica: esta clase solo CREA y CONSULTA. NUNCA actualiza ni borra — no existe
// flujo de edición ni de eliminación de un documento ya registrado (regla del módulo). Las
// inserciones se hacen dentro de la transacción que abren BienesController::crear() /
// BienesController::editar() (la conexión PDO es compartida por todos los modelos), así que un
// rollback también descarta estas filas.
class DocumentoBien extends Model
{
    // Registra un documento nuevo.
    //   $datos: id_bien, tipo_ingreso ('compra'|'donacion'|'traslado'), nombre_original (o null),
    //           ruta_documento (ruta relativa a storage/, nunca el nombre físico suelto),
    //           fecha_registro ('Y-m-d H:i:s'), id_usuario_registra (o null).
    public function registrar(array $datos): int
    {
        $sql = "
            INSERT INTO documentos_bien
                (id_bien, tipo_ingreso, nombre_original, ruta_documento, fecha_registro, id_usuario_registra)
            VALUES
                (:id_bien, :tipo_ingreso, :nombre_original, :ruta_documento, :fecha_registro, :id_usuario_registra)
        ";

        $this->query($sql, [
            ':id_bien' => $datos['id_bien'],
            ':tipo_ingreso' => $datos['tipo_ingreso'],
            ':nombre_original' => $datos['nombre_original'] ?? null,
            ':ruta_documento' => $datos['ruta_documento'],
            ':fecha_registro' => $datos['fecha_registro'],
            ':id_usuario_registra' => $datos['id_usuario_registra'] ?? null,
        ]);

        return (int) $this->lastInsertId();
    }

    // Todos los documentos de un bien, del más antiguo al más reciente (orden de registro).
    public function listarPorBien(int $idBien): array
    {
        $sql = "
            SELECT
                id_documento_bien,
                id_bien,
                tipo_ingreso,
                nombre_original,
                ruta_documento,
                fecha_registro,
                id_usuario_registra
            FROM documentos_bien
            WHERE id_bien = :id_bien
            ORDER BY fecha_registro ASC, id_documento_bien ASC
        ";

        return $this->fetchAll($sql, [':id_bien' => $idBien]);
    }

    // Un documento por su id (lo usa el endpoint controlado "Ver documento": el navegador envía el
    // id del documento, la ruta física se obtiene aquí y se valida contra storage/documentos/).
    public function buscarPorId(int $idDocumentoBien): array|false
    {
        $sql = "
            SELECT
                id_documento_bien,
                id_bien,
                tipo_ingreso,
                nombre_original,
                ruta_documento,
                fecha_registro,
                id_usuario_registra
            FROM documentos_bien
            WHERE id_documento_bien = :id
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id' => $idDocumentoBien]);
    }
}
