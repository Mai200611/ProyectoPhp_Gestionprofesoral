<?php
declare(strict_types=1);

/**
 * IRepositorioDocente.php
 *
 * Contrato del repositorio de `docente`. Lo que el servicio de docente
 * necesita del almacenamiento, sin saber que es MariaDB ni cómo se llama la
 * tabla. La implementación concreta vive en
 * RepositorioDocenteMariaDB.php (Fase 4).
 *
 * Nota importante (RF12 y 3_plan §4.4): este contrato NO declara
 * `actualizarParcial()`. El docente se reemplaza entero (`PUT`), porque
 * cambiar un estudio cambia su línea de investigación principal y sus
 * puentes; un PATCH dejaría la ficha a medio construir. Que el método no
 * exista aquí es la primera línea de defensa: aunque alguien escribiera el
 * controlador, el servicio no tendría a quién llamar.
 *
 * `obtenerCompleto()` y `crearCompleto()` son los métodos del maestro-detalle:
 * devuelven/reciben el docente con sus estudios, intereses, redes,
 * evaluaciones, reconocimientos y experiencia anidados, en una sola
 * operación de negocio (Artículo 11: maestro-detalle como unidad).
 */
interface IRepositorioDocente
{
    /**
     * Devuelve todos los docentes (o hasta $limite).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array;

    /**
     * Devuelve un docente por su cédula, sin detalle anidado.
     * Devuelve null si no existe.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorCedula(string $cedula): ?array;

    /**
     * Devuelve el docente con TODOS sus detalles anidados (estudios,
     * intereses, redes, evaluaciones, reconocimientos, experiencia).
     * Es lo que usa GET /api/docente/{cedula}/completo.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerCompleto(string $cedula): ?array;

    /**
     * Crea un docente con su detalle anidado en una sola operación.
     * Devuelve el docente completo recién creado (con los ids que generó
     * la base en los detalles).
     *
     * @param array<string, mixed> $datos Encabezado + listas anidadas.
     * @return array<string, mixed>
     */
    public function crearCompleto(array $datos): array;

    /**
     * Reemplaza el docente entero (encabezado + detalle). El `PUT`.
     * Devuelve cuántas filas del encabezado se vieron afectadas (0 o 1).
     */
    public function reemplazar(string $cedula, array $datos): int;

    /**
     * Elimina el docente. La base rechaza con 409 si tiene dependencias;
     * la traducción la hace el repositorio (errores_de_integridad.php).
     * Devuelve cuántas filas se eliminaron (0 o 1).
     */
    public function eliminar(string $cedula): int;
}