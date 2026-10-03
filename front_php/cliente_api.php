<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la ÚNICA pieza del front que habla HTTP con la API. Todo lo que el
 *   front sabe de la API vive aquí.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el "repositorio" del front (la analogía la hace 3_plan.md §10): el
 *   equivalente del repositorio en la API, pero del lado del cliente. Su
 *   trabajo es convertir la comunicación HTTP en una estructura simple que
 *   las vistas puedan entender sin saber qué es un 404, un 204 o un cURL.
 *
 *   La estructura que devuelve cada función es siempre la misma:
 *
 *       [
 *           'ok'      => bool,
 *           'datos'   => mixed,       // array o null
 *           'errores' => string[],    // lista de mensajes en español
 *       ]
 *
 *   Las vistas solo miran 'ok' y 'errores'; nunca miran códigos HTTP.
 *
 * ¿Qué NO hace?
 *   - NO pinta HTML.
 *   - NO toca la base de datos. Cero `new PDO`. Cero `require` de
 *     clases de la API (RNF6). Lo único que comparte con la API es el
 *     JSON (D11 del research).
 *   - NO deja que la jerga técnica se escape a la pantalla. La traducción
 *     de "422" a "Los datos enviados tienen errores" ocurre aquí y en
 *     ningún otro lado (RNF7).
 *
 * ¿De dónde saca la URL de la API?
 *   De la variable de entorno URL_API, que el compose le inyecta al
 *   contenedor del front. Adentro del compose vale
 *   "http://api-gestion:8113" (nombre del servicio, NUNCA localhost:
 *   dentro del contenedor del front, localhost es él mismo).
 *
 * ¿Cómo trata al 204?
 *   El 204 no es un error: es la API diciendo "no hay filas". Aquí se
 *   convierte a ok=true con datos=[], y las vistas muestran "todavía no
 *   hay registros" (clarificación C1 de la spec).
 *
 * ¿Cómo trata a "la API no responde"?
 *   Eso NO es lo mismo que un 404. Un 404 es la API funcionando y
 *   diciendo "no existe". Que no llegue nada es que no hay con quién
 *   hablar. Esa diferencia se representa devolviendo null desde
 *   llamar_api() y marcando 'ok' = false con el mensaje "El servicio no
 *   está disponible." (6_contracts.md §10).
 * ---------------------------------------------------------------------------
 */

/**
 * URL base de la API. En Docker viene del entorno. El valor por defecto
 * apunta al puerto del host que publica el compose (8122), por si se
 * corre el front con `php -S` en la máquina, fuera de Docker.
 */
function url_api(): string
{
    $url = getenv('URL_API');
    if ($url === false || $url === '') {
        return 'http://localhost:8122';
    }
    return $url;
}

/**
 * Llama a la API por HTTP y devuelve el cuerpo decodificado (array), o
 * null si la API no respondió.
 *
 * Esta función NO empaqueta el resultado en el sobre ['ok', 'datos',
 * 'errores']: eso lo hacen las funciones de más abajo, que sí conocen el
 * contrato de cada endpoint. Aquí solo se maneja el transporte HTTP.
 *
 * Devuelve null en estos casos:
 *   - cURL falla (no hay con quién hablar).
 *   - La respuesta no trae JSON válido.
 * En cualquier respuesta HTTP con JSON (200, 204, 400, 404, 422, 500),
 * devuelve el array decodificado tal cual.
 */
function llamar_api(string $metodo, string $ruta, ?array $cuerpo = null): ?array
{
    $url = url_api() . $ruta;

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }

    // Cabeceras: JSON tanto lo que mando como lo que espero recibir.
    $cabeceras = ['Content-Type: application/json'];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $cabeceras);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    if ($cuerpo !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cuerpo));
    }

    $respuestaCruda = curl_exec($ch);
    $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    // Si cURL falló, la API no responde: null, que NO es lo mismo que un
    // 404. La API está caída (o hay un problema de red).
    if ($respuestaCruda === false || $errorCurl !== '') {
        return null;
    }

    // El código 204 viene SIN cuerpo: lo tratamos como array vacío con un
    // marcador interno para que las funciones de arriba lo distingan.
    if ($codigoHttp === 204) {
        return ['__codigo' => 204];
    }

    $decodificado = json_decode((string) $respuestaCruda, true);
    if (!is_array($decodificado)) {
        return null;
    }

    // Metemos el código HTTP en el array para que las funciones de arriba
    // decidan qué hacer con el sobre de error.
    $decodificado['__codigo'] = $codigoHttp;
    return $decodificado;
}

/**
 * Traduce el sobre de error de la API a una lista de mensajes en español
 * para mostrar en la pantalla. El usuario nunca ve "422" ni "404": ve la
 * razón, en texto.
 *
 * Reglas:
 *   - Si el cuerpo trae 'errores' (lista), se devuelve tal cual.
 *   - Si no, se arma un mensaje con 'mensaje' y 'detalle' del sobre.
 *   - Si tampoco hay eso, se usa un mensaje genérico.
 */
function mensajes_de_error(?array $respuesta): array
{
    if ($respuesta === null) {
        return ['El servicio no está disponible.'];
    }

    if (isset($respuesta['errores']) && is_array($respuesta['errores']) && $respuesta['errores'] !== []) {
        return array_map('strval', $respuesta['errores']);
    }

    $mensaje = $respuesta['mensaje'] ?? 'Ocurrió un error inesperado.';
    if (isset($respuesta['detalle']) && $respuesta['detalle'] !== '') {
        // El detalle viene del motor. Se muestra tal cual pero SIN
        // códigos HTTP ni rutas de la API.
        $mensaje .= ' ' . $respuesta['detalle'];
    }
    return [(string) $mensaje];
}

