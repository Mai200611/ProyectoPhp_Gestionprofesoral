<?php
declare(strict_types=1);

require_once __DIR__ . '/servicios/ensamblador.php';

// V1
require_once __DIR__ . '/controladores/ControladorAreaConocimiento.php';
require_once __DIR__ . '/controladores/ControladorTerminoClave.php';
require_once __DIR__ . '/controladores/ControladorLineaInvestigacion.php';
require_once __DIR__ . '/controladores/ControladorPrograma.php';
require_once __DIR__ . '/controladores/ControladorRed.php';

// V2
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

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$ruta = rtrim($ruta, '/');
if ($ruta === '') $ruta = '/';

if ($metodo === 'GET' && $ruta === '/') {
    responderJson(200, [
        'mensaje' => 'API Gestión funcionando',
        'version' => 'v2',
        'recursos' => [
            'area_conocimiento', 'termino_clave', 'linea_investigacion', 'programa', 'red',
            'docente', 'estudios_realizados', 'docente_departamento', 'intereses_futuros', 
            'evaluacion_docente', 'reconocimiento', 'experiecia', 'red_docente', 'estudio_ac',
            'apoyo_profesoral', 'beca'
        ],
        'contratos' => 'docs/spec_kit/versiones/v2_tablas_con_fk/6_contracts.md',
    ]);
    exit;
}

$cuerpoCrudo = file_get_contents('php://input') ?: '';
$body = [];
if ($cuerpoCrudo !== '') {
    $decodificado = json_decode($cuerpoCrudo, true);
    if (is_array($decodificado)) $body = $decodificado;
}

// =========================================================================
// V1: Rutas (Llaman directo a sus métodos y usan responderRespuesta)
// =========================================================================

if (preg_match('#^/api/area_conocimiento$#', $ruta)) {
    $c = new ControladorAreaConocimiento(crearServicioAreaConocimiento());
    if ($metodo === 'GET') { responderRespuesta($c->listar($_GET)); exit; }
    if ($metodo === 'POST') { responderRespuesta($c->crear($body)); exit; }
    responderMetodoNoPermitido();
}
if (preg_match('#^/api/area_conocimiento/([^/]+)$#', $ruta, $m)) {
    $c = new ControladorAreaConocimiento(crearServicioAreaConocimiento());
    $id = urldecode($m[1]);
    if ($metodo === 'GET') { responderRespuesta($c->obtener($id)); exit; }
    if ($metodo === 'PUT') { responderRespuesta($c->reemplazar($id, $body)); exit; }
    if ($metodo === 'PATCH') { responderRespuesta($c->actualizarParcial($id, $body)); exit; }
    if ($metodo === 'DELETE') { responderRespuesta($c->eliminar($id)); exit; }
    responderMetodoNoPermitido();
}

if (preg_match('#^/api/termino_clave$#', $ruta)) {
    $c = new ControladorTerminoClave(crearServicioTerminoClave());
    if ($metodo === 'GET') { responderRespuesta($c->listar($_GET)); exit; }
    if ($metodo === 'POST') { responderRespuesta($c->crear($body)); exit; }
    responderMetodoNoPermitido();
}
if (preg_match('#^/api/termino_clave/([^/]+)$#', $ruta, $m)) {
    $c = new ControladorTerminoClave(crearServicioTerminoClave());
    $id = urldecode($m[1]);
    if ($metodo === 'GET') { responderRespuesta($c->obtener($id)); exit; }
    if ($metodo === 'PUT') { responderRespuesta($c->reemplazar($id, $body)); exit; }
    if ($metodo === 'PATCH') { responderRespuesta($c->actualizarParcial($id, $body)); exit; }
    if ($metodo === 'DELETE') { responderRespuesta($c->eliminar($id)); exit; }
    responderMetodoNoPermitido();
}

