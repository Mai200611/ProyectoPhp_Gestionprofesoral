<?php
declare(strict_types=1);

/**
 * IServicioDocente.php
 *
 * Contrato del servicio de `docente`. Lo que el controlador de docente
 * necesita del negocio, sin saber cómo se guarda ni qué motor hay detrás.
 *
 * Sigue el mismo criterio que IRepositorioDocente: NO declara
 * `actualizarParcial()`, porque el docente se reemplaza entero (RF12).
 *
 * `crearCompleto()` es el punto de entrada del maestro-detalle: recibe el
 * encabezado y las listas anidadas, las valida, y delega en el repositorio
 * la escritura atómica (Artículo 11).
 */
interface IServicioDocente
{
    /**
     * Lista docentes (hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array;

    /**
     * Devuelve un docente por su cédula, sin detalle anidado.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerPorCedula(string $cedula): array;

    /**
     * Devuelve el docente con su detalle anidado completo.
     * Es lo que sirve GET /api/docente/{cedula}/completo.
     * Lanza NoEncontradoExcepcion si no existe.
     *
     * @return array<string, mixed>
     */
    public function obtenerCompleto(string $cedula): array;

    /**
     * Crea un docente con su detalle anidado.
     *
     * @param array<string, mixed> $datos Encabezado + listas anidadas.
     * @return array<string, mixed> Docente completo recién creado.
     */
    public function crearCompleto(array $datos): array;

    /**
     * Reemplaza un docente entero (encabezado + detalle).
     * Lanza NoEncontradoExcepcion si la cédula no existe.
     * Devuelve cuántas filas del encabezado se vieron afectadas (0 o 1).
     */
    public function reemplazar(string $cedula, array $datos): int;

    /**
     * Elimina un docente.
     * Lanza NoEncontradoExcepcion si la cédula no existe.
     * Lanza ConflictoDeIntegridadExcepcion (409) si tiene dependencias.
     * Devuelve cuántas filas se eliminaron (0 o 1).
     */
    public function eliminar(string $cedula): int;
}