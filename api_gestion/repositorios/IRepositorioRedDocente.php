<?php
declare(strict_types=1);

/**
 * IRepositorioRedDocente.php
 *
 * Contrato del repositorio de la tabla puente `red_docente`.
 * Implementación concreta: RepositorioRedDocenteMariaDB.php (Fase 4).
 *
 * PK compuesta: (red, docente). Ojo con los nombres de columna reales:
 * son `red` (int) y `docente` (string); 6_contracts §8 los etiqueta como
 * `idr` y `cedula`, pero gana 5_data_model.md (instrucción 8).
 *
 * Mismo patrón que IRepositorioDocenteDepartamento.
 */
interface IRepositorioRedDocente
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorClave(int $red, string $docente): ?array;

    /**
     * Crea un vínculo. La pareja viene dentro de $datos.
     * Devuelve true si se insertó.
     */
    public function crear(array $datos): bool;

    /**
     * Reemplaza un vínculo entero (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function reemplazar(int $red, string $docente, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function actualizarParcial(int $red, string $docente, array $datos): int;

    /**
     * Elimina un vínculo. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function eliminar(int $red, string $docente): int;
}