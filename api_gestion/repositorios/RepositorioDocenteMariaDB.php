<?php
declare(strict_types=1);

require_once __DIR__ . '/IRepositorioDocente.php';
require_once __DIR__ . '/errores_de_integridad.php';
require_once __DIR__ . '/../excepciones/NoEncontradoExcepcion.php';
require_once __DIR__ . '/../excepciones/ConflictoDeIntegridadExcepcion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../utilidades/filtrar_columnas.php';

/**
 * RepositorioDocenteMariaDB.php
 *
 * Implementación concreta sobre MariaDB del contrato IRepositorioDocente.
 *
 * ESTE REPOSITORIO NO ESCRIBE SQL COMPLEJO: llama a los procedimientos
 * almacenados de la base (5_data_model.md §4.1, 3_plan §4.4). La base ya
 * trae las operaciones del maestro-detalle escritas; este archivo las invoca
 * y lee su resultado. Reescribirlas en SQL suelto sería duplicar el negocio
 * y abrir la puerta a dos fuentes de verdad (Artículo 11).
 *
 * Detalles de PDO que cuestan una corrida cada uno (3_plan §4.4):
 *   1. closeCursor() es OBLIGATORIO antes del SELECT @resultado: MariaDB no
 *      deja lanzar otra consulta mientras la anterior siga abierta.
 *   2. El caso de CERO parámetros de entrada hay que tratarlo aparte, o se
 *      arma un `CALL sp(, @resultado)` con coma suelta que el motor rechaza.
 *   3. Los procedimientos rechazan con SIGNAL SQLSTATE '45000', y el único
 *      dato que distingue un caso de otro es el TEXTO del mensaje. Eso es
 *      frágil y se declara (§4.5); queda aislado en interpretarRechazo()
 *      para que el día que se arregle, se arregle en un solo sitio.
 */
class RepositorioDocenteMariaDB implements IRepositorioDocente
{
    private PDO $conexion;

    public function __construct(PDO $conexion)
    {
        $this->conexion = $conexion;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite): array
    {
        $sql = 'SELECT cedula, nombres, apellidos, genero, cargo,
                       fecha_nacimiento, correo, telefono, url_cvlac,
                       fecha_actualizacion, escalafon, perfil, cat_minciencia,
                       nacionalidad, activo, linea_investigacion_principal
                FROM docente
                LIMIT :limite';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':limite', $limite, PDO::PARAM_INT);
        $sentencia->execute();
        $filas = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return $filas === false ? [] : $filas;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorCedula(string $cedula): ?array
    {
        $sql = 'SELECT cedula, nombres, apellidos, genero, cargo,
                       fecha_nacimiento, correo, telefono, url_cvlac,
                       fecha_actualizacion, escalafon, perfil, cat_minciencia,
                       nacionalidad, activo, linea_investigacion_principal
                FROM docente
                WHERE cedula = :cedula';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':cedula', $cedula);
        $sentencia->execute();
        $fila = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    /**
     * Devuelve el docente con TODOS sus detalles anidados, llamando al
     * procedimiento almacenado que ya trae la base.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerCompleto(string $cedula): ?array
    {
        try {
            $resultado = $this->llamarProcedimiento(
                'sp_consultar_docente_y_estudios_realizados_por_docente',
                [$cedula]
            );
        } catch (ConflictoDeIntegridadExcepcion $e) {
            // El procedimiento también puede rechazar con SIGNAL. Si su
            // mensaje indica que no existe, es 404, no 409.
            if ($this->esMensajeDeNoExistencia($e->getMessage())) {
                return null;
            }
            throw $e;
        }

        if ($resultado === null || $resultado === '') {
            return null;
        }

        $decodificado = json_decode($resultado, true);
        return is_array($decodificado) ? $decodificado : null;
    }

    /**
     * Crea un docente con su detalle anidado en UNA sola operación de
     * negocio (Artículo 11: maestro-detalle como unidad).
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function crearCompleto(array $datos): array
    {
        // El procedimiento recibe TODO el body como un único JSON.
        $parametros = [json_encode($datos, JSON_UNESCAPED_UNICODE)];

        $resultado = $this->llamarProcedimiento(
            'sp_crear_docente_con_detalle',
            $parametros
        );

        if ($resultado === null || $resultado === '') {
            // El procedimiento no devolvió nada: recuperamos el docente
            // recién creado por su cédula para devolverlo completo.
            $cedula = (string) ($datos['cedula'] ?? '');
            $completo = $this->obtenerCompleto($cedula);
            return $completo ?? [];
        }

        $decodificado = json_decode($resultado, true);
        return is_array($decodificado) ? $decodificado : [];
    }

    /**
     * Reemplaza el docente entero (encabezado + detalle) en una operación.
     */
    public function reemplazar(string $cedula, array $datos): int
    {
        $parametros = [$cedula, json_encode($datos, JSON_UNESCAPED_UNICODE)];
        $this->llamarProcedimiento('sp_reemplazar_docente_completo', $parametros);
        return 1;
    }

    /**
     * Elimina el docente. La base rechaza con 409 si tiene dependencias
     * (sin cascada en v2); la traducción la hace interpretarRechazo().
     */
    public function eliminar(string $cedula): int
    {
        // No hay procedimiento para el DELETE: es un DELETE directo. Si el
        // motor rechaza por FK, errores_de_integridad.php lo traduce a 409.
        $sql = 'DELETE FROM docente WHERE cedula = :cedula';

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':cedula', $cedula);
            $sentencia->execute();
        } catch (PDOException $e) {
            traducirErrorDeIntegridad($e, 'el docente');
        }

        return $sentencia->rowCount();
    }

