<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioAreaConocimiento.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el CONTROLADOR de `area_conocimiento`: la FRONTERA HTTP del recurso.
 *   Es el único lugar donde se valida el body que llega del cliente.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es la capa de arriba. Su trabajo:
 *     1. Recibir los datos que le pasa el front controller (ya enrutados).
 *     2. VALIDARLOS (frontera HTTP). Si no cumplen, devolver 422 con la
 *        lista de errores — sin tocar el servicio ni la BD.
 *     3. Llamar al servicio correspondiente.
 *     4. Traducir las excepciones a códigos HTTP.
 *     5. Devolver un array ['codigo' => int, 'cuerpo' => array] para que
 *        el front controller lo convierta en respuesta HTTP.
 *
 *   El controlador NO escribe SQL (eso es el repositorio), NO conoce PDO
 *   ni MariaDB, y NO abre conexiones.
 *
 * ¿Qué métodos expone?
 *   Uno por cada operación del contrato:
 *     - listar(): GET /api/area_conocimiento
 *     - obtener(string $id): GET /api/area_conocimiento/{id}
 *     - crear(array $body): POST /api/area_conocimiento
 *     - reemplazar(string $id, array $body): PUT /api/area_conocimiento/{id}
 *     - actualizarParcial(string $id, array $body): PATCH /api/area_conocimiento/{id}
 *     - eliminar(string $id): DELETE /api/area_conocimiento/{id}
 *
 * ¿Cómo se traducen las excepciones?
 *   NoEncontradoExcepcion  → 404 con {estado, mensaje, detalle}
 *   InvalidArgumentException → 400 con {estado, mensaje}
 *   PDOException           → 500 con {estado, mensaje, detalle}
 *   Cualquier otra         → 500 con {estado, mensaje}
 *
 * ¿Por qué devuelve un array en vez de hacer http_response_code() aquí?
 *   Para que el controlador sea independiente de la manera exacta en que
 *   se emite la respuesta. El front controller (index.php) es el único
 *   lugar que hace echo y setea headers. Esto también permite en el
 *   futuro probar controladores sin servidor web.
 * ---------------------------------------------------------------------------
 */
