<?php
declare(strict_types=1);

require_once __DIR__ . '/IServicioDocente.php';
require_once __DIR__ . '/../repositorios/IRepositorioDocente.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ServicioDocente.php
 *
 * Reglas de negocio del docente (maestro-detalle).
 *
 * Este servicio NO conoce HTTP ni MariaDB: recibe datos del controlador,
 * valida las reglas del negocio, y delega en el repositorio a través de su
 * interfaz. (RNF1: capas estrictas.)
 *
 * Tiene DOS cosas que ningún otro servicio de la versión tiene:
 *
 *   1. `crearCompleto()` y `reemplazar()` reciben las LISTAS anidadas
 *      (estudios, intereses, redes) y las validan una por una. Un mensaje
 *      como «el detalle es inválido» no sirve: hay que decir CUÁL elemento
 *      y POR QUÉ, numerado desde 1 (3_plan §4.6).
 *
 *   2. NO tiene `actualizarParcial()`. El docente se reemplaza entero (RF12).
 *      Que el método no exista aquí es la primera línea de defensa: aunque
 *      alguien escribiera el controlador, no tendría a quién llamar.
 */
class ServicioDocente implements IServicioDocente
{
    private IRepositorioDocente $repositorio;

    public function __construct(IRepositorioDocente $repositorio)
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
    public function obtenerPorCedula(string $cedula): array
    {
        $docente = $this->repositorio->obtenerPorCedula($cedula);
        if ($docente === null) {
            throw new NoEncontradoExcepcion('El docente solicitado no existe.');
        }
        return $docente;
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerCompleto(string $cedula): array
    {
        $docente = $this->repositorio->obtenerCompleto($cedula);
        if ($docente === null) {
            throw new NoEncontradoExcepcion('El docente solicitado no existe.');
        }
        return $docente;
    }

    /**
     * Crea un docente con su detalle anidado.
     * Valida el encabezado y cada lista; los errores se numeran desde 1.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed> Docente completo recién creado.
     */
    public function crearCompleto(array $datos): array
    {
        $this->validarEncabezado($datos);
        $this->validarListas($datos);

        return $this->repositorio->crearCompleto($datos);
    }

    /**
     * Reemplaza un docente entero.
     *
     * @param array<string, mixed> $datos
     */
    public function reemplazar(string $cedula, array $datos): int
    {
        // Verificamos existencia explícitamente para poder dar 404 antes de
        // intentar la escritura, que es lo que el PUT de la spec pide.
        if ($this->repositorio->obtenerPorCedula($cedula) === null) {
            throw new NoEncontradoExcepcion('El docente solicitado no existe.');
        }

        $this->validarEncabezado($datos);
        $this->validarListas($datos);

        return $this->repositorio->reemplazar($cedula, $datos);
    }

    public function eliminar(string $cedula): int
    {
        if ($this->repositorio->obtenerPorCedula($cedula) === null) {
            throw new NoEncontradoExcepcion('El docente solicitado no existe.');
        }
        return $this->repositorio->eliminar($cedula);
    }

    // -----------------------------------------------------------------
    // Validaciones del maestro-detalle (3_plan §4.6)
    // -----------------------------------------------------------------

    /**
     * Valida los campos obligatorios del encabezado del docente.
     *
     * @param array<string, mixed> $datos
     */
    private function validarEncabezado(array $datos): void
    {
        $errores = [];

        if (!isset($datos['cedula']) || !is_string($datos['cedula']) || trim($datos['cedula']) === '') {
            $errores[] = 'El campo «cédula» es obligatorio y debe ser texto.';
        }
        if (!isset($datos['nombres']) || !is_string($datos['nombres']) || trim($datos['nombres']) === '') {
            $errores[] = 'El campo «nombres» es obligatorio y debe ser texto.';
        }
        if (!isset($datos['apellidos']) || !is_string($datos['apellidos']) || trim($datos['apellidos']) === '') {
            $errores[] = 'El campo «apellidos» es obligatorio y debe ser texto.';
        }
        if (!isset($datos['linea_investigacion_principal'])) {
            $errores[] = 'El campo «línea de investigación principal» es obligatorio.';
        } elseif (!is_int($datos['linea_investigacion_principal']) && !ctype_digit((string) $datos['linea_investigacion_principal'])) {
            $errores[] = 'El campo «línea de investigación principal» debe ser un número entero.';
        }

        if ($errores !== []) {
            throw new InvalidArgumentException(implode(' ', $errores));
        }
    }

    /**
     * Valida cada lista anidada (estudios, intereses, redes).
     * Los errores se numeran desde 1: el elemento 0 no existe para quien
     * está mirando la pantalla (3_plan §4.6).
     *
     * @param array<string, mixed> $datos
     */
    private function validarListas(array $datos): void
    {
        $errores = [];

        if (isset($datos['estudios'])) {
            if (!is_array($datos['estudios'])) {
                $errores[] = 'El campo «estudios» debe ser una lista.';
            } else {
                foreach ($datos['estudios'] as $indice => $estudio) {
                    $numero = $indice + 1;
                    if (!is_array($estudio)) {
                        $errores[] = "Estudio $numero: debe ser un objeto.";
                        continue;
                    }
                    if (!isset($estudio['titulo']) || !is_string($estudio['titulo']) || trim($estudio['titulo']) === '') {
                        $errores[] = "Estudio $numero: falta el campo «título».";
                    }
                    if (!isset($estudio['universidad']) || !is_string($estudio['universidad']) || trim($estudio['universidad']) === '') {
                        $errores[] = "Estudio $numero: falta el campo «universidad».";
                    }
                    if (!isset($estudio['tipo']) || !is_string($estudio['tipo']) || trim($estudio['tipo']) === '') {
                        $errores[] = "Estudio $numero: falta el campo «tipo».";
                    }
                }
            }
        }

        if (isset($datos['intereses'])) {
            if (!is_array($datos['intereses'])) {
                $errores[] = 'El campo «intereses» debe ser una lista.';
            } else {
                foreach ($datos['intereses'] as $indice => $interes) {
                    $numero = $indice + 1;
                    if (!is_array($interes)) {
                        $errores[] = "Interés $numero: debe ser un objeto.";
                        continue;
                    }
                    if (!isset($interes['termino']) || !is_string($interes['termino']) || trim($interes['termino']) === '') {
                        $errores[] = "Interés $numero: falta el campo «término».";
                    }
                }
            }
        }

        if (isset($datos['redes'])) {
            if (!is_array($datos['redes'])) {
                $errores[] = 'El campo «redes» debe ser una lista.';
            } else {
                foreach ($datos['redes'] as $indice => $red) {
                    $numero = $indice + 1;
                    if (!is_array($red)) {
                        $errores[] = "Red $numero: debe ser un objeto.";
                        continue;
                    }
                    if (!isset($red['idr'])) {
                        $errores[] = "Red $numero: falta el campo «idr».";
                    } elseif (!is_int($red['idr']) && !ctype_digit((string) $red['idr'])) {
                        $errores[] = "Red $numero: «idr» debe ser un número entero.";
                    }
                }
            }
        }

        if ($errores !== []) {
            throw new InvalidArgumentException(implode(' ', $errores));
        }
    }
}