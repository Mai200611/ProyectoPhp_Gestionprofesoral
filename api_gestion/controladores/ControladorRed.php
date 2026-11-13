<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioRed.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el CONTROLADOR de `red`: la FRONTERA HTTP del recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás controladores: valida el body, delega en el
 *   servicio, traduce excepciones a códigos HTTP y devuelve el array
 *   ['codigo' => int, 'cuerpo' => array].
 *
 * ¿Qué métodos expone?
 *     - listar(array $query): GET /api/red
 *     - obtener(int $idr): GET /api/red/{idr}
 *     - crear(array $body): POST /api/red
 *     - reemplazar(int $idr, array $body): PUT /api/red/{idr}
 *     - actualizarParcial(int $idr, array $body): PATCH /api/red/{idr}
 *     - eliminar(int $idr): DELETE /api/red/{idr}
 *
 * Nota sobre el identificador:
 *   En `red` la clave primaria se llama `idr` (INTEGER) y la envía el
 *   cliente en el POST. Por eso es obligatoria al crear.
 *
 * Nota sobre PATCH:
 *   Los campos permitidos son nombre, url y pais. El idr nunca se
 *   modifica.
 * ---------------------------------------------------------------------------
 */
class ControladorRed
{
    private IServicioRed $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioRed();
    }

    // -----------------------------------------------------------------------
    // listar — GET /api/red
    // -----------------------------------------------------------------------
    public function listar(array $query): array
    {
        $limite = 1000;

        if (array_key_exists('limite', $query)) {
            if (!ctype_digit((string) $query['limite'])) {
                return $this->respuesta(422, [
                    'estado'  => 422,
                    'mensaje' => 'Datos inválidos.',
                    'errores' => ['El parámetro limite debe ser un entero positivo.'],
                ]);
            }
            $limite = (int) $query['limite'];
        }

        try {
            $redes = $this->servicio->obtenerTodos($limite);

            if ($redes === []) {
                return $this->respuesta(204, null);
            }

            $datos = [];
            foreach ($redes as $r) {
                $datos[] = $r->toArray();
            }

            return $this->respuesta(200, [
                'tabla'  => 'red',
                'limite' => $limite,
                'total'  => count($datos),
                'datos'  => $datos,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error en la base de datos.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // obtener — GET /api/red/{idr}
    // -----------------------------------------------------------------------
    public function obtener(int $idr): array
    {
        try {
            $r = $this->servicio->obtenerPorIdr($idr);
            return $this->respuesta(200, $r->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una red con idr = {$idr}",
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error en la base de datos.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // crear — POST /api/red
    // -----------------------------------------------------------------------
    public function crear(array $body): array
    {
        $errores = $this->validarCreacion($body);

        if ($errores !== []) {
            return $this->respuesta(422, [
                'estado'  => 422,
                'mensaje' => 'Datos inválidos.',
                'errores' => $errores,
            ]);
        }

        try {
            $ok = $this->servicio->crear($body);

            if (!$ok) {
                return $this->respuesta(500, [
                    'estado'  => 500,
                    'mensaje' => 'No se pudo crear la red.',
                ]);
            }

            return $this->respuesta(200, [
                'estado'  => 200,
                'mensaje' => 'Red creada exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            // idr duplicado u otro error del motor → 500 con detalle
            // (contrato §3, clarificación C3).
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al crear la red.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // reemplazar — PUT /api/red/{idr}
    // -----------------------------------------------------------------------
    public function reemplazar(int $idr, array $body): array
    {
        $errores = $this->validarReemplazo($body);

        if ($errores !== []) {
            return $this->respuesta(422, [
                'estado'  => 422,
                'mensaje' => 'Datos inválidos.',
                'errores' => $errores,
            ]);
        }

        try {
            $filas = $this->servicio->actualizar($idr, $body);

            return $this->respuesta(200, [
                'estado'         => 200,
                'mensaje'        => 'Red reemplazada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una red con idr = {$idr}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al reemplazar la red.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // actualizarParcial — PATCH /api/red/{idr}
    // -----------------------------------------------------------------------
    public function actualizarParcial(int $idr, array $body): array
    {
        if ($body === []) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => 'No se enviaron campos para actualizar.',
            ]);
        }

        $errores = $this->validarParcial($body);

        if ($errores !== []) {
            return $this->respuesta(422, [
                'estado'  => 422,
                'mensaje' => 'Datos inválidos.',
                'errores' => $errores,
            ]);
        }

        try {
            $filas = $this->servicio->actualizar($idr, $body);

            return $this->respuesta(200, [
                'estado'         => 200,
                'mensaje'        => 'Red actualizada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una red con idr = {$idr}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al actualizar la red.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // eliminar — DELETE /api/red/{idr}
    // -----------------------------------------------------------------------
    public function eliminar(int $idr): array
    {
        try {
            $filas = $this->servicio->eliminar($idr);

            return $this->respuesta(200, [
                'estado'          => 200,
                'mensaje'         => 'Red eliminada exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una red con idr = {$idr}",
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al eliminar la red.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // =======================================================================
    // MÉTODOS PRIVADOS DE VALIDACIÓN
    // =======================================================================

    private function validarCreacion(array $body): array
    {
        $errores = [];

        // idr: obligatorio, entero positivo.
        if (!array_key_exists('idr', $body)) {
            $errores[] = 'El campo idr es obligatorio.';
        } elseif (!ctype_digit((string) $body['idr'])) {
            $errores[] = 'El campo idr debe ser un número entero positivo.';
        }

        // nombre, url, pais: obligatorios y no vacíos.
        foreach (['nombre', 'url', 'pais'] as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            if (trim((string) $body[$campo]) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        return $errores;
    }

    private function validarReemplazo(array $body): array
    {
        $errores = [];

        // En PUT el idr va en la ruta: si viene en el body, es un error.
        if (array_key_exists('idr', $body)) {
            $errores[] = 'El campo idr no puede modificarse; va en la ruta.';
        }

        foreach (['nombre', 'url', 'pais'] as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            if (trim((string) $body[$campo]) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        return $errores;
    }

    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = ['nombre', 'url', 'pais'];

        foreach ($body as $campo => $valor) {
            if (!in_array($campo, $permitidos, true)) {
                $errores[] = "El campo {$campo} no es un campo válido.";
                continue;
            }
            if (trim((string) $valor) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        return $errores;
    }

    // =======================================================================
    // UTILIDAD INTERNA
    // =======================================================================

    private function respuesta(int $codigo, ?array $cuerpo): array
    {
        return ['codigo' => $codigo, 'cuerpo' => $cuerpo];
    }
}