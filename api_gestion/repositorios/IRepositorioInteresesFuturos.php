<?php
declare(strict_types=1);

/**
 * IRepositorioInteresesFuturos.php
 *
 * Contrato del repositorio de la tabla puente `intereses_futuros`.
 * Implementación concreta: RepositorioInteresesFuturosMariaDB.php (Fase 4).
 *
 * PK compuesta: (docente, termino_clave). Es la puente más delgada del
 * sistema: solo guarda la relación y `activo`. La forma de los métodos sigue
 * el patrón de las puentes (ver IRepositorioDocenteDepartamento).
 */
interface IRepositorioInteresesFuturos
{
    /**
     * Devuelve todos los intereses (o hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un interés por su pareja (docente, termino_clave),
     * o null si no existe.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorClave(string $docente, string $terminoClave): ?array;

    /**
     * Crea un interés. La pareja viene dentro de $datos.
     * Devuelve true si se insertó.
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