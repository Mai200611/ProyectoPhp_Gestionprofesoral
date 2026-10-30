<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el FRONT CONTROLLER del front. TODAS las peticiones que llegan al
 *   front pasan por aquí (gracias a `php -S 0.0.0.0:8020 index.php`).
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Es el enrutador del front y la única pieza que conoce las direcciones
 *   de la sección 9 de 6_contracts.md. Su trabajo:
 *     1. Servir los archivos estáticos de /publico/ (devolviendo false
 *        para que el servidor embebido los entregue tal cual — ver
 *        "archivos estáticos" más abajo).
 *     2. Enrutar las direcciones del front a las vistas correspondientes.
 *     3. Recibir los formularios y llamar al cliente_api según el botón
 *        (Guardar ficha completa → PUT; Guardar solo lo que cambié →
 *        PATCH; Eliminar → DELETE).
 *     4. Manejar avisos entre pantallas con sesión + redirección
 *        (post/redirect/get).
 *
 * ¿Qué NO hace?
 *   - NO toca la base de datos. Cero `new PDO`. Cero `require` de clases
 *     de la API. Lo único que comparte con la API es el JSON que
 *     manipula cliente_api.php (Artículo 3 y RNF6).
 *   - NO muestra jerga al usuario. Las funciones `redirigir_con` y
 *     `aviso_pendiente` manejan mensajes en español, no códigos.
 *
 * ¿Cómo trata a los archivos estáticos?
 *   El servidor embebido de PHP ejecuta este index.php para TODAS las
 *   peticiones, incluidas /publico/estilos.css. Si el archivo existe en
 *   disco, devolvemos false para que PHP lo sirva tal cual. Si no, el
 *   navegador recibiría un HTML 404 en lugar del CSS y la pantalla se
 *   vería sin estilos (ver 7_quickstart.md §4, "letra con serifas").
 * ---------------------------------------------------------------------------
 */

// ---------------------------------------------------------------------------
// 0. ARCHIVOS ESTÁTICOS
// Si la ruta empieza con /publico/ y el archivo existe en disco, devolvemos
// false para que el servidor embebido lo sirva tal cual. Debe ir ANTES de
// cualquier otro código que responda HTML.
// ---------------------------------------------------------------------------
$uriBruta = $_SERVER['REQUEST_URI'] ?? '/';
$uri = $uriBruta;
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

$rutaEstatica = __DIR__ . $uri;
if (str_starts_with($uri, '/publico/') && is_file($rutaEstatica)) {
    return false;
}

// ---------------------------------------------------------------------------
// 1. ARRANQUE DE SESIÓN
// La sesión la usamos SOLO para pasar avisos cortos entre pantallas
// después de una redirección (patrón post/redirect/get, D13 de research).
// ---------------------------------------------------------------------------
session_start();

// ---------------------------------------------------------------------------
// 2. REQUIRES
// Solo el cliente_api. Nada de la API (RNF6).
// ---------------------------------------------------------------------------
require_once __DIR__ . '/cliente_api.php';

// ---------------------------------------------------------------------------
// 3. AYUDANTES DEL FRONT
// ---------------------------------------------------------------------------

/**
 * Redirige a otra ruta del front y corta la ejecución. Si se pasa un
 * mensaje, lo deja en la sesión para que la próxima pantalla lo muestre
 * una sola vez (aviso_pendiente lo recoge y lo borra).
 */
function redirigir_con(string $ruta, ?string $mensaje = null): void
{
    if ($mensaje !== null) {
        $_SESSION['aviso'] = $mensaje;
    }
    http_response_code(302);
    header('Location: ' . $ruta);
    exit;
}

/**
 * Devuelve el aviso pendiente (si lo hay) y lo borra de la sesión, para
 * que no vuelva a aparecer al refrescar.
 */
function aviso_pendiente(): ?string
{
    if (!isset($_SESSION['aviso'])) {
        return null;
    }
    $aviso = (string) $_SESSION['aviso'];
    unset($_SESSION['aviso']);
    return $aviso;
}

/**
 * Devuelve el prefijo del front ('' en Docker, o '/index.php' si algún
 * servidor lo necesita). Con `php -S ... index.php` no hace falta, así
 * que aquí lo dejamos vacío y generamos URLs "limpias": /programas,
 * /programas/nuevo, etc.
 */
function url_front(string $ruta): string
{
    if ($ruta === '' || $ruta === '/') {
        return '/';
    }
    return '/' . ltrim($ruta, '/');
}

/**
 * Renderiza una vista dentro de la plantilla. La vista se carga en un
 * buffer y se pasa a la plantilla como $contenido.
 *
 * @param string $vista  Nombre del archivo de la vista (sin .php).
 * @param array  $datos  Variables que la vista necesita.
 */
