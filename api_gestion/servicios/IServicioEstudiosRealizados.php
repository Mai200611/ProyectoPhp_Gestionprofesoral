<?php
declare(strict_types=1);

/**
 * IServicioEstudiosRealizados.php
 *
 * Contrato del servicio de `estudios_realizados`. Lo que el controlador
 * necesita del negocio, sin saber cómo se guarda ni qué motor hay detrás.
 *
 * Es el patrón de las fichas con `id` autogenerado:
 *   - `crear()` devuelve `int` (el id que generó la base).
 *   - `reemplazar()` y `actualizarParcial()` devuelven `int` (filas).
 *   - Los métodos por llave lanzan NoEncontradoExcepcion si no existe.
 *   - `eliminar()` puede lanzar ConflictoDeIntegridadExcepcion (409) si
 *     otras fichas dependen del estudio.
 */
interface IServicioEstudiosRealizados
{
    /**
     * Lista estudios (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve un estudio por id.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorId(int $id): array;

    /**
     * Crea un estudio. Devuelve el id que asignó la base.
     *
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): int;

    /**
     * Reemplaza un estudio entero (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function reemplazar(int $id, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function actualizarParcial(int $id, array $datos): int;

    /**
     * Elimina un estudio. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si no existe.
     * Lanza ConflictoDeIntegridadExcepcion si tiene dependencias.
     */
    public function eliminar(int $id): int;
}