<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioPrograma.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el CONTROLADOR de `programa`: la FRONTERA HTTP del recurso.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Igual que los demás controladores: valida el body, delega en el
 *   servicio, traduce excepciones a códigos HTTP y devuelve el array
 *   ['codigo' => int, 'cuerpo' => array].
 *
 * ¿Qué métodos expone?
 *     - listar(array $query): GET /api/programa
 *     - obtener(int $id): GET /api/programa/{id}
 *     - crear(array $body): POST /api/programa
 *     - reemplazar(int $id, array $body): PUT /api/programa/{id}
 *     - actualizarParcial(int $id, array $body): PATCH /api/programa/{id}
 *     - eliminar(int $id): DELETE /api/programa/{id}
 *
 * Nota sobre el identificador:
 *   En `programa` el id es INTEGER y lo envía el cliente en el POST (no
 *   es autoincremental en este esquema). Por eso `id` SÍ es obligatorio
 *   al crear.
 *
 * Nota sobre `fecha_cierre`:
 *   Es el único campo del recurso que admite null. La validación lo trata
 *   con cuidado: si viene null, se acepta; si viene string vacío, se
 *   convierte a null.
 *
 * Nota sobre PATCH:
 *   Los campos permitidos son todos los editables (nombre, tipo, nivel,
 *   fechas, cohortes, graduados, ciudad, facultad). El id nunca.
 * ---------------------------------------------------------------------------
 */
class ControladorPrograma
{
    private IServicioPrograma $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioPrograma();
    }

    // -----------------------------------------------------------------------
    // listar — GET /api/programa
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
            $programas = $this->servicio->obtenerTodos($limite);

            if ($programas === []) {
                return $this->respuesta(204, null);
            }

            $datos = [];
            foreach ($programas as $p) {
                $datos[] = $p->toArray();
            }

            return $this->respuesta(200, [
                'tabla'  => 'programa',
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
    // obtener — GET /api/programa/{id}
    // -----------------------------------------------------------------------
    public function obtener(int $id): array
    {
        try {
            $p = $this->servicio->obtenerPorId($id);
            return $this->respuesta(200, $p->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un programa con id = {$id}",
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
    // crear — POST /api/programa
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
                    'mensaje' => 'No se pudo crear el programa.',
                ]);
            }

            return $this->respuesta(200, [
                'estado'  => 200,
                'mensaje' => 'Programa creado exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al crear el programa.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // reemplazar — PUT /api/programa/{id}
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
                'mensaje'        => 'Programa reemplazado exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un programa con id = {$id}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al reemplazar el programa.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // actualizarParcial — PATCH /api/programa/{id}
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
                'mensaje'        => 'Programa actualizado exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un programa con id = {$id}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al actualizar el programa.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // eliminar — DELETE /api/programa/{id}
    // -----------------------------------------------------------------------
    public function eliminar(int $id): array
    {
        try {
            $filas = $this->servicio->eliminar($id);

            return $this->respuesta(200, [
                'estado'          => 200,
                'mensaje'         => 'Programa eliminado exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un programa con id = {$id}",
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al eliminar el programa.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // =======================================================================
    // MÉTODOS PRIVADOS DE VALIDACIÓN
    // =======================================================================

    /**
     * Lista de campos obligatorios en el body (sin contar `fecha_cierre`).
     */
    private function camposObligatorios(): array
    {
        return [
            'id',
            'nombre',
            'tipo',
            'nivel',
            'fecha_creacion',
            'numero_cohortes',
            'cant_graduados',
            'fecha_actualizacion',
            'ciudad',
            'facultad',
        ];
    }

    private function validarCreacion(array $body): array
    {
        $errores = [];

        foreach ($this->camposObligatorios() as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            if ($body[$campo] === null) {
                $errores[] = "El campo {$campo} no puede ser nulo.";
                continue;
            }
            if (!is_int($body[$campo]) && trim((string) $body[$campo]) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        // id debe ser un entero positivo.
        if (array_key_exists('id', $body) && !ctype_digit((string) $body['id'])) {
            $errores[] = 'El campo id debe ser un número entero positivo.';
        }

        // facultad debe ser un entero.
        if (array_key_exists('facultad', $body) && !ctype_digit((string) $body['facultad'])) {
            $errores[] = 'El campo facultad debe ser un número entero.';
        }

        return $errores;
    }

    private function validarReemplazo(array $body): array
    {
        $errores = [];

        // En PUT el id va en la ruta: si viene en el body, es un error.
        if (array_key_exists('id', $body)) {
            $errores[] = 'El campo id no puede modificarse; va en la ruta.';
        }

        // Los campos obligatorios en PUT son todos menos el id.
        $obligatorios = [
            'nombre',
            'tipo',
            'nivel',
            'fecha_creacion',
            'numero_cohortes',
            'cant_graduados',
            'fecha_actualizacion',
            'ciudad',
            'facultad',
        ];
        foreach ($obligatorios as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            if ($body[$campo] === null) {
                $errores[] = "El campo {$campo} no puede ser nulo.";
                continue;
            }
            if (!is_int($body[$campo]) && trim((string) $body[$campo]) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        if (array_key_exists('facultad', $body) && !ctype_digit((string) $body['facultad'])) {
            $errores[] = 'El campo facultad debe ser un número entero.';
        }

        return $errores;
    }

    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = [
            'nombre',
            'tipo',
            'nivel',
            'fecha_creacion',
            'fecha_cierre',
            'numero_cohortes',
            'cant_graduados',
            'fecha_actualizacion',
            'ciudad',
            'facultad',
        ];

        foreach ($body as $campo => $valor) {
            if (!in_array($campo, $permitidos, true)) {
                $errores[] = "El campo {$campo} no es un campo válido.";
                continue;
            }
            // fecha_cierre admite null explícitamente.
            if ($campo === 'fecha_cierre' && $valor === null) {
                continue;
            }
            if (trim((string) $valor) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        if (array_key_exists('facultad', $body)
            && $body['facultad'] !== null
            && !ctype_digit((string) $body['facultad'])) {
            $errores[] = 'El campo facultad debe ser un número entero.';
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