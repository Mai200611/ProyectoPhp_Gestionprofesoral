<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioDocenteDepartamento.php';
require_once __DIR__ . '/errores_de_integridad_postgres.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

class RepositorioDocenteDepartamentoPostgres implements IRepositorioDocenteDepartamento
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

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
        return $filas === false ? [] : array_map([$this, 'limpiarFila'], $filas);
    }

    public function obtenerPorClave(int $docente, int $departamento): ?array
    {
        $sql = 'SELECT docente, departamento, dedicacion, modalidad,
                       fecha_ingreso, fecha_salida, activo
                FROM docente_departamento
                WHERE docente = :docente AND departamento = :departamento';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':docente', $docente, PDO::PARAM_INT);
        $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->limpiarFila($fila);
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
            traducirErrorPostgres($e, 'la asignación del docente');
        }
    }

    public function reemplazar(int $docente, int $departamento, array $datos): int
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
            $sentencia->bindValue(':docente', $docente, PDO::PARAM_INT);
            $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la asignación del docente');
        }
    }

    public function actualizarParcial(int $docente, int $departamento, array $datos): int
    {
        $columnas = filtrarColumnas($datos, [
            'dedicacion', 'modalidad', 'fecha_ingreso', 'fecha_salida', 'activo',
        ]);
        if ($columnas === []) return 0;
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
            $sentencia->bindValue(':docente', $docente, PDO::PARAM_INT);
            $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la asignación del docente');
        }
    }

    public function eliminar(int $docente, int $departamento): int
    {
        $sql = 'DELETE FROM docente_departamento
                WHERE docente = :docente AND departamento = :departamento';
        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':docente', $docente, PDO::PARAM_INT);
            $sentencia->bindValue(':departamento', $departamento, PDO::PARAM_INT);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la asignación del docente');
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