if (preg_match('#^/api/linea_investigacion$#', $ruta)) {
    $c = new ControladorLineaInvestigacion(crearServicioLineaInvestigacion());
    if ($metodo === 'GET') { responderRespuesta($c->listar($_GET)); exit; }
    if ($metodo === 'POST') { responderRespuesta($c->crear($body)); exit; }
    responderMetodoNoPermitido();
}
if (preg_match('#^/api/linea_investigacion/([^/]+)$#', $ruta, $m)) {
    $c = new ControladorLineaInvestigacion(crearServicioLineaInvestigacion());
    $id = (int)$m[1];
    if ($metodo === 'GET') { responderRespuesta($c->obtener($id)); exit; }
    if ($metodo === 'PUT') { responderRespuesta($c->reemplazar($id, $body)); exit; }
    if ($metodo === 'PATCH') { responderRespuesta($c->actualizarParcial($id, $body)); exit; }
    if ($metodo === 'DELETE') { responderRespuesta($c->eliminar($id)); exit; }
    responderMetodoNoPermitido();
}

if (preg_match('#^/api/programa$#', $ruta)) {
    $c = new ControladorPrograma(crearServicioPrograma());
    if ($metodo === 'GET') { responderRespuesta($c->listar($_GET)); exit; }
    if ($metodo === 'POST') { responderRespuesta($c->crear($body)); exit; }
    responderMetodoNoPermitido();
}
if (preg_match('#^/api/programa/([^/]+)$#', $ruta, $m)) {
    $c = new ControladorPrograma(crearServicioPrograma());
    $id = (int)$m[1];
    if ($metodo === 'GET') { responderRespuesta($c->obtener($id)); exit; }
    if ($metodo === 'PUT') { responderRespuesta($c->reemplazar($id, $body)); exit; }
    if ($metodo === 'PATCH') { responderRespuesta($c->actualizarParcial($id, $body)); exit; }
    if ($metodo === 'DELETE') { responderRespuesta($c->eliminar($id)); exit; }
    responderMetodoNoPermitido();
}

if (preg_match('#^/api/red$#', $ruta)) {
    $c = new ControladorRed(crearServicioRed());
    if ($metodo === 'GET') { responderRespuesta($c->listar($_GET)); exit; }
    if ($metodo === 'POST') { responderRespuesta($c->crear($body)); exit; }
    responderMetodoNoPermitido();
}
if (preg_match('#^/api/red/([^/]+)$#', $ruta, $m)) {
    $c = new ControladorRed(crearServicioRed());
    $id = (int)$m[1];
    if ($metodo === 'GET') { responderRespuesta($c->obtener($id)); exit; }
    if ($metodo === 'PUT') { responderRespuesta($c->reemplazar($id, $body)); exit; }
    if ($metodo === 'PATCH') { responderRespuesta($c->actualizarParcial($id, $body)); exit; }
    if ($metodo === 'DELETE') { responderRespuesta($c->eliminar($id)); exit; }
    responderMetodoNoPermitido();
}

// =========================================================================
// V2: Rutas (Usan manejarGet, manejarPost, etc.)
// =========================================================================

if (preg_match('#^/api/docente(?:/([^/]+))?(?:/(completo))?$#', $ruta, $m)) {
    $controlador = new ControladorDocente(crearServicioDocente());
    $cedula = $m[1] ?? null;
    $completo = $m[2] ?? null;
    if ($metodo === 'GET') {
        $params = [];
        if ($cedula !== null) $params['cedula'] = $cedula;
        if ($completo !== null) $params['completo'] = $completo;
        $controlador->manejarGet($params, $_GET); exit;
    }
    if ($metodo === 'POST' && $cedula === null) { $controlador->manejarPost($body); exit; }
    if ($metodo === 'PUT' && $cedula !== null) { $controlador->manejarPut(['cedula' => $cedula], $body); exit; }
    if ($metodo === 'DELETE' && $cedula !== null) { $controlador->manejarDelete(['cedula' => $cedula]); exit; }
    responderMetodoNoPermitido();
}