class ControladorAreaConocimiento
{
    // El controlador conoce el CONTRATO del servicio (la interfaz), no la
    // implementación concreta. Se la pide al ensamblador.
    private IServicioAreaConocimiento $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioAreaConocimiento();
    }

    // -----------------------------------------------------------------------
    // listar — GET /api/area_conocimiento
    // Acepta el query string ?limite=N. Si no viene, usa 1000 por defecto.
    // Devuelve 200 con {tabla, limite, total, datos}, 204 si está vacía,
    // o 400 si el límite es inválido.
    // -----------------------------------------------------------------------
    public function listar(array $query): array
    {
        // Valor por defecto del límite (contrato §1: 1000).
        $limite = 1000;

        if (array_key_exists('limite', $query)) {
            // La forma debe ser un entero. Si no, 422 (body/query mal
            // formado). Si es un entero ≤ 0, 400 (regla de negocio).
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
            $areas = $this->servicio->obtenerTodos($limite);

            // 204 si no hay registros (contrato §1 y clarificación C1).
            if ($areas === []) {
                return $this->respuesta(204, null);
            }

            $datos = [];
            foreach ($areas as $area) {
                $datos[] = $area->toArray();
            }

            return $this->respuesta(200, [
                'tabla'  => 'area_conocimiento',
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
    // obtener — GET /api/area_conocimiento/{id}
    // Devuelve 200 con el registro, o 404 si no existe.
    // -----------------------------------------------------------------------
    public function obtener(string $id): array
    {
        try {
            $area = $this->servicio->obtenerPorId($id);
            return $this->respuesta(200, $area->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un área con id = {$id}",
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
    // crear — POST /api/area_conocimiento
    // Valida el body: los 4 campos (id, gran_area, area, disciplina)
    // obligatorios y no vacíos. Si todo bien, delega en el servicio.
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
                    'mensaje' => 'No se pudo crear el área de conocimiento.',
                ]);
            }

            return $this->respuesta(200, [
                'estado'  => 200,
                'mensaje' => 'Área de conocimiento creada exitosamente.',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            // PK duplicada u otro error del motor: 500 con detalle
            // (contrato §0 y clarificación C3).
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al crear el área de conocimiento.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // reemplazar — PUT /api/area_conocimiento/{id}
    // PUT exige TODOS los campos editables (gran_area, area, disciplina).
    // El id no se envía en el body: va en la ruta.
    // -----------------------------------------------------------------------
    public function reemplazar(string $id, array $body): array
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
            // El id va en la ruta: no se toca el array $body con un id.
            $filas = $this->servicio->actualizar($id, $body);

            return $this->respuesta(200, [
                'estado'          => 200,
                'mensaje'         => 'Área de conocimiento reemplazada exitosamente.',
                'filasAfectadas'  => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un área con id = {$id}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al reemplazar el área de conocimiento.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // actualizarParcial — PATCH /api/area_conocimiento/{id}
    // PATCH acepta un subconjunto de campos. Body vacío → 400 (contrato §5).
    // -----------------------------------------------------------------------
    public function actualizarParcial(string $id, array $body): array
    {
        // Body vacío → 400 (regla de negocio, no de forma).
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
                'mensaje'        => 'Área de conocimiento actualizada exitosamente.',
                'filasAfectadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un área con id = {$id}",
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, [
                'estado'  => 400,
                'mensaje' => $e->getMessage(),
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al actualizar el área de conocimiento.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // eliminar — DELETE /api/area_conocimiento/{id}
    // -----------------------------------------------------------------------
    public function eliminar(string $id): array
    {
        try {
            $filas = $this->servicio->eliminar($id);

            return $this->respuesta(200, [
                'estado'          => 200,
                'mensaje'         => 'Área de conocimiento eliminada exitosamente.',
                'filasEliminadas' => $filas,
            ]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, [
                'estado'  => 404,
                'mensaje' => $e->getMessage(),
                'detalle' => "No existe un área con id = {$id}",
            ]);
        } catch (PDOException $e) {
            return $this->respuesta(500, [
                'estado'  => 500,
                'mensaje' => 'Error al eliminar el área de conocimiento.',
                'detalle' => $e->getMessage(),
            ]);
        }
    }

    // =======================================================================
    // MÉTODOS PRIVADOS DE VALIDACIÓN
    // =======================================================================

    /**
     * Validación de POST: TODOS los campos, y todos obligatorios.
     * Devuelve un array de mensajes de error en español; vacío si todo bien.
     */
    private function validarCreacion(array $body): array
    {
        $errores = [];
        $obligatorios = ['id', 'gran_area', 'area', 'disciplina'];

        foreach ($obligatorios as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            if (!is_string($body[$campo]) && !is_numeric($body[$campo])) {
                $errores[] = "El campo {$campo} debe ser texto.";
                continue;
            }
            if (trim((string) $body[$campo]) === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
            }
        }

        return $errores;
    }

    /**
     * Validación de PUT: TODOS los campos editables (gran_area, area,
     * disciplina), pero NO el id (va en la ruta).
     */
    private function validarReemplazo(array $body): array
    {
        $errores = [];
        $obligatorios = ['gran_area', 'area', 'disciplina'];

        foreach ($obligatorios as $campo) {
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

    /**
     * Validación de PATCH: solo los campos que vengan, y si vienen deben
     * ser válidos. Si un campo desconocido llega, se rechaza.
     */
    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = ['gran_area', 'area', 'disciplina'];

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

    /**
     * Empaqueta el código HTTP y el cuerpo en el array que devuelve el
     * controlador al front controller.
     */
    private function respuesta(int $codigo, ?array $cuerpo): array
    {
        return ['codigo' => $codigo, 'cuerpo' => $cuerpo];
    }
}