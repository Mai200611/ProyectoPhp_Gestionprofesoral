<?php
declare(strict_types=1);

/**
 * IServicioDocenteDepartamento.php
 *
 * Contrato del servicio de la tabla puente `docente_departamento`.
 * Espeja a IRepositorioDocenteDepartamento, pero en el idioma del negocio:
 * lanza NoEncontradoExcepcion cuando la pareja no existe y deja subir
 * ConflictoDeIntegridadExcepcion cuando la base rechaza.
 */
interface IServicioDocenteDepartamento
{
    /**
     * Lista vinculaciones (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve una vinculación por su pareja.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorClave(string $docente, int $departamento): array;

    /**
     * Crea una vinculación. Devuelve true si se insertó.
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