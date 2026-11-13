<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del servicio de `linea_investigacion`. Define QUÉ
 *   operaciones de negocio se pueden hacer sobre este recurso, sin decir
 *   CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el CONTRATO entre el controlador y la capa de servicios. El
 *   controlador dependerá de ESTA interfaz, no de la clase concreta
 *   `ServicioLineaInvestigacion`.
 *
 * ¿Qué métodos declara?
 *   Los 5 del ciclo CRUD, con la forma específica de este recurso:
 *     - obtenerTodos(int $limite): array
 *     - obtenerPorId(int $id): LineaInvestigacion
 *     - crear(array $datos): bool
 *     - actualizar(int $id, array $datos): int
 *     - eliminar(int $id): int
 *
 * Nota sobre el identificador: en `linea_investigacion` la clave primaria
 * es `id` y es un entero (INTEGER autoincremental). Por eso aquí se tipa
 * como int.
 *
 * Nota sobre crear: el `id` lo genera la BD. El body del POST no lo
 * incluye; el array $datos que llega aquí contiene solo `nombre` y
 * `descripcion`.
 * ---------------------------------------------------------------------------
 */
interface IServicioLineaInvestigacion
{
    /**
     * Devuelve todas las líneas, hasta el límite indicado.
     * @return LineaInvestigacion[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve una línea por su id.
     * Si no existe, lanza NoEncontradoExcepcion (el controlador la
     * traduce a 404).
     */
    public function obtenerPorId(int $id): LineaInvestigacion;

    /**
     * Crea una línea nueva con los datos recibidos.
     * Devuelve true si la operación fue exitosa.
     */
    public function crear(array $datos): bool;

    /**
     * Actualiza la línea identificada por $id con los campos que vengan
     * en $datos. Devuelve el número de filas afectadas.
     * Si la línea no existe, lanza NoEncontradoExcepcion.
     */
    public function actualizar(int $id, array $datos): int;

    /**
     * Elimina la línea identificada por $id. Devuelve el número de filas
     * eliminadas.
     * Si la línea no existe, lanza NoEncontradoExcepcion.
     */
    public function eliminar(int $id): int;
}