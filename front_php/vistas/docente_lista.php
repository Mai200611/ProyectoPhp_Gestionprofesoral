<?php
declare(strict_types=1);

/**
 * docente_lista.php
 *
 * Listado de docentes. Es la única lista de la v2 que muestra un botón
 * «Ver» (para el detalle completo) y cuyo «Eliminar» puede dar 409 si el
 * docente tiene dependencias (RF20).
 *
 * Sin jerga: el texto visible no menciona verbos, códigos ni columnas.
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaDocentes(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Docentes');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Docentes</h1>';
    echo '<a class="btn btn-primary" href="/docentes/nuevo">Agregar docente</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">';
        echo 'Todavía no hay docentes registrados. ';
        echo '<a href="/docentes/nuevo">Cree el primero</a>.';
        echo '</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Cédula</th><th>Nombre completo</th><th>Línea principal</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $cedula = (string) ($fila['cedula'] ?? '');
        $nombre = trim(($fila['nombres'] ?? '') . ' ' . ($fila['apellidos'] ?? ''));
        $linea = (string) ($fila['linea_investigacion_principal'] ?? '');

        echo '<tr>';
        echo '<td>' . htmlspecialchars($cedula) . '</td>';
        echo '<td>' . htmlspecialchars($nombre) . '</td>';
        echo '<td>' . htmlspecialchars($linea) . '</td>';
        echo '<td class="text-end">';
        echo '<a class="btn btn-sm btn-outline-secondary" href="/docentes/' . rawurlencode($cedula) . '">Ver</a> ';
        echo '<a class="btn btn-sm btn-outline-primary" href="/docentes/' . rawurlencode($cedula) . '/editar">Editar</a> ';
        echo '<form method="post" action="/docentes/' . rawurlencode($cedula) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar este docente? Si tiene fichas asociadas, el sistema no lo permitirá.\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}