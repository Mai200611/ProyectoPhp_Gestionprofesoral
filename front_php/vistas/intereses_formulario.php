<?php
declare(strict_types=1);

/**
 * intereses_formulario.php
 *
 * Formulario de alta de un interés futuro (puente docente↔término).
 * Desplegables: docente (cédula — nombre) y término.
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $docentes
 * @param array<int, array<string, mixed>> $terminos
 */
function vistaFormularioInteres(
    array $valores,
    ?array $resultado,
    array $docentes,
    array $terminos
): void {
    plantillaInicio('Nuevo interés');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nuevo interés futuro</h1>';
    echo '<form method="post" action="/intereses/nuevo">';

    echo '<div class="row g-3">';

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

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="termino_clave">Término</label>';
    echo '<select class="form-select" id="termino_clave" name="termino_clave" required>';
    echo '<option value="">Seleccione un término</option>';
    $terminoSeleccionado = (string) ($valores['termino_clave'] ?? '');
    foreach ($terminos as $termino) {
        $clave = (string) ($termino['termino'] ?? '');
        $sel = ($clave === $terminoSeleccionado) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($clave) . '"' . $sel . '>'
            . htmlspecialchars($clave) . '</option>';
    }
    echo '</select></div>';

    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/intereses" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}