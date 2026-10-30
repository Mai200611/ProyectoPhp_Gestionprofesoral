<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioEvaluacionDocente.php';
require_once __DIR__ . '/../repositorios/IRepositorioEvaluacionDocente.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioEvaluacionDocente.php
 *
 * Reglas de negocio de `evaluacion_docente`.
 *
 * Mismo patrón que ServicioEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 *   - `listar()` valida que el límite sea positivo.
 *   - Los métodos por id lanzan NoEncontradoExcepcion si no existe.
 *   - `crear()` devuelve el id que asignó la base.
 */
class ServicioEvaluacionDocente implements IServicioEvaluacionDocente
{
    private IRepositorioEvaluacionDocente $repositorio;

    public function __construct(IRepositorioEvaluacionDocente $repositorio)
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
    public function obtenerPorId(int $id): array
    {
        $evaluacion = $this->repositorio->obtenerPorId($id);
        if ($evaluacion === null) {
            throw new NoEncontradoExcepcion('La evaluación solicitada no existe.');
        }
        return $evaluacion;
    }

    public function crear(array $datos): int
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('La evaluación solicitada no existe.');
        }
        return $this->repositorio->reemplazar($id, $datos);
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('La evaluación solicitada no existe.');
        }
        return $this->repositorio->actualizarParcial($id, $datos);
    }

    public function eliminar(int $id): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('La evaluación solicitada no existe.');
        }
        return $this->repositorio->eliminar($id);
    }
}