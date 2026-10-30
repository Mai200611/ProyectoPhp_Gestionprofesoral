<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioReconocimiento.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';

/**
 * ControladorReconocimiento.php
 *
 * Frontera HTTP de `reconocimiento`.
 *
 * Mismo patrón que ControladorEstudiosRealizados: ficha hija con `id`
 * autogenerado por la base.
 */
class ControladorReconocimiento
{
    private IServicioReconocimiento $servicio;

    public function __construct(IServicioReconocimiento $servicio)
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
            if (isset($parametrosRuta['id'])) {
                $id = $this->leerIdDeRuta($parametrosRuta['id']);
                $reconocimiento = $this->servicio->obtenerPorId($id);
                $this->responder(200, $reconocimiento);
                return;
            }

            $limite = $this->leerLimite($parametrosQuery);
            $reconocimientos = $this->servicio->listar($limite);
            if ($reconocimientos === []) {
                http_response_code(204);
                return;
            }
            $this->responder(200, [
                'tabla' => 'reconocimiento',
                'limite' => $limite,
                'total' => count($reconocimientos),
                'datos' => $reconocimientos,
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
            $id = $this->servicio->crear($body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Reconocimiento creado exitosamente.',
                'id' => $id,
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
            $id = $this->leerIdDeRuta($parametrosRuta['id']);
            $this->validarCuerpoCompleto($body);
            $filas = $this->servicio->reemplazar($id, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Reconocimiento reemplazado exitosamente.',
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
            $id = $this->leerIdDeRuta($parametrosRuta['id']);
            $filas = $this->servicio->actualizarParcial($id, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Reconocimiento actualizado exitosamente.',
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
            $id = $this->leerIdDeRuta($parametrosRuta['id']);
            $filas = $this->servicio->eliminar($id);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Reconocimiento eliminado exitosamente.',
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

    private function leerIdDeRuta(string $valor): int
    {
        if (!ctype_digit($valor)) {
            throw new InvalidArgumentException('El id debe ser un número entero.');
        }
        return (int) $valor;
    }

    private function validarCuerpoCompleto(array $body): void
    {
        $errores = [];
        foreach (['tipo', 'fecha', 'institucion', 'nombre', 'ambito', 'docente'] as $campo) {
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