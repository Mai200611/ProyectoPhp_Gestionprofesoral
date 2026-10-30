<?php
declare(strict_types=1);

/**
 * IRepositorioApoyoProfesoral.php
 *
 * Contrato del repositorio de `apoyo_profesoral`.
 * Implementación concreta: RepositorioApoyoProfesoralMariaDB.php (Fase 4).
 *
 * Aunque 6_contracts §8 la etiquete con PK `(id_estudio, tipo_apoyo)`, la
 * PK real de la tabla es `estudios` SOLA (5_data_model.md §3.15). Gana
 * 5_data_model.md (instrucción 8): la llave es un único `int`.
 *
 * Patrón de ficha con llave que manda el cliente (no autogenerada): el
 * `crear()` devuelve `bool`, porque no hay id nuevo que devolver.
 *   - `obtenerPorId()` recibe el `estudios`.
 *   - `reemplazar()`, `actualizarParcial()` y `eliminar()` también.
 */
interface IRepositorioApoyoProfesoral
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $estudios): ?array;

    /**
     * Crea un apoyo. El `estudios` viene dentro de $datos.
     * Devuelve true si se insertó.
     */
    public function crear(array $datos): bool;

    /**
     * Reemplaza un apoyo entero (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si el `estudios` no existe.
     */
    public function reemplazar(int $estudios, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si el `estudios` no existe.
     */
    public function actualizarParcial(int $estudios, array $datos): int;

    /**
     * Elimina un apoyo. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si el `estudios` no existe.
     */
    public function eliminar(int $estudios): int;
}