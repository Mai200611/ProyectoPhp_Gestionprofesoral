<?php
declare(strict_types=1);

/**
 * IRepositorioEvaluacionDocente.php
 *
 * Contrato del repositorio de `evaluacion_docente`.
 * Implementación concreta: RepositorioEvaluacionDocenteMariaDB.php (Fase 4).
 *
 * Mismo patrón que IRepositorioEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 *   - `crear()` devuelve `int` (el id generado).
 *   - `reemplazar()` y `actualizarParcial()` devuelven `int` (filas).
 *   - Los métodos por id lanzan NoEncontradoExcepcion si no existe.
 *   - `eliminar()` puede lanzar ConflictoDeIntegridadExcepcion si otras
 *     fichas dependen de la evaluación (en la práctica, ninguna; pero el
 *     contrato lo permite por simetría).
 */
interface IRepositorioEvaluacionDocente
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
     * Crea una evaluación. El id NO viene en $datos: lo genera la base.
     * Devuelve el id que la base asignó.
     */
    public function crear(array $datos): int;

    /**
     * Reemplaza una evaluación entera (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function reemplazar(int $id, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function actualizarParcial(int $id, array $datos): int;

    /**
     * Elimina una evaluación. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function eliminar(int $id): int;
}