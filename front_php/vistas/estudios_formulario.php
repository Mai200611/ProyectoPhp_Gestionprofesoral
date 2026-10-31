<?php
declare(strict_types=1);

/**
 * estudios_formulario.php
 *
 * Formulario de alta / edición de un estudio realizado.
 * El campo «docente» es un desplegable con cédula — nombre completo (RF17).
 * En edición hay dos botones: reemplazar completo / actualizar solo lo cambiado.
 *
 * @param array<string, mixed>|null $estudio
 * @param array<int, array<string, mixed>> $docentes
 * @param array<string, mixed> $valores
 * @param array{ok:bool, datos:array, mensaje:string, errores:array, id:?int}|null $resultado
 */
function vistaFormularioEstudio(
    ?array $estudio,
    array $docentes,
    array $valores = [],
    ?array $resultado = null
): void {
    $esEdicion = $estudio !== null;
    $id = (string) ($estudio['id'] ?? '');
    $v = static function (string $campo, $porDefecto = '') use ($estudio, $valores) {
        if (array_key_exists($campo, $valores)) {
            return $valores[$campo];
        }
        return $estudio[$campo] ?? $porDefecto;
    };

    plantillaInicio($esEdicion ? 'Editar estudio' : 'Nuevo estudio');

    if ($resultado !== null && !$resultado['ok']) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($resultado['mensaje']) . '</div>';
    }

    $accion = $esEdicion ? '/estudios/' . rawurlencode($id) . '/editar' : '/estudios/nuevo';

    echo '<h1 class="h3 mb-3">' . ($esEdicion ? 'Editar estudio' : 'Nuevo estudio') . '</h1>';
    echo '<form method="post" action="' . htmlspecialchars($accion) . '">';

    if ($esEdicion) {
        echo '<div class="mb-3"><label class="form-label">Identificador</label>';
        echo '<input class="form-control" value="' . htmlspecialchars($id) . '" readonly></div>';
    }

    echo '<div class="row g-3">';
    campoTexto('titulo', 'Título', (string) $v('titulo'));
    campoTexto('universidad', 'Universidad', (string) $v('universidad'));
    campoTexto('fecha', 'Fecha', (string) $v('fecha'), false, 'date');
    campoTexto('tipo', 'Tipo', (string) $v('tipo'));
    campoTexto('ciudad', 'Ciudad', (string) $v('ciudad'));
    campoTexto('ins_acreditada', 'Institución acreditada (1 = sí, 0 = no)', (string) $v('ins_acreditada', '0'));
    campoTexto('metodologia', 'Metodología', (string) $v('metodologia'));
    campoTexto('pais', 'País', (string) $v('pais'));

    echo '<div class="col-md-6">';
    echo '<label class="form-label" for="docente">Docente</label>';
    echo '<select class="form-select" id="docente" name="docente" required>';
    echo '<option value="">Seleccione un docente</option>';
    $docenteSeleccionado = (string) $v('docente');
    foreach ($docentes as $docente) {
        $cedula = (string) ($docente['cedula'] ?? '');
        $nombre = trim(($docente['nombres'] ?? '') . ' ' . ($docente['apellidos'] ?? ''));
        $sel = ($cedula === $docenteSeleccionado) ? ' selected' : '';
        echo '<option value="' . htmlspecialchars($cedula) . '"' . $sel . '>'
            . htmlspecialchars($cedula . ' — ' . $nombre) . '</option>';
    }
    echo '</select></div>';
    echo '</div>';

    echo '<div class="mt-3">';
    if ($esEdicion) {
        echo '<input type="hidden" name="accion" id="accion" value="completo">';
        echo '<button type="submit" class="btn btn-primary" onclick="document.getElementById(\'accion\').value=\'completo\'">Guardar la ficha completa</button> ';
        echo '<button type="submit" class="btn btn-outline-primary" onclick="document.getElementById(\'accion\').value=\'parcial\'">Guardar solo lo que cambié</button> ';
    } else {
        echo '<button type="submit" class="btn btn-primary">Guardar</button> ';
    }
    echo '<a href="/estudios" class="btn btn-secondary">Cancelar</a>';
    echo '</div>';

    echo '</form>';
    plantillaFin();
}