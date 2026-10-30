<?php
declare(strict_types=1);

/**
 * IServicioEstudioAc.php
 *
 * Contrato del servicio de la tabla puente `estudios_ac` (la tabla con «s»;
 * el recurso público es `/api/estudio_ac` sin «s»).
 *
 * Espeja a IRepositorioEstudioAc en el idioma del negocio:
 * lanza NoEncontradoExcepcion cuando la pareja no existe y deja subir
 * ConflictoDeIntegridadExcepcion cuando la base rechaza.
 */
interface IServicioEstudioAc
{
    /**
     * Lista vínculos estudio↔área (hasta $limite).
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
    public function obtenerPorClave(int $estudio, string $areaConocimiento): array;

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