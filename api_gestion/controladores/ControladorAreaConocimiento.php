<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioAreaConocimiento.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorAreaConocimiento
{
    private IServicioAreaConocimiento $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioAreaConocimiento();
    }

    public function listar(array $query): array
    {
        $limite = 1000;
        if (array_key_exists('limite', $query)) {
            if (!ctype_digit((string) $query['limite'])) {
                return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => ['El parámetro limite debe ser un entero positivo.']]);
            }
            $limite = (int) $query['limite'];
        }

        try {
            $areas = $this->servicio->obtenerTodos($limite);
            if ($areas === []) return $this->respuesta(204, null);

            $datos = [];
            foreach ($areas as $area) {
                $datos[] = $area->toArray();
            }
            return $this->respuesta(200, ['tabla' => 'area_conocimiento', 'limite' => $limite, 'total' => count($datos), 'datos' => $datos]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => $e->getMessage()]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno en la base de datos.']);
        }
    }

    public function obtener(string $id): array
    {
        try {
            $area = $this->servicio->obtenerPorId($id);
            return $this->respuesta(200, $area->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe un área con id = {$id}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno en la base de datos.']);
        }
    }

    public function crear(array $body): array
    {
        $errores = $this->validarCreacion($body);

        if ($errores !== []) {
            return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);
        }

        try {
            $ok = $this->servicio->crear($body);
            if (!$ok) return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'No se pudo crear el área de conocimiento.']);

            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Área de conocimiento creada exitosamente.']);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => $e->getMessage()]);
        } catch (Throwable $e) { 
            $msg = $e->getMessage();
            // Atrapamos ID duplicado
            if (str_contains($msg, '23000') || str_contains($msg, '1062')) {
                return $this->respuesta(409, ['estado' => 409, 'mensaje' => 'Ya existe un área de conocimiento con este ID. Por favor, intenta con otro.']);
            }
            // Atrapamos datos demasiado largos (por si falla la validación previa)
            if (str_contains($msg, 'Data too long') || str_contains($msg, '1406')) {
                return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Uno de los campos supera la longitud máxima permitida.']);
            }
            
            // AQUÍ ESTÁ LA PRUEBA DE VIDA
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => '¡PRUEBA DE VIDA! Error real: ' . $msg]);
        }
    }

    public function reemplazar(string $id, array $body): array
    {
        $errores = $this->validarReemplazo($body);
        if ($errores !== []) {
            return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);
        }

        try {
            $filas = $this->servicio->actualizar($id, $body);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Área reemplazada exitosamente.', 'filasAfectadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe un área con id = {$id}"]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => $e->getMessage()]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al reemplazar el área de conocimiento.']);
        }
    }

    public function actualizarParcial(string $id, array $body): array
    {
        if ($body === []) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => 'No se enviaron campos para actualizar.']);
        }

        $errores = $this->validarParcial($body);
        if ($errores !== []) {
            return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);
        }

        try {
            $filas = $this->servicio->actualizar($id, $body);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Área actualizada exitosamente.', 'filasAfectadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe un área con id = {$id}"]);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => $e->getMessage()]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al actualizar el área.']);
        }
    }

    public function eliminar(string $id): array
    {
        try {
            $filas = $this->servicio->eliminar($id);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Área eliminada exitosamente.', 'filasEliminadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe un área con id = {$id}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al eliminar el área.']);
        }
    }

    // =======================================================================
    // VALIDACIONES ESTRICTAS
    // =======================================================================

    private function validarCreacion(array $body): array
    {
        $errores = [];
        $obligatorios = ['id', 'gran_area', 'area', 'disciplina'];
        
        $longitudes_max = [
            'id' => 6,
            'gran_area' => 60,
            'area' => 60,
            'disciplina' => 150
        ];

        foreach ($obligatorios as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            $valorLimpio = trim((string) $body[$campo]);
            if ($valorLimpio === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
                continue;
            }
            
            if (strlen($valorLimpio) > $longitudes_max[$campo]) {
                $errores[] = "El campo {$campo} no puede superar los {$longitudes_max[$campo]} caracteres.";
            }
        }
        return $errores;
    }

    private function validarReemplazo(array $body): array
    {
        $errores = [];
        $obligatorios = ['gran_area', 'area', 'disciplina'];
        $longitudes_max = ['gran_area' => 60, 'area' => 60, 'disciplina' => 150];

        foreach ($obligatorios as $campo) {
            if (!array_key_exists($campo, $body)) {
                $errores[] = "El campo {$campo} es obligatorio.";
                continue;
            }
            $valorLimpio = trim((string) $body[$campo]);
            if ($valorLimpio === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
                continue;
            }
            if (strlen($valorLimpio) > $longitudes_max[$campo]) {
                $errores[] = "El campo {$campo} no puede superar los {$longitudes_max[$campo]} caracteres.";
            }
        }
        return $errores;
    }

    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = ['gran_area' => 60, 'area' => 60, 'disciplina' => 150];

        foreach ($body as $campo => $valor) {
            if (!array_key_exists($campo, $permitidos)) {
                $errores[] = "El campo {$campo} no es un campo válido.";
                continue;
            }
            $valorLimpio = trim((string) $valor);
            if ($valorLimpio === '') {
                $errores[] = "El campo {$campo} no puede estar vacío.";
                continue;
            }
            if (strlen($valorLimpio) > $permitidos[$campo]) {
                $errores[] = "El campo {$campo} no puede superar los {$permitidos[$campo]} caracteres.";
            }
        }
        return $errores;
    }

    private function respuesta(int $codigo, ?array $cuerpo): array
    {
        return ['codigo' => $codigo, 'cuerpo' => $cuerpo];
    }
}