<?php
declare(strict_types=1);

/**
 * index.php
 *
 * Front controller de la API (api_gestion, puerto 8022).
 *
 * Único punto de entrada: recibe la petición, la enruta al controlador
 * correspondiente y emite la respuesta JSON. Todo el enrutamiento vive aquí,
 * escrito a mano y legible completo (Artículo 2 de la constitución).
 *
 * Responsabilidades:
 *   1. Leer método + ruta + query + body.
 *   2. Despachar a la rama correcta.
 *   3. Responder 405 si el método no está registrado para esa ruta.
 *   4. Responder 404 si la ruta no existe.
 *   5. Responder el diagnóstico en GET /.
 *
 * Lo que este enrutador NO hace:
 *   - No usa un switch por recurso armando el nombre de la clase con texto
 *     (Artículo 10). Cada recurso tiene su bloque escrito.
 *   - No registra PATCH para /api/docente: el docente no admite PATCH, y
 *     si llega uno cae al 405 por defecto (RF12).
 *
 * La v1 sigue respondiendo exactamente igual: sus cinco bloques se
 * conservan y se les delega como antes.
 */

require_once __DIR__ . '/servicios/ensamblador.php';
require_once __DIR__ . '/controladores/ControladorDocente.php';
require_once __DIR__ . '/controladores/ControladorEstudiosRealizados.php';
require_once __DIR__ . '/controladores/ControladorDocenteDepartamento.php';
require_once __DIR__ . '/controladores/ControladorInteresesFuturos.php';
require_once __DIR__ . '/controladores/ControladorEvaluacionDocente.php';
require_once __DIR__ . '/controladores/ControladorReconocimiento.php';
require_once __DIR__ . '/controladores/ControladorExperiencia.php';
require_once __DIR__ . '/controladores/ControladorRedDocente.php';
require_once __DIR__ . '/controladores/ControladorEstudioAc.php';
require_once __DIR__ . '/controladores/ControladorApoyoProfesoral.php';
require_once __DIR__ . '/controladores/ControladorBeca.php';
require_once __DIR__ . '/../config/conexion.php';

// ------------------------------------------------------------------
// Diagnóstico: GET /
// ------------------------------------------------------------------
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$ruta = rtrim($ruta, '/');
if ($ruta === '') {
    $ruta = '/';
}

if ($metodo === 'GET' && $ruta === '/') {
    responderJson(200, [
        'mensaje' => 'API Gestión funcionando',
        'version' => 'v2',
        'recursos' => [
            'area_conocimiento', 'termino_clave', 'linea_investigacion',
            'programa', 'red',
            'docente', 'estudios_realizados', 'docente_departamento',
            'intereses_futuros', 'evaluacion_docente', 'reconocimiento',
            'experiecia', 'red_docente', 'estudio_ac',
            'apoyo_profesoral', 'beca',
        ],
        'contratos' => 'docs/spec_kit/versiones/v2_tablas_con_fk/6_contracts.md',
    ]);
    exit;
}

// ------------------------------------------------------------------
// Cuerpo y query
// ------------------------------------------------------------------
$cuerpoCrudo = file_get_contents('php://input') ?: '';
$body = [];
if ($cuerpoCrudo !== '') {
    $decodificado = json_decode($cuerpoCrudo, true);
    if (is_array($decodificado)) {
        $body = $decodificado;
    }
}

// ------------------------------------------------------------------
// Bloques de enrutamiento, uno por recurso
// ------------------------------------------------------------------
$conexion = obtenerConexion();

// --- /api/docente ---------------------------------------------------
if (preg_match('#^/api/docente(?:/([^/]+))?(?:/(completo))?$#', $ruta, $m)) {
    $controlador = new ControladorDocente(crearServicioDocente($conexion));
    $cedula = $m[1] ?? null;
    $completo = $m[2] ?? null;

    if ($metodo === 'GET') {
        $parametrosRuta = [];
        if ($cedula !== null) {
            $parametrosRuta['cedula'] = $cedula;
        }
        if ($completo !== null) {
            $parametrosRuta['completo'] = $completo;
        }
        $controlador->manejarGet($parametrosRuta, $_GET);
        exit;
    }
    if ($metodo === 'POST' && $cedula === null) {
        $controlador->manejarPost($body);
        exit;
    }
    if ($metodo === 'PUT' && $cedula !== null) {
        $controlador->manejarPut(['cedula' => $cedula], $body);
        exit;
    }
    if ($metodo === 'DELETE' && $cedula !== null) {
        $controlador->manejarDelete(['cedula' => $cedula]);
        exit;
    }
    // Nota: NO hay rama para PATCH. Cae al 405 por defecto (RF12).
    responderJson(405, [
        'estado' => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
        'detalle' => '',
    ]);
    exit;
}

