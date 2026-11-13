<?php
declare(strict_types=1);

/**
 * experiencia_formulario.php
 *
 * Formulario de alta de una experiencia. Desplegable de docente.
 * La tabla interna es `experiecia`; el texto visible dice «Experiencia».
 *
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 * @param array<int, array<string, mixed>> $docentes
 */
function vistaFormularioExperiencia(
    array $valores,
    ?array $resultado,
    array $docentes
): void {
    plantillaInicio('Nueva experiencia');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    echo '<h1 class="h3 mb-3">Nueva experiencia</h1>';
    echo '<form method="post" action="/experiencia/nuevo">';

    echo '<div class="row g-3">';
    campoTexto('nombre_cargo', 'Cargo', (string) ($valores['nombre_cargo'] ?? ''));
    campoTexto('institucion', 'Institución', (string) ($valores['institucion'] ?? ''));
    campoTexto('tipo', 'Tipo', (string) ($valores['tipo'] ?? ''));
    campoTexto('fecha_inicio', 'Fecha de inicio', (string) ($valores['fecha_inicio'] ?? ''), false, 'date');
    campoTexto('fecha_fin', 'Fecha de fin (opcional)', (string) ($valores['fecha_fin'] ?? ''), false, 'date');

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
    echo '<a href="/experiencia" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}