<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del repositorio de `area_conocimiento`. Define QUÉ
 *   operaciones de datos se pueden hacer sobre esa tabla, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el CONTRATO entre el servicio y la capa de datos. El servicio
 *   (Fase 4) dependerá de ESTA interfaz, no de la clase concreta
 *   `RepositorioAreaConocimientoMariaDB`. Eso es lo que hace que:
 *     - Se pueda probar el servicio con un repositorio falso en memoria,
 *       sin MariaDB encendida (criterio 6 de la spec).
 *     - El día que se agregue PostgreSQL o SQL Server (v3, v4), el
 *       servicio NO cambie: solo cambia el ensamblador.
 *
 * ¿Qué métodos declara?
 *   Los 5 del contrato de datos, en su forma específica para
 *   `area_conocimiento`:
 *     - obtenerTodos(int $limite): devuelve la colección de objetos.
 *     - obtenerPorId(string $id): devuelve UN objeto o null si no existe.
 *     - crear(AreaConocimiento $area): inserta y devuelve true/false.
 *     - actualizar(string $id, array $datos): modifica y devuelve el
 *       número de filas afectadas.
 *     - eliminar(string $id): borra y devuelve el número de filas
 *       eliminadas.
 *
 * Nota sobre los tipos del id: en `area_conocimiento` el id es un
 * VARCHAR (valores legibles como '1A01'), no un entero. Por eso aquí se
 * tipa como string.
 *
 * Nota sobre `actualizar`: recibe un array porque tanto PUT como PATCH
 * pueden modificar solamente una parte de los campos. El repositorio se
 * encarga de armar el UPDATE con los campos que lleguen.
 * ---------------------------------------------------------------------------
 */
interface IRepositorioAreaConocimiento
{
    /**
     * Devuelve todos los registros, hasta el límite indicado.
     * @return AreaConocimiento[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un registro por su id, o null si no existe.
     */
    public function obtenerPorId(string $id): ?AreaConocimiento;

    /**
     * Inserta un registro nuevo. Devuelve true si la inserción fue
     * exitosa.
     */
    public function crear(AreaConocimiento $area): bool;

    /**
     * Actualiza el registro identificado por $id con los campos que
     * vengan en $datos. Devuelve el número de filas afectadas.
     */
    public function actualizar(string $id, array $datos): int;

    /**
     * Elimina el registro identificado por $id. Devuelve el número de
     * filas eliminadas.
     */
    public function eliminar(string $id): int;
}