<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioPrograma.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/Programa.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN CONCRETA del repositorio de `programa` contra
 *   MariaDB. La clase que de verdad habla con la base de datos por medio
 *   de PDO y prepared statements.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás repositorios concretos: es la capa más baja,
 *   implementa su interfaz (IRepositorioPrograma) y traduce entre el
 *   mundo PHP (objetos Programa) y el mundo SQL (filas de la tabla).
 *   Esta es la clase que el ensamblador instanciará para conectar el
 *   ServicioPrograma con la base real.
 *
 * Nota sobre el identificador:
 *   En `programa` la clave primaria se llama `id` y es INTEGER. Por eso
 *   los métodos reciben un int.
 *
 * Nota sobre los tipos:
 *   El esquema de la BD guarda casi todas las columnas como VARCHAR
 *   (fechas, cohortes, graduados...). Los únicos campos que van como
 *   enteros son `id` y `facultad`. Al construir el objeto hacemos el
 *   casteo correspondiente: int para esos dos, string para el resto.
 *   `fecha_cierre` admite NULL en la BD, así que lo tratamos con cuidado
 *   al leer (si viene null, se conserva null).
 *
 * Nota sobre el array $datos en crear:
 *   A diferencia de otros repositorios, aquí `crear` recibe un array y no
 *   un objeto Programa. La razón: el `id` lo genera la BD, así que no
 *   puede estar en el objeto al momento de insertar. El servicio armará
 *   este array con los campos validados y el repositorio lo insertará.
 * ---------------------------------------------------------------------------
 */
class RepositorioProgramaMariaDB implements IRepositorioPrograma
{
    // -----------------------------------------------------------------------
    // obtenerTodos
    // Devuelve hasta $limite programas, ordenados por id.
    // -----------------------------------------------------------------------
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

    // -----------------------------------------------------------------------
    // obtenerPorId
    // Devuelve el programa con el id indicado, o null si no existe.
    // -----------------------------------------------------------------------
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

    // -----------------------------------------------------------------------
    // crear
    // Inserta un programa nuevo. Recibe un objeto Programa ya armado por
    // el servicio (con el id que el cliente envió en el POST).
    //
    // Detalle importante: en `programa` el id NO es autoincremental en
    // este esquema — el cliente lo envía. Por eso se incluye en el INSERT.
    // -----------------------------------------------------------------------
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

        // fecha_cierre puede ser null: hay que usar PARAM_NULL en ese caso
        // para que PDO mande el NULL correctamente.
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
        $sentencia->execute();

        return $sentencia->rowCount() === 1;
    }

    // -----------------------------------------------------------------------
    // actualizar
    // Modifica los campos que vengan en $datos para el programa $id.
    // Devuelve el número de filas afectadas.
    //
    // El id no se actualiza aunque venga en $datos: es la clave primaria.
    // Todos los demás campos son editables (nombre, tipo, nivel, fechas,
    // cohortes, graduados, ciudad, facultad).
    //
    // Detalle sobre el casteo por campo: `facultad` es INTEGER en la BD,
    // así que se enlaza como PARAM_INT. Los demás son VARCHAR → PARAM_STR.
    // `fecha_cierre` puede ser null → PARAM_NULL.
    // -----------------------------------------------------------------------
    public function actualizar(int $id, array $datos): int
    {
        // Lista de campos editables. Cada uno con su tipo PDO, para no
        // enlazar todo como string cuando algunos son enteros.
        $tipos = [
            'nombre'              => PDO::PARAM_STR,
            'tipo'                => PDO::PARAM_STR,
            'nivel'               => PDO::PARAM_STR,
            'fecha_creacion'      => PDO::PARAM_STR,
            'fecha_cierre'        => PDO::PARAM_STR,  // se ajusta si es null
            'numero_cohortes'     => PDO::PARAM_STR,
            'cant_graduados'      => PDO::PARAM_STR,
            'fecha_actualizacion' => PDO::PARAM_STR,
            'ciudad'              => PDO::PARAM_STR,
            'facultad'            => PDO::PARAM_INT,
        ];

        $asignaciones = [];
        $valores = [];
        foreach ($tipos as $campo => $tipoPdo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = ['valor' => $datos[$campo], 'tipo' => $tipoPdo];
            }
        }

        if ($asignaciones === []) {
            return 0;
        }

        $sql = 'UPDATE programa
                SET ' . implode(', ', $asignaciones) . '
                WHERE id = :id';

        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);

        foreach ($valores as $campo => $info) {
            $valor = $info['valor'];
            $tipoPdo = $info['tipo'];

            // Si el valor es null, se enlaza como PARAM_NULL aunque el
            // campo sea VARCHAR en la BD.
            if ($valor === null) {
                $sentencia->bindValue(':' . $campo, null, PDO::PARAM_NULL);
                continue;
            }

            // Si el campo es INT, se castea antes de enlazar (por si el
            // valor llegó como string numérico desde el JSON).
            if ($tipoPdo === PDO::PARAM_INT) {
                $sentencia->bindValue(':' . $campo, (int) $valor, PDO::PARAM_INT);
                continue;
            }

            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }

        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // eliminar
    // Borra el programa indicado. Devuelve el número de filas eliminadas.
    // -----------------------------------------------------------------------
    public function eliminar(int $id): int
    {
        $pdo = Conexion::obtener();

        $sql = 'DELETE FROM programa WHERE id = :id';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // filaAObjeto (método privado auxiliar)
    // Convierte una fila de la BD en un objeto Programa.
    //
    // Casteos importantes:
    //   - id       → int
    //   - facultad → int
    //   - fecha_cierre → si viene null, se conserva null
    //   - el resto → string
    // -----------------------------------------------------------------------
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