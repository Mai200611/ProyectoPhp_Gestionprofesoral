<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/LineaInvestigacion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN CONCRETA del repositorio de
 *   `linea_investigacion` contra MariaDB. La clase que de verdad habla
 *   con la base de datos por medio de PDO y prepared statements.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás repositorios concretos: es la capa más baja,
 *   implementa su interfaz (IRepositorioLineaInvestigacion) y traduce
 *   entre el mundo PHP (objetos LineaInvestigacion) y el mundo SQL.
 *
 * Nota sobre el identificador:
 *   En `linea_investigacion` la clave primaria se llama `id` y es un
 *   INTEGER autoincremental. Por eso los métodos reciben un int, y en
 *   `crear` NO se incluye el id: lo genera la BD.
 *
 * Nota sobre el casteo:
 *   El driver mysql entrega los enteros como string ("12" en lugar de
 *   12). Aquí sí importa: al construir el objeto LineaInvestigacion
 *   casteamos `id` a int explícitamente para que el JSON de la API lo
 *   muestre como número.
 * ---------------------------------------------------------------------------
 */
class RepositorioLineaInvestigacionMariaDB implements IRepositorioLineaInvestigacion
{
    // -----------------------------------------------------------------------
    // obtenerTodos
    // Devuelve hasta $limite líneas, ordenadas por id ascendente.
    // -----------------------------------------------------------------------
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

    // -----------------------------------------------------------------------
    // obtenerPorId
    // Devuelve la línea con el id indicado, o null si no existe.
    // -----------------------------------------------------------------------
    public function obtenerPorId(int $id): ?LineaInvestigacion
    {
        $pdo = Conexion::obtener();

        $sql = 'SELECT id, nombre, descripcion
                FROM linea_investigacion
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

    // -----------------------------------------------------------------------
    // crear
    // Inserta una línea nueva. El id lo genera la BD, así que NO viaja en
    // el INSERT. Devuelve true si la inserción fue exitosa.
    // -----------------------------------------------------------------------
    public function crear(LineaInvestigacion $linea): bool
    {
        $pdo = Conexion::obtener();

        $sql = 'INSERT INTO linea_investigacion (nombre, descripcion)
                VALUES (:nombre, :descripcion)';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':nombre', $linea->getNombre(), PDO::PARAM_STR);
        $sentencia->bindValue(':descripcion', $linea->getDescripcion(), PDO::PARAM_STR);
        $sentencia->execute();

        return $sentencia->rowCount() === 1;
    }

    // -----------------------------------------------------------------------
    // actualizar
    // Modifica los campos que vengan en $datos para la línea $id.
    // Devuelve el número de filas afectadas.
    //
    // El id no se actualiza aunque venga en $datos: es la clave primaria.
    // -----------------------------------------------------------------------
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

        if ($asignaciones === []) {
            return 0;
        }

        $sql = 'UPDATE linea_investigacion
                SET ' . implode(', ', $asignaciones) . '
                WHERE id = :id';

        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);

        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // eliminar
    // Borra la línea indicada. Devuelve el número de filas eliminadas
    // (0 si no existía).
    // -----------------------------------------------------------------------
    public function eliminar(int $id): int
    {
        $pdo = Conexion::obtener();

        $sql = 'DELETE FROM linea_investigacion WHERE id = :id';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // filaAObjeto (método privado auxiliar)
    // Convierte una fila de la BD en un objeto LineaInvestigacion.
    // El id se castea a int para que el JSON lo traiga como número.
    // -----------------------------------------------------------------------
    private function filaAObjeto(array $fila): LineaInvestigacion
    {
        return new LineaInvestigacion(
            (int) $fila['id'],
            (string) $fila['nombre'],
            (string) $fila['descripcion']
        );
    }
}