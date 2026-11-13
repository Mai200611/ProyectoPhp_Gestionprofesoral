<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del repositorio de `red`. Define QUÉ operaciones de
 *   datos se pueden hacer sobre esa tabla, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que las otras interfaces de repositorio: es el CONTRATO entre
 *   el servicio y la capa de datos. El servicio dependerá de ESTA
 *   interfaz, no de la clase concreta MariaDB.
 *
 * ¿Qué métodos declara?
 *   Los 5 del contrato de datos, en su forma específica para `red`:
 *     - obtenerTodos(int $limite)
 *     - obtenerPorIdr(int $idr): ?Red
 *     - crear(Red $red): bool
 *     - actualizar(int $idr, array $datos): int
 *     - eliminar(int $idr): int
 *
 * Nota sobre el identificador: en `red` la clave primaria se llama `idr`
 * y es un entero. Por eso el método se llama obtenerPorIdr (no
 * obtenerPorId) y el parámetro se llama $idr. El nombre de la columna se
 * respeta en toda la API: el contrato dice `/api/red/{idr}`.
 * ---------------------------------------------------------------------------
 */
interface IRepositorioRed
{
    /**
     * Devuelve todas las redes, hasta el límite indicado.
     * @return Red[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve una red por su idr, o null si no existe.
     */
    public function obtenerPorIdr(int $idr): ?Red;

    /**
     * Inserta una red nueva. Devuelve true si la inserción fue exitosa.
     */
    public function crear(Red $red): bool;

    /**
     * Actualiza la red identificada por $idr con los campos que vengan
     * en $datos. Devuelve el número de filas afectadas.
     */
    public function actualizar(int $idr, array $datos): int;

    /**
     * Elimina la red identificada por $idr. Devuelve el número de filas
     * eliminadas.
     */
    public function eliminar(int $idr): int;
}