<?php
declare(strict_types=1);

/**
 * IRepositorioDocenteDepartamento.php
 *
 * Contrato del repositorio de la tabla puente `docente_departamento`.
 * Implementación concreta: RepositorioDocenteDepartamentoMariaDB.php (Fase 4).
 *
 * Este contrato es el PATRÓN de las puentes con PK compuesta:
 *   - `obtenerPorClave()` recibe las dos llaves.
 *   - `crear()` recibe las dos llaves dentro de $datos; devuelve `bool`
 *     (no hay id autogenerado que devolver).
 *   - `reemplazar()` y `eliminar()` reciben las dos llaves por separado.
 *   - `actualizarParcial()` también recibe las dos llaves.
 *
 * Los métodos por llave lanzan NoEncontradoExcepcion si la pareja no existe
 * y ConflictoDeIntegridadExcepcion si la base rechaza por integridad
 * (pareja duplicada, FK inexistente, etc.).
 */
interface IRepositorioDocenteDepartamento
{
    /**
     * Devuelve todas las vinculaciones (o hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve una vinculación por su pareja (docente, departamento),
     * o null si no existe.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorClave(string $docente, int $departamento): ?array;

    /**
     * Crea una vinculación. La pareja viene dentro de $datos.
     * Devuelve true si se insertó.
     *
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): bool;

    /**
     * Reemplaza una vinculación entera (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function reemplazar(string $docente, int $departamento, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function actualizarParcial(string $docente, int $departamento, array $datos): int;

    /**
     * Elimina una vinculación. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function eliminar(string $docente, int $departamento): int;
}