// ===========================================================================
// FUNCIONES CLIENTE POR RECURSO
// ===========================================================================
// Una función por operación y recurso — específicas, no genéricas
// (Artículo 10 de la constitución).
//
// Cada una devuelve el sobre uniforme:
//     ['ok' => bool, 'datos' => mixed, 'errores' => string[]]
// ===========================================================================

// ---------------------------------------------------------------------------
// AREA_CONOCIMIENTO
// ---------------------------------------------------------------------------

function listar_areas_conocimiento(): array
{
    $r = llamar_api('GET', '/api/area_conocimiento');
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 204) {
        return ['ok' => true, 'datos' => [], 'errores' => []];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function obtener_area_conocimiento(string $id): array
{
    $r = llamar_api('GET', '/api/area_conocimiento/' . urlencode($id));
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function crear_area_conocimiento(array $datos): array
{
    $r = llamar_api('POST', '/api/area_conocimiento', $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function reemplazar_area_conocimiento(string $id, array $datos): array
{
    $r = llamar_api('PUT', '/api/area_conocimiento/' . urlencode($id), $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function actualizar_area_conocimiento(string $id, array $datos): array
{
    $r = llamar_api('PATCH', '/api/area_conocimiento/' . urlencode($id), $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function eliminar_area_conocimiento(string $id): array
{
    $r = llamar_api('DELETE', '/api/area_conocimiento/' . urlencode($id));
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// ---------------------------------------------------------------------------
// TERMINO_CLAVE
// ---------------------------------------------------------------------------

function listar_terminos_clave(): array
{
    $r = llamar_api('GET', '/api/termino_clave');
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 204) {
        return ['ok' => true, 'datos' => [], 'errores' => []];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function obtener_termino_clave(string $termino): array
{
    $r = llamar_api('GET', '/api/termino_clave/' . urlencode($termino));
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function crear_termino_clave(array $datos): array
{
    $r = llamar_api('POST', '/api/termino_clave', $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function reemplazar_termino_clave(string $termino, array $datos): array
{
    $r = llamar_api('PUT', '/api/termino_clave/' . urlencode($termino), $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function actualizar_termino_clave(string $termino, array $datos): array
{
    $r = llamar_api('PATCH', '/api/termino_clave/' . urlencode($termino), $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function eliminar_termino_clave(string $termino): array
{
    $r = llamar_api('DELETE', '/api/termino_clave/' . urlencode($termino));
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// ---------------------------------------------------------------------------
// LINEA_INVESTIGACION
// ---------------------------------------------------------------------------

function listar_lineas_investigacion(): array
{
    $r = llamar_api('GET', '/api/linea_investigacion');
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 204) {
        return ['ok' => true, 'datos' => [], 'errores' => []];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function obtener_linea_investigacion(int $id): array
{
    $r = llamar_api('GET', '/api/linea_investigacion/' . $id);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function crear_linea_investigacion(array $datos): array
{
    $r = llamar_api('POST', '/api/linea_investigacion', $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function reemplazar_linea_investigacion(int $id, array $datos): array
{
    $r = llamar_api('PUT', '/api/linea_investigacion/' . $id, $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function actualizar_linea_investigacion(int $id, array $datos): array
{
    $r = llamar_api('PATCH', '/api/linea_investigacion/' . $id, $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function eliminar_linea_investigacion(int $id): array
{
    $r = llamar_api('DELETE', '/api/linea_investigacion/' . $id);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// ---------------------------------------------------------------------------
// PROGRAMA
// ---------------------------------------------------------------------------

function listar_programas(): array
{
    $r = llamar_api('GET', '/api/programa');
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 204) {
        return ['ok' => true, 'datos' => [], 'errores' => []];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function obtener_programa(int $id): array
{
    $r = llamar_api('GET', '/api/programa/' . $id);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function crear_programa(array $datos): array
{
    $r = llamar_api('POST', '/api/programa', $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function reemplazar_programa(int $id, array $datos): array
{
    $r = llamar_api('PUT', '/api/programa/' . $id, $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function actualizar_programa(int $id, array $datos): array
{
    $r = llamar_api('PATCH', '/api/programa/' . $id, $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function eliminar_programa(int $id): array
{
    $r = llamar_api('DELETE', '/api/programa/' . $id);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// ---------------------------------------------------------------------------
// RED
// ---------------------------------------------------------------------------

function listar_redes(): array
{
    $r = llamar_api('GET', '/api/red');
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 204) {
        return ['ok' => true, 'datos' => [], 'errores' => []];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function obtener_red(int $idr): array
{
    $r = llamar_api('GET', '/api/red/' . $idr);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function crear_red(array $datos): array
{
    $r = llamar_api('POST', '/api/red', $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function reemplazar_red(int $idr, array $datos): array
{
    $r = llamar_api('PUT', '/api/red/' . $idr, $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function actualizar_red(int $idr, array $datos): array
{
    $r = llamar_api('PATCH', '/api/red/' . $idr, $datos);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

function eliminar_red(int $idr): array
{
    $r = llamar_api('DELETE', '/api/red/' . $idr);
    if ($r === null) {
        return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    }
    if (($r['__codigo'] ?? 0) === 200) {
        return ['ok' => true, 'datos' => $r, 'errores' => []];
    }
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}