// --- /api/estudios_realizados --------------------------------------
if (preg_match('#^/api/estudios_realizados(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorEstudiosRealizados(crearServicioEstudiosRealizados($conexion));
    $id = $m[1] ?? null;
    $this->despacharFicha($metodo, $id, $controlador, $_GET, $body);
    exit;
}

// --- /api/evaluacion_docente ---------------------------------------
if (preg_match('#^/api/evaluacion_docente(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorEvaluacionDocente(crearServicioEvaluacionDocente($conexion));
    $id = $m[1] ?? null;
    $this->despacharFicha($metodo, $id, $controlador, $_GET, $body);
    exit;
}

// --- /api/reconocimiento -------------------------------------------
if (preg_match('#^/api/reconocimiento(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorReconocimiento(crearServicioReconocimiento($conexion));
    $id = $m[1] ?? null;
    $this->despacharFicha($metodo, $id, $controlador, $_GET, $body);
    exit;
}

// --- /api/experiecia -----------------------------------------------
if (preg_match('#^/api/experiecia(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorExperiencia(crearServicioExperiencia($conexion));
    $id = $m[1] ?? null;
    $this->despacharFicha($metodo, $id, $controlador, $_GET, $body);
    exit;
}

// --- /api/docente_departamento -------------------------------------
if (preg_match('#^/api/docente_departamento(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorDocenteDepartamento(crearServicioDocenteDepartamento($conexion));
    $docente = $m[1] ?? null;
    $departamento = $m[2] ?? null;

    if ($metodo === 'GET') {
        $parametrosRuta = [];
        if ($docente !== null) {
            $parametrosRuta['docente'] = $docente;
        }
        if ($departamento !== null) {
            $parametrosRuta['departamento'] = $departamento;
        }
        $controlador->manejarGet($parametrosRuta, $_GET);
        exit;
    }
    if ($metodo === 'POST' && $docente === null) {
        $controlador->manejarPost($body);
        exit;
    }
    if ($docente !== null && $departamento !== null) {
        $parametrosRuta = ['docente' => $docente, 'departamento' => $departamento];
        if ($metodo === 'PUT') {
            $controlador->manejarPut($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'PATCH') {
            $controlador->manejarPatch($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'DELETE') {
            $controlador->manejarDelete($parametrosRuta);
            exit;
        }
    }
    responderMetodoNoPermitido();
}

// --- /api/intereses_futuros ----------------------------------------
if (preg_match('#^/api/intereses_futuros(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorInteresesFuturos(crearServicioInteresesFuturos($conexion));
    $docente = $m[1] ?? null;
    $termino = $m[2] ?? null;

    if ($metodo === 'GET') {
        $parametrosRuta = [];
        if ($docente !== null) {
            $parametrosRuta['docente'] = $docente;
        }
        if ($termino !== null) {
            $parametrosRuta['termino_clave'] = $termino;
        }
        $controlador->manejarGet($parametrosRuta, $_GET);
        exit;
    }
    if ($metodo === 'POST' && $docente === null) {
        $controlador->manejarPost($body);
        exit;
    }
    if ($docente !== null && $termino !== null) {
        $parametrosRuta = ['docente' => $docente, 'termino_clave' => $termino];
        if ($metodo === 'PUT') {
            $controlador->manejarPut($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'PATCH') {
            $controlador->manejarPatch($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'DELETE') {
            $controlador->manejarDelete($parametrosRuta);
            exit;
        }
    }
    responderMetodoNoPermitido();
}

// --- /api/red_docente ----------------------------------------------
if (preg_match('#^/api/red_docente(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorRedDocente(crearServicioRedDocente($conexion));
    $red = $m[1] ?? null;
    $docente = $m[2] ?? null;

    if ($metodo === 'GET') {
        $parametrosRuta = [];
        if ($red !== null) {
            $parametrosRuta['red'] = $red;
        }
        if ($docente !== null) {
            $parametrosRuta['docente'] = $docente;
        }
        $controlador->manejarGet($parametrosRuta, $_GET);
        exit;
    }
    if ($metodo === 'POST' && $red === null) {
        $controlador->manejarPost($body);
        exit;
    }
    if ($red !== null && $docente !== null) {
        $parametrosRuta = ['red' => $red, 'docente' => $docente];
        if ($metodo === 'PUT') {
            $controlador->manejarPut($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'PATCH') {
            $controlador->manejarPatch($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'DELETE') {
            $controlador->manejarDelete($parametrosRuta);
            exit;
        }
    }
    responderMetodoNoPermitido();
}

// --- /api/estudio_ac -----------------------------------------------
if (preg_match('#^/api/estudio_ac(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorEstudioAc(crearServicioEstudioAc($conexion));
    $estudio = $m[1] ?? null;
    $area = $m[2] ?? null;

    if ($metodo === 'GET') {
        $parametrosRuta = [];
        if ($estudio !== null) {
            $parametrosRuta['estudio'] = $estudio;
        }
        if ($area !== null) {
            $parametrosRuta['area_conocimiento'] = $area;
        }
        $controlador->manejarGet($parametrosRuta, $_GET);
        exit;
    }
    if ($metodo === 'POST' && $estudio === null) {
        $controlador->manejarPost($body);
        exit;
    }
    if ($estudio !== null && $area !== null) {
        $parametrosRuta = ['estudio' => $estudio, 'area_conocimiento' => $area];
        if ($metodo === 'PUT') {
            $controlador->manejarPut($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'PATCH') {
            $controlador->manejarPatch($parametrosRuta, $body);
            exit;
        }
        if ($metodo === 'DELETE') {
            $controlador->manejarDelete($parametrosRuta);
            exit;
        }
    }
    responderMetodoNoPermitido();
}

// --- /api/apoyo_profesoral -----------------------------------------
if (preg_match('#^/api/apoyo_profesoral(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorApoyoProfesoral(crearServicioApoyoProfesoral($conexion));
    $estudios = $m[1] ?? null;
    $this->despacharFicha($metodo, $estudios, $controlador, $_GET, $body, 'estudios');
    exit;
}

// --- /api/beca -----------------------------------------------------
if (preg_match('#^/api/beca(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorBeca(crearServicioBeca($conexion));
    $estudios = $m[1] ?? null;
    $this->despacharFicha($metodo, $estudios, $controlador, $_GET, $body, 'estudios');
    exit;
}

// ------------------------------------------------------------------
// La v1 sigue respondiendo: sus cinco bloques se delegan sin tocarlos.
// (Se asume que los bloques ya existen y aquí solo se marca el límite.)
// ------------------------------------------------------------------
require_once __DIR__ . '/rutas_v1.php';

// ------------------------------------------------------------------
// Si nada de lo anterior hizo match:
// ------------------------------------------------------------------
responderJson(404, [
    'estado' => 404,
    'mensaje' => 'La ruta solicitada no existe.',
    'detalle' => '',
]);

// ------------------------------------------------------------------
// Auxiliares
// ------------------------------------------------------------------

/**
 * Despacha las cinco operaciones estándar de una ficha con `id` o llave
 * simple en la ruta. No se usa para las puentes con PK compuesta.
 */
function despacharFicha(
    string $metodo,
    ?string $llave,
    object $controlador,
    array $query,
    array $body,
    string $nombreLlave = 'id'
): void {
    if ($metodo === 'GET') {
        $parametrosRuta = $llave !== null ? [$nombreLlave => $llave] : [];
        $controlador->manejarGet($parametrosRuta, $query);
        return;
    }
    if ($metodo === 'POST' && $llave === null) {
        $controlador->manejarPost($body);
        return;
    }
    if ($llave !== null) {
        $parametrosRuta = [$nombreLlave => $llave];
        if ($metodo === 'PUT') {
            $controlador->manejarPut($parametrosRuta, $body);
            return;
        }
        if ($metodo === 'PATCH') {
            $controlador->manejarPatch($parametrosRuta, $body);
            return;
        }
        if ($metodo === 'DELETE') {
            $controlador->manejarDelete($parametrosRuta);
            return;
        }
    }
    responderMetodoNoPermitido();
}

function responderMetodoNoPermitido(): void
{
    responderJson(405, [
        'estado' => 405,
        'mensaje' => 'Método no permitido para esta ruta.',
        'detalle' => '',
    ]);
    exit;
}

function responderJson(int $codigo, mixed $cuerpo): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
}