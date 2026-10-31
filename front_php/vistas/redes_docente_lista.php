<?php
declare(strict_types=1);

/**
 * redes_docente_lista.php
 *
 * Listado de vínculos red↔docente (puente con PK compuesta (red, docente)).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaRedesDocente(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Redes de docentes');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Redes de docentes</h1>';
    echo '<a class="btn btn-primary" href="/redes-docente/nuevo">Agregar vínculo</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay vínculos. ';
        echo '<a href="/redes-docente/nuevo">Cree el primero</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Red</th><th>Docente</th><th>Fecha de inicio</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $red = (string) ($fila['red'] ?? '');
        $docente = (string) ($fila['docente'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($red) . '</td>';
        echo '<td>' . htmlspecialchars($docente) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['fecha_inicio'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/redes-docente/' . rawurlencode($red) . '/'
            . rawurlencode($docente) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este vínculo?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}