function pintar(string $vista, array $datos = []): void
{
    // Extraemos los datos como variables sueltas para que la vista los
    // use directamente ($programas, $aviso...).
    extract($datos, EXTR_SKIP);

    // Aviso pendiente: lo recogemos aquí para que cualquier vista pueda
    // mostrarlo sin pedirlo.
    $aviso = aviso_pendiente();

    // Capturamos el HTML de la vista.
    ob_start();
    require __DIR__ . '/vistas/' . $vista . '.php';
    $contenido = ob_get_clean();

    // Y lo pasamos a la plantilla común.
    require __DIR__ . '/vistas/plantilla.php';
}

/**
 * Lee del POST los campos indicados y los devuelve como array,
 * recortando los string. No valida nada: eso es tarea de la API
 * (frontera HTTP del otro lado).
 */
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

// ---------------------------------------------------------------------------
// 4. ENRUTAMIENTO DEL FRONT
// Cada ruta del front está mapeada a mano, específicamente, sin genéricos.
// ---------------------------------------------------------------------------

// -------------------------------------------------------------------------
// GET /  → inicio
// -------------------------------------------------------------------------
if ($uri === '/' || $uri === '') {
    pintar('inicio');
    exit;
}

// =========================================================================
// AREA_CONOCIMIENTO
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
    pintar('area_conocimiento_formulario', [
        'modo'   => 'crear',
        'area'   => [],
        'errores' => [],
    ]);
    exit;
}

if ($uri === '/areas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['id', 'gran_area', 'area', 'disciplina']);
    $resultado = crear_area_conocimiento($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('areas'), 'Se agregó el área de conocimiento.');
    } else {
        pintar('area_conocimiento_formulario', [
            'modo'    => 'crear',
            'area'    => $datos,
            'errores' => $resultado['errores'],
        ]);
        exit;
    }
}

if (preg_match('#^/areas/([^/]+)/editar$#', $uri, $m)) {
    $id = urldecode($m[1]);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_area_conocimiento($id);
        if (!$resultado['ok']) {
            redirigir_con(url_front('areas'), $resultado['errores'][0] ?? 'No se pudo abrir el registro.');
        }
        pintar('area_conocimiento_formulario', [
            'modo'    => 'editar',
            'area'    => $resultado['datos'],
            'errores' => [],
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['gran_area', 'area', 'disciplina']);

        if ($verbo === 'PATCH') {
            $resultado = actualizar_area_conocimiento($id, $datos);
        } else {
            $resultado = reemplazar_area_conocimiento($id, $datos);
        }

        if ($resultado['ok']) {
            redirigir_con(url_front('areas'), 'Se guardó el área de conocimiento.');
        } else {
            // Conserva lo que el usuario escribió: mezcla los datos con el id
            // original para que el formulario se vuelva a pintar completo.
            $datos['id'] = $id;
            pintar('area_conocimiento_formulario', [
                'modo'    => 'editar',
                'area'    => $datos,
                'errores' => $resultado['errores'],
            ]);
            exit;
        }
    }
}

if (preg_match('#^/areas/([^/]+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = urldecode($m[1]);
    $resultado = eliminar_area_conocimiento($id);
    if ($resultado['ok']) {
        redirigir_con(url_front('areas'), 'Se eliminó el área de conocimiento.');
    }
    redirigir_con(url_front('areas'), $resultado['errores'][0] ?? 'No se pudo eliminar el registro.');
}

// =========================================================================
// TERMINO_CLAVE
// =========================================================================
if ($uri === '/terminos') {
    $resultado = listar_terminos_clave();
    pintar('termino_clave_lista', [
        'ok'       => $resultado['ok'],
        'terminos' => $resultado['datos'] ?? [],
        'errores'  => $resultado['errores'],
    ]);
    exit;
}

if ($uri === '/terminos/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('termino_clave_formulario', [
        'modo'    => 'crear',
        'termino' => [],
        'errores' => [],
    ]);
    exit;
}

if ($uri === '/terminos/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['termino', 'termino_ingles']);
    $resultado = crear_termino_clave($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('terminos'), 'Se agregó el término clave.');
    } else {
        pintar('termino_clave_formulario', [
            'modo'    => 'crear',
            'termino' => $datos,
            'errores' => $resultado['errores'],
        ]);
        exit;
    }
}

