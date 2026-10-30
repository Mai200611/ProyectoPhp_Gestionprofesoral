<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del formulario de `area_conocimiento`. Sirve para DOS
 *   cosas:
 *     - Crear un registro nuevo (modo 'crear').
 *     - Editar uno existente (modo 'editar').
 *
 * ¿Qué recibe?
 *   - $modo:    'crear' o 'editar'.
 *   - $area:    arreglo con los datos actuales (vacíos al crear).
 *   - $errores: lista de mensajes en español para mostrar bajo el form.
 *
 * ¿Qué botones tiene en modo editar?
 *   DOS, y esto es el corazón de la v1:
 *     - "Guardar la ficha completa" → envía verbo=PUT (reemplazo).
 *     - "Guardar solo lo que cambié" → envía verbo=PATCH (parcial).
 *   Los dos comparten el mismo formulario; el envío cambia por el campo
 *   oculto `verbo`. La diferencia la decide el usuario, no la pantalla.
 *
 * ¿Qué pasa con el ID al editar?
 *   Es la identidad de la ficha: se muestra de solo lectura. No se puede
 *   cambiar.
 *
 * ¿Qué NO hace?
 *   - NO llama a la API.
 *   - NO valida: eso lo hace la API. Aquí solo se prepara el envío.
 * ---------------------------------------------------------------------------
 */
$esEditar = ($modo === 'editar');
$accion = $esEditar
    ? '/areas/' . urlencode((string) ($area['id'] ?? '')) . '/editar'
    : '/areas/nuevo';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">
        <?= $esEditar ? 'Editar área de conocimiento' : 'Nueva área de conocimiento' ?>
    </h1>
    <a href="/areas" class="btn btn-outline-secondary">Volver al listado</a>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>No se pudo guardar:</strong>
        <ul class="mb-0">
            <?php foreach ($errores as $e): ?>
                <li><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>" class="card bg-white">
    <div class="card-body">

        <div class="mb-3">
            <label class="form-label" for="id">ID</label>
            <input type="text" class="form-control" id="id" name="id"
                   value="<?= htmlspecialchars((string) ($area['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   <?= $esEditar ? 'readonly' : 'required' ?>>
            <?php if ($esEditar): ?>
                <div class="form-text">El identificador no se puede cambiar.</div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="gran_area">Gran área</label>
            <input type="text" class="form-control" id="gran_area" name="gran_area"
                   value="<?= htmlspecialchars((string) ($area['gran_area'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="area">Área</label>
            <input type="text" class="form-control" id="area" name="area"
                   value="<?= htmlspecialchars((string) ($area['area'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="disciplina">Disciplina</label>
            <input type="text" class="form-control" id="disciplina" name="disciplina"
                   value="<?= htmlspecialchars((string) ($area['disciplina'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   required>
        </div>

    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <?php if ($esEditar): ?>
            <button type="submit" name="verbo" value="PUT" class="btn btn-primary">
                Guardar la ficha completa
            </button>
            <button type="submit" name="verbo" value="PATCH" class="btn btn-outline-primary">
                Guardar solo lo que cambié
            </button>
        <?php else: ?>
            <button type="submit" class="btn btn-primary">Guardar</button>
        <?php endif; ?>
    </div>
</form>