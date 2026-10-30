<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioBeca.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioBecaMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato IRepositorioBeca.
 *
 * Aunque 6_contracts §8 la etiquete con PK `(id_estudio, tipo_beca)`, la PK
 * real de la tabla es `estudios` SOLA (5_data_model.md §3.16). Gana
 * 5_data_model.md (instrucción 8): la llave es un único `int`.
 *
 * Patrón de ficha con llave que manda el cliente (no autogenerada):
 *   - `crear()` devuelve `bool` (no hay id nuevo que devolver).
 *   - Los métodos por llave reciben el `estudios` como `int`.
 */
class RepositorioBecaMariaDB implements IRepositorioBeca
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
        $sql = 'SELECT estudios, tipo, institucion,
                       fecha_inicio, fecha_fin, activo
                FROM beca
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
        $sql = 'SELECT estudios, tipo, institucion,
                       fecha_inicio, fecha_fin, activo
                FROM beca
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
            'estudios', 'tipo', 'institucion',
            'fecha_inicio', 'fecha_fin', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO beca (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la beca');
        }
    }

    public function reemplazar(int $estudios, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'tipo', 'institucion', 'fecha_inicio', 'fecha_fin', 'activo',
        ]);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE beca SET $asignaciones WHERE estudios = :estudios";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la beca');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(int $estudios, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'tipo', 'institucion', 'fecha_inicio', 'fecha_fin', 'activo',
        ]);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE beca SET $asignaciones WHERE estudios = :estudios";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la beca');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(int $estudios): int
    {
        $sql = 'DELETE FROM beca WHERE estudios = :estudios';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la beca');
        }

        return $sentencia->rowCount();
    }
}