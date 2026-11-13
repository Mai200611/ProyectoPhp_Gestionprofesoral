<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el FRONT CONTROLLER del front. TODAS las peticiones que llegan al
 *   front pasan por aquí.
 * ---------------------------------------------------------------------------
 */

// 0. ARCHIVOS ESTÁTICOS
$uriBruta = $_SERVER['REQUEST_URI'] ?? '/';
$uri = $uriBruta;
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

$rutaEstatica = __DIR__ . $uri;
if (str_starts_with($uri, '/publico/') && is_file($rutaEstatica)) {
    return false;
}

// 1. ARRANQUE DE SESIÓN
session_start();

// 2. REQUIRES
require_once __DIR__ . '/cliente_api.php';

// 3. AYUDANTES DEL FRONT

function redirigir_con(string $ruta, ?string $mensaje = null): void
{
    if ($mensaje !== null) {
        $_SESSION['aviso'] = $mensaje;
    }
    http_response_code(302);
    header('Location: ' . $ruta);
    exit;
}

function aviso_pendiente(): ?string
{
    if (!isset($_SESSION['aviso'])) {
        return null;
    }
    $aviso = (string) $_SESSION['aviso'];
    unset($_SESSION['aviso']);
    return $aviso;
}

function url_front(string $ruta): string
{
    if ($ruta === '' || $ruta === '/') {
        return '/';
    }
    return '/' . ltrim($ruta, '/');
}

function pintar(string $vista, array $datos = []): void
{
    extract($datos, EXTR_SKIP);
    $aviso = aviso_pendiente();

    ob_start();
    require __DIR__ . '/vistas/' . $vista . '.php';
    $contenido = ob_get_clean();

    require __DIR__ . '/vistas/plantilla.php';
}

function tomar_campos_post(array $nombres): array
{
    $datos = [];
    foreach ($nombres as $nombre) {
        if (array_key_exists($nombre, $_POST)) {
            $valor = $_POST[$nombre];
            if (is_string($valor)) {
                $valor = trim($valor);
            }
            $datos[$nombre] = $valor;
        }
    }
    return $datos;
}

// 4. ENRUTAMIENTO DEL FRONT

// GET / → inicio
if ($uri === '/' || $uri === '') {
    pintar('inicio');
    exit;
}

