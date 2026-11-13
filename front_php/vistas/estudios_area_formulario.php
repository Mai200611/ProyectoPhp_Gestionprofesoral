<?php
declare(strict_types=1);

/**
 * estudios_area_formulario.php
 *
 * Formulario de alta de un vínculo estudio↔área.
 * Desplegables: estudio (título) y área (gran área — área).
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $estudios
 * @param array<int, array<string, mixed>> $areas
 */
function vistaFormularioEstudioArea(
    array $valores,
    ?array $resultado,
    array $estudios,
    array $areas
): void {
    plantillaInicio('Nuevo vínculo estudio-área');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nuevo vínculo estudio — área</h1>';
    echo '<form method="post" action="/estudios-area/nuevo">';

    echo '<div class="row g-3">';

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="estudio">Estudio</label>';
    echo '<select class="form-select" id="estudio" name="estudio" required>';
    echo '<option value="">Seleccione un estudio</option>';
    $estudioSeleccionado = (string) ($valores['estudio'] ?? '');
    foreach ($estudios as $estudio) {
        $id = (string) ($estudio['id'] ?? '');
        $titulo = (string) ($estudio['titulo'] ?? '');
        $sel = ($id === $estudioSeleccionado) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($id) . '"' . $sel . '>'
            . htmlspecialchars($titulo) . '</option>';
    }
    echo '</select></div>';

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="area_conocimiento">Área de conocimiento</label>';
    echo '<select class="form-select" id="area_conocimiento" name="area_conocimiento" required>';
    echo '<option value="">Seleccione un área</option>';
    $areaSeleccionada = (string) ($valores['area_conocimiento'] ?? '');
    foreach ($areas as $area) {
        $id = (string) ($area['id'] ?? '');
        $texto = trim(($area['gran_area'] ?? '') . ' — ' . ($area['area'] ?? ''));
        $sel = ($id === $areaSeleccionada) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($id) . '"' . $sel . '>'
            . htmlspecialchars($texto) . '</option>';
    }
    echo '</select></div>';

    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/estudios-area" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}