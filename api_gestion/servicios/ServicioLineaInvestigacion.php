<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioLineaInvestigacion.php';
require_once __DIR__ . '/../repositorios/IRepositorioLineaInvestigacion.php';
require_once __DIR__ . '/../modelos/LineaInvestigacion.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN del servicio de `linea_investigacion`: la capa
 *   donde vive la lógica de negocio de este recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa del medio. El controlador le pasa los datos ya validados
 *   en la frontera HTTP y el servicio decide qué hacer con ellos. El
 *   servicio, a su vez, habla con el repositorio para llegar a la base.
 *
 *   Depende de la INTERFAZ IRepositorioLineaInvestigacion, no de la
 *   clase concreta MariaDB, así que se puede probar con un repositorio
 *   falso.
 *
 * ¿Qué reglas de negocio valida?
 *   1. `limite` > 0.
 *   2. Al crear, `nombre` y `descripcion` no pueden venir vacíos.
 *   3. Al actualizar, si no llega ningún campo, error de negocio → 400.
 *   4. Si la línea no existe al obtener/actualizar/eliminar → 404.
 *
 * Nota sobre el id:
 *   En `linea_investigacion` el id es INTEGER autoincremental, así que
 *   NO viaja en el array al crear. Lo genera la BD.
 * ---------------------------------------------------------------------------
 */
class ServicioLineaInvestigacion implements IServicioLineaInvestigacion
{
    private IRepositorioLineaInvestigacion $repositorio;

    public function __construct(IRepositorioLineaInvestigacion $repositorio)
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
    // obtenerPorId
    // -----------------------------------------------------------------------
    public function obtenerPorId(int $id): LineaInvestigacion
    {
        $linea = $this->repositorio->obtenerPorId($id);

        if ($linea === null) {
            throw new NoEncontradoExcepcion(
                'Línea de investigación no encontrada.'
            );
        }

        return $linea;
    }

    // -----------------------------------------------------------------------
    // crear
    // El id lo genera la BD, así que no se valida como obligatorio.
    // El objeto se arma con id=0 (placeholder): el repositorio hará el
    // INSERT sin el id, y MariaDB asignará el siguiente.
    // -----------------------------------------------------------------------
    public function crear(array $datos): bool
    {
        $obligatorios = ['nombre', 'descripcion'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
        }

        // El id es solo un placeholder: el INSERT del repositorio no lo
        // envía y la BD lo asigna.
        $linea = new LineaInvestigacion(
            0,
            (string) $datos['nombre'],
            (string) $datos['descripcion']
        );

        return $this->repositorio->crear($linea);
    }

    // -----------------------------------------------------------------------
    // actualizar
    // A este método llegan tanto PUT como PATCH.
    // -----------------------------------------------------------------------
    public function actualizar(int $id, array $datos): int
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se enviaron campos para actualizar.'
            );
        }

        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorId($id);

        return $this->repositorio->actualizar($id, $datos);
    }

    // -----------------------------------------------------------------------
    // eliminar
    // -----------------------------------------------------------------------
    public function eliminar(int $id): int
    {
        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorId($id);

        return $this->repositorio->eliminar($id);
    }
}