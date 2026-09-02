<?php

require_once __DIR__ . '/../core/Model.php';

// Escritura y consulta del historial estructurado de modificaciones de un bien (tabla
// `historial_modificaciones_bien`). Complementa —no sustituye— a:
//   - HistorialBien  : solo lectura, reconstruye la línea de tiempo funcional del bien.
//   - historial_sicoin: fuente única del código SICOIN (NO se duplica aquí).
//   - bitacora       : quién hizo qué acción administrativa.
//
// Cada guardado real de "Bienes -> Modificar" genera UN `grupo_cambio` (token hex de 32 caracteres)
// y una fila por cada campo que efectivamente cambió. Append-only: esta clase no expone update ni
// delete. La inserción se hace dentro de la transacción que ya abrió BienesController::editar()
// (la conexión PDO es compartida por todos los modelos), así que un rollback también descarta estas
// filas — nunca queda historial de un cambio que no llegó a confirmarse.
class HistorialModificacionBien extends Model
{
    // Inserta todos los detalles de un mismo guardado bajo el mismo grupo_cambio.
    //   $detalles: lista de arrays con claves 'seccion', 'campo', 'valor_anterior', 'valor_nuevo'
    //              (valor_anterior / valor_nuevo pueden ser null = "Sin registro").
    // No hace nada si $detalles viene vacío (guardado sin cambios reales).
    public function registrarGrupo(
        int $idBien,
        string $grupoCambio,
        int $idUsuario,
        string $fechaHora,
        array $detalles
    ): void {
        if ($detalles === []) {
            return;
        }

        $sql = "
            INSERT INTO historial_modificaciones_bien
                (id_bien, grupo_cambio, seccion, campo, valor_anterior, valor_nuevo, id_usuario, fecha_hora)
            VALUES
                (:id_bien, :grupo_cambio, :seccion, :campo, :valor_anterior, :valor_nuevo, :id_usuario, :fecha_hora)
        ";

        foreach ($detalles as $detalle) {
            $this->query($sql, [
                ':id_bien' => $idBien,
                ':grupo_cambio' => $grupoCambio,
                ':seccion' => (string) $detalle['seccion'],
                ':campo' => (string) $detalle['campo'],
                ':valor_anterior' => $detalle['valor_anterior'] ?? null,
                ':valor_nuevo' => $detalle['valor_nuevo'] ?? null,
                ':id_usuario' => $idUsuario,
                ':fecha_hora' => $fechaHora,
            ]);
        }
    }

    // Detalles de un bien agrupados por grupo_cambio, más reciente primero. Cada grupo:
    //   ['grupo_cambio', 'fecha_hora', 'id_usuario', 'usuario_nombre', 'detalles' => [...]]
    // Pensado para una futura vista dedicada; el Historial integral usa su propia consulta en
    // HistorialBien::eventosModificacion() (mismo patrón self-contained que el resto de ese modelo).
    public function getGruposPorBien(int $idBien): array
    {
        $sql = "
            SELECT
                h.grupo_cambio,
                h.seccion,
                h.campo,
                h.valor_anterior,
                h.valor_nuevo,
                h.id_usuario,
                h.fecha_hora,
                u.nombre_completo AS usuario_nombre
            FROM historial_modificaciones_bien h
            LEFT JOIN usuarios u ON u.id_usuario = h.id_usuario
            WHERE h.id_bien = :id_bien
            ORDER BY h.fecha_hora DESC, h.grupo_cambio ASC, h.id_historial_modificacion ASC
        ";

        $grupos = [];
        foreach ($this->fetchAll($sql, [':id_bien' => $idBien]) as $fila) {
            $clave = (string) $fila['grupo_cambio'];

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'grupo_cambio' => $fila['grupo_cambio'],
                    'fecha_hora' => $fila['fecha_hora'],
                    'id_usuario' => $fila['id_usuario'] !== null ? (int) $fila['id_usuario'] : null,
                    'usuario_nombre' => $fila['usuario_nombre'],
                    'detalles' => [],
                ];
            }

            $grupos[$clave]['detalles'][] = [
                'seccion' => $fila['seccion'],
                'campo' => $fila['campo'],
                'valor_anterior' => $fila['valor_anterior'],
                'valor_nuevo' => $fila['valor_nuevo'],
            ];
        }

        return array_values($grupos);
    }
}
