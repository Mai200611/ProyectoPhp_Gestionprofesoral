<?php
declare(strict_types=1);

/**
 * cliente_api.php
 *
 * ÚNICO archivo del front que habla HTTP con la API (RNF6, Artículo 3).
 * Ninguna vista conoce esta traducción; todas usan las funciones de aquí.
 *
 * Contrato de cada función de lectura:
 *   - Devuelve un array con `ok` (bool), `datos` (array), `mensaje` (string).
 *   - 200 con lista → ok=true, datos=lista.
 *   - 204 (tabla vacía) → ok=true, datos=[] y un mensaje comprensible.
 *   - 400/404/409 → ok=false y mensaje en español (junta mensaje+detalle).
 *   - 422 → ok=false y errores=[...] para pintar uno por línea.
 *   - API caída → null (distinto de error: es «no disponible»).
 */

function urlBaseApi(): string
{
    $url = getenv('URL_API');
    if ($url === false || $url === '') {
        $url = 'http://api-gestion:8022';
    }
    return rtrim($url, '/');
}

/**
 * @param array<string, mixed>|null $cuerpo
 * @return array{0:int, 1:string|null}
 */
function peticionApi(string $metodo, string $ruta, ?array $cuerpo = null): array
{
    $url = urlBaseApi() . $ruta;
    $opciones = [
        'method' => $metodo,
        'ignore_errors' => true,
        'timeout' => 5,
    ];
    if ($cuerpo !== null) {
        $opciones['header'] = "Content-Type: application/json\r\n";
        $opciones['content'] = json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }

    $contexto = stream_context_create(['http' => $opciones]);
    $respuesta = @file_get_contents($url, false, $contexto);

    if ($respuesta === false) {
        return [0, null];
    }

    $codigo = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $linea) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $linea, $m)) {
                $codigo = (int) $m[1];
            }
        }
    }

    return [$codigo, $respuesta];
}

/**
 * @return array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}
 */
function interpretarRespuesta(int $codigo, ?string $cuerpoCrudo): array
{
    if ($codigo === 0) {
        return ['ok' => false, 'datos' => [], 'mensaje' => 'El servicio no está disponible.', 'errores' => [], 'id' => null];
    }

    if ($codigo === 204) {
        return ['ok' => true, 'datos' => [], 'mensaje' => '', 'errores' => [], 'id' => null];
    }

    $decodificado = null;
    if ($cuerpoCrudo !== null && $cuerpoCrudo !== '') {
        $decodificado = json_decode($cuerpoCrudo, true);
    }

    if ($codigo >= 200 && $codigo < 300) {
        $datos = $decodificado;
        $id = null;
        if (is_array($decodificado)) {
            if (isset($decodificado['datos']) && is_array($decodificado['datos'])) {
                $datos = $decodificado['datos'];
            }
            if (isset($decodificado['id'])) {
                $id = (int) $decodificado['id'];
            }
        }
        $mensaje = '';
        if (is_array($decodificado) && isset($decodificado['mensaje'])) {
            $mensaje = (string) $decodificado['mensaje'];
        }
        return ['ok' => true, 'datos' => is_array($datos) ? $datos : [], 'mensaje' => $mensaje, 'errores' => [], 'id' => $id];
    }

    if (is_array($decodificado)) {
        $mensaje = (string) ($decodificado['mensaje'] ?? 'Ocurrió un problema.');
        $detalle = (string) ($decodificado['detalle'] ?? '');
        if ($detalle !== '') {
            $mensaje = rtrim($mensaje, '.') . '. ' . $detalle;
        }
        return ['ok' => false, 'datos' => [], 'mensaje' => $mensaje, 'errores' => [], 'id' => null];
    }

    return ['ok' => false, 'datos' => [], 'mensaje' => 'Ocurrió un problema inesperado.', 'errores' => [], 'id' => null];
}

// ==================================================================
// Funciones de la v1 (se asume que se mantienen)
// ==================================================================
// (Si tienes las funciones de la v1, asegúrate de que estén aquí arriba)

// ==================================================================
// Funciones de la v2
// ==================================================================

// ---------- docente -----------------------------------------------
function listar_docentes(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/docente?limite=1000');
    return interpretarRespuesta($c, $b);
}

function obtener_docente(string $cedula): ?array
{
    [$c, $b] = peticionApi('GET', '/api/docente/' . rawurlencode($cedula));
    return interpretarRespuesta($c, $b);
}

function obtener_docente_completo(string $cedula): ?array
{
    [$c, $b] = peticionApi('GET', '/api/docente/' . rawurlencode($cedula) . '/completo');
    return interpretarRespuesta($c, $b);
}

function crear_docente(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/docente', $datos);
    return interpretarRespuesta($c, $b);
}

