<?php

require_once __DIR__ . '/../core/Model.php';

// Snapshot congelado de los datos de INGRESO de un bien (tabla `ingreso_bien_original`, 1 fila por
// bien). Fuente del evento "Ingreso del bien" del Historial integral para las columnas MUTABLES:
// así una edición posterior desde "Bienes -> Modificar" no reescribe retroactivamente ese evento.
//
// Append-once por lógica: esta clase solo crea y consulta. NUNCA actualiza ni borra — el flujo de
// edición de bienes no debe tocar esta tabla. Los cambios posteriores viven en
// historial_modificaciones_bien (evento "Modificación de información"), no aquí.
class IngresoBienOriginal extends Model
{
    // Crea el snapshot del bien recién registrado. Debe llamarse DENTRO de la transacción de
    // BienesController::crear() (conexión PDO compartida): si el registro se revierte, el snapshot
    // también. La UNIQUE(id_bien) garantiza que no se dupliquen snapshots.
    //   $datos: id_bien, tipo_ingreso ('compra'|'donacion'|'traslado'), fecha_ingreso_original,
    //           y las columnas *_original propias del tipo (el resto quedan NULL).
    public function crearSnapshot(array $datos): int
    {
        $sql = "
            INSERT INTO ingreso_bien_original (
                id_bien,
                tipo_ingreso,
                fecha_ingreso_original,
                proveedor_original,
                entidad_donante_original,
                procedencia_donacion_original,
                unidad_ejecutora_origen_original,
                procedencia_traslado_original
            ) VALUES (
                :id_bien,
                :tipo_ingreso,
                :fecha_ingreso_original,
                :proveedor_original,
                :entidad_donante_original,
                :procedencia_donacion_original,
                :unidad_ejecutora_origen_original,
                :procedencia_traslado_original
            )
        ";

        $this->query($sql, [
            ':id_bien' => $datos['id_bien'],
            ':tipo_ingreso' => $datos['tipo_ingreso'],
            ':fecha_ingreso_original' => $datos['fecha_ingreso_original'] ?? null,
            ':proveedor_original' => $datos['proveedor_original'] ?? null,
            ':entidad_donante_original' => $datos['entidad_donante_original'] ?? null,
            ':procedencia_donacion_original' => $datos['procedencia_donacion_original'] ?? null,
            ':unidad_ejecutora_origen_original' => $datos['unidad_ejecutora_origen_original'] ?? null,
            ':procedencia_traslado_original' => $datos['procedencia_traslado_original'] ?? null,
        ]);

        return (int) $this->lastInsertId();
    }

    public function buscarPorBien(int $idBien): array|false
    {
        $sql = "
            SELECT
                id_snapshot_ingreso,
                id_bien,
                tipo_ingreso,
                fecha_ingreso_original,
                proveedor_original,
                entidad_donante_original,
                procedencia_donacion_original,
                unidad_ejecutora_origen_original,
                procedencia_traslado_original,
                created_at
            FROM ingreso_bien_original
            WHERE id_bien = :id_bien
            LIMIT 1
        ";

        return $this->fetchOne($sql, [':id_bien' => $idBien]);
    }

    public function existePorBien(int $idBien): bool
    {
        $sql = "SELECT 1 FROM ingreso_bien_original WHERE id_bien = :id_bien LIMIT 1";

        return $this->fetchOne($sql, [':id_bien' => $idBien]) !== false;
    }
}
