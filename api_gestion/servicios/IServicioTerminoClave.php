<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del servicio de `termino_clave`. Define QUÉ operaciones
 *   de negocio se pueden hacer sobre este recurso, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el CONTRATO entre el controlador y la capa de servicios. El
 *   controlador dependerá de ESTA interfaz, no de la clase concreta
 *   `ServicioTerminoClave`.
 *
 * ¿Qué métodos declara?
 *   Los 5 del ciclo CRUD, con la forma específica de este recurso:
 *     - obtenerTodos(int $limite): array
 *     - obtenerPorTermino(string $termino): TerminoClave
 *     - crear(array $datos): bool
 *     - actualizar(string $termino, array $datos): int
 *     - eliminar(string $termino): int
 *
 * Nota sobre el identificador: en `termino_clave` la clave primaria NO se
 * llama `id`, se llama `termino` y es un string. Por eso el método se
 * llama obtenerPorTermino (no obtenerPorId) y el parámetro se llama
 * $termino. El contrato lo refleja igual: `/api/termino_clave/{termino}`.
 *
 * Nota sobre obtenerPorTermino: igual que en los demás recursos, si el
 * término no existe el servicio lanza NoEncontradoExcepcion (el
 * controlador la traduce a 404). Por eso el método devuelve el objeto,
 * no null.
 * ---------------------------------------------------------------------------
 */
interface IServicioTerminoClave
{
    /**
     * Devuelve todos los términos, hasta el límite indicado.
     * @return TerminoClave[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un término por su clave primaria (`termino`).
     * Si no existe, lanza NoEncontradoExcepcion (el controlador la
     * traduce a 404).
     */
    public function obtenerPorTermino(string $termino): TerminoClave;

    /**
     * Crea un término nuevo con los datos recibidos.
     * Devuelve true si la operación fue exitosa.
     */
    public function crear(array $datos): bool;

    /**
     * Actualiza el término identificado por $termino con los campos que
     * vengan en $datos (en la práctica, `termino_ingles`). Devuelve el
     * número de filas afectadas.
     * Si el término no existe, lanza NoEncontradoExcepcion.
     */
    public function actualizar(string $termino, array $datos): int;

    /**
     * Elimina el término identificado por $termino. Devuelve el número
     * de filas eliminadas.
     * Si el término no existe, lanza NoEncontradoExcepcion.
     */
    public function eliminar(string $termino): int;
}