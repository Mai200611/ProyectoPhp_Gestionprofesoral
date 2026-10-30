<?php
declare(strict_types=1);

/**
 * index.php (del front)
 *
 * Front controller del front_php (puerto 8020).
 *
 * Único punto de entrada: recibe la petición del navegador, la enruta a la
 * vista correspondiente y responde HTML. Todo el enrutamiento vive aquí,
 * escrito a mano y legible completo (Artículo 2 de la constitución).
 *
 * Reglas de esta capa:
 *   - El front NO abre conexiones a la base ni incluye clases de la API
 *     (Artículo 3, RNF6). Solo habla HTTP con la API por cliente_api.php.
 *   - Cada recurso tiene su bloque de rutas, sin tabla de configuración
 *     (3_plan §4.8). El bloque de docente es el único con pantalla de
 *     detalle y el único sin «guardar solo lo que cambié».
 *   - Los formularios con FK cargan sus desplegables llamando a las
 *     funciones de la API antes de pintar (RF17).
 *   - Con la API caída, las pantallas siguen respondiendo con su aviso
 *     (RF21, RF39).
 *
 * La v1 sigue respondiendo por sus mismas direcciones (`/areas`,
 * `/terminos`, `/lineas`, `/programas`, `/redes`). Esos bloques se
 * delegan a `rutas_v1.php` sin tocarlos.
 */

require_once __DIR__ . '/cliente_api.php';

// Cargamos la plantilla y las vistas base.
require_once __DIR__ . '/vistas/plantilla.php';
require_once __DIR__ . '/vistas/inicio.php';
require_once __DIR__ . '/vistas/no_encontrada.php';

// Vistas de los 11 recursos nuevos (lista y formulario).
foreach (glob(__DIR__ . '/vistas/*_lista.php') as $archivo) {
    require_once $archivo;
}
foreach (glob(__DIR__ . '/vistas/*_formulario.php') as $archivo) {
    require_once $archivo;
}
require_once __DIR__ . '/vistas/docente_detalle.php';

// ------------------------------------------------------------------
// Lectura de la ruta y del método
// ------------------------------------------------------------------
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$ruta = rtrim($ruta, '/');
if ($ruta === '') {
    $ruta = '/';
}

// ------------------------------------------------------------------
// Inicio
// ------------------------------------------------------------------
if ($metodo === 'GET' && $ruta === '/') {
    vistaInicio();
    exit;
}

// ------------------------------------------------------------------
// Las rutas de la v1 se delegan sin cambios
// ------------------------------------------------------------------
require_once __DIR__ . '/rutas_v1.php';

// Si las rutas v1 no hicieron match, seguimos con las de la v2.
if (!empty($GLOBALS['v1_atendio'])) {
    exit;
}

