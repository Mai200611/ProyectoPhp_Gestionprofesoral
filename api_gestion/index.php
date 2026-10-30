<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// REQUIRES
// El front controller de la API carga todos los controladores. Cada
// controlador, a su vez, carga su interfaz de servicio, su modelo y el
// ensamblador, así que desde aquí basta con los controladores.
// ---------------------------------------------------------------------------
require_once __DIR__ . '/controladores/ControladorAreaConocimiento.php';
require_once __DIR__ . '/controladores/ControladorTerminoClave.php';
require_once __DIR__ . '/controladores/ControladorLineaInvestigacion.php';
require_once __DIR__ . '/controladores/ControladorPrograma.php';
require_once __DIR__ . '/controladores/ControladorRed.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el FRONT CONTROLLER de la API: el único punto de entrada. TODAS las
 *   peticiones HTTP a la API pasan por aquí.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el enrutador. Su trabajo es:
 *     1. Fijar el header JSON.
 *     2. Leer el método HTTP (GET, POST, PUT, PATCH, DELETE).
 *     3. Leer la ruta desde REQUEST_URI y quitarle el query string.
 *     4. Identificar el recurso y el verbo (por if/elseif explícitos,
 *        como pide el 3_plan.md §9).
 *     5. Delegar al método correspondiente del controlador.
 *     6. Convertir la respuesta ['codigo' => int, 'cuerpo' => array] en
 *        la respuesta HTTP real (http_response_code + echo json_encode).
 *
 * ¿Qué NO hace?
 *   - NO valida datos (eso es tarea del controlador).
 *   - NO conoce la base de datos.
 *   - NO arma SQL.
 *
 * ¿Qué endpoints cubre?
 *   - GET    /                          → diagnóstico
 *   - GET    /api/area_conocimiento     → listar
 *   - GET    /api/area_conocimiento/{id} → obtener
 *   - POST   /api/area_conocimiento     → crear
 *   - PUT    /api/area_conocimiento/{id} → reemplazar
 *   - PATCH  /api/area_conocimiento/{id} → actualizar parcial
 *   - DELETE /api/area_conocimiento/{id} → eliminar
 *   ... y el mismo juego para termino_clave, linea_investigacion, programa
 *   y red. En total, 7 endpoints por recurso × 5 recursos + 1 diagnóstico.
 *
 * ¿Qué hace cuando la ruta no existe?
 *   Devuelve 404 con un mensaje claro en JSON.
 *
 * ¿Qué hace cuando el verbo no corresponde a una ruta existente?
 *   Devuelve 405 (method not allowed) con un mensaje claro.
 * ---------------------------------------------------------------------------
 */

// 1. Cabecera JSON para todas las respuestas.
header('Content-Type: application/json; charset=utf-8');

// 2. Método HTTP (GET, POST, PUT, PATCH, DELETE).
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 3. Ruta sin query string.
//    REQUEST_URI trae, por ejemplo: "/api/programa/1?limite=3"
//    Queremos quedarnos solo con: "/api/programa/1".
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$posInterrogacion = strpos($uri, '?');
if ($posInterrogacion !== false) {
    $uri = substr($uri, 0, $posInterrogacion);
}

// 4. Normalizar la ruta: quitar la barra final (excepto cuando la ruta es "/").
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}

// 5. Query string ya parseado por PHP (en $_GET).
$query = $_GET;

// 6. Leer el body JSON (solo si el método lo admite).
//    json_decode con true para que devuelva array asociativo.
$bodyCrudo = file_get_contents('php://input');
$body = [];
if ($bodyCrudo !== false && trim($bodyCrudo) !== '') {
    $decodificado = json_decode($bodyCrudo, true);
    if (is_array($decodificado)) {
        $body = $decodificado;
    }
    // Si el body venía mal formado, $body queda como array vacío y el
    // controlador responderá 422 cuando lo valide.
}

// 7. Enrutamiento explícito, con if/elseif por ruta y verbo.
//    La razón de hacerlo así (y no con un array de rutas o regex) es
//    didáctica: todo el enrutamiento se lee de arriba abajo en un solo
//    archivo, y cada caso se ve a simple vista (3_plan.md §9).

// ---------------------------------------------------------------------------
// GET /  → diagnóstico
// ---------------------------------------------------------------------------
if ($metodo === 'GET' && $uri === '/') {
    responderJson(200, [
        'mensaje'   => 'API Gestión funcionando',
        'version'   => 'v1',
        'contratos' => 'docs/spec_kit/versiones/v1_catalogo_mariadb/6_contracts.md',
    ]);
    exit;
}

