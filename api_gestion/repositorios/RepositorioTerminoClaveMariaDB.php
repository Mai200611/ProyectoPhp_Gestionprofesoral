<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioTerminoClave.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/TerminoClave.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN CONCRETA del repositorio de `termino_clave`
 *   contra MariaDB. La clase que de verdad habla con la base de datos
 *   por medio de PDO y prepared statements.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás repositorios concretos: es la capa más baja,
 *   implementa su interfaz (IRepositorioTerminoClave) y traduce entre el
 *   mundo PHP (objetos TerminoClave) y el mundo SQL (filas de la tabla).
 *
 * Nota sobre el identificador:
 *   En `termino_clave` la clave primaria es `termino` (VARCHAR) — no hay
 *   un `id` separado. Por eso los métodos se llaman obtenerPorTermino,
 *   actualizar($termino, ...) y eliminar($termino), y todos reciben un
 *   string como identificador.
 *
 * Nota sobre "no encontrado":
 *   Igual que en los otros repositorios, obtenerPorTermino devuelve null
 *   cuando no hay fila. La traducción a "no encontrado" (con su 404 en
 *   HTTP) es trabajo del servicio y del controlador, no de aquí.
 * ---------------------------------------------------------------------------
 */
class RepositorioTerminoClaveMariaDB implements IRepositorioTerminoClave
{
    // -----------------------------------------------------------------------
    // obtenerTodos
    // Devuelve hasta $limite términos, ordenados alfabéticamente por
    // `termino`. El ORDER BY usa `termino` (no `id`) porque ese es el
    // campo que existe: no hay id separado en esta tabla.
    //
    // :limite va como PARAM_INT. Es la misma advertencia que en el
    // repositorio anterior: MariaDB no acepta un LIMIT pasado como string.
    // -----------------------------------------------------------------------
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

    // -----------------------------------------------------------------------
    // obtenerPorTermino
    // Devuelve el término cuyo campo `termino` coincide, o null si no
    // existe.
    // -----------------------------------------------------------------------
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

    // -----------------------------------------------------------------------
    // crear
    // Inserta un término nuevo. Devuelve true si la inserción fue exitosa.
    //
    // El término viene incluido en el objeto porque es la clave primaria
    // legible (VARCHAR) y el cliente la envía en el POST. Si ya existe,
    // la BD lanza una PDOException por clave duplicada; esa excepción la
    // atrapará el controlador para responder 500 con el detalle del motor,
    // tal como pide el contrato.
    // -----------------------------------------------------------------------
    public function crear(TerminoClave $termino): bool
    {
        $pdo = Conexion::obtener();

        $sql = 'INSERT INTO termino_clave (termino, termino_ingles)
                VALUES (:termino, :termino_ingles)';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':termino', $termino->getTermino(), PDO::PARAM_STR);
        $sentencia->bindValue(':termino_ingles', $termino->getTerminoIngles(), PDO::PARAM_STR);
        $sentencia->execute();

        return $sentencia->rowCount() === 1;
    }

    // -----------------------------------------------------------------------
    // actualizar
    // Modifica los campos que vengan en $datos para el término $termino.
    // Devuelve el número de filas afectadas.
    //
    // En la práctica el único campo editable es `termino_ingles` (el
    // `termino` es la clave primaria y no se toca). El bucle de abajo es
    // genérico por si en el futuro se agregaran más columnas editables,
    // pero la lista $permitidos solo admite ese campo.
    // -----------------------------------------------------------------------
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

        if ($asignaciones === []) {
            return 0;
        }

        $sql = 'UPDATE termino_clave
                SET ' . implode(', ', $asignaciones) . '
                WHERE termino = :termino';

        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);

        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':termino', $termino, PDO::PARAM_STR);

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // eliminar
    // Borra el término indicado. Devuelve el número de filas eliminadas
    // (0 si no existía).
    // -----------------------------------------------------------------------
    public function eliminar(string $termino): int
    {
        $pdo = Conexion::obtener();

        $sql = 'DELETE FROM termino_clave WHERE termino = :termino';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':termino', $termino, PDO::PARAM_STR);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // filaAObjeto (método privado auxiliar)
    // Convierte una fila de la BD en un objeto TerminoClave.
    // -----------------------------------------------------------------------
    private function filaAObjeto(array $fila): TerminoClave
    {
        return new TerminoClave(
            (string) $fila['termino'],
            (string) $fila['termino_ingles']
        );
    }
}