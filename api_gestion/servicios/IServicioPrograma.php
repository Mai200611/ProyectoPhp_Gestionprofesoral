<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del servicio de `programa`. Define QUÉ operaciones de
 *   negocio se pueden hacer sobre este recurso, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el CONTRATO entre el controlador y la capa de servicios. El
 *   controlador dependerá de ESTA interfaz, no de la clase concreta
 *   `ServicioPrograma`. Esta es también la interfaz que la prueba de
 *   capas (Fase 4) usará cuando instancie `ServicioPrograma` con un
 *   repositorio falso en memoria.
 *
 * ¿Qué métodos declara?
 *   Los 5 del ciclo CRUD, con la forma específica de este recurso:
 *     - obtenerTodos(int $limite): array
 *     - obtenerPorId(int $id): Programa
 *     - crear(array $datos): bool
 *     - actualizar(int $id, array $datos): int
 *     - eliminar(int $id): int
 *
 * Nota sobre el identificador: en `programa` la clave primaria es `id`
 * y es un entero (INTEGER). Coincide en forma con la de
 * `linea_investigacion`, pero son recursos distintos: cada uno tiene su
 * propia interfaz, sin genéricos.
 *
 * Nota sobre obtenerPorId: si el programa no existe, el servicio lanza
 * NoEncontradoExcepcion (el controlador la traduce a 404).
 * ---------------------------------------------------------------------------
 */
interface IServicioPrograma
{
    /**
     * Devuelve todos los programas, hasta el límite indicado.
     * @return Programa[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un programa por su id.
     * Si no existe, lanza NoEncontradoExcepcion (el controlador la
     * traduce a 404).
     */
    public function obtenerPorId(int $id): Programa;

    /**
     * Crea un programa nuevo con los datos recibidos.
     * Devuelve true si la operación fue exitosa.
     */
    public function crear(array $datos): bool;

    /**
     * Actualiza el programa identificado por $id con los campos que
     * vengan en $datos. Devuelve el número de filas afectadas.
     * Si el programa no existe, lanza NoEncontradoExcepcion.
     */
    public function actualizar(int $id, array $datos): int;

    /**
     * Elimina el programa identificado por $id. Devuelve el número de
     * filas eliminadas.
     * Si el programa no existe, lanza NoEncontradoExcepcion.
     */
    public function eliminar(int $id): int;
}