<?php 
declare(strict_types=1); 
$esEditar = ($modo === 'editar');
$accion = $esEditar ? '/estudios/' . urlencode((string) ($estudio['id'] ?? '')) . '/editar' : '/estudios/nuevo';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0"><?= $esEditar ? 'Editar estudio' : 'Nuevo estudio' ?></h1>
    <a href="/estudios" class="btn btn-outline-secondary">Volver al listado</a>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>No se pudo guardar:</strong>
        <ul class="mb-0">
            <?php foreach ($errores as $e): ?><li><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>" class="card bg-white">
    <div class="card-body">
        <?php if ($esEditar): ?>
            <div class="mb-3">
                <label class="form-label">ID</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars((string) ($estudio['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>
        <?php endif; ?>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="titulo">Título</label>
                <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars((string) ($estudio['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="universidad">Universidad</label>
                <input type="text" class="form-control" id="universidad" name="universidad" value="<?= htmlspecialchars((string) ($estudio['universidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tipo">Tipo</label>
                <input type="text" class="form-control" id="tipo" name="tipo" value="<?= htmlspecialchars((string) ($estudio['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cedula">Docente</label>
                <select class="form-select" id="cedula" name="cedula" required>
                    <option value="">Seleccione un docente...</option>
                    <?php if (is_array($docentes)): ?>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= htmlspecialchars((string) ($d['cedula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (($estudio['cedula'] ?? '') === ($d['cedula'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($d['cedula'] ?? '') . ' - ' . ($d['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <?php if ($esEditar): ?>
            <button type="submit" name="verbo" value="PUT" class="btn btn-primary">Guardar ficha completa</button>
            <button type="submit" name="verbo" value="PATCH" class="btn btn-outline-primary">Guardar solo lo que cambié</button>
        <?php else: ?>
            <button type="submit" class="btn btn-primary">Guardar</button>
        <?php endif; ?>
    </div>
</form>