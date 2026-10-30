<?php
declare(strict_types=1);

/**
 * IRepositorioExperiencia.php
 *
 * Contrato del repositorio de la tabla `experiecia` (errata histórica: sin
 * la «n»; ver 5_data_model.md §3.12 y 3_plan §6). La CLASE y la interfaz se
 * llaman `Experiencia` en PascalCase correcto.
 * Implementación concreta: RepositorioExperienciaMariaDB.php (Fase 4).
 *
 * Mismo patrón que IRepositorioEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 *   - `crear()` devuelve `int` (el id generado).
 *   - `reemplazar()` y `actualizarParcial()` devuelven `int` (filas).
 *   - Los métodos por id lanzan NoEncontradoExcepcion si no existe.
 */
interface IRepositorioExperiencia
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
     * Crea una experiencia. El id NO viene en $datos: lo genera la base.
     * Devuelve el id que la base asignó.
     */
    public function crear(array $datos): int;

    /**
     * Reemplaza una experiencia entera (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function reemplazar(int $id, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function actualizarParcial(int $id, array $datos): int;

    /**
     * Elimina una experiencia. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si el id no existe.
     */
    public function eliminar(int $id): int;
}