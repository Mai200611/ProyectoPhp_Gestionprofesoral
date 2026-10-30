<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioDocenteDepartamento.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';

/**
 * ControladorDocenteDepartamento.php
 *
 * Frontera HTTP de la tabla puente `docente_departamento`
 * (PK compuesta `(docente, departamento)`).
 *
 * Es el PATRÓN de las puentes con PK compuesta:
 *   - La ruta del PUT/PATCH/DELETE lleva LAS DOS llaves:
 *     `/api/docente_departamento/{docente}/{departamento}`.
 *   - `manejarPost()` recibe las dos llaves dentro del body.
 *   - `manejarPost()` devuelve `{estado, mensaje}` sin id (no hay id que
 *     devolver).
 */
class ControladorDocenteDepartamento
{
    private IServicioDocenteDepartamento $servicio;

    public function __construct(IServicioDocenteDepartamento $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * @param array<string, string> $parametrosRuta
     * @param array<string, string> $parametrosQuery
     */
    public function manejarGet(array $parametrosRuta, array $parametrosQuery): void
    {
        try {
            if (isset($parametrosRuta['docente'], $parametrosRuta['departamento'])) {
                $departamento = $this->leerEntero($parametrosRuta['departamento'], 'departamento');
                $vinculo = $this->servicio->obtenerPorClave($parametrosRuta['docente'], $departamento);
                $this->responder(200, $vinculo);
                return;
            }

            $limite = $this->leerLimite($parametrosQuery);
            $vinculos = $this->servicio->listar($limite);
            if ($vinculos === []) {
                http_response_code(204);
                return;
            }
            $this->responder(200, [
                'tabla' => 'docente_departamento',
                'limite' => $limite,
                'total' => count($vinculos),
                'datos' => $vinculos,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responderError(400, $e->getMessage());
        } catch (NoEncontradoExcepcion $e) {
            $this->responderError(404, $e->getMessage());
        } catch (ConflictoDeIntegridadExcepcion $e) {
            $this->responderError(409, $e->getMessage(), $e->obtenerDetalle());
        } catch (Throwable $e) {
            $this->responderError(500, 'Ocurrió un error inesperado.', $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    public function manejarPost(array $body): void
    {
        try {
            $this->validarCuerpoCompleto($body);
            $this->servicio->crear($body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vinculación docente-departamento creada exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responderErroresValidacion($e->getMessage());
        } catch (ConflictoDeIntegridadExcepcion $e) {
            $this->responderError(409, $e->getMessage(), $e->obtenerDetalle());
        } catch (Throwable $e) {
            $this->responderError(500, 'Ocurrió un error inesperado.', $e->getMessage());
        }
    }

    /**
     * @param array<string, string> $parametrosRuta
     * @param array<string, mixed>  $body
     */
    public function manejarPut(array $parametrosRuta, array $body): void
    {
        try {
            $departamento = $this->leerEntero($parametrosRuta['departamento'], 'departamento');
            $this->validarCuerpoCompleto($body);
            $filas = $this->servicio->reemplazar($parametrosRuta['docente'], $departamento, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vinculación docente-departamento reemplazada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responderErroresValidacion($e->getMessage());
        } catch (NoEncontradoExcepcion $e) {
            $this->responderError(404, $e->getMessage());
        } catch (ConflictoDeIntegridadExcepcion $e) {
            $this->responderError(409, $e->getMessage(), $e->obtenerDetalle());
        } catch (Throwable $e) {
            $this->responderError(500, 'Ocurrió un error inesperado.', $e->getMessage());
        }
    }

    /**
     * @param array<string, string> $parametrosRuta
     * @param array<string, mixed>  $body
     */
    public function manejarPatch(array $parametrosRuta, array $body): void
    {
        try {
            if ($body === []) {
                throw new InvalidArgumentException('La petición parcial no incluye ningún campo.');
            }
            $departamento = $this->leerEntero($parametrosRuta['departamento'], 'departamento');
            $filas = $this->servicio->actualizarParcial($parametrosRuta['docente'], $departamento, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vinculación docente-departamento actualizada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responderError(400, $e->getMessage());
        } catch (NoEncontradoExcepcion $e) {
            $this->responderError(404, $e->getMessage());
        } catch (ConflictoDeIntegridadExcepcion $e) {
            $this->responderError(409, $e->getMessage(), $e->obtenerDetalle());
        } catch (Throwable $e) {
            $this->responderError(500, 'Ocurrió un error inesperado.', $e->getMessage());
        }
    }

    /**
     * @param array<string, string> $parametrosRuta
     */
    public function manejarDelete(array $parametrosRuta): void
    {
        try {
            $departamento = $this->leerEntero($parametrosRuta['departamento'], 'departamento');
            $filas = $this->servicio->eliminar($parametrosRuta['docente'], $departamento);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vinculación docente-departamento eliminada exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->responderError(400, $e->getMessage());
        } catch (NoEncontradoExcepcion $e) {
            $this->responderError(404, $e->getMessage());
        } catch (ConflictoDeIntegridadExcepcion $e) {
            $this->responderError(409, $e->getMessage(), $e->obtenerDetalle());
        } catch (Throwable $e) {
            $this->responderError(500, 'Ocurrió un error inesperado.', $e->getMessage());
        }
    }

    private function leerLimite(array $parametrosQuery): int
    {
        if (!isset($parametrosQuery['limite'])) {
            return 1000;
        }
        $valor = $parametrosQuery['limite'];
        if (!ctype_digit($valor)) {
            throw new InvalidArgumentException('El límite debe ser un número entero.');
        }
        $limite = (int) $valor;
        if ($limite <= 0) {
            throw new InvalidArgumentException('El límite debe ser un entero mayor que cero.');
        }
        return $limite;
    }

    private function leerEntero(string $valor, string $campo): int
    {
        if (!ctype_digit($valor)) {
            throw new InvalidArgumentException("El campo «{$campo}» debe ser un número entero.");
        }
        return (int) $valor;
    }

    private function validarCuerpoCompleto(array $body): void
    {
        $errores = [];
        foreach (['docente', 'departamento', 'dedicacion', 'modalidad', 'fecha_ingreso'] as $campo) {
            if (!isset($body[$campo]) || $body[$campo] === '') {
                $errores[] = "Falta el campo «{$campo}».";
            }
        }
        if ($errores !== []) {
            throw new InvalidArgumentException(implode(' ', $errores));
        }
    }

    private function responderErroresValidacion(string $mensaje): void
    {
        $errores = preg_split('/(?<=\.)\s+/', trim($mensaje)) ?: [$mensaje];
        $this->responder(422, [
            'estado' => 422,
            'mensaje' => 'La petición contiene datos inválidos.',
            'errores' => array_values(array_filter($errores, static fn($e) => $e !== '')),
        ]);
    }

    private function responder(int $codigo, mixed $cuerpo): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }

    private function responderError(int $codigo, string $mensaje, string $detalle = ''): void
    {
        $this->responder($codigo, [
            'estado' => $codigo,
            'mensaje' => $mensaje,
            'detalle' => $detalle,
        ]);
    }
}