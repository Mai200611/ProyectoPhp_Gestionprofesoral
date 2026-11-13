<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la INTERFAZ del repositorio de `linea_investigacion`. Define QUÉ
 *   operaciones de datos se pueden hacer sobre esa tabla, sin decir CÓMO.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que las otras interfaces de repositorio: es el CONTRATO entre
 *   el servicio y la capa de datos. El servicio dependerá de ESTA
 *   interfaz, no de la clase concreta MariaDB.
 *
 * ¿Qué métodos declara?
 *   Los 5 del contrato de datos, en su forma específica para
 *   `linea_investigacion`:
 *     - obtenerTodos(int $limite)
 *     - obtenerPorId(int $id): ?LineaInvestigacion
 *     - crear(LineaInvestigacion $linea): bool
 *     - actualizar(int $id, array $datos): int
 *     - eliminar(int $id): int
 *
 * Nota sobre el identificador: en `linea_investigacion` la clave primaria
 * se llama `id` y es un entero (INTEGER autoincremental en la BD). Por
 * eso aquí el tipo es int, a diferencia de `area_conocimiento` cuyo id
 * es un string legible.
 * ---------------------------------------------------------------------------
 */
interface IRepositorioLineaInvestigacion
{
    /**
     * Devuelve todas las líneas, hasta el límite indicado.
     * @return LineaInvestigacion[]
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve una línea por su id, o null si no existe.
     */
    public function obtenerPorId(int $id): ?LineaInvestigacion;

    /**
     * Inserta una línea nueva. El id lo genera la base de datos. Devuelve
     * true si la inserción fue exitosa.
     */
    public function crear(LineaInvestigacion $linea): bool;

    /**
     * Actualiza la línea identificada por $id con los campos que vengan
     * en $datos. Devuelve el número de filas afectadas.
     */
    public function actualizar(int $id, array $datos): int;

    /**
     * Elimina la línea identificada por $id. Devuelve el número de filas
     * eliminadas.
     */
    public function eliminar(int $id): int;
}