<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del servicio de `area_conocimiento`. Define QUÉ
 *   operaciones de negocio se pueden hacer sobre este recurso, sin decir
 *   CÓMO se implementan.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el CONTRATO entre el controlador y la capa de servicios. El
 *   controlador (Fase 5) dependerá de ESTA interfaz, no de la clase
 *   concreta `ServicioAreaConocimiento`. Eso hace que:
 *     - El controlador no sepa si el servicio usa un repositorio MariaDB
 *       o un repositorio falso.
 *     - El día que cambie la implementación del servicio, el controlador
 *       no se toca.
 *
 * ¿Qué métodos declara?
 *   Los 5 del ciclo CRUD, con la forma específica de este recurso:
 *     - obtenerTodos(int $limite): array
 *     - obtenerPorId(string $id): AreaConocimiento
 *     - crear(array $datos): bool
 *     - actualizar(string $id, array $datos): int
 *     - eliminar(string $id): int
 *
 * Nota sobre los tipos del id: en `area_conocimiento` el id es un VARCHAR
 * (valores como '1A01'), por eso aquí es string.
 *
 * Nota sobre crear: recibe un array con los datos del body (no un objeto
 * AreaConocimiento ya armado). El servicio es quien arma el objeto a
 * partir de los datos validados por el controlador. Esa decisión deja al
 * controlador como frontera HTTP pura (valida y delega) y mantiene al
 * servicio como el que conoce la lógica de negocio.
 *
 * Nota sobre obtenerPorId: el contrato de la API dice que si el registro
 * no existe, debe responder 404. Ese 404 sale de aquí: el servicio
 * lanzará `NoEncontradoExcepcion` cuando el repositorio devuelva null, y
 * el controlador la traducirá a un 404. Por eso el método NO devuelve
 * null: o devuelve el objeto, o lanza la excepción.
 * ---------------------------------------------------------------------------
 */
interface IServicioAreaConocimiento
{
    /**
     * Devuelve todos los registros, hasta el límite indicado.
     * @return AreaConocimiento[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un registro por su id.
     * Si no existe, lanza NoEncontradoExcepcion (el controlador la
     * traduce a 404).
     */
    public function obtenerPorId(string $id): AreaConocimiento;

    /**
     * Crea un registro nuevo con los datos recibidos.
     * Devuelve true si la operación fue exitosa.
     */
    public function crear(array $datos): bool;

    /**
     * Actualiza el registro identificado por $id con los campos que
     * vengan en $datos. Devuelve el número de filas afectadas.
     * Si el registro no existe, lanza NoEncontradoExcepcion.
     */
    public function actualizar(string $id, array $datos): int;

    /**
     * Elimina el registro identificado por $id. Devuelve el número de
     * filas eliminadas.
     * Si el registro no existe, lanza NoEncontradoExcepcion.
     */
    public function eliminar(string $id): int;
}