if (preg_match('#^/api/estudios_realizados(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorEstudiosRealizados(crearServicioEstudiosRealizados());
    despacharFicha($metodo, $m[1] ?? null, $controlador, $_GET, $body); exit;
}

if (preg_match('#^/api/evaluacion_docente(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorEvaluacionDocente(crearServicioEvaluacionDocente());
    despacharFicha($metodo, $m[1] ?? null, $controlador, $_GET, $body); exit;
}

if (preg_match('#^/api/reconocimiento(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorReconocimiento(crearServicioReconocimiento());
    despacharFicha($metodo, $m[1] ?? null, $controlador, $_GET, $body); exit;
}

if (preg_match('#^/api/experiecia(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorExperiencia(crearServicioExperiencia());
    despacharFicha($metodo, $m[1] ?? null, $controlador, $_GET, $body); exit;
}

if (preg_match('#^/api/docente_departamento(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorDocenteDepartamento(crearServicioDocenteDepartamento());
    despacharPuente($metodo, $m, $controlador, $_GET, $body, ['docente', 'departamento']); exit;
}

if (preg_match('#^/api/intereses_futuros(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorInteresesFuturos(crearServicioInteresesFuturos());
    despacharPuente($metodo, $m, $controlador, $_GET, $body, ['docente', 'termino_clave']); exit;
}

if (preg_match('#^/api/red_docente(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorRedDocente(crearServicioRedDocente());
    despacharPuente($metodo, $m, $controlador, $_GET, $body, ['red', 'docente']); exit;
}

if (preg_match('#^/api/estudio_ac(?:/([^/]+))?(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorEstudioAc(crearServicioEstudioAc());
    despacharPuente($metodo, $m, $controlador, $_GET, $body, ['estudio', 'area_conocimiento']); exit;
}

if (preg_match('#^/api/apoyo_profesoral(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorApoyoProfesoral(crearServicioApoyoProfesoral());
    despacharFicha($metodo, $m[1] ?? null, $controlador, $_GET, $body, 'estudios'); exit;
}

if (preg_match('#^/api/beca(?:/([^/]+))?$#', $ruta, $m)) {
    $controlador = new ControladorBeca(crearServicioBeca());
    despacharFicha($metodo, $m[1] ?? null, $controlador, $_GET, $body, 'estudios'); exit;
}

responderJson(404, ['estado' => 404, 'mensaje' => 'La ruta solicitada no existe.', 'detalle' => '']);

// ------------------------------------------------------------------
// Funciones Auxiliares
// ------------------------------------------------------------------
function despacharFicha(string $metodo, ?string $llave, object $controlador, array $query, array $body, string $nombreLlave = 'id'): void {
    if ($metodo === 'GET') {
        $controlador->manejarGet($llave !== null ? [$nombreLlave => $llave] : [], $query); return;
    }
    if ($metodo === 'POST' && $llave === null) { $controlador->manejarPost($body); return; }
    if ($llave !== null) {
        $params = [$nombreLlave => $llave];
        if ($metodo === 'PUT') { $controlador->manejarPut($params, $body); return; }
        if ($metodo === 'PATCH') { $controlador->manejarPatch($params, $body); return; }
        if ($metodo === 'DELETE') { $controlador->manejarDelete($params); return; }
    }
    responderMetodoNoPermitido();
}

function despacharPuente(string $metodo, array $m, object $controlador, array $query, array $body, array $nombresLlaves): void {
    $llave1 = $m[1] ?? null;
    $llave2 = $m[2] ?? null;
    if ($metodo === 'GET') {
        $params = [];
        if ($llave1 !== null) $params[$nombresLlaves[0]] = $llave1;
        if ($llave2 !== null) $params[$nombresLlaves[1]] = $llave2;
        $controlador->manejarGet($params, $query); return;
    }
    if ($metodo === 'POST' && $llave1 === null) { $controlador->manejarPost($body); return; }
    if ($llave1 !== null && $llave2 !== null) {
        $params = [$nombresLlaves[0] => $llave1, $nombresLlaves[1] => $llave2];
        if ($metodo === 'PUT') { $controlador->manejarPut($params, $body); return; }
        if ($metodo === 'PATCH') { $controlador->manejarPatch($params, $body); return; }
        if ($metodo === 'DELETE') { $controlador->manejarDelete($params); return; }
    }
    responderMetodoNoPermitido();
}

function responderMetodoNoPermitido(): void {
    responderJson(405, ['estado' => 405, 'mensaje' => 'Método no permitido para esta ruta.', 'detalle' => '']); exit;
}

function responderJson(int $codigo, mixed $cuerpo): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
}

function responderRespuesta(array $respuesta): void {
    http_response_code($respuesta['codigo']);
    if ($respuesta['codigo'] !== 204 && $respuesta['cuerpo'] !== null) {
        echo json_encode($respuesta['cuerpo'], JSON_UNESCAPED_UNICODE);
    }
}