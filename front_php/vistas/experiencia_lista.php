<?php
declare(strict_types=1);

/**
 * experiencia_lista.php
 *
 * Listado de experiencia (ficha hija con id autogenerado).
 * La tabla interna se llama `experiecia` (errata histórica), pero el texto
 * visible al usuario dice «Experiencia».
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaExperiencias(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Experiencia');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Experiencia</h1>';
    echo '<a class="btn btn-primary" href="/experiencia/nuevo">Agregar experiencia</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay experiencia registrada. ';
        echo '<a href="/experiencia/nuevo">Cree la primera</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Identificador</th><th>Cargo</th><th>Institución</th><th>Tipo</th><th>Docente</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $id = (string) ($fila['id'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($id) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['nombre_cargo'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['institucion'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['tipo'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['docente'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/experiencia/' . rawurlencode($id) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar esta experiencia?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}
