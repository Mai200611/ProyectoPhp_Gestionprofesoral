<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioExperiencia.php';
require_once __DIR__ . '/../repositorios/IRepositorioExperiencia.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioExperiencia.php
 *
 * Reglas de negocio de la tabla `experiecia` (errata histórica en la base;
 * la clase y el servicio se llaman `Experiencia` en PascalCase correcto,
 * 3_plan §6).
 *
 * Mismo patrón que ServicioEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 */
class ServicioExperiencia implements IServicioExperiencia
{
    private IRepositorioExperiencia $repositorio;

    public function __construct(IRepositorioExperiencia $repositorio)
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
        $experiencia = $this->repositorio->obtenerPorId($id);
        if ($experiencia === null) {
            throw new NoEncontradoExcepcion('La experiencia solicitada no existe.');
        }
        return $experiencia;
    }

    public function crear(array $datos): int
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('La experiencia solicitada no existe.');
        }
        return $this->repositorio->reemplazar($id, $datos);
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('La experiencia solicitada no existe.');
        }
        return $this->repositorio->actualizarParcial($id, $datos);
    }

    public function eliminar(int $id): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('La experiencia solicitada no existe.');
        }
        return $this->repositorio->eliminar($id);
    }
}