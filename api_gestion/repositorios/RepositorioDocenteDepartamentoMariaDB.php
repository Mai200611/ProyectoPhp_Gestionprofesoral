<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioDocenteDepartamento.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioDocenteDepartamentoMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato
 * IRepositorioDocenteDepartamento (tabla puente con PK compuesta
 * `(docente, departamento)`).
 *
 * Este archivo es el PATRÓN de las puentes con PK compuesta:
 *   - `crear()` recibe las dos llaves dentro de $datos y devuelve `bool`
 *     (no hay id autogenerado que devolver).
 *   - `obtenerPorClave()`, `reemplazar()`, `actualizarParcial()` y
 *     `eliminar()` reciben las dos llaves por separado.
 *   - Los errores de integridad (pareja duplicada, FK inexistente) se
 *     traducen con traducirErrorDeIntegridad() a 409.
 *
 * Ojo con los nombres de columna reales: `docente` (string) y `departamento`
 * (int → programa.id). 6_contracts §8 usa etiquetas didácticas
 * (`cedula`, `id_programa`); gana 5_data_model.md (instrucción 8).
 */
class RepositorioDocenteDepartamentoMariaDB implements IRepositorioDocenteDepartamento
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
        $sql = 'SELECT docente, departamento, dedicacion, modalidad,
                       fecha_ingreso, fecha_salida, activo
                FROM docente_departamento
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
    public function obtenerPorClave(string $docente, int $departamento): ?array
    {
        $sql = 'SELECT docente, departamento, dedicacion, modalidad,
                       fecha_ingreso, fecha_salida, activo
                FROM docente_departamento
                WHERE docente = :docente AND departamento = :departamento';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':docente', $docente);
        $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): bool
    {
        $columnas = filtrarColumnas($datos, [
            'docente', 'departamento', 'dedicacion', 'modalidad',
            'fecha_ingreso', 'fecha_salida', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO docente_departamento (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la vinculación docente-departamento');
        }
    }

    public function reemplazar(string $docente, int $departamento, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'dedicacion', 'modalidad', 'fecha_ingreso', 'fecha_salida', 'activo',
        ]);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE docente_departamento SET $asignaciones
                WHERE docente = :docente AND departamento = :departamento";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':docente', $docente);
            $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la vinculación docente-departamento');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(string $docente, int $departamento, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'dedicacion', 'modalidad', 'fecha_ingreso', 'fecha_salida', 'activo',
        ]);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE docente_departamento SET $asignaciones
                WHERE docente = :docente AND departamento = :departamento";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':docente', $docente);
            $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la vinculación docente-departamento');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(string $docente, int $departamento): int
    {
        $sql = 'DELETE FROM docente_departamento
                WHERE docente = :docente AND departamento = :departamento';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':docente', $docente);
            $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'la vinculación docente-departamento');
        }

        return $sentencia->rowCount();
    }
}