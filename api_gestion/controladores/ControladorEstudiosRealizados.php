<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioEstudiosRealizados.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';

/**
 * ControladorEstudiosRealizados.php
 *
 * Frontera HTTP de `estudios_realizados`.
 *
 * Es el PATRÓN de las fichas con `id` autogenerado:
 *   - `manejarGet()` resuelve listar / obtener por id.
 *   - `manejarPost()` NO valida el id (no viene, lo pone la base) y
 *     DEVUELVE el id que asignó la base (6_contracts §3, RF9).
 *   - `manejarPut()` exige todos los campos obligatorios; si falta uno,
 *     responde 422 con la lista de errores.
 *   - `manejarPatch()` responde 400 si el body viene vacío (regla de
 *     negocio: pedir una actualización sin decir qué actualizar).
 *   - `manejarDelete()` traduce el 409 de la base si el estudio tiene
 *     dependencias (apoyo, beca, estudio_ac).
 *
 * Lo que este controlador NO hace: tocar SQL ni hablar con el motor.
 */
class ControladorEstudiosRealizados
{
    private IServicioEstudiosRealizados $servicio;

    public function __construct(IServicioEstudiosRealizados $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * GET /api/estudios_realizados?limite=N
     * GET /api/estudios_realizados/{id}
     *
     * @param array<string, string> $parametrosRuta
     * @param array<string, string> $parametrosQuery
     */
    public function manejarGet(array $parametrosRuta, array $parametrosQuery): void
    {
        try {
            if (isset($parametrosRuta['id'])) {
                $id = $this->leerIdDeRuta($parametrosRuta['id']);
                $estudio = $this->servicio->obtenerPorId($id);
                $this->responder(200, $estudio);
                return;
            }

            $limite = $this->leerLimite($parametrosQuery);
            $estudios = $this->servicio->listar($limite);
            if ($estudios === []) {
                http_response_code(204);
                return;
            }
            $this->responder(200, [
                'tabla' => 'estudios_realizados',
                'limite' => $limite,
                'total' => count($estudios),
                'datos' => $estudios,
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
     * POST /api/estudios_realizados
     * El `id` NO viene en el body: lo pone la base. La respuesta SÍ lo trae.
     *
     * @param array<string, mixed> $body
     */
    public function manejarPost(array $body): void
    {
        try {
            $this->validarCuerpoCompleto($body);
            $id = $this->servicio->crear($body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Estudio creado exitosamente.',
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
     * PUT /api/estudios_realizados/{id}
     *
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
                'mensaje' => 'Estudio reemplazado exitosamente.',
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
     * PATCH /api/estudios_realizados/{id}
     *
     * @param array<string, string> $parametrosRuta
     * @param array<string, mixed>  $body
     */
    public function manejarPatch(array $parametrosRuta, array $body): void
    {
        try {
            if ($body === []) {
                // Regla de negocio: PATCH sin campos es 400, no 200.
                throw new InvalidArgumentException('La petición parcial no incluye ningún campo.');
            }
            $id = $this->leerIdDeRuta($parametrosRuta['id']);
            $filas = $this->servicio->actualizarParcial($id, $body);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Estudio actualizado exitosamente.',
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
     * DELETE /api/estudios_realizados/{id}
     *
     * @param array<string, string> $parametrosRuta
     */
    public function manejarDelete(array $parametrosRuta): void
    {
        try {
            $id = $this->leerIdDeRuta($parametrosRuta['id']);
            $filas = $this->servicio->eliminar($id);
            $this->responder(200, [
                'estado' => 200,
                'mensaje' => 'Estudio eliminado exitosamente.',
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
     * Convierte el id de la ruta a int. Un id no numérico es 400 (la forma
     * del dato está mal), no 404: eso no es un identificador.
     */
    private function leerIdDeRuta(string $valor): int
    {
        if (!ctype_digit($valor)) {
            throw new InvalidArgumentException('El id debe ser un número entero.');
        }
        return (int) $valor;
    }

    /**
     * Valida que el cuerpo tenga todos los campos obligatorios de la ficha
     * (el PUT y el POST los exigen; el PATCH no).
     *
     * @param array<string, mixed> $body
     */
    private function validarCuerpoCompleto(array $body): void
    {
        $errores = [];

        $obligatorios = ['titulo', 'universidad', 'tipo', 'docente'];
        foreach ($obligatorios as $campo) {
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