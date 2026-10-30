<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioApoyoProfesoral.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioApoyoProfesoralMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato
 * IRepositorioApoyoProfesoral.
 *
 * Aunque 6_contracts §8 la etiquete con PK `(id_estudio, tipo_apoyo)`, la PK
 * real de la tabla es `estudios` SOLA (5_data_model.md §3.15). Gana
 * 5_data_model.md (instrucción 8): la llave es un único `int`.
 *
 * Patrón de ficha con llave que manda el cliente (no autogenerada):
 *   - `crear()` devuelve `bool` (no hay id nuevo que devolver).
 *   - Los métodos por llave reciben el `estudios` como `int`.
 */
class RepositorioApoyoProfesoralMariaDB implements IRepositorioApoyoProfesoral
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
        $sql = 'SELECT estudios, con_apoyo, institucion, tipo, activo
                FROM apoyo_profesoral
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
    public function obtenerPorId(int $estudios): ?array
    {
        $sql = 'SELECT estudios, con_apoyo, institucion, tipo, activo
                FROM apoyo_profesoral
                WHERE estudios = :estudios';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): bool
    {
        $columnas = filtrarColumnas($datos, [
            'estudios', 'con_apoyo', 'institucion', 'tipo', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO apoyo_profesoral (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el apoyo profesoral');
        }
    }

    public function reemplazar(int $estudios, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'con_apoyo', 'institucion', 'tipo', 'activo',
        ]);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE apoyo_profesoral SET $asignaciones WHERE estudios = :estudios";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el apoyo profesoral');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(int $estudios, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'con_apoyo', 'institucion', 'tipo', 'activo',
        ]);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE apoyo_profesoral SET $asignaciones WHERE estudios = :estudios";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el apoyo profesoral');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(int $estudios): int
    {
        $sql = 'DELETE FROM apoyo_profesoral WHERE estudios = :estudios';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el apoyo profesoral');
        }

        return $sentencia->rowCount();
    }
}