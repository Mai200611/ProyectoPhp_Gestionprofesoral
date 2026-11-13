<?php
declare(strict_types=1);

/**
 * IRepositorioReconocimiento.php
 *
 * Contrato del repositorio de `reconocimiento`.
 * Implementación concreta: RepositorioReconocimientoMariaDB.php (Fase 4).
 *
 * Mismo patrón que IRepositorioEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 *   - `crear()` devuelve `int` (el id generado).
 *   - `reemplazar()` y `actualizarParcial()` devuelven `int` (filas).
 *   - Los métodos por id lanzan NoEncontradoExcepcion si no existe.
 *   - `eliminar()` puede lanzar ConflictoDeIntegridadExcepcion si otras
 *     fichas dependen del reconocimiento (por simetría del contrato).
 */
interface IRepositorioReconocimiento
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array;

    /**
     * Crea un reconocimiento. El id NO viene en $datos: lo genera la base.
     * Devuelve el id que la base asignó.
     */
    public function crear(array $datos): int;

    /**
     * Reemplaza un reconocimiento entero (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function reemplazar(int $id, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function actualizarParcial(int $id, array $datos): int;

    /**
     * Elimina un reconocimiento. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function eliminar(int $id): int;
}