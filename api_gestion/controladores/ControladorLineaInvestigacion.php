<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioLineaInvestigacion.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el CONTROLADOR de `linea_investigacion`: la FRONTERA HTTP del
 *   recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás controladores: valida el body, delega en el
 *   servicio, traduce excepciones a códigos HTTP y devuelve el array
 *   ['codigo' => int, 'cuerpo' => array].
 *
 * ¿Qué métodos expone?
 *     - listar(array $query): GET /api/linea_investigacion
 *     - obtener(int $id): GET /api/linea_investigacion/{id}
 *     - crear(array $body): POST /api/linea_investigacion
 *     - reemplazar(int $id, array $body): PUT /api/linea_investigacion/{id}
 *     - actualizarParcial(int $id, array $body): PATCH /api/linea_investigacion/{id}
 *     - eliminar(int $id): DELETE /api/linea_investigacion/{id}
 *
 * Nota sobre el identificador:
 *   En `linea_investigacion` el id es INTEGER autoincremental. Por eso
 *   los métodos que lo usan lo reciben ya convertido a int (el front
 *   controller hace ese casteo desde el string de la ruta).
 *
 * Nota sobre POST:
 *   El id NO viaja en el body: lo genera la BD. Si el cliente lo manda,
 *   se ignora y se devuelve 422 (mejor ser explícitos que silenciosos).
 *
 * Nota sobre PUT:
 *   Los campos obligatorios en el body son `nombre` y `descripcion`.
 * ---------------------------------------------------------------------------
 */
class ControladorLineaInvestigacion
{
    private IServicioLineaInvestigacion $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioLineaInvestigacion();
    }

    // -----------------------------------------------------------------------
    // listar — GET /api/linea_investigacion
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
            $lineas = $this->servicio->obtenerTodos($limite);

            if ($lineas === []) {
                return $this->respuesta(204, null);
            }

            $datos = [];
            foreach ($lineas as $l) {
                $datos[] = $l->toArray();
            }

            return $this->respuesta(200, [
                'tabla'  => 'linea_investigacion',
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
    // obtener — GET /api/linea_investigacion/{id}
    // -----------------------------------------------------------------------
    public function obtener(int $id): array
    {
        try {
            $l = $this->servicio->obtenerPorId($id);
            return $this->respuesta(200, $l->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una línea con id = {$id}",
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
    // crear — POST /api/linea_investigacion
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
                    'mensaje' => 'No se pudo crear la línea de investigación.',
                ]);
            }

            return $this->respuesta(200, [
                'estado'  => 200,
                'mensaje' => 'Línea de investigación creada exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al crear la línea de investigación.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // reemplazar — PUT /api/linea_investigacion/{id}
    // -----------------------------------------------------------------------
    public function reemplazar(int $id, array $body): array
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
            $filas = $this->servicio->actualizar($id, $body);

            return $this->respuesta(200, [
                'estado'         => 200,
                'mensaje'        => 'Línea de investigación reemplazada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una línea con id = {$id}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al reemplazar la línea de investigación.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // actualizarParcial — PATCH /api/linea_investigacion/{id}
    // -----------------------------------------------------------------------
    public function actualizarParcial(int $id, array $body): array
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
            $filas = $this->servicio->actualizar($id, $body);

            return $this->respuesta(200, [
                'estado'         => 200,
                'mensaje'        => 'Línea de investigación actualizada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una línea con id = {$id}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al actualizar la línea de investigación.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // eliminar — DELETE /api/linea_investigacion/{id}
    // -----------------------------------------------------------------------
    public function eliminar(int $id): array
    {
        try {
            $filas = $this->servicio->eliminar($id);

            return $this->respuesta(200, [
                'estado'          => 200,
                'mensaje'         => 'Línea de investigación eliminada exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe una línea con id = {$id}",
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al eliminar la línea de investigación.',
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

        // En POST el id NO se admite: lo genera la BD.
        if (array_key_exists('id', $body)) {
            $errores[] = 'El campo id no debe enviarse; lo genera la base de datos.';
        }

        if (!array_key_exists('nombre', $body)) {
            $errores[] = 'El campo nombre es obligatorio.';
        } elseif (trim((string) $body['nombre']) === '') {
            $errores[] = 'El campo nombre no puede estar vacío.';
        }

        if (!array_key_exists('descripcion', $body)) {
            $errores[] = 'El campo descripcion es obligatorio.';
        } elseif (trim((string) $body['descripcion']) === '') {
            $errores[] = 'El campo descripcion no puede estar vacío.';
        }

        return $errores;
    }

    private function validarReemplazo(array $body): array
    {
        $errores = [];

        // En PUT tampoco se admite el id en el body: va en la ruta.
        if (array_key_exists('id', $body)) {
            $errores[] = 'El campo id no puede modificarse; va en la ruta.';
        }

        if (!array_key_exists('nombre', $body)) {
            $errores[] = 'El campo nombre es obligatorio.';
        } elseif (trim((string) $body['nombre']) === '') {
            $errores[] = 'El campo nombre no puede estar vacío.';
        }

        if (!array_key_exists('descripcion', $body)) {
            $errores[] = 'El campo descripcion es obligatorio.';
        } elseif (trim((string) $body['descripcion']) === '') {
            $errores[] = 'El campo descripcion no puede estar vacío.';
        }

        return $errores;
    }

    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = ['nombre', 'descripcion'];

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