<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioDocente.php';
require_once __DIR__ . '/errores_de_integridad_postgres.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/Conexion.php';

class RepositorioDocentePostgres implements IRepositorioDocente
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT cedula, nombres, apellidos, genero, cargo,
                       fecha_nacimiento, correo, telefono, url_cvlac,
                       fecha_actualizacion, escalafon, perfil, cat_minciencia,
                       activo, linea_investigacion_principal
                FROM docente
                LIMIT :limite';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        
        // Limpiamos booleanos y fechas para que el JSON sea idéntico a MariaDB
        return $filas === false ? [] : array_map([$this, 'limpiarFila'], $filas);
    }

    public function obtenerPorCedula(string $cedula): ?array
    {
        $sql = 'SELECT cedula, nombres, apellidos, genero, cargo,
                       fecha_nacimiento, correo, telefono, url_cvlac,
                       fecha_actualizacion, escalafon, perfil, cat_minciencia,
                       activo, linea_investigacion_principal
                FROM docente
                WHERE cedula = :cedula';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':cedula', $cedula);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $this->limpiarFila($fila);
    }

    public function obtenerCompleto(string $cedula): ?array
    {
        try {
            // En Postgres, llamamos a la función y esperamos el JSON directamente
            $resultado = $this->llamarFuncion(
                'sp_consultar_docente_y_estudios_realizados_por_docente',
                [$cedula]
            );
        } catch (PDOException $e) {
            $this->interpretarRechazo($e);
        }

        if ($resultado === null || $resultado === '') {
            return null;
        }

        $decodificado = json_decode($resultado, true);
        if (!is_array($decodificado)) {
            return null;
        }

        // Limpiamos el JSON anidado (json_agg nulos, booleanos, fechas)
        return $this->limpiarDetalles($decodificado);
    }

    public function crearCompleto(array $datos): array
    {
        $parametros = [json_encode($datos, JSON_UNESCAPED_UNICODE)];

        try {
            // El casteo ::jsonb le dice a Postgres cómo interpretar el string
            $resultado = $this->llamarFuncion(
                'sp_crear_docente_con_detalle',
                $parametros,
                '?::jsonb'
            );
        } catch (PDOException $e) {
            $this->interpretarRechazo($e);
        }

        if ($resultado === null || $resultado === '') {
            $cedula = (string) ($datos['cedula'] ?? '');
            $completo = $this->obtenerCompleto($cedula);
            return $completo ?? [];
        }

        $decodificado = json_decode($resultado, true);
        return is_array($decodificado) ? $this->limpiarDetalles($decodificado) : [];
    }

    public function reemplazar(string $cedula, array $datos): int
    {
        $parametros = [$cedula, json_encode($datos, JSON_UNESCAPED_UNICODE)];
        try {
            $this->llamarFuncion('sp_reemplazar_docente_completo', $parametros, '?, ?::jsonb');
        } catch (PDOException $e) {
            $this->interpretarRechazo($e);
        }
        return 1;
    }

    public function eliminar(string $cedula): int
    {
        $sql = 'DELETE FROM docente WHERE cedula = :cedula';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':cedula', $cedula);
            $sentencia->execute();
        } catch (PDOException $e) {
            $this->interpretarRechazo($e);
        }

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------
    // Auxiliares privados (Aquí ocurre la magia de Postgres)
    // -----------------------------------------------------------------

    private function llamarFuncion(string $nombre, array $parametros, string $marcadores = ''): ?string
    {
        if ($marcadores === '') {
            $marcadores = implode(', ', array_fill(0, count($parametros), '?'));
        }
        
        // Postgres usa SELECT * FROM funcion(...) en vez de CALL
        $sql = "SELECT * FROM $nombre($marcadores)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);
        
        $resultado = $sentencia->fetchColumn();
        return $resultado === false ? null : (string) $resultado;
    }

    private function interpretarRechazo(PDOException $error): never
    {
        $mensajeCompleto = $error->getMessage();
        // Postgres añade un bloque CONTEXT: ... al final. Lo recortamos.
        $mensaje = explode('CONTEXT:', $mensajeCompleto)[0];

        if (stripos($mensaje, 'no existe') !== false) {
            throw new NoEncontradoExcepcion('El docente solicitado no existe.');
        }

        if (stripos($mensaje, 'dependencia') !== false || stripos($mensaje, 'no se puede eliminar') !== false || stripos($mensaje, 'violates foreign key') !== false) {
            throw new ConflictoDeIntegridadExcepcion(
                'No se puede eliminar el docente porque hay otras fichas que dependen de él.',
                'Elimine primero sus estudios, evaluaciones, reconocimientos o experiencia.'
            );
        }

        if (stripos($mensaje, 'ya existe') !== false || stripos($mensaje, 'duplicate key') !== false) {
            throw new ConflictoDeIntegridadExcepcion(
                'Ya existe el docente con esa llave.',
                'La cédula que intentó usar ya está registrada.'
            );
        }

        throw $error;
    }

    // Convierte booleanos y quita microsegundos a las fechas planas
    private function limpiarFila(array $fila): array
    {
        if (isset($fila['activo'])) $fila['activo'] = (int)(bool)$fila['activo'];
        foreach ($fila as $k => $v) {
            if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+$/', $v)) {
                $fila[$k] = substr($v, 0, 19); // Quita los .microsegundos
            }
        }
        return $fila;
    }

    // Convierte booleanos, fechas y arregla los json_agg que vienen como NULL
    private function limpiarDetalles(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->limpiarDetalles($value);
            } elseif (is_bool($value)) {
                $data[$key] = (int) $value;
            } elseif (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+$/', $value)) {
                $data[$key] = substr($value, 0, 19);
            } elseif ($value === null && preg_match('/(s|es)$/', (string)$key) && !in_array($key, ['nombres', 'apellidos'])) {
                // Si Postgres devuelve null en un arreglo plural (ej. estudios), lo forzamos a []
                $data[$key] = [];
            }
        }
        return $data;
    }
}