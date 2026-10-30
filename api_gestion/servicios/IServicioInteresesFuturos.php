<?php
declare(strict_types=1);

/**
 * IServicioInteresesFuturos.php
 *
 * Contrato del servicio de la tabla puente `intereses_futuros`.
 * Espeja a IRepositorioInteresesFuturos en el idioma del negocio:
 * lanza NoEncontradoExcepcion cuando la pareja no existe y deja subir
 * ConflictoDeIntegridadExcepcion cuando la base rechaza.
 */
interface IServicioInteresesFuturos
{
    /**
     * Lista intereses (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve un interés por su pareja.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorClave(string $docente, string $terminoClave): array;

    /**
     * Crea un interés. Devuelve true si se insertó.
     *
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): bool;

    /**
     * Reemplaza un interés entero (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function reemplazar(string $docente, string $terminoClave, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function actualizarParcial(string $docente, string $terminoClave, array $datos): int;

    /**
     * Elimina un interés. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si la pareja no existe.
     */
    public function eliminar(string $docente, string $terminoClave): int;
}