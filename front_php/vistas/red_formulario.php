<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del formulario de `red`. Sirve para crear y para editar.
 *
 * ¿Qué recibe?
 *   - $modo:    'crear' o 'editar'.
 *   - $red:     arreglo con los datos actuales (vacío al crear).
 *   - $errores: mensajes en español para mostrar bajo el form.
 *
 * ¿Qué pasa con el idr?
 *   Es la clave primaria: se muestra de solo lectura al editar.
 *
 * ¿Qué botones tiene al editar?
 *   - "Guardar la ficha completa" → PUT (nombre + url + pais).
 *   - "Guardar solo lo que cambié" → PATCH (solo lo modificado).
 * ---------------------------------------------------------------------------
 */
$esEditar = ($modo === 'editar');
$accion = $esEditar
    ? '/redes/' . urlencode((string) ($red['idr'] ?? '')) . '/editar'
    : '/redes/nuevo';

/**
 * Helper local para no repetir htmlspecialchars en cada input.
 */
function v($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">
        <?= $esEditar ? 'Editar red' : 'Nueva red' ?>
    </h1>
    <a href="/redes" class="btn btn-outline-secondary">Volver al listado</a>
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

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="idr">IDR</label>
                <input type="number" class="form-control" id="idr" name="idr"
                       value="<?= v($red['idr'] ?? '') ?>"
                       <?= $esEditar ? 'readonly' : 'required' ?>>
                <?php if ($esEditar): ?>
                    <div class="form-text">El identificador no se puede cambiar.</div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                       value="<?= v($red['nombre'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="url">URL</label>
                <input type="text" class="form-control" id="url" name="url"
                       value="<?= v($red['url'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="pais">País</label>
                <input type="text" class="form-control" id="pais" name="pais"
                       value="<?= v($red['pais'] ?? '') ?>" required>
            </div>
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