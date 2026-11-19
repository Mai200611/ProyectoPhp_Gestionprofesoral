<?php
declare(strict_types=1);

require_once __DIR__ . '/../servicios/IServicioLineaInvestigacion.php';
require_once __DIR__ . '/../servicios/ensamblador.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';

class ControladorLineaInvestigacion
{
    private IServicioLineaInvestigacion $servicio;

    public function __construct()
    {
        $this->servicio = crearServicioLineaInvestigacion();
    }

    public function listar(array $query): array
    {
        try {
            $lineas = $this->servicio->obtenerTodos();
            if ($lineas === []) return $this->respuesta(204, null);
            $datos = [];
            foreach ($lineas as $l) { $datos[] = $l->toArray(); }
            return $this->respuesta(200, ['tabla' => 'linea_investigacion', 'total' => count($datos), 'datos' => $datos]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno en la base de datos.']);
        }
    }

    public function obtener(int $id): array
    {
        try {
            $l = $this->servicio->obtenerPorId($id);
            return $this->respuesta(200, $l->toArray());
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe la línea con id = {$id}"]);
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
            if (!$ok) return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'No se pudo crear la línea.']);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Línea creada exitosamente.']);
        } catch (InvalidArgumentException $e) {
            return $this->respuesta(400, ['estado' => 400, 'mensaje' => $e->getMessage()]);
        } catch (Throwable $e) { 
            $msg = $e->getMessage();
            if (str_contains($msg, '23000') || str_contains($msg, '1062')) {
                return $this->respuesta(409, ['estado' => 409, 'mensaje' => 'Ya existe una línea con este nombre.']);
            }
            if (str_contains($msg, 'Data too long') || str_contains($msg, '1406')) {
                return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Uno de los campos supera la longitud máxima permitida.']);
            }
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Ocurrió un error interno al procesar la solicitud.']);
        }
    }

    public function reemplazar(int $id, array $body): array
    {
        $errores = $this->validarReemplazo($body);
        if ($errores !== []) return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);

        try {
            $filas = $this->servicio->actualizar($id, $body);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Línea reemplazada exitosamente.', 'filasAfectadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe la línea con id = {$id}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al reemplazar la línea.']);
        }
    }

    public function actualizarParcial(int $id, array $body): array
    {
        if ($body === []) return $this->respuesta(400, ['estado' => 400, 'mensaje' => 'No se enviaron campos para actualizar.']);
        $errores = $this->validarParcial($body);
        if ($errores !== []) return $this->respuesta(422, ['estado' => 422, 'mensaje' => 'Datos inválidos.', 'errores' => $errores]);

        try {
            $filas = $this->servicio->actualizar($id, $body);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Línea actualizada exitosamente.', 'filasAfectadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe la línea con id = {$id}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al actualizar la línea.']);
        }
    }

    public function eliminar(int $id): array
    {
        try {
            $filas = $this->servicio->eliminar($id);
            return $this->respuesta(200, ['estado' => 200, 'mensaje' => 'Línea eliminada exitosamente.', 'filasEliminadas' => $filas]);
        } catch (NoEncontradoExcepcion $e) {
            return $this->respuesta(404, ['estado' => 404, 'mensaje' => $e->getMessage(), 'detalle' => "No existe la línea con id = {$id}"]);
        } catch (PDOException $e) {
            return $this->respuesta(500, ['estado' => 500, 'mensaje' => 'Error interno al eliminar la línea.']);
        }
    }

    private function validarCreacion(array $body): array
    {
        $errores = [];
        $longitudes_max = ['nombre' => 45, 'descripcion' => 256];

        if (!isset($body['nombre']) || trim((string)$body['nombre']) === '') {
            $errores[] = "El campo nombre es obligatorio.";
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
        $longitudes_max = ['nombre' => 45, 'descripcion' => 256];
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
        $permitidos = ['nombre' => 45, 'descripcion' => 256];
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