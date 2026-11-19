<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioTerminoClave.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/TerminoClave.php';

class RepositorioTerminoClavePostgres implements IRepositorioTerminoClave
{
    public function obtenerTodos(int $limite): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT termino, termino_ingles
                FROM termino_clave
                ORDER BY termino
                LIMIT :limite';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll();
        $terminos = [];
        foreach ($filas as $fila) {
            $terminos[] = $this->filaAObjeto($fila);
        }
        return $terminos;
    }

    public function obtenerPorTermino(string $termino): ?TerminoClave
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT termino, termino_ingles
                FROM termino_clave
                WHERE termino = :termino';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':termino', $termino, PDO::PARAM_STR);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        if ($fila === false) {
            return null;
        }
        return $this->filaAObjeto($fila);
    }

    public function crear(TerminoClave $termino): bool
    {
        $pdo = Conexion::obtener();
        $sql = 'INSERT INTO termino_clave (termino, termino_ingles)
                VALUES (:termino, :termino_ingles)';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':termino', $termino->getTermino(), PDO::PARAM_STR);
        $sentencia->bindValue(':termino_ingles', $termino->getTerminoIngles(), PDO::PARAM_STR);
        try {
            $sentencia->execute();
            return $sentencia->rowCount() === 1;
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el término clave');
        }
    }

    public function actualizar(string $termino, array $datos): int
    {
        $permitidos = ['termino_ingles'];
        $asignaciones = [];
        $valores = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) return 0;
        $sql = 'UPDATE termino_clave SET ' . implode(', ', $asignaciones) . ' WHERE termino = :termino';
        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);
        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':termino', $termino, PDO::PARAM_STR);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    public function eliminar(string $termino): int
    {
        $pdo = Conexion::obtener();
        $sql = 'DELETE FROM termino_clave WHERE termino = :termino';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':termino', $termino, PDO::PARAM_STR);
        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'el término clave');
        }
    }

    private function filaAObjeto(array $fila): TerminoClave
    {
        return new TerminoClave(
            (string) $fila['termino'],
            (string) $fila['termino_ingles']
        );
    }
}