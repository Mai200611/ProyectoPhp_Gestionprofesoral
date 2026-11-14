<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioExperiencia.php';
require_once __DIR__ . '/errores_de_integridad_postgres.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

class RepositorioExperienciaPostgres implements IRepositorioExperiencia
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

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
        return $filas === false ? [] : array_map([$this, 'limpiarFila'], $filas);
    }

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
        return $fila === false ? null : $this->limpiarFila($fila);
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
                VALUES (' . implode(', ', $marcadores) . ') RETURNING id';
        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->execute();
            return (int) $sentencia->fetchColumn();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la experiencia');
        }
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
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la experiencia');
        }
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'nombre_cargo', 'institucion', 'tipo',
            'fecha_inicio', 'fecha_fin', 'activo', 'docente',
        ]);
        if ($columnas === []) return 0;
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
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la experiencia');
        }
    }

    public function eliminar(int $id): int
    {
        $sql = 'DELETE FROM experiecia WHERE id = :id';
        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la experiencia');
        }
    }

    private function limpiarFila(array $fila): array
    {
        if (isset($fila['activo'])) {
            $fila['activo'] = (int) (bool) $fila['activo'];
        }
        foreach ($fila as $k => $v) {
            if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+$/', $v)) {
                $fila[$k] = substr($v, 0, 19);
            }
        }
        return $fila;
    }
}