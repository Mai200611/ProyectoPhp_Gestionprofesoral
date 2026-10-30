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
}<?php
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
 *
 * Contrato de cada función de escritura:
 *   - Devuelve un array con `ok`, `mensaje`, `errores` e `id` (si aplica).
 *
 * Traducción del 409 (RF18, 6_contracts §12): se muestra `mensaje` + `detalle`
 * tal cual vienen, SIN números de estado y SIN jerga.
 */

/**
 * URL base de la API. Se toma de entorno, con el valor del compose.
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
 * Ejecuta una petición HTTP a la API y devuelve [codigo, cuerpoCrudo|null].
 * Si la API no responde, devuelve [0, null] (no es error: es caída).
 *
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
 * Traduce la respuesta de la API a la forma que las vistas esperan.
 *
 * @return array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}
 */
function interpretarRespuesta(int $codigo, ?string $cuerpoCrudo): array
{
    $vacio = ['ok' => false, 'datos' => [], 'mensaje' => '', 'errores' => [], 'id' => null];

    if ($codigo === 0) {
        // La API no respondió: la vista muestra el aviso, no un error.
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

    // 400 / 404 / 409 / 500: mensaje + detalle en un solo string, sin jerga.
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
// Funciones de la v1 (area_conocimiento, termino_clave,
// linea_investigacion, programa, red) — SIN CAMBIOS.
// ==================================================================
// (AQUÍ VAN TAL CUAL LAS FUNCIONES DE LA v1. No se tocan.)

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