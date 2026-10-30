<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del repositorio de `programa`. Define QUÉ operaciones
 *   de datos se pueden hacer sobre esa tabla, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que las otras interfaces de repositorio: es el CONTRATO entre
 *   el servicio y la capa de datos. El servicio dependerá de ESTA
 *   interfaz, no de la clase concreta MariaDB. Es justo esta interfaz
 *   la que la prueba de capas (Fase 4) usará para probar el servicio
 *   con un repositorio falso en memoria, sin MariaDB encendida.
 *
 * ¿Qué métodos declara?
 *   Los 5 del contrato de datos, en su forma específica para `programa`:
 *     - obtenerTodos(int $limite)
 *     - obtenerPorId(int $id): ?Programa
 *     - crear(Programa $programa): bool
 *     - actualizar(int $id, array $datos): int
 *     - eliminar(int $id): int
 *
 * Nota sobre el identificador: en `programa` la clave primaria se llama
 * `id` y es un entero (INTEGER). Coincide en forma con la de
 * `linea_investigacion`, pero son recursos distintos y por eso cada uno
 * tiene su propia interfaz — el Artículo 10 de la constitución prohíbe
 * una interfaz genérica que sirva para todos.
 * ---------------------------------------------------------------------------
 */
interface IRepositorioPrograma
{
    /**
     * Devuelve todos los programas, hasta el límite indicado.
     * @return Programa[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un programa por su id, o null si no existe.
     */
    public function obtenerPorId(int $id): ?Programa;

    /**
     * Inserta un programa nuevo. Devuelve true si la inserción fue
     * exitosa.
     */
    public function crear(Programa $programa): bool;

    /**
     * Actualiza el programa identificado por $id con los campos que
     * vengan en $datos. Devuelve el número de filas afectadas.
     */
    public function actualizar(int $id, array $datos): int;

    /**
     * Elimina el programa identificado por $id. Devuelve el número de
     * filas eliminadas.
     */
    public function eliminar(int $id): int;
}