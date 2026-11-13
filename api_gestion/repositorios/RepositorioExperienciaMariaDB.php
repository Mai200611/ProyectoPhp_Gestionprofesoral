<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioExperiencia.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioExperienciaMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato IRepositorioExperiencia.
 *
 * ERRATA HISTÓRICA: la tabla en la base se llama `experiecia` (sin la «n»),
 * pero la clase, la interfaz y este repositorio se llaman `Experiencia`.
 * El SQL apunta a la tabla con su nombre real; el código usa el nombre
 * correcto (3_plan §6).
 *
 * Mismo patrón que RepositorioEstudiosRealizadosMariaDB: ficha hija con
 * `id` autogenerado por la base.
 */
class RepositorioExperienciaMariaDB implements IRepositorioExperiencia
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
        $sql = 'SELECT id, nombre_cargo, institucion, tipo,
                       fecha_inicio, fecha_fin, activo, docente
                FROM experiecia
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
    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT id, nombre_cargo, institucion, tipo,
                       fecha_inicio, fecha_fin, activo, docente
                FROM experiecia
                WHERE id = :id';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'nombre_cargo', 'institucion', 'tipo',
            'fecha_inicio', 'fecha_fin', 'activo', 'docente',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO experiecia (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la experiencia');
        }

        return (int) $this->conexion->lastInsertId();
    }

    public function reemplazar(int $id, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'nombre_cargo', 'institucion', 'tipo',
            'fecha_inicio', 'fecha_fin', 'activo', 'docente',
        ]);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE experiecia SET $asignaciones WHERE id = :id";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la experiencia');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'nombre_cargo', 'institucion', 'tipo',
            'fecha_inicio', 'fecha_fin', 'activo', 'docente',
        ]);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE experiecia SET $asignaciones WHERE id = :id";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la experiencia');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(int $id): int
    {
        $sql = 'DELETE FROM experiecia WHERE id = :id';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la experiencia');
        }

        return $sentencia->rowCount();
    }
}