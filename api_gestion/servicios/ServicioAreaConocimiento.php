<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioAreaConocimiento.php';
require_once __DIR__ . '/../repositorios/IRepositorioAreaConocimiento.php';
require_once __DIR__ . '/../modelos/AreaConocimiento.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN del servicio de `area_conocimiento`: la capa
 *   donde vive la lógica de negocio de este recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa del medio. El controlador (Fase 5) le pasa los datos ya
 *   validados en la frontera HTTP y el servicio decide qué hacer con
 *   ellos. El servicio, a su vez, habla con el repositorio para llegar
 *   a la base de datos.
 *
 *   Lo importante: el servicio depende de la INTERFAZ del repositorio
 *   (IRepositorioAreaConocimiento), no de la clase concreta MariaDB.
 *   Eso es lo que permite la prueba de capas (criterio 6 de la spec).
 *
 * ¿Qué reglas de negocio valida?
 *   1. `limite` debe ser mayor que 0. Si no, es un error de negocio y se
 *      lanza InvalidArgumentException → el controlador lo traduce a 400.
 *   2. Al crear, los campos obligatorios no pueden venir vacíos. Es la
 *      misma validación que hace el controlador, pero aquí se repite
 *      como segunda línea de defensa: si alguien llama al servicio
 *      directamente, no puede saltarse la regla.
 *   3. Al actualizar, si no llega ningún campo, es un error de negocio
 *      → InvalidArgumentException → 400 (PATCH con body vacío).
 *
 * ¿Qué NO hace?
 *   - NO conoce HTTP. No sabe qué es un 400 ni un 404.
 *   - NO escribe SQL. Eso es cosa del repositorio.
 *   - NO valida la FORMA de los datos (tipos, campos permitidos...). Eso
 *     es cosa del controlador, que es la frontera HTTP.
 * ---------------------------------------------------------------------------
 */
class ServicioAreaConocimiento implements IServicioAreaConocimiento
{
    /**
     * El repositorio con el que este servicio va a hablar. Es un tipo
     * INTERFAZ, no una clase concreta — eso es lo que hace que el
     * servicio no sepa si está hablando con MariaDB o con un repositorio
     * falso en memoria.
     */
    private IRepositorioAreaConocimiento $repositorio;

    /**
     * El repositorio llega por constructor. El servicio NO lo instancia
     * él mismo: se lo dan desde afuera (típicamente el ensamblador).
     * Esto se llama inyección de dependencias.
     */
    public function __construct(IRepositorioAreaConocimiento $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    // -----------------------------------------------------------------------
    // obtenerTodos
    // Regla de negocio: el límite debe ser positivo.
    // -----------------------------------------------------------------------
    public function obtenerTodos(int $limite): array
    {
        if ($limite <= 0) {
            throw new InvalidArgumentException(
                'El límite debe ser mayor que cero.'
            );
        }

        return $this->repositorio->obtenerTodos($limite);
    }

    // -----------------------------------------------------------------------
    // obtenerPorId
    // Si el repositorio no encuentra nada, lanzamos
    // NoEncontradoExcepcion (el controlador la traducirá a 404).
    // -----------------------------------------------------------------------
    public function obtenerPorId(string $id): AreaConocimiento
    {
        $area = $this->repositorio->obtenerPorId($id);

        if ($area === null) {
            throw new NoEncontradoExcepcion(
                'Área de conocimiento no encontrada.'
            );
        }

        return $area;
    }

    // -----------------------------------------------------------------------
    // crear
    // Recibe el array del body ya validado por el controlador. Aquí
    // volvemos a comprobar que los campos obligatorios estén presentes y
    // no vacíos (segunda línea de defensa).
    //
    // El id viene incluido porque en `area_conocimiento` el id es un
    // VARCHAR legible que envía el cliente (no es autoincremental).
    // -----------------------------------------------------------------------
    public function crear(array $datos): bool
    {
        $obligatorios = ['id', 'gran_area', 'area', 'disciplina'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
        }

        $area = new AreaConocimiento(
            (string) $datos['id'],
            (string) $datos['gran_area'],
            (string) $datos['area'],
            (string) $datos['disciplina']
        );

        return $this->repositorio->crear($area);
    }

    // -----------------------------------------------------------------------
    // actualizar
    // PUT y PATCH llegan aquí por el mismo método: la diferencia la hace
    // el controlador, que decide qué campos pasar en $datos.
    //
    // Regla de negocio: si $datos viene vacío, no hay nada que actualizar
    // → InvalidArgumentException → 400.
    //
    // Antes de actualizar comprobamos que el recurso exista. Si no existe,
    // NoEncontradoExcepcion → 404.
    // -----------------------------------------------------------------------
    public function actualizar(string $id, array $datos): int
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se enviaron campos para actualizar.'
            );
        }

        // Comprobamos que exista (esto lanzará 404 si no).
        $this->obtenerPorId($id);

        return $this->repositorio->actualizar($id, $datos);
    }

    // -----------------------------------------------------------------------
    // eliminar
    // Regla de negocio: no se puede eliminar algo que no existe → 404.
    // Lo comprobamos con obtenerPorId antes de borrar.
    // -----------------------------------------------------------------------
    public function eliminar(string $id): int
    {
        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorId($id);

        return $this->repositorio->eliminar($id);
    }
}