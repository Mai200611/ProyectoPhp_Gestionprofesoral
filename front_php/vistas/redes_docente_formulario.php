<?php
declare(strict_types=1);

/**
 * redes_docente_formulario.php
 *
 * Formulario de alta de un vínculo red↔docente.
 * Desplegables: red (nombre) y docente (cédula — nombre).
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $redes
 * @param array<int, array<string, mixed>> $docentes
 */
function vistaFormularioRedDocente(
    array $valores,
    ?array $resultado,
    array $redes,
    array $docentes
): void {
    plantillaInicio('Nuevo vínculo');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nuevo vínculo red — docente</h1>';
    echo '<form method="post" action="/redes-docente/nuevo">';

    echo '<div class="row g-3">';

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="red">Red</label>';
    echo '<select class="form-select" id="red" name="red" required>';
    echo '<option value="">Seleccione una red</option>';
    $redSeleccionada = (string) ($valores['red'] ?? '');
    foreach ($redes as $red) {
        $idr = (string) ($red['idr'] ?? '');
        $nombre = (string) ($red['nombre'] ?? '');
        $sel = ($idr === $redSeleccionada) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($idr) . '"' . $sel . '>'
            . htmlspecialchars($nombre) . '</option>';
    }
    echo '</select></div>';

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

    campoTexto('fecha_inicio', 'Fecha de inicio', (string) ($valores['fecha_inicio'] ?? ''), false, 'date');
    campoTexto('fecha_fin', 'Fecha de fin (opcional)', (string) ($valores['fecha_fin'] ?? ''), false, 'date');
    campoTexto('act_destacadas', 'Actividades destacadas', (string) ($valores['act_destacadas'] ?? ''));

    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/redes-docente" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}