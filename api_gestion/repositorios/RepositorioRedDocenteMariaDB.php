<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioRedDocente.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioRedDocenteMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato IRepositorioRedDocente
 * (tabla puente con PK compuesta `(red, docente)`).
 *
 * Mismo patrón que RepositorioDocenteDepartamentoMariaDB.
 * Ojo con los nombres de columna reales: `red` (int) y `docente` (string).
 * 6_contracts §8 usa etiquetas didácticas (`idr`, `cedula`); gana
 * 5_data_model.md (instrucción 8).
 */
class RepositorioRedDocenteMariaDB implements IRepositorioRedDocente
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
        $sql = 'SELECT red, docente, fecha_inicio, fecha_fin,
                       act_destacadas, activo
                FROM red_docente
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
    public function obtenerPorClave(int $red, string $docente): ?array
    {
        $sql = 'SELECT red, docente, fecha_inicio, fecha_fin,
                       act_destacadas, activo
                FROM red_docente
                WHERE red = :red AND docente = :docente';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':red', $red, PDO::PARAM_INT);
        $sentencia->bindValue(':docente', $docente);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): bool
    {
        $columnas = filtrarColumnas($datos, [
            'red', 'docente', 'fecha_inicio', 'fecha_fin',
            'act_destacadas', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO red_docente (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo red-docente');
        }
    }

    public function reemplazar(int $red, string $docente, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'fecha_inicio', 'fecha_fin', 'act_destacadas', 'activo',
        ]);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE red_docente SET $asignaciones
                WHERE red = :red AND docente = :docente";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':red', $red, PDO::PARAM_INT);
            $sentencia->bindValue(':docente', $docente);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo red-docente');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(int $red, string $docente, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'fecha_inicio', 'fecha_fin', 'act_destacadas', 'activo',
        ]);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE red_docente SET $asignaciones
                WHERE red = :red AND docente = :docente";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':red', $red, PDO::PARAM_INT);
            $sentencia->bindValue(':docente', $docente);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo red-docente');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(int $red, string $docente): int
    {
        $sql = 'DELETE FROM red_docente
                WHERE red = :red AND docente = :docente';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':red', $red, PDO::PARAM_INT);
            $sentencia->bindValue(':docente', $docente);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el vínculo red-docente');
        }

        return $sentencia->rowCount();
    }
}