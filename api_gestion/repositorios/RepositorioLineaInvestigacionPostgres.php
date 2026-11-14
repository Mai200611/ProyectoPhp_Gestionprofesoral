<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/LineaInvestigacion.php';

class RepositorioLineaInvestigacionPostgres implements IRepositorioLineaInvestigacion
{
    public function obtenerTodos(int $limite): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT id, nombre, descripcion
                FROM linea_investigacion
                ORDER BY id
                LIMIT :limite';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll();
        $lineas = [];
        foreach ($filas as $fila) {
            $lineas[] = $this->filaAObjeto($fila);
        }
        return $lineas;
    }

    public function obtenerPorId(int $id): ?LineaInvestigacion
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT id, nombre, descripcion FROM linea_investigacion WHERE id = :id';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        if ($fila === false) {
            return null;
        }
        return $this->filaAObjeto($fila);
    }

    public function crear(LineaInvestigacion $linea): bool
    {
        $pdo = Conexion::obtener();
        $sql = 'INSERT INTO linea_investigacion (nombre, descripcion)
                VALUES (:nombre, :descripcion)';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':nombre', $linea->getNombre(), PDO::PARAM_STR);
        $sentencia->bindValue(':descripcion', $linea->getDescripcion(), PDO::PARAM_STR);
        try {
            $sentencia->execute();
            return $sentencia->rowCount() === 1;
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la línea de investigación');
        }
    }

    public function actualizar(int $id, array $datos): int
    {
        $permitidos = ['nombre', 'descripcion'];
        $asignaciones = [];
        $valores = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) return 0;
        $sql = 'UPDATE linea_investigacion SET ' . implode(', ', $asignaciones) . ' WHERE id = :id';
        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);
        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    public function eliminar(int $id): int
    {
        $pdo = Conexion::obtener();
        $sql = 'DELETE FROM linea_investigacion WHERE id = :id';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la línea de investigación');
        }
    }

    private function filaAObjeto(array $fila): LineaInvestigacion
    {
        return new LineaInvestigacion(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['descripcion']
        );
    }
}