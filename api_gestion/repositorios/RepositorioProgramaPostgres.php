<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioPrograma.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/Programa.php';

class RepositorioProgramaPostgres implements IRepositorioPrograma
{
    public function obtenerTodos(int $limite): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT id, nombre, tipo, nivel, fecha_creacion, fecha_cierre,
                       numero_cohortes, cant_graduados, fecha_actualizacion,
                       ciudad, facultad
                FROM programa
                ORDER BY id
                LIMIT :limite';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll();
        $programas = [];
        foreach ($filas as $fila) {
            $programas[] = $this->filaAObjeto($fila);
        }
        return $programas;
    }

    public function obtenerPorId(int $id): ?Programa
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT id, nombre, tipo, nivel, fecha_creacion, fecha_cierre,
                       numero_cohortes, cant_graduados, fecha_actualizacion,
                       ciudad, facultad
                FROM programa
                WHERE id = :id';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        if ($fila === false) {
            return null;
        }
        return $this->filaAObjeto($fila);
    }

    public function crear(Programa $programa): bool
    {
        $pdo = Conexion::obtener();
        $sql = 'INSERT INTO programa
                    (id, nombre, tipo, nivel, fecha_creacion, fecha_cierre,
                     numero_cohortes, cant_graduados, fecha_actualizacion,
                     ciudad, facultad)
                VALUES
                    (:id, :nombre, :tipo, :nivel, :fecha_creacion, :fecha_cierre,
                     :numero_cohortes, :cant_graduados, :fecha_actualizacion,
                     :ciudad, :facultad)';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $programa->getId(), PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $programa->getNombre(), PDO::PARAM_STR);
        $sentencia->bindValue(':tipo', $programa->getTipo(), PDO::PARAM_STR);
        $sentencia->bindValue(':nivel', $programa->getNivel(), PDO::PARAM_STR);
        $sentencia->bindValue(':fecha_creacion', $programa->getFechaCreacion(), PDO::PARAM_STR);
        $fechaCierre = $programa->getFechaCierre();
        if ($fechaCierre === null) {
            $sentencia->bindValue(':fecha_cierre', null, PDO::PARAM_NULL);
        } else {
            $sentencia->bindValue(':fecha_cierre', $fechaCierre, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':numero_cohortes', $programa->getNumeroCohortes(), PDO::PARAM_STR);
        $sentencia->bindValue(':cant_graduados', $programa->getCantGraduados(), PDO::PARAM_STR);
        $sentencia->bindValue(':fecha_actualizacion', $programa->getFechaActualizacion(), PDO::PARAM_STR);
        $sentencia->bindValue(':ciudad', $programa->getCiudad(), PDO::PARAM_STR);
        $sentencia->bindValue(':facultad', $programa->getFacultad(), PDO::PARAM_INT);
        try {
            $sentencia->execute();
            return $sentencia->rowCount() === 1;
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el programa');
        }
    }

    public function actualizar(int $id, array $datos): int
    {
        $tipos = [
            'nombre' => PDO::PARAM_STR,
            'tipo' => PDO::PARAM_STR,
            'nivel' => PDO::PARAM_STR,
            'fecha_creacion' => PDO::PARAM_STR,
            'fecha_cierre' => PDO::PARAM_STR,
            'numero_cohortes' => PDO::PARAM_STR,
            'cant_graduados' => PDO::PARAM_STR,
            'fecha_actualizacion' => PDO::PARAM_STR,
            'ciudad' => PDO::PARAM_STR,
            'facultad' => PDO::PARAM_INT,
        ];
        $asignaciones = [];
        $valores = [];
        foreach ($tipos as $campo => $tipoPdo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = ['valor' => $datos[$campo], 'tipo' => $tipoPdo];
            }
        }
        if ($asignaciones === []) return 0;
        $sql = 'UPDATE programa SET ' . implode(', ', $asignaciones) . ' WHERE id = :id';
        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);
        foreach ($valores as $campo => $info) {
            $valor = $info['valor'];
            $tipoPdo = $info['tipo'];
            if ($valor === null) {
                $sentencia->bindValue(':' . $campo, null, PDO::PARAM_NULL);
            } elseif ($tipoPdo === PDO::PARAM_INT) {
                $sentencia->bindValue(':' . $campo, (int) $valor, PDO::PARAM_INT);
            } else {
                $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
            }
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el programa');
        }
    }

    public function eliminar(int $id): int
    {
        $pdo = Conexion::obtener();
        $sql = 'DELETE FROM programa WHERE id = :id';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el programa');
        }
    }

    private function filaAObjeto(array $fila): Programa
    {
        return new Programa(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['tipo'],
            (string) $fila['nivel'],
            (string) $fila['fecha_creacion'],
            $fila['fecha_cierre'] === null ? null : (string) $fila['fecha_cierre'],
            (string) $fila['numero_cohortes'],
            (string) $fila['cant_graduados'],
            (string) $fila['fecha_actualizacion'],
            (string) $fila['ciudad'],
            (int) $fila['facultad']
        );
    }
}