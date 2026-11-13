<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioRedDocente.php';
require_once __DIR__ . '/../repositorios/IRepositorioRedDocente.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioRedDocente.php
 *
 * Reglas de negocio de la tabla puente `red_docente`
 * (PK compuesta `(red, docente)`).
 *
 * Mismo patrón que ServicioDocenteDepartamento: las dos llaves viajan
 * juntas, `crear()` devuelve `bool`, y los métodos por pareja lanzan
 * NoEncontradoExcepcion si no existe.
 *
 * Ojo con los tipos de las llaves: `red` es `int` (id de la red) y
 * `docente` es `string` (cédula). No se confunden con los de otras puentes.
 */
class ServicioRedDocente implements IServicioRedDocente
{
    private IRepositorioRedDocente $repositorio;

    public function __construct(IRepositorioRedDocente $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $limite): array
    {
        if ($limite <= 0) {
            throw new InvalidArgumentException('El límite debe ser un entero mayor que cero.');
        }
        return $this->repositorio->obtenerTodos($limite);
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerPorClave(int $red, string $docente): array
    {
        $vinculo = $this->repositorio->obtenerPorClave($red, $docente);
        if ($vinculo === null) {
            throw new NoEncontradoExcepcion('El vínculo red-docente solicitado no existe.');
        }
        return $vinculo;
    }

    public function crear(array $datos): bool
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $red, string $docente, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($red, $docente) === null) {
            throw new NoEncontradoExcepcion('El vínculo red-docente solicitado no existe.');
        }
        return $this->repositorio->reemplazar($red, $docente, $datos);
    }

    public function actualizarParcial(int $red, string $docente, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($red, $docente) === null) {
            throw new NoEncontradoExcepcion('El vínculo red-docente solicitado no existe.');
        }
        return $this->repositorio->actualizarParcial($red, $docente, $datos);
    }

    public function eliminar(int $red, string $docente): int
    {
        if ($this->repositorio->obtenerPorClave($red, $docente) === null) {
            throw new NoEncontradoExcepcion('El vínculo red-docente solicitado no existe.');
        }
        return $this->repositorio->eliminar($red, $docente);
    }
}