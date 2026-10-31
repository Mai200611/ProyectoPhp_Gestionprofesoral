<?php
declare(strict_types=1);

/**
 * apoyos_lista.php
 *
 * Listado de apoyos professorales (hija de estudios_realizados;
 * PK = `estudios` sola).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaApoyos(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Apoyos professorales');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Apoyos professorales</h1>';
    echo '<a class="btn btn-primary" href="/apoyos/nuevo">Agregar apoyo</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay apoyos. ';
        echo '<a href="/apoyos/nuevo">Cree el primero</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Estudio</th><th>Con apoyo</th><th>Institución</th><th>Tipo</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $estudios = (string) ($fila['estudios'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($estudios) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['con_apoyo'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['institucion'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['tipo'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/apoyos/' . rawurlencode($estudios) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este apoyo?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}