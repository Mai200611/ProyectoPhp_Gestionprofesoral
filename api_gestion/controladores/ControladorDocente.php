<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioDocente.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';

/**
 * ControladorDocente.php
 *
 * Frontera HTTP del recurso `docente` (maestro-detalle).
 *
 * Sus responsabilidades:
 *   1. Validar la FORMA del body antes de llamar al servicio (422).
 *   2. Traducir las excepciones del servicio a códigos HTTP y al sobre
 *      uniforme `{estado, mensaje, detalle}` / `{estado, mensaje, errores}`.
 *   3. NUNCA tocar SQL ni conocer el motor (RNF1).
 *
 * Lo que este controlador NO tiene, a propósito (RF12):
 *   - Un método `actualizarParcial()`. El docente se reemplaza entero, y
 *     el enrutador no registra PATCH para este recurso. Si llega un PATCH,
 *     cae al 405 por defecto del enrutador.
 */
class ControladorDocente
{
    private IServicioDocente $servicio;

    public function __construct(IServicioDocente $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * GET /api/docente?limite=N
     * GET /api/docente/{cedula}
     * GET /api/docente/{cedula}/completo
     *
     * @param array<string, string> $parametrosRuta
     * @param array<string, string> $parametrosQuery
     */
    public function manejarGet(array $parametrosRuta, array $parametrosQuery): void
    {
        try {
            // GET /api/docente/{cedula}/completo
            if (isset($parametrosRuta['cedula'], $parametrosRuta['completo'])) {
                $docente = $this->servicio->obtenerCompleto($parametrosRuta['cedula']);
                $this->responder(200, $docente);
                return;
            }

            // GET /api/docente/{cedula}
            if (isset($parametrosRuta['cedula'])) {
                $docente = $this->servicio->obtenerPorCedula($parametrosRuta['cedula']);
                $this->responder(200, $docente);
                return;
            }

            // GET /api/docente?limite=N
            $limite = $this->leerLimite($parametrosQuery);
            $docentes = $this->servicio->listar($limite);

            if ($docentes === []) {
                http_response_code(204);
                return;
            }

            $this->responder(200, [
                'tabla' => 'docente',
                'limite' => $limite,
                'total' => count($docentes),
                'datos' => $docentes,
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
     * POST /api/docente
     * Crea el docente con su detalle anidado y devuelve el docente completo.
     *
     * @param array<string, mixed> $body
     */
    public function manejarPost(array $body): void
    {
        try {
            $creado = $this->servicio->crearCompleto($body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Docente creado exitosamente.',
                'datos' => $creado,
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
     * PUT /api/docente/{cedula}
     * Reemplaza el docente entero (encabezado + detalle).
     *
     * @param array<string, string> $parametrosRuta
     * @param array<string, mixed>  $body
     */
    public function manejarPut(array $parametrosRuta, array $body): void
    {
        try {
            $filas = $this->servicio->reemplazar($parametrosRuta['cedula'], $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Docente reemplazado exitosamente.',
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
     * DELETE /api/docente/{cedula}
     * La API intenta el borrado; si tiene dependencias, la base rechaza
     * y el repositorio lo traduce a ConflictoDeIntegridadExcepcion (409).
     *
     * @param array<string, string> $parametrosRuta
     */
    public function manejarDelete(array $parametrosRuta): void
    {
        try {
            $filas = $this->servicio->eliminar($parametrosRuta['cedula']);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Docente eliminado exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            $this->responderError(404, $e->getMessage());
        } catch (ConflictoDeIntegridadExcepcion $e) {
            $this->responderError(409, $e->getMessage(), $e->obtenerDetalle());
        } catch (Throwable $e) {
            $this->responderError(500, 'Ocurrió un error inesperado.', $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Auxiliares privados
    // -----------------------------------------------------------------

    /**
     * @param array<string, string> $parametrosQuery
     */
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

    /**
     * Convierte un mensaje de validación concatenado en la lista de errores
     * que el contrato espera en 422.
     */
    private function responderErroresValidacion(string $mensaje): void
    {
        // El servicio concatena con espacio, pero algunas frases llevan
        // puntos. Partimos por «. » sin comerse el punto final.
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