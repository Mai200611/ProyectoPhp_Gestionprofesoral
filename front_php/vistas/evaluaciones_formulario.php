<?php
declare(strict_types=1);

/**
 * evaluaciones_formulario.php
 *
 * Formulario de alta de una evaluación. Desplegable de docente.
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $docentes
 */
function vistaFormularioEvaluacion(
    array $valores,
    ?array $resultado,
    array $docentes
): void {
    plantillaInicio('Nueva evaluación');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nueva evaluación</h1>';
    echo '<form method="post" action="/evaluaciones/nuevo">';

    echo '<div class="row g-3">';
    campoTexto('semestre', 'Semestre', (string) ($valores['semestre'] ?? ''));
    campoTexto('calificacion', 'Calificación', (string) ($valores['calificacion'] ?? ''));

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="docente">Docente</label>';
    echo '<select class="form-select" id="docente" name="docente" required>';
    echo '<option value="">Seleccione un docente</option>';
    $docenteSeleccionado = (string) ($valores['docente'] ?? '');
    foreach ($docentes as $docente) {
        $cedula = (string) ($docente['cedula'] ?? '');
        $nombre = trim(($docente['nombres'] ?? '') . ' ' . ($docente['apellidos'] ?? ''));
        $sel = ($cedula === $docenteSeleccionado) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($cedula) . '"' . $sel . '>'
            . htmlspecialchars($cedula . ' — ' . $nombre) . '</option>';
    }
    echo '</select></div>';
    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/evaluaciones" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}