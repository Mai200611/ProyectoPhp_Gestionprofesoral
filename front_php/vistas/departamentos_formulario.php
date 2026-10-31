<?php
declare(strict_types=1);

/**
 * departamentos_formulario.php
 *
 * Formulario de alta de una vinculación docente↔programa.
 * Desplegables: docente (cédula — nombre) y departamento (nombre del programa).
 * El desplegable NO valida: si la pareja ya existe, la API responderá 409 y
 * se mostrará el motivo en español.
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $docentes
 * @param array<int, array<string, mixed>> $programas
 */
function vistaFormularioDepartamento(
    array $valores,
    ?array $resultado,
    array $docentes,
    array $programas
): void {
    plantillaInicio('Nueva vinculación');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nueva vinculación</h1>';
    echo '<form method="post" action="/departamentos/nuevo">';

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
    echo '<label class="form-label" for="departamento">Departamento (programa)</label>';
    echo '<select class="form-select" id="departamento" name="departamento" required>';
    echo '<option value="">Seleccione un programa</option>';
    $departamentoSeleccionado = (string) ($valores['departamento'] ?? '');
    foreach ($programas as $programa) {
        $id = (string) ($programa['id'] ?? '');
        $nombre = (string) ($programa['nombre'] ?? '');
        $sel = ($id === $departamentoSeleccionado) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($id) . '"' . $sel . '>'
            . htmlspecialchars($nombre) . '</option>';
    }
    echo '</select></div>';

    campoTexto('dedicacion', 'Dedicación', (string) ($valores['dedicacion'] ?? ''));
    campoTexto('modalidad', 'Modalidad', (string) ($valores['modalidad'] ?? ''));
    campoTexto('fecha_ingreso', 'Fecha de ingreso', (string) ($valores['fecha_ingreso'] ?? ''), false, 'date');
    campoTexto('fecha_salida', 'Fecha de salida (opcional)', (string) ($valores['fecha_salida'] ?? ''), false, 'date');

    echo '</div>';

    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    echo '<a href="/departamentos" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}