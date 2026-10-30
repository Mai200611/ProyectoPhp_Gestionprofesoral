<?php
declare(strict_types=1);

/**
 * IRepositorioEstudiosRealizados.php
 *
 * Contrato del repositorio de `estudios_realizados`. Lo que el servicio
 * necesita del almacenamiento, sin saber que es MariaDB ni cómo se llama la
 * tabla. La implementación concreta vive en
 * RepositorioEstudiosRealizadosMariaDB.php (Fase 4).
 *
 * Este contrato es el PATRÓN de las fichas con `id` autogenerado:
 *   - `crear()` devuelve `int` (el id que generó la base, 3_plan §4.2).
 *   - `reemplazar()` devuelve `int` (filas afectadas).
 *   - `actualizarParcial()` recibe solo los campos enviados y devuelve `int`.
 *   - `eliminar()` devuelve `int` (filas eliminadas).
 *
 * Los métodos `reemplazar`, `actualizarParcial` y `eliminar` lanzan
 * `NoEncontradoExcepcion` (404) si la llave no existe y
 * `ConflictoDeIntegridadExcepcion` (409) si la base rechaza por integridad;
 * la traducción la hace el repositorio con errores_de_integridad.php.
 */
interface IRepositorioEstudiosRealizados
{
    /**
     * Devuelve todos los estudios (o hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un estudio por su id, o null si no existe.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array;

    /**
     * Crea un estudio. El id NO viene en $datos: lo genera la base.
     * Devuelve el id que la base asignó.
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
     * Lanza NoEncontradoExcepcion si el id no existe.
     * Lanza ConflictoDeIntegridadExcepcion si otras fichas dependen de él
     * (apoyo_profesoral, beca, estudio_ac).
     */
    public function eliminar(int $id): int;
}