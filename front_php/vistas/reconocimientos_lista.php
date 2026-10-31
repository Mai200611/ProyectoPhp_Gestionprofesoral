<?php
declare(strict_types=1);

/**
 * reconocimientos_lista.php
 *
 * Listado de reconocimientos (ficha hija con id autogenerado).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaReconocimientos(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Reconocimientos');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Reconocimientos</h1>';
    echo '<a class="btn btn-primary" href="/reconocimientos/nuevo">Agregar reconocimiento</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay reconocimientos. ';
        echo '<a href="/reconocimientos/nuevo">Cree el primero</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Identificador</th><th>Nombre</th><th>Tipo</th><th>Institución</th><th>Docente</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $id = (string) ($fila['id'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($id) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['nombre'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['tipo'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['institucion'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['docente'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/reconocimientos/' . rawurlencode($id) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este reconocimiento?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}