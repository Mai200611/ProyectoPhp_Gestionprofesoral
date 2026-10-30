<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del servicio de `red`. Define QUÉ operaciones de
 *   negocio se pueden hacer sobre este recurso, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el CONTRATO entre el controlador y la capa de servicios. El
 *   controlador dependerá de ESTA interfaz, no de la clase concreta
 *   `ServicioRed`.
 *
 * ¿Qué métodos declara?
 *   Los 5 del ciclo CRUD, con la forma específica de este recurso:
 *     - obtenerTodos(int $limite): array
 *     - obtenerPorIdr(int $idr): Red
 *     - crear(array $datos): bool
 *     - actualizar(int $idr, array $datos): int
 *     - eliminar(int $idr): int
 *
 * Nota sobre el identificador: en `red` la clave primaria se llama `idr`
 * y es un entero. Por eso el método se llama obtenerPorIdr (no
 * obtenerPorId) y el parámetro se llama $idr. El nombre de la columna se
 * respeta en toda la API: `/api/red/{idr}`.
 *
 * Nota sobre obtenerPorIdr: si la red no existe, el servicio lanza
 * NoEncontradoExcepcion (el controlador la traduce a 404).
 * ---------------------------------------------------------------------------
 */
interface IServicioRed
{
    /**
     * Devuelve todas las redes, hasta el límite indicado.
     * @return Red[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve una red por su idr.
     * Si no existe, lanza NoEncontradoExcepcion (el controlador la
     * traduce a 404).
     */
    public function obtenerPorIdr(int $idr): Red;

    /**
     * Crea una red nueva con los datos recibidos.
     * Devuelve true si la operación fue exitosa.
     */
    public function crear(array $datos): bool;

    /**
     * Actualiza la red identificada por $idr con los campos que vengan
     * en $datos. Devuelve el número de filas afectadas.
     * Si la red no existe, lanza NoEncontradoExcepcion.
     */
    public function actualizar(int $idr, array $datos): int;

    /**
     * Elimina la red identificada por $idr. Devuelve el número de filas
     * eliminadas.
     * Si la red no existe, lanza NoEncontradoExcepcion.
     */
    public function eliminar(int $idr): int;
}