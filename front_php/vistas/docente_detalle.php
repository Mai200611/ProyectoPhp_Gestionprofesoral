<?php
declare(strict_types=1);

/**
 * docente_detalle.php
 *
 * Pantalla de ver un docente completo: sus datos y, anidados, sus estudios
 * (con sus apoyos y becas), sus intereses, sus redes, sus evaluaciones, sus
 * reconocimientos y su experiencia.
 *
 * Es la ÚNICA pantalla de la v2 con esta forma (RF19). Hace UNA sola
 * llamada a la API (GET /api/docente/{cedula}/completo) y pinta todo; no
 * hace varias llamadas y las cose. Ver 4_research D6.
 *
 * Sin jerga: el texto visible no menciona verbos, códigos ni columnas.
 *
 * @param array<string, mixed>|null $docente
 */
function vistaDetalleDocente(?array $docente): void
{
    plantillaInicio('Detalle del docente');

    if ($docente === null) {
        echo '<div class="alert alert-warning">';
        echo 'No se pudo cargar el docente. ';
        echo '<a href="/docentes">Volver al listado</a>.';
        echo '</div>';
        plantillaFin();
        return;
    }

    $cedula = (string) ($docente['cedula'] ?? '');
    $nombre = trim(($docente['nombres'] ?? '') . ' ' . ($docente['apellidos'] ?? ''));

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">' . htmlspecialchars($nombre) . '</h1>';
    echo '<div>';
    echo '<a class="btn btn-outline-secondary" href="/docentes">Volver</a> ';
    echo '<a class="btn btn-primary" href="/docentes/' . rawurlencode($cedula) . '/editar">Editar</a>';
    echo '</div>';
    echo '</div>';

    // ---------- Datos del docente ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Datos personales</h2>';
    echo '<dl class="row mb-0">';
    dato('Cédula', (string) ($docente['cedula'] ?? ''));
    dato('Nombres', (string) ($docente['nombres'] ?? ''));
    dato('Apellidos', (string) ($docente['apellidos'] ?? ''));
    dato('Género', (string) ($docente['genero'] ?? ''));
    dato('Cargo', (string) ($docente['cargo'] ?? ''));
    dato('Correo', (string) ($docente['correo'] ?? ''));
    dato('Teléfono', (string) ($docente['telefono'] ?? ''));
    dato('Escalafón', (string) ($docente['escalafon'] ?? ''));
    dato('Categoría Minciencias', (string) ($docente['cat_minciencia'] ?? ''));
    dato('Nacionalidad', (string) ($docente['nacionalidad'] ?? ''));
    dato('Línea de investigación principal', (string) ($docente['linea_investigacion_principal'] ?? ''));
    echo '</dl></div></div>';

    // ---------- Estudios (con apoyos y becas) ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Estudios realizados</h2>';
    $estudios = $docente['estudios'] ?? [];
    if ($estudios === []) {
        echo '<p class="text-muted mb-0">Este docente no tiene estudios registrados.</p>';
    } else {
        echo '<ul class="list-group list-group-flush">';
        foreach ($estudios as $estudio) {
            echo '<li class="list-group-item">';
            echo '<strong>' . htmlspecialchars((string) ($estudio['titulo'] ?? '')) . '</strong> — ';
            echo htmlspecialchars((string) ($estudio['universidad'] ?? '')) . ' · ';
            echo htmlspecialchars((string) ($estudio['tipo'] ?? '')) . ' · ';
            echo htmlspecialchars((string) ($estudio['fecha'] ?? ''));

            if (!empty($estudio['apoyos'])) {
                echo '<div class="mt-2"><em>Apoyos:</em><ul>';
                foreach ($estudio['apoyos'] as $apoyo) {
                    echo '<li>' . htmlspecialchars((string) ($apoyo['institucion'] ?? ''))
                        . ' — ' . htmlspecialchars((string) ($apoyo['tipo'] ?? '')) . '</li>';
                }
                echo '</ul></div>';
            }
            if (!empty($estudio['becas'])) {
                echo '<div class="mt-2"><em>Becas:</em><ul>';
                foreach ($estudio['becas'] as $beca) {
                    echo '<li>' . htmlspecialchars((string) ($beca['institucion'] ?? ''))
                        . ' — ' . htmlspecialchars((string) ($beca['tipo'] ?? '')) . '</li>';
                }
                echo '</ul></div>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }
    echo '</div></div>';

    // ---------- Intereses ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Intereses futuros</h2>';
    $intereses = $docente['intereses'] ?? [];
    if ($intereses === []) {
        echo '<p class="text-muted mb-0">Sin intereses registrados.</p>';
    } else {
        echo '<ul class="mb-0">';
        foreach ($intereses as $interes) {
            echo '<li>' . htmlspecialchars((string) ($interes['termino_clave'] ?? '')) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div></div>';

    // ---------- Redes ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Redes académicas</h2>';
    $redes = $docente['redes'] ?? [];
    if ($redes === []) {
        echo '<p class="text-muted mb-0">Sin redes registradas.</p>';
    } else {
        echo '<ul class="mb-0">';
        foreach ($redes as $red) {
            echo '<li>Red ' . htmlspecialchars((string) ($red['red'] ?? ''))
                . ' — ' . htmlspecialchars((string) ($red['act_destacadas'] ?? '')) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div></div>';

    // ---------- Evaluaciones ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Evaluaciones</h2>';
    $evaluaciones = $docente['evaluaciones'] ?? [];
    if ($evaluaciones === []) {
        echo '<p class="text-muted mb-0">Sin evaluaciones registradas.</p>';
    } else {
        echo '<ul class="mb-0">';
        foreach ($evaluaciones as $ev) {
            echo '<li>' . htmlspecialchars((string) ($ev['semestre'] ?? ''))
                . ': ' . htmlspecialchars((string) ($ev['calificacion'] ?? '')) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div></div>';

    // ---------- Reconocimientos ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Reconocimientos</h2>';
    $reconocimientos = $docente['reconocimientos'] ?? [];
    if ($reconocimientos === []) {
        echo '<p class="text-muted mb-0">Sin reconocimientos registrados.</p>';
    } else {
        echo '<ul class="mb-0">';
        foreach ($reconocimientos as $rec) {
            echo '<li>' . htmlspecialchars((string) ($rec['nombre'] ?? ''))
                . ' — ' . htmlspecialchars((string) ($rec['institucion'] ?? '')) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div></div>';

    // ---------- Experiencia ----------
    echo '<div class="card mb-4"><div class="card-body">';
    echo '<h2 class="h5">Experiencia</h2>';
    $experiencias = $docente['experiencias'] ?? [];
    if ($experiencias === []) {
        echo '<p class="text-muted mb-0">Sin experiencia registrada.</p>';
    } else {
        echo '<ul class="mb-0">';
        foreach ($experiencias as $exp) {
            echo '<li>' . htmlspecialchars((string) ($exp['nombre_cargo'] ?? ''))
                . ' — ' . htmlspecialchars((string) ($exp['institucion'] ?? '')) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div></div>';

    plantillaFin();
}

/**
 * Pinta una pareja etiqueta/valor.
 */
function dato(string $etiqueta, string $valor): void
{
    echo '<dt class="col-sm-3">' . htmlspecialchars($etiqueta) . '</dt>';
    echo '<dd class="col-sm-9">' . htmlspecialchars($valor !== '' ? $valor : '—') . '</dd>';
}