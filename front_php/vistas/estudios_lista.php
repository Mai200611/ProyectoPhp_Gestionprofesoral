<?php
declare(strict_types=1);

/**
 * estudios_lista.php
 *
 * Listado de estudios realizados. Patrón de ficha con id autogenerado.
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaEstudios(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Estudios realizados');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Estudios realizados</h1>';
    echo '<a class="btn btn-primary" href="/estudios/nuevo">Agregar estudio</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay estudios. ';
        echo '<a href="/estudios/nuevo">Cree el primero</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Identificador</th><th>Título</th><th>Universidad</th><th>Tipo</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $id = (string) ($fila['id'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($id) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['titulo'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['universidad'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['tipo'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<a class="btn btn-sm btn-outline-primary" href="/estudios/' . rawurlencode($id) . '/editar">Editar</a> ';
        echo '<form method="post" action="/estudios/' . rawurlencode($id) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este estudio?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}