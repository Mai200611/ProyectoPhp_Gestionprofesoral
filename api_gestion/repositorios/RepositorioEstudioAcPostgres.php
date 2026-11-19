<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioEstudioAc.php';
require_once __DIR__ . '/errores_de_integridad_postgres.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

class RepositorioEstudioAcPostgres implements IRepositorioEstudioAc
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT estudio, area_conocimiento, activo
                FROM estudios_ac
                LIMIT :limite';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        
        // Limpiamos el booleano de PostgreSQL a 1/0
        return $filas === false ? [] : array_map([$this, 'limpiarFila'], $filas);
    }

    public function obtenerPorClave(int $estudio, string $areaConocimiento): ?array
    {
        $sql = 'SELECT estudio, area_conocimiento, activo
                FROM estudios_ac
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
        $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        
        return $fila === false ? null : $this->limpiarFila($fila);
    }

    public function crear(array $datos): bool
    {
        $columnas = filtrarColumnas($datos, [
            'estudio', 'area_conocimiento', 'activo',
        ]);

        $nombres = array_keys($columnas);
        $marcadores = array_map(static fn(string $c): string => ':' . $c, $nombres);
        $sql = 'INSERT INTO estudios_ac (' . implode(', ', $nombres) . ')
                VALUES (' . implode(', ', $marcadores) . ')';

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            return $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el vínculo estudio-área');
        }
    }

    public function reemplazar(int $estudio, string $areaConocimiento, array $datos): int
    {
        $columnas = filtrarColumnas($datos, ['activo']);
        $asignaciones = implode(', ', array_map(
            static fn(string $c): string => "$c = :$c",
            array_keys($columnas)
        ));
        $sql = "UPDATE estudios_ac SET $asignaciones
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento";

        try {
            $sentencia = $this->conexion->prepare($sql);
            foreach ($columnas as $columna => $valor) {
                $sentencia->bindValue(':' . $columna, $valor);
            }
            $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
            $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el vínculo estudio-área');
        }
    }

    public function actualizarParcial(int $estudio, string $areaConocimiento, array $datos): int
    {
        return $this->reemplazar($estudio, $areaConocimiento, $datos);
    }

    public function eliminar(int $estudio, string $areaConocimiento): int
    {
        $sql = 'DELETE FROM estudios_ac
                WHERE estudio = :estudio AND area_conocimiento = :area_conocimiento';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':estudio', $estudio, PDO::PARAM_INT);
            $sentencia->bindValue(':area_conocimiento', $areaConocimiento);
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el vínculo estudio-área');
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