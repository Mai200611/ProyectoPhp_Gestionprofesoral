<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del formulario de `linea_investigacion`. Sirve para crear
 *   y para editar.
 *
 * ¿Qué recibe?
 *   - $modo:    'crear' o 'editar'.
 *   - $linea:   arreglo con los datos actuales (vacío al crear).
 *   - $errores: mensajes en español para mostrar bajo el form.
 *
 * ¿Qué pasa con el id?
 *   - Al crear: no se envía. La API lo genera.
 *   - Al editar: se muestra de solo lectura (es la identidad de la ficha).
 *
 * ¿Qué botones tiene al editar?
 *   - "Guardar la ficha completa" → PUT (nombre + descripcion).
 *   - "Guardar solo lo que cambié" → PATCH (solo lo que el usuario cambie).
 * ---------------------------------------------------------------------------
 */
$esEditar = ($modo === 'editar');
$accion = $esEditar
    ? '/lineas/' . urlencode((string) ($linea['id'] ?? '')) . '/editar'
    : '/lineas/nuevo';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">
        <?= $esEditar ? 'Editar línea de investigación' : 'Nueva línea de investigación' ?>
    </h1>
    <a href="/lineas" class="btn btn-outline-secondary">Volver al listado</a>
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

        <?php if ($esEditar): ?>
            <div class="mb-3">
                <label class="form-label" for="id">ID</label>
                <input type="text" class="form-control" id="id"
                       value="<?= htmlspecialchars((string) ($linea['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                       readonly>
                <div class="form-text">El identificador no se puede cambiar.</div>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label" for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre"
                   value="<?= htmlspecialchars((string) ($linea['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?= htmlspecialchars((string) ($linea['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
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