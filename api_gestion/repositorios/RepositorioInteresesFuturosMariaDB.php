<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioInteresesFuturos.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioInteresesFuturosMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato
 * IRepositorioInteresesFuturos (tabla puente con PK compuesta
 * `(docente, termino_clave)`).
 *
 * Mismo patrón que RepositorioDocenteDepartamentoMariaDB.
 * Ojo con los nombres de columna reales: `docente` (string) y
 * `termino_clave` (string). 6_contracts §8 usa la etiqueta `termino`;
 * gana 5_data_model.md (instrucción 8).
 */
class RepositorioInteresesFuturosMariaDB implements IRepositorioInteresesFuturos
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
        $sql = 'SELECT docente, termino_clave, activo
                FROM intereses_futuros
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
    public function obtenerPorClave(string $docente, string $terminoClave): ?array
    {
        $sql = 'SELECT docente, termino_clave, activo
                FROM intereses_futuros
                WHERE docente = :docente AND termino_clave = :termino_clave';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':docente', $docente);
        $sentencia->bindValue(':termino_clave', $terminoClave);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    public function crear(array $datos): bool
    {
        $columnas = filtrarColumnas($datos, [
            'docente', 'termino_clave', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO intereses_futuros (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el interés futuro');
        }
    }

    public function reemplazar(string $docente, string $terminoClave, array $datos): int
    {
        $columnas = filtrarColumnas($datos, ['activo']);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE intereses_futuros SET $asignaciones
                WHERE docente = :docente AND termino_clave = :termino_clave";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':docente', $docente);
            $sentencia->bindValue(':termino_clave', $terminoClave);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el interés futuro');
        }

        return $sentencia->rowCount();
    }

    public function actualizarParcial(string $docente, string $terminoClave, array $datos): int
    {
        $columnas = filtrarColumnas($datos, ['activo']);
        if ($columnas === []) {
            return 0;
        }
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE intereses_futuros SET $asignaciones
                WHERE docente = :docente AND termino_clave = :termino_clave";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':docente', $docente);
            $sentencia->bindValue(':termino_clave', $terminoClave);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el interés futuro');
        }

        return $sentencia->rowCount();
    }

    public function eliminar(string $docente, string $terminoClave): int
    {
        $sql = 'DELETE FROM intereses_futuros
                WHERE docente = :docente AND termino_clave = :termino_clave';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':docente', $docente);
            $sentencia->bindValue(':termino_clave', $terminoClave);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el interés futuro');
        }

        return $sentencia->rowCount();
    }
}