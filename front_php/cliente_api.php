<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la ÚNICA pieza del front que habla HTTP con la API.
 * ---------------------------------------------------------------------------
 */

function url_api(): string
{
    $url = getenv('URL_API');
    if ($url === false || $url === '') {
        return 'http://localhost:8122'; // Puerto local por defecto
    }
    return $url;
}

function llamar_api(string $metodo, string $ruta, ?array $cuerpo = null): ?array
{
    $url = url_api() . $ruta;
    $ch = curl_init($url);
    if ($ch === false) return null;

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

    if ($respuestaCruda === false || $errorCurl !== '') return null;
    if ($codigoHttp === 204) return ['__codigo' => 204];

    $decodificado = json_decode((string) $respuestaCruda, true);
    if (!is_array($decodificado)) return null;

    $decodificado['__codigo'] = $codigoHttp;
    return $decodificado;
}

function mensajes_de_error(?array $respuesta): array
{
    if ($respuesta === null) return ['El servicio no está disponible.'];
    if (isset($respuesta['errores']) && is_array($respuesta['errores']) && $respuesta['errores'] !== []) {
        return array_map('strval', $respuesta['errores']);
    }
    $mensaje = $respuesta['mensaje'] ?? 'Ocurrió un error inesperado.';
    if (isset($respuesta['detalle']) && $respuesta['detalle'] !== '') {
        $mensaje .= ' ' . $respuesta['detalle'];
    }
    return [(string) $mensaje];
}

// =========================================================================
// RECURSOS V1
// =========================================================================

