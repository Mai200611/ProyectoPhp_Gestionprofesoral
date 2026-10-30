<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioEstudiosRealizados.php';
require_once __DIR__ . '/../repositorios/IRepositorioEstudiosRealizados.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioEstudiosRealizados.php
 *
 * Reglas de negocio de `estudios_realizados`.
 *
 * Este servicio es el PATRÓN de las fichas con `id` autogenerado:
 *   - `listar()` valida que el límite sea positivo.
 *   - `obtenerPorId()` lanza NoEncontradoExcepcion si el repositorio no
 *     devuelve la fila.
 *   - `crear()` devuelve el id que asignó la base.
 *   - `reemplazar()` y `actualizarParcial()` verifican existencia antes de
 *     escribir, para poder dar 404 explícito (lo pide 6_contracts.md).
 *
 * El servicio NO conoce HTTP ni MariaDB: recibe datos del controlador,
 * aplica las reglas del negocio y delega en el repositorio a través de su
 * interfaz (RNF1).
 */
class ServicioEstudiosRealizados implements IServicioEstudiosRealizados
{
    private IRepositorioEstudiosRealizados $repositorio;

    public function __construct(IRepositorioEstudiosRealizados $repositorio)
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
        $estudio = $this->repositorio->obtenerPorId($id);
        if ($estudio === null) {
            throw new NoEncontradoExcepcion('El estudio solicitado no existe.');
        }
        return $estudio;
    }

    public function crear(array $datos): int
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('El estudio solicitado no existe.');
        }
        return $this->repositorio->reemplazar($id, $datos);
    }

    public function actualizarParcial(int $id, array $datos): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('El estudio solicitado no existe.');
        }
        return $this->repositorio->actualizarParcial($id, $datos);
    }

    public function eliminar(int $id): int
    {
        if ($this->repositorio->obtenerPorId($id) === null) {
            throw new NoEncontradoExcepcion('El estudio solicitado no existe.');
        }
        return $this->repositorio->eliminar($id);
    }
}