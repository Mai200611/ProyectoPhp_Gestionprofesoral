<?php
declare(strict_types=1);

/**
 * IServicioReconocimiento.php
 *
 * Contrato del servicio de `reconocimiento`.
 *
 * Espeja a IRepositorioReconocimiento en el idioma del negocio:
 * `crear()` devuelve el id que asignó la base; los métodos por id lanzan
 * NoEncontradoExcepcion cuando el id no existe.
 */
interface IServicioReconocimiento
{
    /**
     * Lista reconocimientos (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve un reconocimiento por id.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorId(int $id): array;

    /**
     * Crea un reconocimiento. Devuelve el id que asignó la base.
     *
     * @param array<string, mixed> $datos
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