if (preg_match('#^/terminos/([^/]+)/editar$#', $uri, $m)) {
    $termino = urldecode($m[1]);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_termino_clave($termino);
        if (!$resultado['ok']) {
            redirigir_con(url_front('terminos'), $resultado['errores'][0] ?? 'No se pudo abrir el registro.');
        }
        pintar('termino_clave_formulario', [
            'modo'    => 'editar',
            'termino' => $resultado['datos'],
            'errores' => [],
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['termino_ingles']);

        if ($verbo === 'PATCH') {
            $resultado = actualizar_termino_clave($termino, $datos);
        } else {
            $resultado = reemplazar_termino_clave($termino, $datos);
        }

        if ($resultado['ok']) {
            redirigir_con(url_front('terminos'), 'Se guardó el término clave.');
        } else {
            $datos['termino'] = $termino;
            pintar('termino_clave_formulario', [
                'modo'    => 'editar',
                'termino' => $datos,
                'errores' => $resultado['errores'],
            ]);
            exit;
        }
    }
}

if (preg_match('#^/terminos/([^/]+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $termino = urldecode($m[1]);
    $resultado = eliminar_termino_clave($termino);
    if ($resultado['ok']) {
        redirigir_con(url_front('terminos'), 'Se eliminó el término clave.');
    }
    redirigir_con(url_front('terminos'), $resultado['errores'][0] ?? 'No se pudo eliminar el registro.');
}

// =========================================================================
// LINEA_INVESTIGACION
// =========================================================================
if ($uri === '/lineas') {
    $resultado = listar_lineas_investigacion();
    pintar('linea_investigacion_lista', [
        'ok'      => $resultado['ok'],
        'lineas'  => $resultado['datos'] ?? [],
        'errores' => $resultado['errores'],
    ]);
    exit;
}

if ($uri === '/lineas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('linea_investigacion_formulario', [
        'modo'    => 'crear',
        'linea'   => [],
        'errores' => [],
    ]);
    exit;
}

if ($uri === '/lineas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['nombre', 'descripcion']);
    $resultado = crear_linea_investigacion($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('lineas'), 'Se agregó la línea de investigación.');
    } else {
        pintar('linea_investigacion_formulario', [
            'modo'    => 'crear',
            'linea'   => $datos,
            'errores' => $resultado['errores'],
        ]);
        exit;
    }
}

if (preg_match('#^/lineas/(\d+)/editar$#', $uri, $m)) {
    $id = (int) $m[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_linea_investigacion($id);
        if (!$resultado['ok']) {
            redirigir_con(url_front('lineas'), $resultado['errores'][0] ?? 'No se pudo abrir el registro.');
        }
        pintar('linea_investigacion_formulario', [
            'modo'    => 'editar',
            'linea'   => $resultado['datos'],
            'errores' => [],
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['nombre', 'descripcion']);

        if ($verbo === 'PATCH') {
            $resultado = actualizar_linea_investigacion($id, $datos);
        } else {
            $resultado = reemplazar_linea_investigacion($id, $datos);
        }

        if ($resultado['ok']) {
            redirigir_con(url_front('lineas'), 'Se guardó la línea de investigación.');
        } else {
            $datos['id'] = $id;
            pintar('linea_investigacion_formulario', [
                'modo'    => 'editar',
                'linea'   => $datos,
                'errores' => $resultado['errores'],
            ]);
            exit;
        }
    }
}

if (preg_match('#^/lineas/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $m[1];
    $resultado = eliminar_linea_investigacion($id);
    if ($resultado['ok']) {
        redirigir_con(url_front('lineas'), 'Se eliminó la línea de investigación.');
    }
    redirigir_con(url_front('lineas'), $resultado['errores'][0] ?? 'No se pudo eliminar el registro.');
}

// =========================================================================
// PROGRAMA
// =========================================================================
if ($uri === '/programas') {
    $resultado = listar_programas();
    pintar('programa_lista', [
        'ok'        => $resultado['ok'],
        'programas' => $resultado['datos'] ?? [],
        'errores'   => $resultado['errores'],
    ]);
    exit;
}

if ($uri === '/programas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('programa_formulario', [
        'modo'     => 'crear',
        'programa' => [],
        'errores'  => [],
    ]);
    exit;
}

if ($uri === '/programas/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post([
        'id', 'nombre', 'tipo', 'nivel', 'fecha_creacion', 'fecha_cierre',
        'numero_cohortes', 'cant_graduados', 'fecha_actualizacion',
        'ciudad', 'facultad',
    ]);
    // Convertimos los enteros que vienen del formulario como string.
    if (isset($datos['id'])) $datos['id'] = (int) $datos['id'];
    if (isset($datos['facultad'])) $datos['facultad'] = (int) $datos['facultad'];
    if (isset($datos['fecha_cierre']) && $datos['fecha_cierre'] === '') {
        $datos['fecha_cierre'] = null;
    }

    $resultado = crear_programa($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('programas'), 'Se agregó el programa.');
    } else {
        pintar('programa_formulario', [
            'modo'     => 'crear',
            'programa' => $datos,
            'errores'  => $resultado['errores'],
        ]);
        exit;
    }
}

