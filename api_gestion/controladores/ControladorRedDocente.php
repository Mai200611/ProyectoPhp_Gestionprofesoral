<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioRedDocente.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';

/**
 * ControladorRedDocente.php
 *
 * Frontera HTTP de la tabla puente `red_docente`
 * (PK compuesta `(red, docente)`; `red` es int, `docente` es string).
 *
 * Mismo patrón que ControladorDocenteDepartamento. Ojo con los tipos:
 * en la ruta, `red` se convierte a int; `docente` viaja tal cual.
 */
class ControladorRedDocente
{
    private IServicioRedDocente $servicio;

    public function __construct(IServicioRedDocente $servicio)
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
            if (isset($parametrosRuta['red'], $parametrosRuta['docente'])) {
                $red = $this->leerEntero($parametrosRuta['red'], 'red');
                $vinculo = $this->servicio->obtenerPorClave($red, $parametrosRuta['docente']);
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
                'tabla' => 'red_docente',
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
                'mensaje' => 'Vínculo red-docente creado exitosamente.',
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
            $red = $this->leerEntero($parametrosRuta['red'], 'red');
            $this->validarCuerpoCompleto($body);
            $filas = $this->servicio->reemplazar($red, $parametrosRuta['docente'], $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vínculo red-docente reemplazado exitosamente.',
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
            $red = $this->leerEntero($parametrosRuta['red'], 'red');
            $filas = $this->servicio->actualizarParcial($red, $parametrosRuta['docente'], $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vínculo red-docente actualizado exitosamente.',
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
            $red = $this->leerEntero($parametrosRuta['red'], 'red');
            $filas = $this->servicio->eliminar($red, $parametrosRuta['docente']);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Vínculo red-docente eliminado exitosamente.',
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
        foreach (['red', 'docente', 'fecha_inicio'] as $campo) {
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