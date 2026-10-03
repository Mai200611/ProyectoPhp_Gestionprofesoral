<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioPrograma.php';
require_once __DIR__ . '/../repositorios/IRepositorioPrograma.php';
require_once __DIR__ . '/../modelos/Programa.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN del servicio de `programa`: la capa donde vive
 *   la lógica de negocio de este recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa del medio. El controlador le pasa los datos ya validados
 *   en la frontera HTTP y el servicio decide qué hacer con ellos. El
 *   servicio, a su vez, habla con el repositorio para llegar a la base.
 *
 *   Depende de la INTERFAZ IRepositorioPrograma, no de la clase concreta
 *   MariaDB. Esta es la interfaz que la prueba de capas (prueba_capas.php)
 *   implementará con un repositorio falso en memoria para demostrar el
 *   criterio 6 de la spec: el servicio funciona sin MariaDB.
 *
 * ¿Qué reglas de negocio valida?
 *   1. `limite` > 0.
 *   2. Al crear, los campos obligatorios no pueden venir vacíos.
 *   3. Al actualizar, si no llega ningún campo, error de negocio → 400.
 *   4. Si el programa no existe al obtener/actualizar/eliminar → 404.
 *
 * Nota sobre el id:
 *   En `programa` el id NO es autoincremental (el esquema lo exige en el
 *   INSERT: la columna no tiene AUTO_INCREMENT). El cliente lo envía en
 *   el POST. Por eso es obligatorio al crear.
 *
 * Nota sobre los campos:
 *   `fecha_cierre` admite NULL. En el array $datos puede llegar como
 *   null (no como string vacío) — esa decisión la toma el controlador
 *   al leer el body JSON.
 * ---------------------------------------------------------------------------
 */
class ServicioPrograma implements IServicioPrograma
{
    private IRepositorioPrograma $repositorio;

    public function __construct(IRepositorioPrograma $repositorio)
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
    public function obtenerPorId(int $id): Programa
    {
        $programa = $this->repositorio->obtenerPorId($id);

        if ($programa === null) {
            throw new NoEncontradoExcepcion(
                'Programa no encontrado.'
            );
        }

        return $programa;
    }

    // -----------------------------------------------------------------------
    // crear
    // El id SÍ es obligatorio en este recurso (lo envía el cliente).
    // Los demás campos también, salvo fecha_cierre, que puede ser null.
    //
    // Detalle sobre fecha_cierre: si viene null, se acepta; si viene
    // como string, también. El controlador ya decidió esto al leer el
    // body, así que aquí solo copiamos el valor tal cual.
    // -----------------------------------------------------------------------
    public function crear(array $datos): bool
    {
        $obligatorios = [
            'id',
            'nombre',
            'tipo',
            'nivel',
            'fecha_creacion',
            'numero_cohortes',
            'cant_graduados',
            'fecha_actualizacion',
            'ciudad',
            'facultad',
        ];
        foreach ($obligatorios as $campo) {
            if (!array_key_exists($campo, $datos)) {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
            // Los campos que no admiten null tienen que venir con contenido.
            if ($datos[$campo] === null || trim((string) $datos[$campo]) === '') {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
        }

        // fecha_cierre es opcional y admite null. Si no vino, se asume null.
        $fechaCierre = array_key_exists('fecha_cierre', $datos)
            ? $datos['fecha_cierre']
            : null;

        $programa = new Programa(
            (int)    $datos['id'],
            (string) $datos['nombre'],
            (string) $datos['tipo'],
            (string) $datos['nivel'],
            (string) $datos['fecha_creacion'],
            $fechaCierre === null ? null : (string) $fechaCierre,
            (string) $datos['numero_cohortes'],
            (string) $datos['cant_graduados'],
            (string) $datos['fecha_actualizacion'],
            (string) $datos['ciudad'],
            (int)    $datos['facultad']
        );

        return $this->repositorio->crear($programa);
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