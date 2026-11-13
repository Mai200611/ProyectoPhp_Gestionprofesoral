<?php
declare(strict_types=1);

$esEditar = ($modo === 'editar');
$accion = $esEditar
    ? '/terminos/' . urlencode((string) ($termino['termino'] ?? '')) . '/editar'
    : '/terminos/nuevo';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">
        <?= $esEditar ? 'Editar término clave' : 'Nuevo término clave' ?>
    </h1>
    <a href="/terminos" class="btn btn-outline-secondary">Volver al listado</a>
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
            <label class="form-label" for="termino">Término</label>
            <input type="text" class="form-control" id="termino" name="termino"
                   value="<?= htmlspecialchars((string) ($termino['termino'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   <?= $esEditar ? 'readonly' : 'required' ?>>
            <?php if ($esEditar): ?>
                <div class="form-text">El término no se puede cambiar.</div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label" for="termino_ingles">Término en inglés</label>
            <input type="text" class="form-control" id="termino_ingles" name="termino_ingles"
                   value="<?= htmlspecialchars((string) ($termino['termino_ingles'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   required>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <?php if ($esEditar): ?>
            <button type="submit" name="verbo" value="PUT" class="btn btn-primary">Guardar la ficha completa</button>
            <button type="submit" name="verbo" value="PATCH" class="btn btn-outline-primary">Guardar solo lo que cambié</button>
        <?php else: ?>
            <button type="submit" class="btn btn-primary">Guardar</button>
        <?php endif; ?>
    </div>
</form>