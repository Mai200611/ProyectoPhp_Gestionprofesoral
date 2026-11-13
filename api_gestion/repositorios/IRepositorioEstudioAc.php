<?php
declare(strict_types=1);

/**
 * IRepositorioEstudioAc.php
 *
 * Contrato del repositorio de la tabla puente `estudios_ac`.
 * Implementación concreta: RepositorioEstudioAcMariaDB.php (Fase 4).
 *
 * Ojo con los dos nombres:
 *   - La tabla en la base se llama `estudios_ac` (con «s», 5_data_model.md
 *     §3.14).
 *   - El recurso público de la API es `/api/estudio_ac` (sin «s»,
 *     6_contracts.md).
 *   - Las columnas reales son `estudio` (int) y `area_conocimiento`
 *     (string); 6_contracts §8 las etiqueta como `id_estudio` e `id_area`,
 *     pero gana 5_data_model.md (instrucción 8).
 *
 * Mismo patrón que las demás puentes.
 */
interface IRepositorioEstudioAc
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorClave(int $estudio, string $areaConocimiento): ?array;

    /**
     * Crea un vínculo. La pareja viene dentro de $datos.
     * Devuelve true si se insertó.
     */
    public function crear(array $datos): bool;

    /**
     * Reemplaza un vínculo entero (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function reemplazar(int $estudio, string $areaConocimiento, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function actualizarParcial(int $estudio, string $areaConocimiento, array $datos): int;

    /**
     * Elimina un vínculo. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function eliminar(int $estudio, string $areaConocimiento): int;
}