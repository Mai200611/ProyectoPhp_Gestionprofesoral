<?php
declare(strict_types=1);

/**
 * docente_formulario.php
 *
 * Formulario de creación / edición del docente (maestro-detalle).
 *
 * Lo que esta vista hace y por qué:
 *   - Cinco renglones FIJOS para estudios; los vacíos se ignoran al armar
 *     el body (RF19, 4_research D5). Nada de JavaScript que agregue filas.
 *   - Casillas múltiples para intereses y redes: se marcan las que ya tiene
 *     y se agregan las que se elijan.
 *   - El desplegable de línea de investigación principal muestra SOLO las
 *     líneas existentes (RF17, 4_research D7). El desplegable NO valida.
 *   - Al editar, el docente ya está cargado con su detalle; al enviar, el
 *     enrutador manda un reemplazo completo (no hay «guardar solo lo que
 *     cambié», porque no hay PATCH para docente; RF12).
 *   - Los errores se muestran en español, sin números ni jerga (RNF7).
 *
 * @param array<string, mixed>|null $docente        Docente cargado (edición) o null (alta).
 * @param array<int, array<string, mixed>> $lineas  Líneas existentes para el desplegable.
 * @param array<string, mixed> $valores             Lo que la persona escribió (si falló).
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaFormularioDocente(
    ?array $docente,
    array $lineas,
    array $valores = [],
    ?array $resultado = null
): void {
    $esEdicion = $docente !== null;
    $cedula = (string) ($docente['cedula'] ?? ($valores['cedula'] ?? ''));

    // Los valores a mostrar: si hay $valores (falló al guardar), esos ganan.
    $v = static function (string $campo, $porDefecto = '') use ($docente, $valores) {
        if (array_key_exists($campo, $valores)) {
            return $valores[$campo];
        }
        return $docente[$campo] ?? $porDefecto;
    };

    // Listas que ya tiene el docente (edición).
    $estudiosExistentes = $docente['estudios'] ?? [];
    $interesesExistentes = $docente['intereses'] ?? [];
    $redesExistentes = $docente['redes'] ?? [];

    // Términos y redes para las casillas múltiples.
    $terminos = listar_terminos();
    $terminosLista = $terminos !== null ? $terminos['datos'] : [];
    $redes = listar_redes();
    $redesLista = $redes !== null ? $redes['datos'] : [];

    plantillaInicio($esEdicion ? 'Editar docente' : 'Nuevo docente');

    // Avisos de error de la API (409, 422…), en español.
    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
        if (!empty($resultado['errores'])) {
            echo '<ul class="text-danger">';
            foreach ($resultado['errores'] as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul>';
        }
    }

    $accion = $esEdicion
        ? '/docentes/' . rawurlencode($cedula) . '/editar'
        : '/docentes/nuevo';

    echo '<h1 class="h3 mb-3">' . ($esEdicion ? 'Editar docente' : 'Nuevo docente') . '</h1>';
    echo '<form method="post" action="' . htmlspecialchars($accion) . '">';

    // ---------- Encabezado del docente ----------
    echo '<fieldset class="mb-4"><legend class="h5">Datos del docente</legend>';
    echo '<div class="row g-3">';

    campoTexto('cedula', 'Cédula', (string) $v('cedula'), $esEdicion);
    campoTexto('nombres', 'Nombres', (string) $v('nombres'));
    campoTexto('apellidos', 'Apellidos', (string) $v('apellidos'));
    campoTexto('genero', 'Género', (string) $v('genero'));
    campoTexto('cargo', 'Cargo', (string) $v('cargo'));
    campoTexto('fecha_nacimiento', 'Fecha de nacimiento', (string) $v('fecha_nacimiento'), false, 'date');
    campoTexto('correo', 'Correo', (string) $v('correo'), false, 'email');
    campoTexto('telefono', 'Teléfono', (string) $v('telefono'));
    campoTexto('url_cvlac', 'URL del CVLAC', (string) $v('url_cvlac'));
    campoTexto('fecha_actualizacion', 'Fecha de actualización', (string) $v('fecha_actualizacion'), false, 'date');
    campoTexto('escalafon', 'Escalafón', (string) $v('escalafon'));
    campoTexto('cat_minciencia', 'Categoría Minciencias', (string) $v('cat_minciencia'));
    campoTexto('nacionalidad', 'Nacionalidad', (string) $v('nacionalidad'));

    echo '<div class="col-12">';
    echo '<label class="form-label" for="perfil">Perfil</label>';
    echo '<textarea class="form-control" id="perfil" name="perfil" rows="3">'
        . htmlspecialchars((string) $v('perfil')) . '</textarea>';
    echo '</div>';

    // Desplegable de línea de investigación principal: SOLO líneas existentes.
    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="linea_investigacion_principal">Línea de investigación principal</label>';
    echo '<select class="form-select" id="linea_investigacion_principal" name="linea_investigacion_principal" required>';
    echo '<option value="">Seleccione una línea</option>';
    $lineaSeleccionada = (string) $v('linea_investigacion_principal');
    foreach ($lineas as $linea) {
        $id = (string) ($linea['id'] ?? '');
        $nombre = (string) ($linea['nombre'] ?? '');
        $sel = ($id === $lineaSeleccionada) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($id) . '"' . $sel . '>'
            . htmlspecialchars($nombre) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    echo '</div></fieldset>';

    // ---------- Estudios (cinco renglones fijos) ----------
    echo '<fieldset class="mb-4"><legend class="h5">Estudios realizados</legend>';
    echo '<p class="text-muted">Agregue hasta cinco estudios. Los renglones vacíos se ignoran.</p>';
    for ($i = 0; $i < 5; $i++) {
        $estudio = $estudiosExistentes[$i] ?? [];
        echo '<div class="border rounded p-3 mb-3">';
        echo '<h2 class="h6">Estudio ' . ($i + 1) . '</h2>';
        echo '<div class="row g-3">';
        campoTexto("estudio_titulo_$i", 'Título', (string) ($estudio['titulo'] ?? ''));
        campoTexto("estudio_universidad_$i", 'Universidad', (string) ($estudio['universidad'] ?? ''));
        campoTexto("estudio_fecha_$i", 'Fecha', (string) ($estudio['fecha'] ?? ''), false, 'date');
        campoTexto("estudio_tipo_$i", 'Tipo', (string) ($estudio['tipo'] ?? ''));
        campoTexto("estudio_ciudad_$i", 'Ciudad', (string) ($estudio['ciudad'] ?? ''));
        campoTexto("estudio_ins_acreditada_$i", 'Institución acreditada (1 = sí, 0 = no)', (string) ($estudio['ins_acreditada'] ?? '0'));
        campoTexto("estudio_metodologia_$i", 'Metodología', (string) ($estudio['metodologia'] ?? ''));
        campoTexto("estudio_pais_$i", 'País', (string) ($estudio['pais'] ?? ''));
        echo '</div></div>';
    }
    echo '</fieldset>';

    // ---------- Intereses (casillas múltiples) ----------
    echo '<fieldset class="mb-4"><legend class="h5">Intereses futuros</legend>';
    if ($terminosLista === []) {
        echo '<p class="text-muted">No hay términos registrados.</p>';
    } else {
        echo '<div class="row">';
        foreach ($terminosLista as $termino) {
            $clave = (string) ($termino['termino'] ?? '');
            $marcado = false;
            foreach ($interesesExistentes as $interes) {
                if ((string) ($interes['termino_clave'] ?? '') === $clave) {
                    $marcado = true;
                    break;
                }
            }
            echo '<div class="col-md-4">';
            echo '<div class="form-check">';
            echo '<input class="form-check-input" type="checkbox" '
                . 'name="intereses[]" value="' . htmlspecialchars($clave) . '" '
                . 'id="int_' . htmlspecialchars($clave) . '"'
                . ($marcado ? ' checked' : '') . '>';
            echo '<label class="form-check-label" for="int_' . htmlspecialchars($clave) . '">'
                . htmlspecialchars($clave) . '</label>';
            echo '</div></div>';
        }
        echo '</div>';
    }
    echo '</fieldset>';

    // ---------- Redes (casillas múltiples) ----------
    echo '<fieldset class="mb-4"><legend class="h5">Redes académicas</legend>';
    if ($redesLista === []) {
        echo '<p class="text-muted">No hay redes registradas.</p>';
    } else {
        echo '<div class="row">';
        foreach ($redesLista as $red) {
            $idr = (string) ($red['idr'] ?? '');
            $nombre = (string) ($red['nombre'] ?? '');
            $marcado = false;
            foreach ($redesExistentes as $redExistente) {
                if ((string) ($redExistente['red'] ?? '') === $idr) {
                    $marcado = true;
                    break;
                }
            }
            echo '<div class="col-md-4">';
            echo '<div class="form-check">';
            echo '<input class="form-check-input" type="checkbox" '
                . 'name="redes[]" value="' . htmlspecialchars($idr) . '" '
                . 'id="red_' . htmlspecialchars($idr) . '"'
                . ($marcado ? ' checked' : '') . '>';
            echo '<label class="form-check-label" for="red_' . htmlspecialchars($idr) . '">'
                . htmlspecialchars($nombre) . '</label>';
            echo '</div></div>';
        }
        echo '</div>';
    }
    echo '</fieldset>';

    echo '<div class="d-flex gap-2">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button>';
    echo '<a href="/docentes" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}

/**
 * Pinta un campo de texto con su etiqueta.
 */
function campoTexto(
    string $nombre,
    string $etiqueta,
    string $valor,
    bool $soloLectura = false,
    string $tipo = 'text'
): void {
    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="' . htmlspecialchars($nombre) . '">'
        . htmlspecialchars($etiqueta) . '</label>';
    echo '<input class="form-control" type="' . htmlspecialchars($tipo) . '" '
        . 'id="' . htmlspecialchars($nombre) . '" '
        . 'name="' . htmlspecialchars($nombre) . '" '
        . 'value="' . htmlspecialchars($valor) . '"'
        . ($soloLectura ? ' readonly' : '') . '>';
    echo '</div>';
}