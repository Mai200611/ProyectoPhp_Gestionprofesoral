<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioRed.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/Red.php';

class RepositorioRedPostgres implements IRepositorioRed
{
    public function obtenerTodos(int $limite): array
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT idr, nombre, url, pais FROM red ORDER BY idr LIMIT :limite';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll();
        $redes = [];
        foreach ($filas as $fila) {
            $redes[] = $this->filaAObjeto($fila);
        }
        return $redes;
    }

    public function obtenerPorIdr(int $idr): ?Red
    {
        $pdo = Conexion::obtener();
        $sql = 'SELECT idr, nombre, url, pais FROM red WHERE idr = :idr';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':idr', $idr, PDO::PARAM_INT);
        $sentencia->execute();
        $fila = $sentencia->fetch();
        if ($fila === false) {
            return null;
        }
        return $this->filaAObjeto($fila);
    }

    public function crear(Red $red): bool
    {
        $pdo = Conexion::obtener();
        $sql = 'INSERT INTO red (idr, nombre, url, pais) VALUES (:idr, :nombre, :url, :pais)';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':idr', $red->getIdr(), PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $red->getNombre(), PDO::PARAM_STR);
        $sentencia->bindValue(':url', $red->getUrl(), PDO::PARAM_STR);
        $sentencia->bindValue(':pais', $red->getPais(), PDO::PARAM_STR);
        try {
            $sentencia->execute();
            return $sentencia->rowCount() === 1;
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la red');
        }
    }

    public function actualizar(int $idr, array $datos): int
    {
        $permitidos = ['nombre', 'url', 'pais'];
        $asignaciones = [];
        $valores = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = $datos[$campo];
            }
        }
        if ($asignaciones === []) return 0;
        $sql = 'UPDATE red SET ' . implode(', ', $asignaciones) . ' WHERE idr = :idr';
        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);
        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':idr', $idr, PDO::PARAM_INT);
        $sentencia->execute();
        return $sentencia->rowCount();
    }

    public function eliminar(int $idr): int
    {
        $pdo = Conexion::obtener();
        $sql = 'DELETE FROM red WHERE idr = :idr';
        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':idr', $idr, PDO::PARAM_INT);
        try {
            $sentencia->execute();
            return $sentencia->rowCount();
        } catch (PDOException $e) {
            traducirErrorPostgres($e, 'la red');
        }
    }

    private function filaAObjeto(array $fila): Red
    {
        return new Red(
            (int) $fila['idr'],
            (string) $fila['nombre'],
            (string) $fila['url'],
            (string) $fila['pais']
        );
    }
}