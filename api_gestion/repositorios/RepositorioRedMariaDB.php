<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioRed.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/Red.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN CONCRETA del repositorio de `red` contra
 *   MariaDB. La clase que de verdad habla con la base de datos por medio
 *   de PDO y prepared statements.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás repositorios concretos: es la capa más baja,
 *   implementa su interfaz (IRepositorioRed) y traduce entre el mundo
 *   PHP (objetos Red) y el mundo SQL (filas de la tabla).
 *
 * Nota sobre el identificador:
 *   En `red` la clave primaria se llama `idr` y es INTEGER. Por eso los
 *   métodos reciben un int y se llaman obtenerPorIdr, actualizar($idr,...)
 *   y eliminar($idr). El nombre de la columna se respeta en toda la API.
 *
 * Nota sobre el casteo:
 *   `idr` es INTEGER en la BD → se castea a int al construir el objeto.
 *   Los otros tres campos (nombre, url, pais) son VARCHAR → string.
 * ---------------------------------------------------------------------------
 */
class RepositorioRedMariaDB implements IRepositorioRed
{
    // -----------------------------------------------------------------------
    // obtenerTodos
    // Devuelve hasta $limite redes, ordenadas por idr ascendente.
    // -----------------------------------------------------------------------
    public function obtenerTodos(int $limite): array
    {
        $pdo = Conexion::obtener();

        $sql = 'SELECT idr, nombre, url, pais
                FROM red
                ORDER BY idr
                LIMIT :limite';

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

    // -----------------------------------------------------------------------
    // obtenerPorIdr
    // Devuelve la red con el idr indicado, o null si no existe.
    // -----------------------------------------------------------------------
    public function obtenerPorIdr(int $idr): ?Red
    {
        $pdo = Conexion::obtener();

        $sql = 'SELECT idr, nombre, url, pais
                FROM red
                WHERE idr = :idr';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':idr', $idr, PDO::PARAM_INT);
        $sentencia->execute();

        $fila = $sentencia->fetch();

        if ($fila === false) {
            return null;
        }

        return $this->filaAObjeto($fila);
    }

    // -----------------------------------------------------------------------
    // crear
    // Inserta una red nueva. En `red`, el idr lo envía el cliente (es la
    // clave primaria legible), así que viaja en el INSERT.
    //
    // Si el idr ya existe, la BD lanzará una PDOException por clave
    // duplicada; el controlador la traducirá a un 500 con el detalle del
    // motor, según el contrato.
    // -----------------------------------------------------------------------
    public function crear(Red $red): bool
    {
        $pdo = Conexion::obtener();

        $sql = 'INSERT INTO red (idr, nombre, url, pais)
                VALUES (:idr, :nombre, :url, :pais)';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':idr', $red->getIdr(), PDO::PARAM_INT);
        $sentencia->bindValue(':nombre', $red->getNombre(), PDO::PARAM_STR);
        $sentencia->bindValue(':url', $red->getUrl(), PDO::PARAM_STR);
        $sentencia->bindValue(':pais', $red->getPais(), PDO::PARAM_STR);
        $sentencia->execute();

        return $sentencia->rowCount() === 1;
    }

    // -----------------------------------------------------------------------
    // actualizar
    // Modifica los campos que vengan en $datos para la red $idr.
    // Devuelve el número de filas afectadas.
    //
    // El idr no se actualiza aunque venga en $datos: es la clave primaria.
    // Campos editables: nombre, url, pais (todos VARCHAR).
    // -----------------------------------------------------------------------
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

        if ($asignaciones === []) {
            return 0;
        }

        $sql = 'UPDATE red
                SET ' . implode(', ', $asignaciones) . '
                WHERE idr = :idr';

        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);

        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':idr', $idr, PDO::PARAM_INT);

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // eliminar
    // Borra la red indicada. Devuelve el número de filas eliminadas
    // (0 si no existía).
    // -----------------------------------------------------------------------
    public function eliminar(int $idr): int
    {
        $pdo = Conexion::obtener();

        $sql = 'DELETE FROM red WHERE idr = :idr';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':idr', $idr, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // filaAObjeto (método privado auxiliar)
    // Convierte una fila de la BD en un objeto Red.
    // idr se castea a int para que el JSON lo traiga como número.
    // -----------------------------------------------------------------------
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