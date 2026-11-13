<?php
declare(strict_types=1);

/**
 * apoyos_formulario.php
 *
 * Formulario de alta de un apoyo profesoral. Desplegable de estudio.
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $estudios
 */
function vistaFormularioApoyo(
    array $valores,
    ?array $resultado,
    array $estudios
): void {
    plantillaInicio('Nuevo apoyo profesoral');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nuevo apoyo profesoral</h1>';
    echo '<form method="post" action="/apoyos/nuevo">';

    echo '<div class="row g-3">';

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="estudios">Estudio</label>';
    echo '<select class="form-select" id="estudios" name="estudios" required>';
    echo '<option value="">Seleccione un estudio</option>';
    $estudioSeleccionado = (string) ($valores['estudios'] ?? '');
    foreach ($estudios as $estudio) {
        $id = (string) ($estudio['id'] ?? '');
        $titulo = (string) ($estudio['titulo'] ?? '');
        $sel = ($id === $estudioSeleccionado) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($id) . '"' . $sel . '>'
            . htmlspecialchars($titulo) . '</option>';
    }
    echo '</select></div>';

    campoTexto('con_apoyo', 'Con apoyo (1 = sí, 0 = no)', (string) ($valores['con_apoyo'] ?? '0'));
    campoTexto('institucion', 'Institución', (string) ($valores['institucion'] ?? ''));
    campoTexto('tipo', 'Tipo', (string) ($valores['tipo'] ?? ''));

    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/apoyos" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}