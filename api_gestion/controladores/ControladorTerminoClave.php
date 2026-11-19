<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioTerminoClave.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorTerminoClave
{
    private IServicioTerminoClave $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioTerminoClave();
    }

    public function listar(array $query): array
    {
        try {
            $terminos = $this->servicio->obtenerTodos();
            if ($terminos === []) return $this->respuesta(204, null);

            $datos = [];
            foreach ($terminos as $t) {
                $datos[] = $t->toArray();
            }
            return $this->respuesta(200, ['tabla' => 'termino_clave', 'total' => count($datos), 'datos' => $datos]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno en la base de datos.']);
        }
    }

    public function obtener(string $termino): array
    {
        try {
            $t = $this->servicio->obtenerPorId($termino);
            return $this->respuesta(200, $t->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe el término = {$termino}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno en la base de datos.']);
        }
    }

    public function crear(array $body): array
    {
        $errores = $this->validarCreacion($body);
        if ($errores !== []) return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);

        try {
            $ok = $this->servicio->crear($body);
            if (!$ok) return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'No se pudo crear el término.']);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Término creado exitosamente.']);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => $e->getMessage()]);
        } catch (Throwable $e) { 
            $msg = $e->getMessage();
            if (str_contains($msg, '23000') || str_contains($msg, '1062')) {
                return $this->respuesta(409, ['estado' => 409, 'mensaje' => 'Ya existe un término con este nombre.']);
            }
            if (str_contains($msg, 'Data too long') || str_contains($msg, '1406')) {
                return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Uno de los campos supera la longitud máxima permitida.']);
            }
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Ocurrió un error interno al procesar la solicitud.']);
        }
    }

    public function reemplazar(string $termino, array $body): array
    {
        $errores = $this->validarReemplazo($body);
        if ($errores !== []) return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);

        try {
            $filas = $this->servicio->actualizar($termino, $body);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Término reemplazado exitosamente.', 'filasAfectadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe el término = {$termino}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al reemplazar el término.']);
        }
    }

    public function actualizarParcial(string $termino, array $body): array
    {
        if ($body === []) return $this->respuesta(400, ['estado' => 400, 'mensaje' => 'No se enviaron campos para actualizar.']);
        
        $errores = $this->validarParcial($body);
        if ($errores !== []) return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);

        try {
            $filas = $this->servicio->actualizar($termino, $body);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Término actualizado exitosamente.', 'filasAfectadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe el término = {$termino}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al actualizar el término.']);
        }
    }

    public function eliminar(string $termino): array
    {
        try {
            $filas = $this->servicio->eliminar($termino);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Término eliminado exitosamente.', 'filasEliminadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe el término = {$termino}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al eliminar el término.']);
        }
    }

    private function validarCreacion(array $body): array
    {
        $errores = [];
        $longitudes_max = ['termino' => 30, 'termino_ingles' => 30];
        
        if (!isset($body['termino']) || trim((string)$body['termino']) === '') {
            $errores[] = "El campo término es obligatorio.";
        }

        foreach ($longitudes_max as $campo => $max) {
            if (isset($body[$campo]) && strlen(trim((string)$body[$campo])) > $max) {
                $errores[] = "El campo {$campo} no puede superar los {$max} caracteres.";
            }
        }
        return $errores;
    }

    private function validarReemplazo(array $body): array
    {
        $errores = [];
        $longitudes_max = ['termino_ingles' => 30];
        foreach ($longitudes_max as $campo => $max) {
            if (isset($body[$campo]) && strlen(trim((string)$body[$campo])) > $max) {
                $errores[] = "El campo {$campo} no puede superar los {$max} caracteres.";
            }
        }
        return $errores;
    }

    private function validarParcial(array $body): array
    {
        $errores = [];
        $permitidos = ['termino_ingles' => 30];
        foreach ($body as $campo => $valor) {
            if (!array_key_exists($campo, $permitidos)) {
                $errores[] = "El campo {$campo} no es válido para actualizar.";
                continue;
            }
            if (strlen(trim((string)$valor)) > $permitidos[$campo]) {
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