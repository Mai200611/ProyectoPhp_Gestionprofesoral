<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioBeca.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';

/**
 * ControladorBeca.php
 *
 * Frontera HTTP de `beca`.
 *
 * La PK real es `estudios` SOLA (5_data_model.md §3.16). Patrón de ficha
 * con llave que manda el cliente:
 *   - `POST` devuelve `{estado, mensaje}` SIN id (no hay id autogenerado).
 *   - `GET/{estudios}`, `PUT/{estudios}`, `PATCH/{estudios}`,
 *     `DELETE/{estudios}` reciben el `estudios` en la ruta.
 */
class ControladorBeca
{
    private IServicioBeca $servicio;

    public function __construct(IServicioBeca $servicio)
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
            if (isset($parametrosRuta['estudios'])) {
                $estudios = $this->leerEntero($parametrosRuta['estudios'], 'estudios');
                $beca = $this->servicio->obtenerPorId($estudios);
                $this->responder(200, $beca);
                return;
            }

            $limite = $this->leerLimite($parametrosQuery);
            $becas = $this->servicio->listar($limite);
            if ($becas === []) {
                http_response_code(204);
                return;
            }
            $this->responder(200, [
                'tabla' => 'beca',
                'limite' => $limite,
                'total' => count($becas),
                'datos' => $becas,
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
                'mensaje' => 'Beca creada exitosamente.',
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
            $estudios = $this->leerEntero($parametrosRuta['estudios'], 'estudios');
            $this->validarCuerpoCompleto($body);
            $filas = $this->servicio->reemplazar($estudios, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Beca reemplazada exitosamente.',
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
            $estudios = $this->leerEntero($parametrosRuta['estudios'], 'estudios');
            $filas = $this->servicio->actualizarParcial($estudios, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Beca actualizada exitosamente.',
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
            $estudios = $this->leerEntero($parametrosRuta['estudios'], 'estudios');
            $filas = $this->servicio->eliminar($estudios);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Beca eliminada exitosamente.',
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
        foreach (['estudios', 'tipo', 'institucion', 'fecha_inicio'] as $campo) {
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