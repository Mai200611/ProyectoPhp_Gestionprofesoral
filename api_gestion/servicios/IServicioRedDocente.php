<?php
declare(strict_types=1);

/**
 * IServicioRedDocente.php
 *
 * Contrato del servicio de la tabla puente `red_docente`.
 * Espeja a IRepositorioRedDocente en el idioma del negocio:
 * lanza NoEncontradoExcepcion cuando la pareja no existe y deja subir
 * ConflictoDeIntegridadExcepcion cuando la base rechaza.
 */
interface IServicioRedDocente
{
    /**
     * Lista vínculos red↔docente (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve un vínculo por su pareja.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorClave(int $red, string $docente): array;

    /**
     * Crea un vínculo. Devuelve true si se insertó.
     *
     * @param array<string, mixed> $datos
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