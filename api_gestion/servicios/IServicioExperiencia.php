<?php
declare(strict_types=1);

/**
 * IServicioExperiencia.php
 *
 * Contrato del servicio de `experiecia` (errata histórica en la tabla; la
 * clase y la interfaz se llaman `Experiencia` en PascalCase correcto).
 *
 * Espeja a IRepositorioExperiencia en el idioma del negocio:
 * `crear()` devuelve el id que asignó la base; los métodos por id lanzan
 * NoEncontradoExcepcion cuando el id no existe.
 */
interface IServicioExperiencia
{
    /**
     * Lista experiencias (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve una experiencia por id.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorId(int $id): array;

    /**
     * Crea una experiencia. Devuelve el id que asignó la base.
     *
     * @param array<string, mixed> $datos
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