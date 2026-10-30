<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioRed.php';
require_once __DIR__ . '/../repositorios/IRepositorioRed.php';
require_once __DIR__ . '/../modelos/Red.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN del servicio de `red`: la capa donde vive la
 *   lógica de negocio de este recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa del medio. El controlador le pasa los datos ya validados
 *   en la frontera HTTP y el servicio decide qué hacer con ellos. El
 *   servicio, a su vez, habla con el repositorio para llegar a la base.
 *
 *   Depende de la INTERFAZ IRepositorioRed, no de la clase concreta
 *   MariaDB, así que se puede probar con un repositorio falso.
 *
 * ¿Qué reglas de negocio valida?
 *   1. `limite` > 0.
 *   2. Al crear, todos los campos (idr, nombre, url, pais) son
 *      obligatorios y no pueden venir vacíos.
 *   3. Al actualizar, si no llega ningún campo, error de negocio → 400.
 *   4. Si la red no existe al obtener/actualizar/eliminar → 404.
 *
 * Nota sobre el identificador:
 *   En `red` la clave primaria se llama `idr` (INTEGER) y la envía el
 *   cliente en el POST. Por eso es obligatoria al crear.
 * ---------------------------------------------------------------------------
 */
class ServicioRed implements IServicioRed
{
    private IRepositorioRed $repositorio;

    public function __construct(IRepositorioRed $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    // -----------------------------------------------------------------------
    // obtenerTodos
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
    // obtenerPorIdr
    // -----------------------------------------------------------------------
    public function obtenerPorIdr(int $idr): Red
    {
        $red = $this->repositorio->obtenerPorIdr($idr);

        if ($red === null) {
            throw new NoEncontradoExcepcion(
                'Red no encontrada.'
            );
        }

        return $red;
    }

    // -----------------------------------------------------------------------
    // crear
    // Todos los campos son obligatorios en este recurso (incluido idr).
    // -----------------------------------------------------------------------
    public function crear(array $datos): bool
    {
        $obligatorios = ['idr', 'nombre', 'url', 'pais'];
        foreach ($obligatorios as $campo) {
            if (!array_key_exists($campo, $datos) || $datos[$campo] === null) {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
            // idr es int, no se le exige contenido alfanumérico.
            if ($campo !== 'idr' && trim((string) $datos[$campo]) === '') {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
        }

        $red = new Red(
            (int)    $datos['idr'],
            (string) $datos['nombre'],
            (string) $datos['url'],
            (string) $datos['pais']
        );

        return $this->repositorio->crear($red);
    }

    // -----------------------------------------------------------------------
    // actualizar
    // A este método llegan tanto PUT como PATCH.
    // -----------------------------------------------------------------------
    public function actualizar(int $idr, array $datos): int
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se enviaron campos para actualizar.'
            );
        }

        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorIdr($idr);

        return $this->repositorio->actualizar($idr, $datos);
    }

    // -----------------------------------------------------------------------
    // eliminar
    // -----------------------------------------------------------------------
    public function eliminar(int $idr): int
    {
        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorIdr($idr);

        return $this->repositorio->eliminar($idr);
    }
}