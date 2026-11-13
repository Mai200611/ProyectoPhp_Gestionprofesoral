<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioEstudiosRealizados.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioEstudiosRealizadosMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato
 * IRepositorioEstudiosRealizados.
 *
 * Este archivo es el PATRÓN de las fichas con `id` autogenerado:
 *   - `crear()` usa lastInsertId() y devuelve el id.
 *   - `reemplazar()` y `actualizarParcial()` cuentan filas afectadas.
 *   - `eliminar()` cuenta filas eliminadas.
 *   - TODOS los métodos envuelven sus escrituras en try/catch de PDOException
 *     y traducen el veredicto con traducirErrorDeIntegridad() (Fase 1).
 *
 * El SQL siempre va en prepared statements (RNF3). Los valores del body
 * pasan por filtrarColumnas() para descartar lo que esta API no acepta
 * (por ejemplo, un `id` en el POST; 3_plan §4.3).
 */
class RepositorioEstudiosRealizadosMariaDB implements IRepositorioEstudiosRealizados
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
        $sql = 'SELECT id, titulo, universidad, fecha, tipo, ciudad,
                       ins_acreditada, metodologia, perfil_egresado, pais,
                       activo, docente
                FROM estudios_realizados
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
        $sql = 'SELECT id, titulo, universidad, fecha, tipo, ciudad,
                       ins_acreditada, metodologia, perfil_egresado, pais,
                       activo, docente
                FROM estudios_realizados
                WHERE id = :id';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): int
    {
        // El `id` NO se acepta: lo genera la base. La lista blanca lo deja fuera.
        $columnas = filtrarColumnas($datos, [
            'titulo', 'universidad', 'fecha', 'tipo', 'ciudad',
            'ins_acreditada', 'metodologia', 'perfil_egresado', 'pais',
            'activo', 'docente',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO estudios_realizados (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el estudio');
        }

        return (int) $this->conexion->lastInsertId();
    }

    public function reemplazar(int $id, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'titulo', 'universidad', 'fecha', 'tipo', 'ciudad',
            'ins_acreditada', 'metodologia', 'perfil_egresado', 'pais',
            'activo', 'docente',
        ]);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE estudios_realizados SET $asignaciones WHERE id = :id";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el estudio');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'titulo', 'universidad', 'fecha', 'tipo', 'ciudad',
            'ins_acreditada', 'metodologia', 'perfil_egresado', 'pais',
            'activo', 'docente',
        ]);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE estudios_realizados SET $asignaciones WHERE id = :id";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el estudio');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(int $id): int
    {
        $sql = 'DELETE FROM estudios_realizados WHERE id = :id';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el estudio');
        }

        return $sentencia->rowCount();
    }
}