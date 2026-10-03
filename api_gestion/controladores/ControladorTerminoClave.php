<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioTerminoClave.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el CONTROLADOR de `termino_clave`: la FRONTERA HTTP del recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás controladores: valida el body que llega del
 *   cliente (422 si está mal), delega en el servicio, traduce excepciones
 *   a códigos HTTP (400/404/500) y devuelve el array
 *   ['codigo' => int, 'cuerpo' => array].
 *
 * ¿Qué métodos expone?
 *     - listar(array $query): GET /api/termino_clave
 *     - obtener(string $termino): GET /api/termino_clave/{termino}
 *     - crear(array $body): POST /api/termino_clave
 *     - reemplazar(string $termino, array $body): PUT /api/termino_clave/{termino}
 *     - actualizarParcial(string $termino, array $body): PATCH /api/termino_clave/{termino}
 *     - eliminar(string $termino): DELETE /api/termino_clave/{termino}
 *
 * Nota sobre el identificador:
 *   En `termino_clave` la clave primaria es `termino` (string), no `id`.
 *   Por eso todos los métodos reciben string y el campo del body que
 *   identifica el recurso se llama `termino`.
 *
 * Nota sobre PUT:
 *   El único campo del body en PUT es `termino_ingles` (el `termino` va
 *   en la ruta). Si falta `termino_ingles`, es 422.
 *
 * Nota sobre PATCH:
 *   El único campo permitido es `termino_ingles`. Body vacío → 400.
 * ---------------------------------------------------------------------------
 */
class ControladorTerminoClave
{
    private IServicioTerminoClave $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioTerminoClave();
    }

    // -----------------------------------------------------------------------
    // listar — GET /api/termino_clave
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
            $terminos = $this->servicio->obtenerTodos($limite);

            if ($terminos === []) {
                return $this->respuesta(204, null);
            }

            $datos = [];
            foreach ($terminos as $t) {
                $datos[] = $t->toArray();
            }

            return $this->respuesta(200, [
                'tabla'  => 'termino_clave',
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
    // obtener — GET /api/termino_clave/{termino}
    // -----------------------------------------------------------------------
    public function obtener(string $termino): array
    {
        try {
            $t = $this->servicio->obtenerPorTermino($termino);
            return $this->respuesta(200, $t->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un término con termino = {$termino}",
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
    // crear — POST /api/termino_clave
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
                    'mensaje' => 'No se pudo crear el término clave.',
                ]);
            }

            return $this->respuesta(200, [
                'estado'  => 200,
                'mensaje' => 'Término clave creado exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            // PK duplicada (el `termino` ya existe) → 500 con detalle
            // del motor (contrato §3, clarificación C3).
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al crear el término clave.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // reemplazar — PUT /api/termino_clave/{termino}
    // -----------------------------------------------------------------------
    public function reemplazar(string $termino, array $body): array
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
            $filas = $this->servicio->actualizar($termino, $body);

            return $this->respuesta(200, [
                'estado'         => 200,
                'mensaje'        => 'Término clave reemplazado exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un término con termino = {$termino}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al reemplazar el término clave.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // actualizarParcial — PATCH /api/termino_clave/{termino}
    // -----------------------------------------------------------------------
    public function actualizarParcial(string $termino, array $body): array
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
            $filas = $this->servicio->actualizar($termino, $body);

            return $this->respuesta(200, [
                'estado'         => 200,
                'mensaje'        => 'Término clave actualizado exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un término con termino = {$termino}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al actualizar el término clave.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // eliminar — DELETE /api/termino_clave/{termino}
    // -----------------------------------------------------------------------
    public function eliminar(string $termino): array
    {
        try {
            $filas = $this->servicio->eliminar($termino);

            return $this->respuesta(200, [
                'estado'          => 200,
                'mensaje'         => 'Término clave eliminado exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un término con termino = {$termino}",
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al eliminar el término clave.',
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

        // termino es obligatorio (es la PK legible que envía el cliente).
        if (!array_key_exists('termino', $body)) {
            $errores[] = 'El campo termino es obligatorio.';
        } elseif (trim((string) $body['termino']) === '') {
            $errores[] = 'El campo termino no puede estar vacío.';
        }

        // termino_ingles es obligatorio.
        if (!array_key_exists('termino_ingles', $body)) {
            $errores[] = 'El campo termino_ingles es obligatorio.';
        } elseif (trim((string) $body['termino_ingles']) === '') {
            $errores[] = 'El campo termino_ingles no puede estar vacío.';
        }

        return $errores;
    }

    private function validarReemplazo(array $body): array
    {
        $errores = [];

        // En PUT solo se espera termino_ingles; el termino va en la ruta.
        if (!array_key_exists('termino_ingles', $body)) {
            $errores[] = 'El campo termino_ingles es obligatorio.';
        } elseif (trim((string) $body['termino_ingles']) === '') {
            $errores[] = 'El campo termino_ingles no puede estar vacío.';
        }

        // Si el cliente envió `termino` en el body, es un campo no
        // permitido en PUT: la PK viaja en la ruta.
        if (array_key_exists('termino', $body)) {
            $errores[] = 'El campo termino no puede modificarse; va en la ruta.';
        }

        return $errores;
    }

    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = ['termino_ingles'];

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