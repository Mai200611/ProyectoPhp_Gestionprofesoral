<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del repositorio de `termino_clave`. Define QUÉ
 *   operaciones de datos se pueden hacer sobre esa tabla, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que las otras interfaces de repositorio: es el CONTRATO entre
 *   el servicio y la capa de datos. El servicio dependerá de ESTA
 *   interfaz, no de la clase concreta MariaDB.
 *
 * ¿Qué métodos declara?
 *   Los 5 del contrato de datos, en su forma específica para
 *   `termino_clave`:
 *     - obtenerTodos(int $limite)
 *     - obtenerPorTermino(string $termino): ?TerminoClave
 *     - crear(TerminoClave $termino): bool
 *     - actualizar(string $termino, array $datos): int
 *     - eliminar(string $termino): int
 *
 * Nota sobre el identificador: en `termino_clave` la clave primaria NO
 * se llama `id`, se llama `termino` y es un string. Por eso el método
 * se llama obtenerPorTermino (no obtenerPorId) y recibe un string. El
 * contrato de la API refleja lo mismo: `/api/termino_clave/{termino}`.
 * ---------------------------------------------------------------------------
 */
interface IRepositorioTerminoClave
{
    /**
     * Devuelve todos los términos, hasta el límite indicado.
     * @return TerminoClave[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un término por su clave primaria (`termino`), o null si
     * no existe.
     */
    public function obtenerPorTermino(string $termino): ?TerminoClave;

    /**
     * Inserta un término nuevo. Devuelve true si la inserción fue
     * exitosa.
     */
    public function crear(TerminoClave $termino): bool;

    /**
     * Actualiza el término identificado por $termino con los campos que
     * vengan en $datos (en la práctica, `termino_ingles`). Devuelve el
     * número de filas afectadas.
     */
    public function actualizar(string $termino, array $datos): int;

    /**
     * Elimina el término identificado por $termino. Devuelve el número
     * de filas eliminadas.
     */
    public function eliminar(string $termino): int;
}