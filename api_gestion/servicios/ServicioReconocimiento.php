<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioReconocimiento.php';
require_once __DIR__ . '/../repositorios/IRepositorioReconocimiento.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioReconocimiento.php
 *
 * Reglas de negocio de `reconocimiento`.
 *
 * Mismo patrón que ServicioEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 *   - `listar()` valida que el límite sea positivo.
 *   - Los métodos por id lanzan NoEncontradoExcepcion si no existe.
 *   - `crear()` devuelve el id que asignó la base.
 */
class ServicioReconocimiento implements IServicioReconocimiento
{
    private IRepositorioReconocimiento $repositorio;

    public function __construct(IRepositorioReconocimiento $repositorio)
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
        $reconocimiento = $this->repositorio->obtenerPorId($id);
        if ($reconocimiento === null) {
            throw new NoEncontradoExcepcion('El reconocimiento solicitado no existe.');
        }
        return $reconocimiento;
    }

    public function crear(array $datos): int
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('El reconocimiento solicitado no existe.');
        }
        return $this->repositorio->reemplazar($id, $datos);
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('El reconocimiento solicitado no existe.');
        }
        return $this->repositorio->actualizarParcial($id, $datos);
    }

    public function eliminar(int $id): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('El reconocimiento solicitado no existe.');
        }
        return $this->repositorio->eliminar($id);
    }
}