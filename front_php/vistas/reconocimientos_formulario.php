<?php
declare(strict_types=1);

/**
 * reconocimientos_formulario.php
 *
 * Formulario de alta de un reconocimiento. Desplegable de docente.
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $docentes
 */
function vistaFormularioReconocimiento(
    array $valores,
    ?array $resultado,
    array $docentes
): void {
    plantillaInicio('Nuevo reconocimiento');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nuevo reconocimiento</h1>';
    echo '<form method="post" action="/reconocimientos/nuevo">';

    echo '<div class="row g-3">';
    campoTexto('tipo', 'Tipo', (string) ($valores['tipo'] ?? ''));
    campoTexto('fecha', 'Fecha', (string) ($valores['fecha'] ?? ''), false, 'date');
    campoTexto('institucion', 'Institución', (string) ($valores['institucion'] ?? ''));
    campoTexto('nombre', 'Nombre', (string) ($valores['nombre'] ?? ''));
    campoTexto('ambito', 'Ámbito', (string) ($valores['ambito'] ?? ''));

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
    echo '<a href="/reconocimientos" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}