<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioInteresesFuturos.php';
require_once __DIR__ . '/../repositorios/IRepositorioInteresesFuturos.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioInteresesFuturos.php
 *
 * Reglas de negocio de la tabla puente `intereses_futuros`
 * (PK compuesta `(docente, termino_clave)`).
 *
 * Mismo patrón que ServicioDocenteDepartamento: las dos llaves viajan
 * juntas, `crear()` devuelve `bool`, y los métodos por pareja lanzan
 * NoEncontradoExcepcion si no existe.
 */
class ServicioInteresesFuturos implements IServicioInteresesFuturos
{
    private IRepositorioInteresesFuturos $repositorio;

    public function __construct(IRepositorioInteresesFuturos $repositorio)
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
    public function obtenerPorClave(string $docente, string $terminoClave): array
    {
        $interes = $this->repositorio->obtenerPorClave($docente, $terminoClave);
        if ($interes === null) {
            throw new NoEncontradoExcepcion('El interés solicitado no existe.');
        }
        return $interes;
    }

    public function crear(array $datos): bool
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(string $docente, string $terminoClave, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($docente, $terminoClave) === null) {
            throw new NoEncontradoExcepcion('El interés solicitado no existe.');
        }
        return $this->repositorio->reemplazar($docente, $terminoClave, $datos);
    }

    public function actualizarParcial(string $docente, string $terminoClave, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($docente, $terminoClave) === null) {
            throw new NoEncontradoExcepcion('El interés solicitado no existe.');
        }
        return $this->repositorio->actualizarParcial($docente, $terminoClave, $datos);
    }

    public function eliminar(string $docente, string $terminoClave): int
    {
        if ($this->repositorio->obtenerPorClave($docente, $terminoClave) === null) {
            throw new NoEncontradoExcepcion('El interés solicitado no existe.');
        }
        return $this->repositorio->eliminar($docente, $terminoClave);
    }
}