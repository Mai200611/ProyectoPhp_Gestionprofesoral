<?php 
declare(strict_types=1); 
$esEditar = ($modo === 'editar');
$accion = $esEditar ? '/docentes/' . urlencode((string) ($docente['cedula'] ?? '')) . '/editar' : '/docentes/nuevo';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0"><?= $esEditar ? 'Editar docente' : 'Nuevo docente' ?></h1>
    <a href="/docentes" class="btn btn-outline-secondary">Volver al listado</a>
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
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="cedula">Cédula</label>
                <input type="text" class="form-control" id="cedula" name="cedula" value="<?= htmlspecialchars((string) ($docente['cedula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $esEditar ? 'readonly' : 'required' ?>>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="nombre_completo">Nombre completo</label>
                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars((string) ($docente['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="linea_investigacion_principal">Línea de investigación principal</label>
                <select class="form-select" id="linea_investigacion_principal" name="linea_investigacion_principal">
                    <option value="">Ninguna</option>
                    <?php foreach ($lineas as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= (($docente['linea_investigacion_principal'] ?? '') == $l['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $l['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="escalafon">Escalafón</label>
                <input type="text" class="form-control" id="escalafon" name="escalafon" value="<?= htmlspecialchars((string) ($docente['escalafon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <?php if ($esEditar): ?>
            <button type="submit" name="verbo" value="PUT" class="btn btn-primary">Guardar la ficha completa</button>
        <?php else: ?>
            <button type="submit" class="btn btn-primary">Guardar</button>
        <?php endif; ?>
    </div>
</form>