    // -----------------------------------------------------------------
    // Auxiliares privados
    // -----------------------------------------------------------------

    /**
     * Llama a un procedimiento almacenado que devuelve su resultado como
     * JSON en un parámetro OUT, leído vía variable de sesión.
     *
     * @param array<int, mixed> $parametros Entradas del procedimiento.
     * @return string|null El JSON de salida, o null si no hubo resultado.
     */
    private function llamarProcedimiento(string $nombre, array $parametros): ?string
    {
        // Caso especial: cero parámetros de entrada (3_plan §4.4, punto 2).
        if ($parametros === []) {
            $sql = "CALL $nombre(@resultado)";
        } else {
            $marcadores = implode(', ', array_fill(0, count($parametros), '?'));
            $sql = "CALL $nombre($marcadores, @resultado)";
        }

        try {
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->execute($parametros);
            // Obligatorio antes de lanzar la siguiente consulta.
            $sentencia->closeCursor();
        } catch (PDOException $e) {
            // El procedimiento puede rechazar con SIGNAL SQLSTATE '45000'.
            // En ese caso, distinguimos «no existe» de «no se deja».
            $this->interpretarRechazo($e);
        }

        $resultado = $this->conexion->query('SELECT @resultado')->fetchColumn();
        return $resultado === false ? null : (string) $resultado;
    }

    /**
     * Traduce un rechazo del procedimiento (SIGNAL SQLSTATE '45000').
     *
     * FRAGILIDAD DECLARADA (3_plan §4.5): el único dato que distingue un
     * caso de otro es el TEXTO del mensaje. Se aísla aquí para que el día
     * que los procedimientos traigan su propio código, se cambie en un
     * solo método.
     *
     * @return never
     */
    private function interpretarRechazo(PDOException $error): never
    {
        $mensaje = $error->getMessage();

        // «Docente X no existe» → 404.
        if ($this->esMensajeDeNoExistencia($mensaje)) {
            throw new NoEncontradoExcepcion('El docente solicitado no existe.');
        }

        // «Tiene dependencias» / «no se puede eliminar» → 409.
        if (stripos($mensaje, 'dependencia') !== false
            || stripos($mensaje, 'no se puede eliminar') !== false) {
            throw new ConflictoDeIntegridadExcepcion(
                'No se puede eliminar el docente porque hay otras fichas que dependen de él.',
                'Elimine primero sus estudios, evaluaciones, reconocimientos o experiencia.'
            );
        }

        // «Ya existe» → 409.
        if (stripos($mensaje, 'ya existe') !== false) {
            throw new ConflictoDeIntegridadExcepcion(
                'Ya existe el docente con esa llave.',
                'La cédula que intentó usar ya está registrada.'
            );
        }

        // Cualquier otro rechazo no reconocido sube como 500.
        throw $error;
    }

    /**
     * Heurística para reconocer «no existe» en el texto del SIGNAL.
     */
    private function esMensajeDeNoExistencia(string $mensaje): bool
    {
        return stripos($mensaje, 'no existe') !== false
            || stripos($mensaje, 'no encontrad') !== false;
    }
}