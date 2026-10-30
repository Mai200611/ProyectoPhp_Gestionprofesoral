<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioDocenteDepartamento.php';
require_once __DIR__ . '/../repositorios/IRepositorioDocenteDepartamento.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioDocenteDepartamento.php
 *
 * Reglas de negocio de la tabla puente `docente_departamento` (PK compuesta
 * `(docente, departamento)`).
 *
 * Es el PATRÓN de las puentes con PK compuesta:
 *   - `listar()` valida el límite.
 *   - `obtenerPorClave()` recibe las DOS llaves y lanza
 *     NoEncontradoExcepcion si la pareja no existe.
 *   - `crear()` recibe las dos llaves dentro de $datos y devuelve `bool`
 *     (no hay id autogenerado que devolver).
 *   - `reemplazar()`, `actualizarParcial()` y `eliminar()` reciben las dos
 *     llaves por separado y verifican existencia antes de escribir.
 */
class ServicioDocenteDepartamento implements IServicioDocenteDepartamento
{
    private IRepositorioDocenteDepartamento $repositorio;

    public function __construct(IRepositorioDocenteDepartamento $repositorio)
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
    public function obtenerPorClave(string $docente, int $departamento): array
    {
        $vinculacion = $this->repositorio->obtenerPorClave($docente, $departamento);
        if ($vinculacion === null) {
            throw new NoEncontradoExcepcion('La vinculación solicitada no existe.');
        }
        return $vinculacion;
    }

    public function crear(array $datos): bool
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(string $docente, int $departamento, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($docente, $departamento) === null) {
            throw new NoEncontradoExcepcion('La vinculación solicitada no existe.');
        }
        return $this->repositorio->reemplazar($docente, $departamento, $datos);
    }

    public function actualizarParcial(string $docente, int $departamento, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($docente, $departamento) === null) {
            throw new NoEncontradoExcepcion('La vinculación solicitada no existe.');
        }
        return $this->repositorio->actualizarParcial($docente, $departamento, $datos);
    }

    public function eliminar(string $docente, int $departamento): int
    {
        if ($this->repositorio->obtenerPorClave($docente, $departamento) === null) {
            throw new NoEncontradoExcepcion('La vinculación solicitada no existe.');
        }
        return $this->repositorio->eliminar($docente, $departamento);
    }
}