// ==================================================================
// DOCENTE (la única pantalla con detalle y sin «guardar solo lo que cambié»)
// ==================================================================
if (preg_match('#^/docentes$#', $ruta)) {
    $resultado = listar_docentes();
    vistaListaDocentes($resultado);
    exit;
}
if (preg_match('#^/docentes/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $datos = leerBodyDocenteDesdeFormulario($_POST);
        $resultado = crear_docente($datos);
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /docentes');
            exit;
        }
        // Si falló, volvemos a pintar con lo escrito y los motivos.
        $lineas = listar_lineas();
        $lineasLista = $lineas !== null ? $lineas['datos'] : [];
        vistaFormularioDocente(null, $lineasLista, $datos, $resultado);
        exit;
    }
    $lineas = listar_lineas();
    $lineasLista = $lineas !== null ? $lineas['datos'] : [];
    vistaFormularioDocente(null, $lineasLista, [], null);
    exit;
}
if (preg_match('#^/docentes/([^/]+)/editar$#', $ruta, $m)) {
    $cedula = $m[1];
    if ($metodo === 'POST') {
        $datos = leerBodyDocenteDesdeFormulario($_POST);
        $resultado = reemplazar_docente($cedula, $datos);
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /docentes/' . rawurlencode($cedula));
            exit;
        }
        $completo = obtener_docente_completo($cedula);
        $docente = $completo !== null ? $completo['datos'] : null;
        $lineas = listar_lineas();
        $lineasLista = $lineas !== null ? $lineas['datos'] : [];
        vistaFormularioDocente($docente, $lineasLista, $datos, $resultado);
        exit;
    }
    $completo = obtener_docente_completo($cedula);
    $docente = $completo !== null ? $completo['datos'] : null;
    $lineas = listar_lineas();
    $lineasLista = $lineas !== null ? $lineas['datos'] : [];
    vistaFormularioDocente($docente, $lineasLista, [], null);
    exit;
}
if (preg_match('#^/docentes/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_docente($m[1]);
    vistaListaDocentes(listar_docentes(), $resultado['mensaje'] ?? '');
    exit;
}
if (preg_match('#^/docentes/([^/]+)$#', $ruta, $m)) {
    $completo = obtener_docente_completo($m[1]);
    vistaDetalleDocente($completo !== null ? $completo['datos'] : null);
    exit;
}

// ==================================================================
// Fichas con `id` autogenerado y puentes con PK compuesta: mismo patrón.
// Cada bloque está escrito, no generado en bucle (Artículo 10).
// ==================================================================

// --- /estudios -----------------------------------------------------
if (preg_match('#^/estudios$#', $ruta)) {
    $resultado = listar_estudios();
    vistaListaEstudios($resultado);
    exit;
}
if (preg_match('#^/estudios/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_estudio(leerBodySimple($_POST, ['titulo','universidad','fecha','tipo','ciudad','ins_acreditada','metodologia','perfil_egresado','pais','docente']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /estudios');
            exit;
        }
        vistaFormularioEstudio(null, listar_docentes()['datos'] ?? [], $_POST, $resultado);
        exit;
    }
    vistaFormularioEstudio(null, listar_docentes()['datos'] ?? [], [], null);
    exit;
}
if (preg_match('#^/estudios/([^/]+)/editar$#', $ruta, $m)) {
    if ($metodo === 'POST') {
        $accion = $_POST['accion'] ?? 'completo';
        $datos = leerBodySimple($_POST, ['titulo','universidad','fecha','tipo','ciudad','ins_acreditada','metodologia','perfil_egresado','pais','docente']);
        $resultado = $accion === 'parcial' ? actualizar_estudio($m[1], $datos) : reemplazar_estudio($m[1], $datos);
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /estudios');
            exit;
        }
        vistaFormularioEstudio(obtener_estudio($m[1])['datos'] ?? null, listar_docentes()['datos'] ?? [], $_POST, $resultado);
        exit;
    }
    vistaFormularioEstudio(obtener_estudio($m[1])['datos'] ?? null, listar_docentes()['datos'] ?? [], [], null);
    exit;
}
if (preg_match('#^/estudios/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_estudio($m[1]);
    vistaListaEstudios(listar_estudios(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /departamentos ------------------------------------------------
if (preg_match('#^/departamentos$#', $ruta)) {
    vistaListaDepartamentos(listar_departamentos());
    exit;
}
if (preg_match('#^/departamentos/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_departamento(leerBodySimple($_POST, ['docente','departamento','dedicacion','modalidad','fecha_ingreso','fecha_salida']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /departamentos');
            exit;
        }
        vistaFormularioDepartamento($_POST, $resultado, listar_docentes()['datos'] ?? [], listar_programas()['datos'] ?? []);
        exit;
    }
    vistaFormularioDepartamento([], null, listar_docentes()['datos'] ?? [], listar_programas()['datos'] ?? []);
    exit;
}
if (preg_match('#^/departamentos/([^/]+)/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_departamento($m[1], $m[2]);
    vistaListaDepartamentos(listar_departamentos(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /intereses ----------------------------------------------------
if (preg_match('#^/intereses$#', $ruta)) {
    vistaListaIntereses(listar_intereses());
    exit;
}
if (preg_match('#^/intereses/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_interes(leerBodySimple($_POST, ['docente','termino_clave']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /intereses');
            exit;
        }
        vistaFormularioInteres($_POST, $resultado, listar_docentes()['datos'] ?? [], listar_terminos()['datos'] ?? []);
        exit;
    }
    vistaFormularioInteres([], null, listar_docentes()['datos'] ?? [], listar_terminos()['datos'] ?? []);
    exit;
}
if (preg_match('#^/intereses/([^/]+)/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_interes($m[1], $m[2]);
    vistaListaIntereses(listar_intereses(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /evaluaciones -------------------------------------------------
if (preg_match('#^/evaluaciones$#', $ruta)) {
    vistaListaEvaluaciones(listar_evaluaciones());
    exit;
}
if (preg_match('#^/evaluaciones/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_evaluacion(leerBodySimple($_POST, ['semestre','calificacion','docente']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /evaluaciones');
            exit;
        }
        vistaFormularioEvaluacion($_POST, $resultado, listar_docentes()['datos'] ?? []);
        exit;
    }
    vistaFormularioEvaluacion([], null, listar_docentes()['datos'] ?? []);
    exit;
}
if (preg_match('#^/evaluaciones/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_evaluacion($m[1]);
    vistaListaEvaluaciones(listar_evaluaciones(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /reconocimientos ----------------------------------------------
if (preg_match('#^/reconocimientos$#', $ruta)) {
    vistaListaReconocimientos(listar_reconocimientos());
    exit;
}
if (preg_match('#^/reconocimientos/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_reconocimiento(leerBodySimple($_POST, ['tipo','fecha','institucion','nombre','ambito','docente']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /reconocimientos');
            exit;
        }
        vistaFormularioReconocimiento($_POST, $resultado, listar_docentes()['datos'] ?? []);
        exit;
    }
    vistaFormularioReconocimiento([], null, listar_docentes()['datos'] ?? []);
    exit;
}
if (preg_match('#^/reconocimientos/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_reconocimiento($m[1]);
    vistaListaReconocimientos(listar_reconocimientos(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /experiencia --------------------------------------------------
if (preg_match('#^/experiencia$#', $ruta)) {
    vistaListaExperiencias(listar_experiencias());
    exit;
}
if (preg_match('#^/experiencia/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_experiencia(leerBodySimple($_POST, ['nombre_cargo','institucion','tipo','fecha_inicio','fecha_fin','docente']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /experiencia');
            exit;
        }
        vistaFormularioExperiencia($_POST, $resultado, listar_docentes()['datos'] ?? []);
        exit;
    }
    vistaFormularioExperiencia([], null, listar_docentes()['datos'] ?? []);
    exit;
}
if (preg_match('#^/experiencia/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_experiencia($m[1]);
    vistaListaExperiencias(listar_experiencias(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /redes-docente ------------------------------------------------
if (preg_match('#^/redes-docente$#', $ruta)) {
    vistaListaRedesDocente(listar_redes_docente());
    exit;
}
if (preg_match('#^/redes-docente/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_red_docente(leerBodySimple($_POST, ['red','docente','fecha_inicio','fecha_fin','act_destacadas']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /redes-docente');
            exit;
        }
        vistaFormularioRedDocente($_POST, $resultado, listar_redes()['datos'] ?? [], listar_docentes()['datos'] ?? []);
        exit;
    }
    vistaFormularioRedDocente([], null, listar_redes()['datos'] ?? [], listar_docentes()['datos'] ?? []);
    exit;
}
if (preg_match('#^/redes-docente/([^/]+)/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_red_docente($m[1], $m[2]);
    vistaListaRedesDocente(listar_redes_docente(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /estudios-area ------------------------------------------------
if (preg_match('#^/estudios-area$#', $ruta)) {
    vistaListaEstudiosArea(listar_estudios_area());
    exit;
}
if (preg_match('#^/estudios-area/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_estudio_area(leerBodySimple($_POST, ['estudio','area_conocimiento']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /estudios-area');
            exit;
        }
        vistaFormularioEstudioArea($_POST, $resultado, listar_estudios()['datos'] ?? [], listar_areas()['datos'] ?? []);
        exit;
    }
    vistaFormularioEstudioArea([], null, listar_estudios()['datos'] ?? [], listar_areas()['datos'] ?? []);
    exit;
}
if (preg_match('#^/estudios-area/([^/]+)/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_estudio_area($m[1], $m[2]);
    vistaListaEstudiosArea(listar_estudios_area(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /apoyos -------------------------------------------------------
if (preg_match('#^/apoyos$#', $ruta)) {
    vistaListaApoyos(listar_apoyos());
    exit;
}
if (preg_match('#^/apoyos/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_apoyo(leerBodySimple($_POST, ['estudios','con_apoyo','institucion','tipo']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /apoyos');
            exit;
        }
        vistaFormularioApoyo($_POST, $resultado, listar_estudios()['datos'] ?? []);
        exit;
    }
    vistaFormularioApoyo([], null, listar_estudios()['datos'] ?? []);
    exit;
}
if (preg_match('#^/apoyos/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_apoyo($m[1]);
    vistaListaApoyos(listar_apoyos(), $resultado['mensaje'] ?? '');
    exit;
}

// --- /becas --------------------------------------------------------
if (preg_match('#^/becas$#', $ruta)) {
    vistaListaBecas(listar_becas());
    exit;
}
if (preg_match('#^/becas/nuevo$#', $ruta)) {
    if ($metodo === 'POST') {
        $resultado = crear_beca(leerBodySimple($_POST, ['estudios','tipo','institucion','fecha_inicio','fecha_fin']));
        if ($resultado !== null && $resultado['ok']) {
            header('Location: /becas');
            exit;
        }
        vistaFormularioBeca($_POST, $resultado, listar_estudios()['datos'] ?? []);
        exit;
    }
    vistaFormularioBeca([], null, listar_estudios()['datos'] ?? []);
    exit;
}
if (preg_match('#^/becas/([^/]+)/eliminar$#', $ruta, $m)) {
    $resultado = eliminar_beca($m[1]);
    vistaListaBecas(listar_becas(), $resultado['mensaje'] ?? '');
    exit;
}

// ------------------------------------------------------------------
// Nada hizo match: 404 con el marco de la aplicación.
// ------------------------------------------------------------------
vistaNoEncontrada();
exit;

// ==================================================================
// Auxiliares: construyen el body que se manda a la API
// ==================================================================

/**
 * Lee los campos permitidos de $_POST y convierte la FORMA cuando el
 * formulario produce texto y la API espera números (RF17, C7).
 * El front convierte la forma; NO juzga el valor.
 *
 * @param array<string, mixed> $post
 * @param array<int, string>   $campos
 * @return array<string, mixed>
 */
function leerBodySimple(array $post, array $campos): array
{
    $cuerpo = [];
    $numericos = ['ins_acreditada','con_apoyo','calificacion','estudios','red','departamento','estudio'];
    foreach ($campos as $campo) {
        if (!array_key_exists($campo, $post)) {
            continue;
        }
        $valor = $post[$campo];
        if ($valor === '' || $valor === null) {
            continue;
        }
        if (in_array($campo, $numericos, true) && ctype_digit((string) $valor)) {
            $cuerpo[$campo] = (int) $valor;
        } elseif ($campo === 'calificacion' && is_numeric($valor)) {
            $cuerpo[$campo] = (float) $valor;
        } else {
            $cuerpo[$campo] = $valor;
        }
    }
    return $cuerpo;
}

/**
 * Construye el body del docente con sus listas anidadas a partir de los
 * cinco renglones fijos del formulario y las casillas múltiples (RF19,
 * 4_research D5).
 *
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function leerBodyDocenteDesdeFormulario(array $post): array
{
    $datos = leerBodySimple($post, [
        'cedula','nombres','apellidos','genero','cargo','fecha_nacimiento',
        'correo','telefono','url_cvlac','fecha_actualizacion','escalafon',
        'perfil','cat_minciencia','nacionalidad','linea_investigacion_principal',
    ]);
    if (isset($datos['linea_investigacion_principal']) && ctype_digit((string) $datos['linea_investigacion_principal'])) {
        $datos['linea_investigacion_principal'] = (int) $datos['linea_investigacion_principal'];
    }

    // Cinco renglones fijos de estudios; los vacíos se ignoran.
    $estudios = [];
    for ($i = 0; $i < 5; $i++) {
        $titulo = $post["estudio_titulo_$i"] ?? '';
        if (trim($titulo) === '') {
            continue;
        }
        $estudios[] = [
            'titulo' => $titulo,
            'universidad' => $post["estudio_universidad_$i"] ?? '',
            'fecha' => $post["estudio_fecha_$i"] ?? '',
            'tipo' => $post["estudio_tipo_$i"] ?? '',
            'ciudad' => $post["estudio_ciudad_$i"] ?? '',
            'ins_acreditada' => (int) ($post["estudio_ins_acreditada_$i"] ?? 0),
            'metodologia' => $post["estudio_metodologia_$i"] ?? '',
            'perfil_egresado' => $post["estudio_perfil_egresado_$i"] ?? '',
            'pais' => $post["estudio_pais_$i"] ?? '',
        ];
    }
    if ($estudios !== []) {
        $datos['estudios'] = $estudios;
    }

    // Intereses: casillas múltiples con los términos elegidos.
    $intereses = [];
    foreach (($post['intereses'] ?? []) as $termino) {
        if (trim((string) $termino) !== '') {
            $intereses[] = ['termino' => $termino];
        }
    }
    if ($intereses !== []) {
        $datos['intereses'] = $intereses;
    }

    // Redes: casillas múltiples con los idr elegidos.
    $redes = [];
    foreach (($post['redes'] ?? []) as $idr) {
        if (ctype_digit((string) $idr)) {
            $redes[] = ['idr' => (int) $idr];
        }
    }
    if ($redes !== []) {
        $datos['redes'] = $redes;
    }

    return $datos;
}