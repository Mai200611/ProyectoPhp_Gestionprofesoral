<?php
declare(strict_types=1);

/**
 * IServicioBeca.php
 *
 * Contrato del servicio de `beca`.
 *
 * Espeja a IRepositorioBeca en el idioma del negocio:
 * `crear()` devuelve `bool` (la llave la manda el cliente); los métodos
 * por `estudios` lanzan NoEncontradoExcepcion si no existe.
 */
interface IServicioBeca
{
    /**
     * Lista becas (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve una beca por el id del estudio.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorId(int $estudios): array;

    /**
     * Crea una beca. Devuelve true si se insertó.
     *
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): bool;

    /**
     * Reemplaza una beca entera (el PUT). Devuelve filas afectadas.
     * Lanza NoEncontradoExcepcion si el `estudios` no existe.
     */
    public function reemplazar(int $estudios, array $datos): int;

    /**
     * Actualiza solo los campos enviados (el PATCH). Devuelve filas
     * afectadas. Lanza NoEncontradoExcepcion si el `estudios` no existe.
     */
    public function actualizarParcial(int $estudios, array $datos): int;

    /**
     * Elimina una beca. Devuelve filas eliminadas.
     * Lanza NoEncontradoExcepcion si el `estudios` no existe.
     */
    public function eliminar(int $estudios): int;
}