function reemplazar_docente(string $cedula, array $datos): ?array
{
    [$c, $b] = peticionApi('PUT', '/api/docente/' . rawurlencode($cedula), $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_docente(string $cedula): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/docente/' . rawurlencode($cedula));
    return interpretarRespuesta($c, $b);
}

// ---------- estudios_realizados -----------------------------------
function listar_estudios(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/estudios_realizados?limite=1000');
    return interpretarRespuesta($c, $b);
}

function obtener_estudio(string $id): ?array
{
    [$c, $b] = peticionApi('GET', '/api/estudios_realizados/' . rawurlencode($id));
    return interpretarRespuesta($c, $b);
}

function crear_estudio(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/estudios_realizados', $datos);
    return interpretarRespuesta($c, $b);
}

function reemplazar_estudio(string $id, array $datos): ?array
{
    [$c, $b] = peticionApi('PUT', '/api/estudios_realizados/' . rawurlencode($id), $datos);
    return interpretarRespuesta($c, $b);
}

function actualizar_estudio(string $id, array $datos): ?array
{
    [$c, $b] = peticionApi('PATCH', '/api/estudios_realizados/' . rawurlencode($id), $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_estudio(string $id): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/estudios_realizados/' . rawurlencode($id));
    return interpretarRespuesta($c, $b);
}

// ---------- docente_departamento ----------------------------------
function listar_departamentos(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/docente_departamento?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_departamento(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/docente_departamento', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_departamento(string $docente, string $departamento): ?array
{
    $ruta = '/api/docente_departamento/' . rawurlencode($docente) . '/' . rawurlencode($departamento);
    [$c, $b] = peticionApi('DELETE', $ruta);
    return interpretarRespuesta($c, $b);
}

// ---------- intereses_futuros -------------------------------------
function listar_intereses(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/intereses_futuros?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_interes(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/intereses_futuros', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_interes(string $docente, string $termino): ?array
{
    $ruta = '/api/intereses_futuros/' . rawurlencode($docente) . '/' . rawurlencode($termino);
    [$c, $b] = peticionApi('DELETE', $ruta);
    return interpretarRespuesta($c, $b);
}

// ---------- evaluacion_docente ------------------------------------
function listar_evaluaciones(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/evaluacion_docente?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_evaluacion(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/evaluacion_docente', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_evaluacion(string $id): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/evaluacion_docente/' . rawurlencode($id));
    return interpretarRespuesta($c, $b);
}

// ---------- reconocimiento ----------------------------------------
function listar_reconocimientos(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/reconocimiento?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_reconocimiento(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/reconocimiento', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_reconocimiento(string $id): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/reconocimiento/' . rawurlencode($id));
    return interpretarRespuesta($c, $b);
}

// ---------- experiencia (tabla experiecia) ------------------------
function listar_experiencias(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/experiecia?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_experiencia(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/experiecia', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_experiencia(string $id): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/experiecia/' . rawurlencode($id));
    return interpretarRespuesta($c, $b);
}

// ---------- red_docente -------------------------------------------
function listar_redes_docente(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/red_docente?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_red_docente(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/red_docente', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_red_docente(string $red, string $docente): ?array
{
    $ruta = '/api/red_docente/' . rawurlencode($red) . '/' . rawurlencode($docente);
    [$c, $b] = peticionApi('DELETE', $ruta);
    return interpretarRespuesta($c, $b);
}

// ---------- estudio_ac --------------------------------------------
function listar_estudios_area(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/estudio_ac?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_estudio_area(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/estudio_ac', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_estudio_area(string $estudio, string $area): ?array
{
    $ruta = '/api/estudio_ac/' . rawurlencode($estudio) . '/' . rawurlencode($area);
    [$c, $b] = peticionApi('DELETE', $ruta);
    return interpretarRespuesta($c, $b);
}

// ---------- apoyo_profesoral --------------------------------------
function listar_apoyos(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/apoyo_profesoral?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_apoyo(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/apoyo_profesoral', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_apoyo(string $estudios): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/apoyo_profesoral/' . rawurlencode($estudios));
    return interpretarRespuesta($c, $b);
}

// ---------- beca --------------------------------------------------
function listar_becas(): ?array
{
    [$c, $b] = peticionApi('GET', '/api/beca?limite=1000');
    return interpretarRespuesta($c, $b);
}

function crear_beca(array $datos): ?array
{
    [$c, $b] = peticionApi('POST', '/api/beca', $datos);
    return interpretarRespuesta($c, $b);
}

function eliminar_beca(string $estudios): ?array
{
    [$c, $b] = peticionApi('DELETE', '/api/beca/' . rawurlencode($estudios));
    return interpretarRespuesta($c, $b);
}