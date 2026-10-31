<?php
declare(strict_types=1);

/**
 * intereses_lista.php
 *
 * Listado de intereses futuros (puente docente↔término, PK compuesta).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaIntereses(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Intereses futuros');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Intereses futuros</h1>';
    echo '<a class="btn btn-primary" href="/intereses/nuevo">Agregar interés</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay intereses. ';
        echo '<a href="/intereses/nuevo">Cree el primero</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Docente</th><th>Término</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $docente = (string) ($fila['docente'] ?? '');
        $termino = (string) ($fila['termino_clave'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($docente) . '</td>';
        echo '<td>' . htmlspecialchars($termino) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/intereses/' . rawurlencode($docente) . '/'
            . rawurlencode($termino) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este interés?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}