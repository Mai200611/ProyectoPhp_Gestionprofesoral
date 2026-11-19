<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioApoyoProfesoral.php';
require_once __DIR__ . '/errores_de_integridad_postgres.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

class RepositorioApoyoProfesoralPostgres implements IRepositorioApoyoProfesoral
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT estudios, con_apoyo, institucion, tipo, activo
                FROM apoyo_profesoral
                LIMIT :limite';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        
        // Limpiamos booleanos
        return $filas === false ? [] : array_map([$this, 'limpiarFila'], $filas);
    }

    public function obtenerPorId(int $estudios): ?array
    {
        $sql = 'SELECT estudios, con_apoyo, institucion, tipo, activo
                FROM apoyo_profesoral
                WHERE estudios = :estudios';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->limpiarFila($fila);
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
            traducirErrorPostgres($e, 'el apoyo profesoral');
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
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el apoyo profesoral');
        }
    }

    public function actualizarParcial(int $estudios, array $datos): int
    {
        return $this->reemplazar($estudios, $datos);
    }

    public function eliminar(int $estudios): int
    {
        $sql = 'DELETE FROM apoyo_profesoral WHERE estudios = :estudios';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':estudios', $estudios, PDO::PARAM_INT);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el apoyo profesoral');
        }
    }

    private function limpiarFila(array $fila): array
    {
        if (isset($fila['activo'])) {
            $fila['activo'] = (int) (bool) $fila['activo'];
        }
        return $fila;
    }
}