// =========================================================================
// AREA_CONOCIMIENTO (v1)
// =========================================================================
if ($uri === '/areas') {
    $resultado = listar_areas_conocimiento();
    pintar('area_conocimiento_lista', [
        'ok'      => $resultado['ok'],
        'areas'   => $resultado['datos'] ?? [],
        'errores' => $resultado['errores'],
    ]);
    exit;
}
if ($uri === '/areas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('area_conocimiento_formulario', ['modo' => 'crear', 'area' => [], 'errores' => []]);
    exit;
}
if ($uri === '/areas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['id', 'gran_area', 'area', 'disciplina']);
    $resultado = crear_area_conocimiento($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('areas'), 'Se agregó el área de conocimiento.');
    } else {
        pintar('area_conocimiento_formulario', ['modo' => 'crear', 'area' => $datos, 'errores' => $resultado['errores']]);
        exit;
    }
}
if (preg_match('#^/areas/([^/]+)/editar$#', $uri, $m)) {
    $id = urldecode($m[1]);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_area_conocimiento($id);
        if (!$resultado['ok']) redirigir_con(url_front('areas'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('area_conocimiento_formulario', ['modo' => 'editar', 'area' => $resultado['datos'], 'errores' => []]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['gran_area', 'area', 'disciplina']);
        $resultado = ($verbo === 'PATCH') ? actualizar_area_conocimiento($id, $datos) : reemplazar_area_conocimiento($id, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('areas'), 'Se guardó el área de conocimiento.');
        } else {
            $datos['id'] = $id;
            pintar('area_conocimiento_formulario', ['modo' => 'editar', 'area' => $datos, 'errores' => $resultado['errores']]);
            exit;
        }
    }
}
if (preg_match('#^/areas/([^/]+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = urldecode($m[1]);
    $resultado = eliminar_area_conocimiento($id);
    redirigir_con(url_front('areas'), $resultado['ok'] ? 'Se eliminó el área.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// TERMINO_CLAVE (v1)
// =========================================================================
if ($uri === '/terminos') {
    $resultado = listar_terminos_clave();
    pintar('termino_clave_lista', ['ok' => $resultado['ok'], 'terminos' => $resultado['datos'] ?? [], 'errores' => $resultado['errores']]);
    exit;
}
if ($uri === '/terminos/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('termino_clave_formulario', ['modo' => 'crear', 'termino' => [], 'errores' => []]);
    exit;
}
if ($uri === '/terminos/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['termino', 'termino_ingles']);
    $resultado = crear_termino_clave($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('terminos'), 'Se agregó el término clave.');
    } else {
        pintar('termino_clave_formulario', ['modo' => 'crear', 'termino' => $datos, 'errores' => $resultado['errores']]);
        exit;
    }
}
if (preg_match('#^/terminos/([^/]+)/editar$#', $uri, $m)) {
    $termino = urldecode($m[1]);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_termino_clave($termino);
        if (!$resultado['ok']) redirigir_con(url_front('terminos'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('termino_clave_formulario', ['modo' => 'editar', 'termino' => $resultado['datos'], 'errores' => []]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['termino_ingles']);
        $resultado = ($verbo === 'PATCH') ? actualizar_termino_clave($termino, $datos) : reemplazar_termino_clave($termino, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('terminos'), 'Se guardó el término clave.');
        } else {
            $datos['termino'] = $termino;
            pintar('termino_clave_formulario', ['modo' => 'editar', 'termino' => $datos, 'errores' => $resultado['errores']]);
            exit;
        }
    }
}
if (preg_match('#^/terminos/([^/]+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $termino = urldecode($m[1]);
    $resultado = eliminar_termino_clave($termino);
    redirigir_con(url_front('terminos'), $resultado['ok'] ? 'Se eliminó el término.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// LINEA_INVESTIGACION (v1)
// =========================================================================
if ($uri === '/lineas') {
    $resultado = listar_lineas_investigacion();
    pintar('linea_investigacion_lista', ['ok' => $resultado['ok'], 'lineas' => $resultado['datos'] ?? [], 'errores' => $resultado['errores']]);
    exit;
}
if ($uri === '/lineas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('linea_investigacion_formulario', ['modo' => 'crear', 'linea' => [], 'errores' => []]);
    exit;
}
if ($uri === '/lineas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['nombre', 'descripcion']);
    $resultado = crear_linea_investigacion($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('lineas'), 'Se agregó la línea.');
    } else {
        pintar('linea_investigacion_formulario', ['modo' => 'crear', 'linea' => $datos, 'errores' => $resultado['errores']]);
        exit;
    }
}
if (preg_match('#^/lineas/(\d+)/editar$#', $uri, $m)) {
    $id = (int) $m[1];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_linea_investigacion($id);
        if (!$resultado['ok']) redirigir_con(url_front('lineas'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('linea_investigacion_formulario', ['modo' => 'editar', 'linea' => $resultado['datos'], 'errores' => []]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['nombre', 'descripcion']);
        $resultado = ($verbo === 'PATCH') ? actualizar_linea_investigacion($id, $datos) : reemplazar_linea_investigacion($id, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('lineas'), 'Se guardó la línea.');
        } else {
            $datos['id'] = $id;
            pintar('linea_investigacion_formulario', ['modo' => 'editar', 'linea' => $datos, 'errores' => $resultado['errores']]);
            exit;
        }
    }
}
if (preg_match('#^/lineas/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $m[1];
    $resultado = eliminar_linea_investigacion($id);
    redirigir_con(url_front('lineas'), $resultado['ok'] ? 'Se eliminó la línea.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// PROGRAMA (v1)
// =========================================================================
if ($uri === '/programas') {
    $resultado = listar_programas();
    pintar('programa_lista', ['ok' => $resultado['ok'], 'programas' => $resultado['datos'] ?? [], 'errores' => $resultado['errores']]);
    exit;
}
if ($uri === '/programas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('programa_formulario', ['modo' => 'crear', 'programa' => [], 'errores' => []]);
    exit;
}
if ($uri === '/programas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['id', 'nombre', 'tipo', 'nivel', 'fecha_creacion', 'fecha_cierre', 'numero_cohortes', 'cant_graduados', 'fecha_actualizacion', 'ciudad', 'facultad']);
    if (isset($datos['id'])) $datos['id'] = (int) $datos['id'];
    if (isset($datos['facultad'])) $datos['facultad'] = (int) $datos['facultad'];
    if (isset($datos['fecha_cierre']) && $datos['fecha_cierre'] === '') $datos['fecha_cierre'] = null;

    $resultado = crear_programa($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('programas'), 'Se agregó el programa.');
    } else {
        pintar('programa_formulario', ['modo' => 'crear', 'programa' => $datos, 'errores' => $resultado['errores']]);
        exit;
    }
}
if (preg_match('#^/programas/(\d+)/editar$#', $uri, $m)) {
    $id = (int) $m[1];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_programa($id);
        if (!$resultado['ok']) redirigir_con(url_front('programas'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('programa_formulario', ['modo' => 'editar', 'programa' => $resultado['datos'], 'errores' => []]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['nombre', 'tipo', 'nivel', 'fecha_creacion', 'fecha_cierre', 'numero_cohortes', 'cant_graduados', 'fecha_actualizacion', 'ciudad', 'facultad']);
        if (isset($datos['facultad'])) $datos['facultad'] = (int) $datos['facultad'];
        if (isset($datos['fecha_cierre']) && $datos['fecha_cierre'] === '') $datos['fecha_cierre'] = null;

        $resultado = ($verbo === 'PATCH') ? actualizar_programa($id, $datos) : reemplazar_programa($id, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('programas'), 'Se guardó el programa.');
        } else {
            $datos['id'] = $id;
            pintar('programa_formulario', ['modo' => 'editar', 'programa' => $datos, 'errores' => $resultado['errores']]);
            exit;
        }
    }
}
if (preg_match('#^/programas/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $m[1];
    $resultado = eliminar_programa($id);
    redirigir_con(url_front('programas'), $resultado['ok'] ? 'Se eliminó el programa.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// RED (v1)
// =========================================================================
if ($uri === '/redes') {
    $resultado = listar_redes();
    pintar('red_lista', ['ok' => $resultado['ok'], 'redes' => $resultado['datos'] ?? [], 'errores' => $resultado['errores']]);
    exit;
}
if ($uri === '/redes/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('red_formulario', ['modo' => 'crear', 'red' => [], 'errores' => []]);
    exit;
}
if ($uri === '/redes/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['idr', 'nombre', 'url', 'pais']);
    if (isset($datos['idr'])) $datos['idr'] = (int) $datos['idr'];

    $resultado = crear_red($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('redes'), 'Se agregó la red.');
    } else {
        pintar('red_formulario', ['modo' => 'crear', 'red' => $datos, 'errores' => $resultado['errores']]);
        exit;
    }
}
if (preg_match('#^/redes/(\d+)/editar$#', $uri, $m)) {
    $idr = (int) $m[1];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_red($idr);
        if (!$resultado['ok']) redirigir_con(url_front('redes'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('red_formulario', ['modo' => 'editar', 'red' => $resultado['datos'], 'errores' => []]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['nombre', 'url', 'pais']);
        $resultado = ($verbo === 'PATCH') ? actualizar_red($idr, $datos) : reemplazar_red($idr, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('redes'), 'Se guardó la red.');
        } else {
            $datos['idr'] = $idr;
            pintar('red_formulario', ['modo' => 'editar', 'red' => $datos, 'errores' => $resultado['errores']]);
            exit;
        }
    }
}
if (preg_match('#^/redes/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $idr = (int) $m[1];
    $resultado = eliminar_red($idr);
    redirigir_con(url_front('redes'), $resultado['ok'] ? 'Se eliminó la red.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// DOCENTE (v2)
// =========================================================================
if ($uri === '/docentes') {
    $resultado = listar_docentes();
    pintar('docente_lista', ['ok' => $resultado['ok'], 'docentes' => $resultado['datos'] ?? [], 'errores' => $resultado['errores']]);
    exit;
}
if ($uri === '/docentes/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $lineas = listar_lineas_investigacion();
    pintar('docente_formulario', ['modo' => 'crear', 'docente' => [], 'lineas' => $lineas['datos'] ?? [], 'errores' => []]);
    exit;
}
if ($uri === '/docentes/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['cedula', 'nombre_completo', 'linea_investigacion_principal', 'escalafon']);
    $resultado = crear_docente($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('docentes'), 'Se agregó el docente.');
    } else {
        $lineas = listar_lineas_investigacion();
        pintar('docente_formulario', ['modo' => 'crear', 'docente' => $datos, 'lineas' => $lineas['datos'] ?? [], 'errores' => $resultado['errores']]);
        exit;
    }
}
if (preg_match('#^/docentes/([^/]+)/editar$#', $uri, $m)) {
    $cedula = urldecode($m[1]);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_docente_completo($cedula);
        $lineas = listar_lineas_investigacion();
        if (!$resultado['ok']) redirigir_con(url_front('docentes'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('docente_formulario', ['modo' => 'editar', 'docente' => $resultado['datos'], 'lineas' => $lineas['datos'] ?? [], 'errores' => []]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = tomar_campos_post(['nombre_completo', 'linea_investigacion_principal', 'escalafon']);
        $resultado = reemplazar_docente($cedula, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('docentes'), 'Se guardó el docente.');
        } else {
            $lineas = listar_lineas_investigacion();
            $datos['cedula'] = $cedula;
            pintar('docente_formulario', ['modo' => 'editar', 'docente' => $datos, 'lineas' => $lineas['datos'] ?? [], 'errores' => $resultado['errores']]);
            exit;
        }
    }
}
if (preg_match('#^/docentes/([^/]+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = urldecode($m[1]);
    $resultado = eliminar_docente($cedula);
    redirigir_con(url_front('docentes'), $resultado['ok'] ? 'Se eliminó el docente.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// ESTUDIOS_REALIZADOS (v2)
// =========================================================================
if ($uri === '/estudios') {
    $resultado = listar_estudios();
    pintar('estudios_lista', [
        'ok' => $resultado['ok'], 
        'estudios' => $resultado['datos'] ?? [], 
        'errores' => $resultado['errores']
    ]);
    exit;
}
if ($uri === '/estudios/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $docentes = listar_docentes();
    pintar('estudios_formulario', [
        'modo' => 'crear', 
        'estudio' => [], 
        'docentes' => $docentes['datos'] ?? [], 
        'errores' => []
    ]);
    exit;
}
if ($uri === '/estudios/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['titulo', 'universidad', 'tipo', 'cedula']);
    $resultado = crear_estudio($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('estudios'), 'Se agregó el estudio.');
    } else {
        $docentes = listar_docentes();
        pintar('estudios_formulario', [
            'modo' => 'crear', 
            'estudio' => $datos, 
            'docentes' => $docentes['datos'] ?? [], 
            'errores' => $resultado['errores']
        ]);
        exit;
    }
}
if (preg_match('#^/estudios/(\d+)/editar$#', $uri, $m)) {
    $id = (int) $m[1];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_estudio($id);
        $docentes = listar_docentes();
        if (!$resultado['ok']) redirigir_con(url_front('estudios'), $resultado['errores'][0] ?? 'No se pudo abrir.');
        pintar('estudios_formulario', [
            'modo' => 'editar', 
            'estudio' => $resultado['datos'], 
            'docentes' => $docentes['datos'] ?? [], 
            'errores' => []
        ]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['titulo', 'universidad', 'tipo', 'cedula']);
        $resultado = ($verbo === 'PATCH') ? actualizar_estudio($id, $datos) : reemplazar_estudio($id, $datos);
        if ($resultado['ok']) {
            redirigir_con(url_front('estudios'), 'Se guardó el estudio.');
        } else {
            $docentes = listar_docentes();
            $datos['id'] = $id;
            pintar('estudios_formulario', [
                'modo' => 'editar', 
                'estudio' => $datos, 
                'docentes' => $docentes['datos'] ?? [], 
                'errores' => $resultado['errores']
            ]);
            exit;
        }
    }
}
if (preg_match('#^/estudios/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $m[1];
    $resultado = eliminar_estudio($id);
    redirigir_con(url_front('estudios'), $resultado['ok'] ? 'Se eliminó el estudio.' : ($resultado['errores'][0] ?? 'No se pudo eliminar.'));
}

// =========================================================================
// NUEVAS ENTIDADES V2 (Rutas para listar)
// =========================================================================
if ($uri === '/departamentos') {
    $r = listar_departamentos();
    pintar('departamentos_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/intereses') {
    $r = listar_intereses();
    pintar('intereses_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/evaluaciones') {
    $r = listar_evaluaciones();
    pintar('evaluaciones_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/reconocimientos') {
    $r = listar_reconocimientos();
    pintar('reconocimientos_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/experiencia') {
    $r = listar_experiencias();
    pintar('experiencia_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/redes-docente') {
    $r = listar_redes_docente();
    pintar('redes_docente_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/estudios-area') {
    $r = listar_estudios_ac();
    pintar('estudios_area_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/apoyos') {
    $r = listar_apoyos();
    pintar('apoyos_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}
if ($uri === '/becas') {
    $r = listar_becas();
    pintar('becas_lista', ['ok' => $r['ok'], 'datos' => $r['datos'] ?? [], 'errores' => $r['errores']]); exit;
}

// =========================================================================
// NINGUNA RUTA COINCIDE → 404
// =========================================================================
http_response_code(404);
pintar('no_encontrada');
exit;