<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioApoyoProfesoral.php';
require_once __DIR__ . '/../repositorios/IRepositorioApoyoProfesoral.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioApoyoProfesoral.php
 *
 * Reglas de negocio de `apoyo_profesoral`.
 *
 * La PK real es `estudios` SOLA (5_data_model.md §3.15), no compuesta.
 * Patrón de ficha con llave que manda el cliente:
 *   - `crear()` devuelve `bool` (no hay id autogenerado).
 *   - Los métodos por `estudios` lanzan NoEncontradoExcepcion si no existe.
 */
class ServicioApoyoProfesoral implements IServicioApoyoProfesoral
{
    private IRepositorioApoyoProfesoral $repositorio;

    public function __construct(IRepositorioApoyoProfesoral $repositorio)
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
    public function obtenerPorId(int $estudios): array
    {
        $apoyo = $this->repositorio->obtenerPorId($estudios);
        if ($apoyo === null) {
            throw new NoEncontradoExcepcion('El apoyo profesoral solicitado no existe.');
        }
        return $apoyo;
    }

    public function crear(array $datos): bool
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $estudios, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($estudios) === null) {
            throw new NoEncontradoExcepcion('El apoyo profesoral solicitado no existe.');
        }
        return $this->repositorio->reemplazar($estudios, $datos);
    }

    public function actualizarParcial(int $estudios, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($estudios) === null) {
            throw new NoEncontradoExcepcion('El apoyo profesoral solicitado no existe.');
        }
        return $this->repositorio->actualizarParcial($estudios, $datos);
    }

    public function eliminar(int $estudios): int
    {
        if ($this->repositorio->obtenerPorId($estudios) === null) {
            throw new NoEncontradoExcepcion('El apoyo profesoral solicitado no existe.');
        }
        return $this->repositorio->eliminar($estudios);
    }
}