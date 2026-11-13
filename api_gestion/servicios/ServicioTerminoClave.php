<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioTerminoClave.php';
require_once __DIR__ . '/../repositorios/IRepositorioTerminoClave.php';
require_once __DIR__ . '/../modelos/TerminoClave.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la IMPLEMENTACIÓN del servicio de `termino_clave`: la capa donde
 *   vive la lógica de negocio de este recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa del medio. El controlador le pasa los datos ya validados
 *   en la frontera HTTP y el servicio decide qué hacer con ellos. El
 *   servicio, a su vez, habla con el repositorio para llegar a la base.
 *
 *   Depende de la INTERFAZ IRepositorioTerminoClave, no de la clase
 *   concreta MariaDB, así que se puede probar con un repositorio falso.
 *
 * ¿Qué reglas de negocio valida?
 *   1. `limite` > 0.
 *   2. Al crear, ni `termino` ni `termino_ingles` pueden venir vacíos.
 *   3. Al actualizar, si no llega ningún campo, es error de negocio → 400.
 *   4. Si el término no existe al obtener/actualizar/eliminar → 404.
 *
 * Nota sobre el identificador:
 *   En `termino_clave` la clave primaria es `termino` (VARCHAR), no `id`.
 *   Por eso todos los métodos trabajan con $termino como string.
 * ---------------------------------------------------------------------------
 */
class ServicioTerminoClave implements IServicioTerminoClave
{
    private IRepositorioTerminoClave $repositorio;

    public function __construct(IRepositorioTerminoClave $repositorio)
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
    // obtenerPorTermino
    // -----------------------------------------------------------------------
    public function obtenerPorTermino(string $termino): TerminoClave
    {
        $encontrado = $this->repositorio->obtenerPorTermino($termino);

        if ($encontrado === null) {
            throw new NoEncontradoExcepcion(
                'Término clave no encontrado.'
            );
        }

        return $encontrado;
    }

    // -----------------------------------------------------------------------
    // crear
    // El array $datos ya viene filtrado y validado por el controlador.
    // Aquí hacemos la segunda línea de defensa: campos obligatorios.
    // -----------------------------------------------------------------------
    public function crear(array $datos): bool
    {
        $obligatorios = ['termino', 'termino_ingles'];
        foreach ($obligatorios as $campo) {
            if (!isset($datos[$campo]) || trim((string) $datos[$campo]) === '') {
                throw new InvalidArgumentException(
                    "El campo {$campo} es obligatorio."
                );
            }
        }

        $termino = new TerminoClave(
            (string) $datos['termino'],
            (string) $datos['termino_ingles']
        );

        return $this->repositorio->crear($termino);
    }

    // -----------------------------------------------------------------------
    // actualizar
    // A este método llegan tanto PUT como PATCH. La diferencia la hace el
    // controlador, que decide qué campos incluir en $datos.
    // -----------------------------------------------------------------------
    public function actualizar(string $termino, array $datos): int
    {
        if ($datos === []) {
            throw new InvalidArgumentException(
                'No se enviaron campos para actualizar.'
            );
        }

        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorTermino($termino);

        return $this->repositorio->actualizar($termino, $datos);
    }

    // -----------------------------------------------------------------------
    // eliminar
    // -----------------------------------------------------------------------
    public function eliminar(string $termino): int
    {
        // Comprobamos que exista (lanza 404 si no).
        $this->obtenerPorTermino($termino);

        return $this->repositorio->eliminar($termino);
    }
}