// ===========================================================================
// RECURSO: /api/area_conocimiento
// ===========================================================================
if (preg_match('#^/api/area_conocimiento$#', $uri)) {
    $controlador = new ControladorAreaConocimiento();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->listar($query));
        exit;
    }
    if ($metodo === 'POST') {
        responderRespuesta($controlador->crear($body));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

if (preg_match('#^/api/area_conocimiento/([^/]+)$#', $uri, $coincidencias)) {
    $id = urldecode($coincidencias[1]);
    $controlador = new ControladorAreaConocimiento();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->obtener($id));
        exit;
    }
    if ($metodo === 'PUT') {
        responderRespuesta($controlador->reemplazar($id, $body));
        exit;
    }
    if ($metodo === 'PATCH') {
        responderRespuesta($controlador->actualizarParcial($id, $body));
        exit;
    }
    if ($metodo === 'DELETE') {
        responderRespuesta($controlador->eliminar($id));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

// ===========================================================================
// RECURSO: /api/termino_clave
// ===========================================================================
if (preg_match('#^/api/termino_clave$#', $uri)) {
    $controlador = new ControladorTerminoClave();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->listar($query));
        exit;
    }
    if ($metodo === 'POST') {
        responderRespuesta($controlador->crear($body));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

if (preg_match('#^/api/termino_clave/([^/]+)$#', $uri, $coincidencias)) {
    $termino = urldecode($coincidencias[1]);
    $controlador = new ControladorTerminoClave();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->obtener($termino));
        exit;
    }
    if ($metodo === 'PUT') {
        responderRespuesta($controlador->reemplazar($termino, $body));
        exit;
    }
    if ($metodo === 'PATCH') {
        responderRespuesta($controlador->actualizarParcial($termino, $body));
        exit;
    }
    if ($metodo === 'DELETE') {
        responderRespuesta($controlador->eliminar($termino));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

// ===========================================================================
// RECURSO: /api/linea_investigacion
// ===========================================================================
if (preg_match('#^/api/linea_investigacion$#', $uri)) {
    $controlador = new ControladorLineaInvestigacion();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->listar($query));
        exit;
    }
    if ($metodo === 'POST') {
        responderRespuesta($controlador->crear($body));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

if (preg_match('#^/api/linea_investigacion/([^/]+)$#', $uri, $coincidencias)) {
    $idTexto = urldecode($coincidencias[1]);

    // El id de este recurso es entero. Si no lo es, respondemos 400.
    if (!ctype_digit($idTexto)) {
        responderJson(400, [
            'estado'  => 400,
            'mensaje' => 'El id debe ser un número entero.',
        ]);
        exit;
    }
    $id = (int) $idTexto;
    $controlador = new ControladorLineaInvestigacion();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->obtener($id));
        exit;
    }
    if ($metodo === 'PUT') {
        responderRespuesta($controlador->reemplazar($id, $body));
        exit;
    }
    if ($metodo === 'PATCH') {
        responderRespuesta($controlador->actualizarParcial($id, $body));
        exit;
    }
    if ($metodo === 'DELETE') {
        responderRespuesta($controlador->eliminar($id));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

// ===========================================================================
// RECURSO: /api/programa
// ===========================================================================
if (preg_match('#^/api/programa$#', $uri)) {
    $controlador = new ControladorPrograma();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->listar($query));
        exit;
    }
    if ($metodo === 'POST') {
        responderRespuesta($controlador->crear($body));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

if (preg_match('#^/api/programa/([^/]+)$#', $uri, $coincidencias)) {
    $idTexto = urldecode($coincidencias[1]);

    if (!ctype_digit($idTexto)) {
        responderJson(400, [
            'estado'  => 400,
            'mensaje' => 'El id debe ser un número entero.',
        ]);
        exit;
    }
    $id = (int) $idTexto;
    $controlador = new ControladorPrograma();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->obtener($id));
        exit;
    }
    if ($metodo === 'PUT') {
        responderRespuesta($controlador->reemplazar($id, $body));
        exit;
    }
    if ($metodo === 'PATCH') {
        responderRespuesta($controlador->actualizarParcial($id, $body));
        exit;
    }
    if ($metodo === 'DELETE') {
        responderRespuesta($controlador->eliminar($id));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

// ===========================================================================
// RECURSO: /api/red
// ===========================================================================
if (preg_match('#^/api/red$#', $uri)) {
    $controlador = new ControladorRed();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->listar($query));
        exit;
    }
    if ($metodo === 'POST') {
        responderRespuesta($controlador->crear($body));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

if (preg_match('#^/api/red/([^/]+)$#', $uri, $coincidencias)) {
    $idrTexto = urldecode($coincidencias[1]);

    if (!ctype_digit($idrTexto)) {
        responderJson(400, [
            'estado'  => 400,
            'mensaje' => 'El idr debe ser un número entero.',
        ]);
        exit;
    }
    $idr = (int) $idrTexto;
    $controlador = new ControladorRed();

    if ($metodo === 'GET') {
        responderRespuesta($controlador->obtener($idr));
        exit;
    }
    if ($metodo === 'PUT') {
        responderRespuesta($controlador->reemplazar($idr, $body));
        exit;
    }
    if ($metodo === 'PATCH') {
        responderRespuesta($controlador->actualizarParcial($idr, $body));
        exit;
    }
    if ($metodo === 'DELETE') {
        responderRespuesta($controlador->eliminar($idr));
        exit;
    }
    responderJson(405, [
        'estado'  => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
    ]);
    exit;
}

// ===========================================================================
// RUTA NO ENCONTRADA
// ===========================================================================
responderJson(404, [
    'estado'  => 404,
    'mensaje' => 'Ruta no encontrada.',
    'detalle' => "No existe el recurso {$uri}",
]);
exit;

// ===========================================================================
// FUNCIONES AUXILIARES
// ===========================================================================

/**
 * Responde un JSON con el código HTTP indicado. Se usa para el endpoint
 * de diagnóstico, los 404, los 405 y demás respuestas "planas".
 */
function responderJson(int $codigo, array $cuerpo): void
{
    http_response_code($codigo);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Responde lo que devolvió un controlador. Si el código es 204, NO se
 * envía cuerpo (así lo exige el contrato §1: "204 sin cuerpo"). El
 * http_response_code(204) además le dice al cliente que ignore el body.
 */
function responderRespuesta(array $respuesta): void
{
    $codigo = $respuesta['codigo'];
    $cuerpo = $respuesta['cuerpo'];

    http_response_code($codigo);

    if ($codigo === 204 || $cuerpo === null) {
        return;
    }

    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}