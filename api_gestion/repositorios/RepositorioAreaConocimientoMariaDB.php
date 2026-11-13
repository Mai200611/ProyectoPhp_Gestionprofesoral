<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/../modelos/AreaConocimiento.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN CONCRETA del repositorio de `area_conocimiento`
 *   contra MariaDB. Es la clase que de verdad habla con la base de datos
 *   por medio de PDO y prepared statements.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa más baja del sistema (justo antes de la BD). Implementa la
 *   interfaz IRepositorioAreaConocimiento, así que los servicios pueden
 *   depender de la interfaz sin conocer esta clase. Esta clase es la que
 *   el ensamblador (Fase 4) instanciará para conectar el servicio con la
 *   base real.
 *
 * ¿Qué hace exactamente?
 *   Traduce entre el mundo PHP (objetos AreaConocimiento) y el mundo SQL
 *   (filas de la tabla). En cada método:
 *     1. Arma la sentencia SQL con parámetros con nombre (:id, :limite...).
 *     2. La prepara con PDO.
 *     3. Enlaza los valores tipados (ojo con :limite → PARAM_INT).
 *     4. Ejecuta.
 *     5. Convierte las filas devueltas en objetos del modelo.
 *
 * ¿Qué NO hace?
 *   - NO conoce HTTP. No sabe qué es un 404 ni un 422.
 *   - NO valida datos. Da por hecho que lo que recibe ya pasó por la
 *     validación del controlador.
 *   - NO lanza NoEncontradoExcepcion. Solo devuelve null cuando no hay
 *     nada. La traducción a "recurso no encontrado" es trabajo del
 *     servicio.
 *
 * Notas sobre el casteo de tipos:
 *   El driver mysql de PDO entrega los enteros como string (por ejemplo
 *   "12" en lugar de 12). Al construir cada objeto AreaConocimiento,
 *   casteamos explícitamente para que el JSON de la API muestre números
 *   donde corresponde. En `area_conocimiento` los cuatro campos son
 *   VARCHAR en la BD, así que aquí todo viaja como string — no hay nada
 *   que castear. Lo dejamos así, respetando el esquema dado.
 * ---------------------------------------------------------------------------
 */
class RepositorioAreaConocimientoMariaDB implements IRepositorioAreaConocimiento
{
    // -----------------------------------------------------------------------
    // obtenerTodos
    // Devuelve hasta $limite registros, ordenados por id.
    //
    // Detalle clave para MariaDB: el :limite DEBE enlazarse con
    // PDO::PARAM_INT. Si se enlaza como string, MariaDB tira un error de
    // sintaxis porque el LIMIT exige un entero. Lo dice el plan §7 y el
    // quickstart §4 lo repite como error típico.
    // -----------------------------------------------------------------------
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

        $filas = $sentencia->fetchAll();

        $areas = [];
        foreach ($filas as $fila) {
            $areas[] = $this->filaAObjeto($fila);
        }

        return $areas;
    }

    // -----------------------------------------------------------------------
    // obtenerPorId
    // Devuelve el registro con el id indicado, o null si no existe.
    // -----------------------------------------------------------------------
    public function obtenerPorId(string $id): ?AreaConocimiento
    {
        $pdo = Conexion::obtener();

        $sql = 'SELECT id, gran_area, area, disciplina
                FROM area_conocimiento
                WHERE id = :id';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_STR);
        $sentencia->execute();

        $fila = $sentencia->fetch();

        if ($fila === false) {
            return null;
        }

        return $this->filaAObjeto($fila);
    }

    // -----------------------------------------------------------------------
    // crear
    // Inserta un registro nuevo. Devuelve true si la inserción fue exitosa.
    //
    // El id viene incluido en el objeto porque en `area_conocimiento` el
    // id es un VARCHAR legible (ej. '1A01'), no un autoincremental: el
    // cliente SÍ lo envía en el POST.
    // -----------------------------------------------------------------------
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
        $sentencia->execute();

        return $sentencia->rowCount() === 1;
    }

    // -----------------------------------------------------------------------
    // actualizar
    // Modifica los campos que vengan en $datos para el registro $id.
    // Devuelve el número de filas afectadas.
    //
    // El array $datos puede traer uno o varios campos (PUT trae todos,
    // PATCH trae algunos). Aquí se arma dinámicamente el SET, pero cada
    // valor viaja SIEMPRE como parámetro con nombre — nunca concatenado
    // al SQL.
    //
    // Nota: el id no se actualiza aunque venga en $datos. Es la clave
    // primaria; la identidad de la ficha no cambia.
    //
    // Nota sobre las filas afectadas: gracias a MYSQL_ATTR_FOUND_ROWS
    // (activado en Conexion.php), rowCount devuelve las filas ENCONTRADAS
    // por el WHERE, no solo las que cambiaron. Así un PATCH que reescribe
    // el mismo valor sigue devolviendo 1, y el contrato queda satisfecho.
    // -----------------------------------------------------------------------
    public function actualizar(string $id, array $datos): int
    {
        // Campos que el contrato permite actualizar en este recurso.
        // Cualquier otro se ignora (el controlador ya filtró y validó).
        $permitidos = ['gran_area', 'area', 'disciplina'];

        $asignaciones = [];
        $valores = [];
        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $asignaciones[] = $campo . ' = :' . $campo;
                $valores[$campo] = $datos[$campo];
            }
        }

        // Si no llegó ningún campo editable, no hay nada que hacer.
        if ($asignaciones === []) {
            return 0;
        }

        $sql = 'UPDATE area_conocimiento
                SET ' . implode(', ', $asignaciones) . '
                WHERE id = :id';

        $pdo = Conexion::obtener();
        $sentencia = $pdo->prepare($sql);

        foreach ($valores as $campo => $valor) {
            $sentencia->bindValue(':' . $campo, (string) $valor, PDO::PARAM_STR);
        }
        $sentencia->bindValue(':id', $id, PDO::PARAM_STR);

        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // eliminar
    // Borra el registro con el id indicado. Devuelve el número de filas
    // eliminadas (0 si no existía).
    // -----------------------------------------------------------------------
    public function eliminar(string $id): int
    {
        $pdo = Conexion::obtener();

        $sql = 'DELETE FROM area_conocimiento WHERE id = :id';

        $sentencia = $pdo->prepare($sql);
        $sentencia->bindValue(':id', $id, PDO::PARAM_STR);
        $sentencia->execute();

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------------
    // filaAObjeto (método privado auxiliar)
    // Convierte una fila de la BD (arreglo asociativo) en un objeto del
    // modelo AreaConocimiento.
    //
    // Se aísla aquí para no repetir la conversión en cada método de
    // lectura (obtenerTodos y obtenerPorId).
    // -----------------------------------------------------------------------
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