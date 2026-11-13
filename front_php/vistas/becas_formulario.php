<?php
declare(strict_types=1);

/**
 * becas_formulario.php
 *
 * Formulario de alta de una beca. Desplegable de estudio.
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $estudios
 */
function vistaFormularioBeca(
    array $valores,
    ?array $resultado,
    array $estudios
): void {
    plantillaInicio('Nueva beca');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nueva beca</h1>';
    echo '<form method="post" action="/becas/nuevo">';

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

    campoTexto('tipo', 'Tipo', (string) ($valores['tipo'] ?? ''));
    campoTexto('institucion', 'Institución', (string) ($valores['institucion'] ?? ''));
    campoTexto('fecha_inicio', 'Fecha de inicio', (string) ($valores['fecha_inicio'] ?? ''), false, 'date');
    campoTexto('fecha_fin', 'Fecha de fin (opcional)', (string) ($valores['fecha_fin'] ?? ''), false, 'date');

    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/becas" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}