// --- AREA_CONOCIMIENTO ---
function listar_areas_conocimiento(): array {
    $r = llamar_api('GET', '/api/area_conocimiento');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_area_conocimiento(string $id): array {
    $r = llamar_api('GET', '/api/area_conocimiento/' . urlencode($id));
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_area_conocimiento(array $datos): array {
    $r = llamar_api('POST', '/api/area_conocimiento', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_area_conocimiento(string $id, array $datos): array {
    $r = llamar_api('PUT', '/api/area_conocimiento/' . urlencode($id), $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function actualizar_area_conocimiento(string $id, array $datos): array {
    $r = llamar_api('PATCH', '/api/area_conocimiento/' . urlencode($id), $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_area_conocimiento(string $id): array {
    $r = llamar_api('DELETE', '/api/area_conocimiento/' . urlencode($id));
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// --- TERMINO_CLAVE ---
function listar_terminos_clave(): array {
    $r = llamar_api('GET', '/api/termino_clave');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_termino_clave(string $termino): array {
    $r = llamar_api('GET', '/api/termino_clave/' . urlencode($termino));
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_termino_clave(array $datos): array {
    $r = llamar_api('POST', '/api/termino_clave', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_termino_clave(string $termino, array $datos): array {
    $r = llamar_api('PUT', '/api/termino_clave/' . urlencode($termino), $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function actualizar_termino_clave(string $termino, array $datos): array {
    $r = llamar_api('PATCH', '/api/termino_clave/' . urlencode($termino), $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_termino_clave(string $termino): array {
    $r = llamar_api('DELETE', '/api/termino_clave/' . urlencode($termino));
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// --- LINEA_INVESTIGACION ---
function listar_lineas_investigacion(): array {
    $r = llamar_api('GET', '/api/linea_investigacion');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_linea_investigacion(int $id): array {
    $r = llamar_api('GET', '/api/linea_investigacion/' . $id);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_linea_investigacion(array $datos): array {
    $r = llamar_api('POST', '/api/linea_investigacion', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_linea_investigacion(int $id, array $datos): array {
    $r = llamar_api('PUT', '/api/linea_investigacion/' . $id, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function actualizar_linea_investigacion(int $id, array $datos): array {
    $r = llamar_api('PATCH', '/api/linea_investigacion/' . $id, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_linea_investigacion(int $id): array {
    $r = llamar_api('DELETE', '/api/linea_investigacion/' . $id);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// --- PROGRAMA ---
function listar_programas(): array {
    $r = llamar_api('GET', '/api/programa');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_programa(int $id): array {
    $r = llamar_api('GET', '/api/programa/' . $id);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_programa(array $datos): array {
    $r = llamar_api('POST', '/api/programa', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_programa(int $id, array $datos): array {
    $r = llamar_api('PUT', '/api/programa/' . $id, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function actualizar_programa(int $id, array $datos): array {
    $r = llamar_api('PATCH', '/api/programa/' . $id, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_programa(int $id): array {
    $r = llamar_api('DELETE', '/api/programa/' . $id);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// --- RED ---
function listar_redes(): array {
    $r = llamar_api('GET', '/api/red');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_red(int $idr): array {
    $r = llamar_api('GET', '/api/red/' . $idr);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_red(array $datos): array {
    $r = llamar_api('POST', '/api/red', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_red(int $idr, array $datos): array {
    $r = llamar_api('PUT', '/api/red/' . $idr, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function actualizar_red(int $idr, array $datos): array {
    $r = llamar_api('PATCH', '/api/red/' . $idr, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_red(int $idr): array {
    $r = llamar_api('DELETE', '/api/red/' . $idr);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// =========================================================================
// RECURSOS V2
// =========================================================================

// --- DOCENTE ---
function listar_docentes(): array {
    $r = llamar_api('GET', '/api/docente');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_docente(string $cedula): array {
    $r = llamar_api('GET', '/api/docente/' . urlencode($cedula));
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_docente_completo(string $cedula): array {
    $r = llamar_api('GET', '/api/docente/' . urlencode($cedula) . '/completo');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_docente(array $datos): array {
    $r = llamar_api('POST', '/api/docente', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_docente(string $cedula, array $datos): array {
    $r = llamar_api('PUT', '/api/docente/' . urlencode($cedula), $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_docente(string $cedula): array {
    $r = llamar_api('DELETE', '/api/docente/' . urlencode($cedula));
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// --- ESTUDIOS_REALIZADOS ---
function listar_estudios(): array {
    $r = llamar_api('GET', '/api/estudios_realizados');
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 204) return ['ok' => true, 'datos' => [], 'errores' => []];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function obtener_estudio(int $id): array {
    $r = llamar_api('GET', '/api/estudios_realizados/' . $id);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function crear_estudio(array $datos): array {
    $r = llamar_api('POST', '/api/estudios_realizados', $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function reemplazar_estudio(int $id, array $datos): array {
    $r = llamar_api('PUT', '/api/estudios_realizados/' . $id, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function actualizar_estudio(int $id, array $datos): array {
    $r = llamar_api('PATCH', '/api/estudios_realizados/' . $id, $datos);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}
function eliminar_estudio(int $id): array {
    $r = llamar_api('DELETE', '/api/estudios_realizados/' . $id);
    if ($r === null) return ['ok' => false, 'datos' => null, 'errores' => ['El servicio no está disponible.']];
    if (($r['__codigo'] ?? 0) === 200) return ['ok' => true, 'datos' => $r, 'errores' => []];
    return ['ok' => false, 'datos' => null, 'errores' => mensajes_de_error($r)];
}

// --- DOCENTE_DEPARTAMENTO ---
function listar_departamentos(): array {
    $r = llamar_api('GET', '/api/docente_departamento');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []]; // Si falla, devuelve vacío
}

// --- INTERESES_FUTUROS ---
function listar_intereses(): array {
    $r = llamar_api('GET', '/api/intereses_futuros');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- EVALUACION_DOCENTE ---
function listar_evaluaciones(): array {
    $r = llamar_api('GET', '/api/evaluacion_docente');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- RECONOCIMIENTO ---
function listar_reconocimientos(): array {
    $r = llamar_api('GET', '/api/reconocimiento');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- EXPERIECIA ---
function listar_experiencias(): array {
    $r = llamar_api('GET', '/api/experiecia');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- RED_DOCENTE ---
function listar_redes_docente(): array {
    $r = llamar_api('GET', '/api/red_docente');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- ESTUDIO_AC ---
function listar_estudios_ac(): array {
    $r = llamar_api('GET', '/api/estudio_ac');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- APOYO_PROFESORAL ---
function listar_apoyos(): array {
    $r = llamar_api('GET', '/api/apoyo_profesoral');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}

// --- BECA ---
function listar_becas(): array {
    $r = llamar_api('GET', '/api/beca');
    return ['ok' => true, 'datos' => $r['datos'] ?? [], 'errores' => []];
}