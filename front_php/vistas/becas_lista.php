<?php
declare(strict_types=1);

/**
 * becas_lista.php
 *
 * Listado de becas (hija de estudios_realizados; PK = `estudios` sola).
 */

/**
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaListaBecas(?array $resultado, string $avisoExito = ''): void
{
    $datos = $resultado['datos'] ?? [];
    $avisoError = '';
    if ($resultado !== null && !$resultado['ok']) {
        $avisoError = $resultado['mensaje'];
    }

    plantillaInicio('Becas');

    if ($avisoExito !== '') {
        echo '<div class="alert alert-success">' . htmlspecialchars($avisoExito) . '</div>';
    }
    if ($avisoError !== '') {
        echo '<div class="alert alert-danger">' . htmlspecialchars($avisoError) . '</div>';
    }

    echo '<div class="d-flex justify-content-between align-items-center mb-3">';
    echo '<h1 class="h3 m-0">Becas</h1>';
    echo '<a class="btn btn-primary" href="/becas/nuevo">Agregar beca</a>';
    echo '</div>';

    if ($datos === []) {
        echo '<div class="alert alert-info">Todavía no hay becas. ';
        echo '<a href="/becas/nuevo">Cree la primera</a>.</div>';
        plantillaFin();
        return;
    }

    echo '<table class="table table-striped align-middle">';
    echo '<thead><tr>';
    echo '<th>Estudio</th><th>Tipo</th><th>Institución</th><th>Inicio</th><th>Fin</th><th class="text-end">Acciones</th>';
    echo '</tr></thead><tbody>';

    foreach ($datos as $fila) {
        $estudios = (string) ($fila['estudios'] ?? '');
        echo '<tr>';
        echo '<td>' . htmlspecialchars($estudios) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['tipo'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['institucion'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['fecha_inicio'] ?? '')) . '</td>';
        echo '<td>' . htmlspecialchars((string) ($fila['fecha_fin'] ?? '')) . '</td>';
        echo '<td class="text-end">';
        echo '<form method="post" action="/becas/' . rawurlencode($estudios) . '/eliminar" class="d-inline" '
            . 'onsubmit="return confirm(\'¿Eliminar esta beca?\');">';
        echo '<button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>';
        echo '</form>';
        echo '</td></tr>';
    }

    echo '</tbody></table>';
    plantillaFin();
}