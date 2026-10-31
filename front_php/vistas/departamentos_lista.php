<?php
declare(strict_types=1);

/**
 * departamentos_lista.php
 *
 * Listado de vinculaciones docente↔programa (tabla puente con PK compuesta).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaDepartamentos(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Vinculaciones');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Vinculaciones docente — departamento</h1>';
    echo '<a class="btn btn-primary" href="/departamentos/nuevo">Agregar vinculación</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay vinculaciones. ';
        echo '<a href="/departamentos/nuevo">Cree la primera</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Docente</th><th>Departamento</th><th>Dedicación</th><th>Modalidad</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $docente = (string) ($fila['docente'] ?? '');
        $departamento = (string) ($fila['departamento'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($docente) . '</td>';
        echo '<td>' . htmlspecialchars($departamento) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['dedicacion'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['modalidad'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/departamentos/' . rawurlencode($docente) . '/'
            . rawurlencode($departamento) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar esta vinculación?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}