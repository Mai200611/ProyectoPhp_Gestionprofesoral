<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioEstudioAc.php';
require_once __DIR__ . '/../repositorios/IRepositorioEstudioAc.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioEstudioAc.php
 *
 * Reglas de negocio de la tabla puente `estudios_ac`
 * (PK compuesta `(estudio, area_conocimiento)`).
 *
 * Mismo patrón que ServicioDocenteDepartamento. Ojo con los tipos:
 * `estudio` es `int` (id de estudios_realizados) y `area_conocimiento` es
 * `string` (id legible del área).
 *
 * Nombres: la tabla se llama `estudios_ac` (con «s»); el recurso público es
 * `/api/estudio_ac` (sin «s», 6_contracts.md). El servicio y la interfaz
 * usan el singular del recurso (`ServicioEstudioAc`).
 */
class ServicioEstudioAc implements IServicioEstudioAc
{
    private IRepositorioEstudioAc $repositorio;

    public function __construct(IRepositorioEstudioAc $repositorio)
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
    public function obtenerPorClave(int $estudio, string $areaConocimiento): array
    {
        $vinculo = $this->repositorio->obtenerPorClave($estudio, $areaConocimiento);
        if ($vinculo === null) {
            throw new NoEncontradoExcepcion('El vínculo estudio-área solicitado no existe.');
        }
        return $vinculo;
    }

    public function crear(array $datos): bool
    {
        return $this->repositorio->crear($datos);
    }

    public function reemplazar(int $estudio, string $areaConocimiento, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($estudio, $areaConocimiento) === null) {
            throw new NoEncontradoExcepcion('El vínculo estudio-área solicitado no existe.');
        }
        return $this->repositorio->reemplazar($estudio, $areaConocimiento, $datos);
    }

    public function actualizarParcial(int $estudio, string $areaConocimiento, array $datos): int
    {
        if ($this->repositorio->obtenerPorClave($estudio, $areaConocimiento) === null) {
            throw new NoEncontradoExcepcion('El vínculo estudio-área solicitado no existe.');
        }
        return $this->repositorio->actualizarParcial($estudio, $areaConocimiento, $datos);
    }

    public function eliminar(int $estudio, string $areaConocimiento): int
    {
        if ($this->repositorio->obtenerPorClave($estudio, $areaConocimiento) === null) {
            throw new NoEncontradoExcepcion('El vínculo estudio-área solicitado no existe.');
        }
        return $this->repositorio->eliminar($estudio, $areaConocimiento);
    }
}