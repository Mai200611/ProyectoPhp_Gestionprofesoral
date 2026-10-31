<?php
declare(strict_types=1);

/**
 * estudios_area_lista.php
 *
 * Listado de vínculos estudio↔área (puente con PK compuesta
 * (estudio, area_conocimiento)).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaEstudiosArea(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Estudios y áreas');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Estudios y áreas de conocimiento</h1>';
    echo '<a class="btn btn-primary" href="/estudios-area/nuevo">Agregar vínculo</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay vínculos. ';
        echo '<a href="/estudios-area/nuevo">Cree el primero</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Estudio</th><th>Área de conocimiento</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $estudio = (string) ($fila['estudio'] ?? '');
        $area = (string) ($fila['area_conocimiento'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($estudio) . '</td>';
        echo '<td>' . htmlspecialchars($area) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/estudios-area/' . rawurlencode($estudio) . '/'
            . rawurlencode($area) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este vínculo?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}