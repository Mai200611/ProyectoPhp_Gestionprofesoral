<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioEstudioAc.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioEstudioAcMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato IRepositorioEstudioAc
 * (tabla puente con PK compuesta `(estudio, area_conocimiento)`).
 *
 * Ojo con los dos nombres:
 *   - La tabla en la base se llama `estudios_ac` (con «s»).
 *   - El recurso público de la API es `/api/estudio_ac` (sin «s»).
 *   - Las columnas reales son `estudio` (int) y `area_conocimiento`
 *     (string). 6_contracts §8 usa etiquetas didácticas (`id_estudio`,
 *     `id_area`); gana 5_data_model.md (instrucción 8).
 *
 * Mismo patrón que RepositorioDocenteDepartamentoMariaDB.
 */
class RepositorioEstudioAcMariaDB implements IRepositorioEstudioAc
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT estudio, area_conocimiento, activo
                FROM estudios_ac
                LIMIT :limite';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return $filas === false ? [] : $filas;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorClave(int $estudio, string $areaConocimiento): ?array
    {
        $sql = 'SELECT estudio, area_conocimiento, activo
                FROM estudios_ac
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
        $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): bool
    {
        $columnas = filtrarColumnas($datos, [
            'estudio', 'area_conocimiento', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO estudios_ac (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo estudio-área');
        }
    }

    public function reemplazar(int $estudio, string $areaConocimiento, array $datos): int
    {
        $columnas = filtrarColumnas($datos, ['activo']);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE estudios_ac SET $asignaciones
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
            $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo estudio-área');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(int $estudio, string $areaConocimiento, array $datos): int
    {
        $columnas = filtrarColumnas($datos, ['activo']);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE estudios_ac SET $asignaciones
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
            $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo estudio-área');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(int $estudio, string $areaConocimiento): int
    {
        $sql = 'DELETE FROM estudios_ac
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
            $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo estudio-área');
        }

        return $sentencia->rowCount();
    }
}