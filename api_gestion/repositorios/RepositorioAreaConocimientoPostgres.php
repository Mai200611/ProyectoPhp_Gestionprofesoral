<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/errores_de_integridad_postgres.php';
require_once __DIR__ . '/../modelos/AreaConocimiento.php';

class RepositorioAreaConocimientoPostgres implements IRepositorioAreaConocimiento
{
    public function obtenerTodos(int $limite): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT id, gran_area, area, disciplina
                FROM area_conocimiento
                ORDER BY id
                LIMIT :limite';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();

        $areas = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $areas[] = $this->filaAObjeto($fila);
        }
        return $areas;
    }

    public function obtenerPorId(string $id): ?AreaConocimiento
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT id, gran_area, area, disciplina FROM area_conocimiento WHERE id = :id';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_STR);
        $sentencia->execute();

        $fila = $sentencia->fetch();
        return ($fila === false) ? null : $this->filaAObjeto($fila);
    }

    public function crear(AreaConocimiento $area): bool
    {
        $pdo = Conexion::obtener();
        $sql = 'INSERT INTO area_conocimiento (id, gran_area, area, disciplina)
                VALUES (:id, :gran_area, :area, :disciplina)';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $area->getId(), PDO::PARAM_STR);
        $sentencia->bindValue(':gran_area', $area->getGranArea(), PDO::PARAM_STR);
        $sentencia->bindValue(':area', $area->getArea(), PDO::PARAM_STR);
        $sentencia->bindValue(':disciplina', $area->getDisciplina(), PDO::PARAM_STR);
        
        try {
            $sentencia->execute();
            return $sentencia->rowCount() === 1;
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el área de conocimiento');
        }
    }

    public function actualizar(string $id, array $datos): int
    {
        $permitidos = ['gran_area', 'area', 'disciplina'];
        $asignaciones = [];
        $valores = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) return 0;

        $sql = 'UPDATE area_conocimiento SET ' . implode(', ', $asignaciones) . ' WHERE id = :id';
        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);
        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_STR);

        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el área de conocimiento');
        }
    }

    public function eliminar(string $id): int
    {
        $pdo = Conexion::obtener();
        $sql = 'DELETE FROM area_conocimiento WHERE id = :id';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_STR);

        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el área de conocimiento');
        }
    }

    private function filaAObjeto(array $fila): AreaConocimiento
    {
        return new AreaConocimiento(
            (string) $fila['id'],
            (string) $fila['gran_area'],
            (string) $fila['area'],
            (string) $fila['disciplina']
        );
    }
}