if (preg_match('#^/programas/(\d+)/editar$#', $uri, $m)) {
    $id = (int) $m[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_programa($id);
        if (!$resultado['ok']) {
            redirigir_con(url_front('programas'), $resultado['errores'][0] ?? 'No se pudo abrir el registro.');
        }
        pintar('programa_formulario', [
            'modo'     => 'editar',
            'programa' => $resultado['datos'],
            'errores'  => [],
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post([
            'nombre', 'tipo', 'nivel', 'fecha_creacion', 'fecha_cierre',
            'numero_cohortes', 'cant_graduados', 'fecha_actualizacion',
            'ciudad', 'facultad',
        ]);
        if (isset($datos['facultad'])) $datos['facultad'] = (int) $datos['facultad'];
        if (isset($datos['fecha_cierre']) && $datos['fecha_cierre'] === '') {
            $datos['fecha_cierre'] = null;
        }

        if ($verbo === 'PATCH') {
            $resultado = actualizar_programa($id, $datos);
        } else {
            $resultado = reemplazar_programa($id, $datos);
        }

        if ($resultado['ok']) {
            redirigir_con(url_front('programas'), 'Se guardó el programa.');
        } else {
            $datos['id'] = $id;
            pintar('programa_formulario', [
                'modo'     => 'editar',
                'programa' => $datos,
                'errores'  => $resultado['errores'],
            ]);
            exit;
        }
    }
}

if (preg_match('#^/programas/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $m[1];
    $resultado = eliminar_programa($id);
    if ($resultado['ok']) {
        redirigir_con(url_front('programas'), 'Se eliminó el programa.');
    }
    redirigir_con(url_front('programas'), $resultado['errores'][0] ?? 'No se pudo eliminar el registro.');
}

// =========================================================================
// RED
// =========================================================================
if ($uri === '/redes') {
    $resultado = listar_redes();
    pintar('red_lista', [
        'ok'      => $resultado['ok'],
        'redes'   => $resultado['datos'] ?? [],
        'errores' => $resultado['errores'],
    ]);
    exit;
}

if ($uri === '/redes/nuevo' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    pintar('red_formulario', [
        'modo'    => 'crear',
        'red'     => [],
        'errores' => [],
    ]);
    exit;
}

if ($uri === '/redes/nuevo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = tomar_campos_post(['idr', 'nombre', 'url', 'pais']);
    if (isset($datos['idr'])) $datos['idr'] = (int) $datos['idr'];

    $resultado = crear_red($datos);
    if ($resultado['ok']) {
        redirigir_con(url_front('redes'), 'Se agregó la red.');
    } else {
        pintar('red_formulario', [
            'modo'    => 'crear',
            'red'     => $datos,
            'errores' => $resultado['errores'],
        ]);
        exit;
    }
}

if (preg_match('#^/redes/(\d+)/editar$#', $uri, $m)) {
    $idr = (int) $m[1];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtener_red($idr);
        if (!$resultado['ok']) {
            redirigir_con(url_front('redes'), $resultado['errores'][0] ?? 'No se pudo abrir el registro.');
        }
        pintar('red_formulario', [
            'modo'    => 'editar',
            'red'     => $resultado['datos'],
            'errores' => [],
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $verbo = $_POST['verbo'] ?? 'PUT';
        $datos = tomar_campos_post(['nombre', 'url', 'pais']);

        if ($verbo === 'PATCH') {
            $resultado = actualizar_red($idr, $datos);
        } else {
            $resultado = reemplazar_red($idr, $datos);
        }

        if ($resultado['ok']) {
            redirigir_con(url_front('redes'), 'Se guardó la red.');
        } else {
            $datos['idr'] = $idr;
            pintar('red_formulario', [
                'modo'    => 'editar',
                'red'     => $datos,
                'errores' => $resultado['errores'],
            ]);
            exit;
        }
    }
}

if (preg_match('#^/redes/(\d+)/eliminar$#', $uri, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $idr = (int) $m[1];
    $resultado = eliminar_red($idr);
    if ($resultado['ok']) {
        redirigir_con(url_front('redes'), 'Se eliminó la red.');
    }
    redirigir_con(url_front('redes'), $resultado['errores'][0] ?? 'No se pudo eliminar el registro.');
}

// =========================================================================
// NINGUNA RUTA COINCIDE → 404
// =========================================================================
http_response_code(404);
pintar('no_encontrada');