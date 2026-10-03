<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del formulario de `programa`. Sirve para crear y para
 *   editar.
 *
 * ¿Qué recibe?
 *   - $modo:     'crear' o 'editar'.
 *   - $programa: arreglo con los datos actuales (vacío al crear).
 *   - $errores:  mensajes en español para mostrar bajo el form.
 *
 * ¿Qué pasa con el id al editar?
 *   Es la clave primaria: se muestra de solo lectura.
 *
 * ¿Qué pasa con fecha_cierre?
 *   Admite vacío (null). Si el usuario deja el campo vacío, se envía
 *   null — el controlador de la API lo acepta como valor válido.
 *
 * ¿Qué botones tiene al editar?
 *   - "Guardar la ficha completa" → PUT (todos los campos editables).
 *   - "Guardar solo lo que cambié" → PATCH (solo lo modificado).
 * ---------------------------------------------------------------------------
 */
$esEditar = ($modo === 'editar');
$accion = $esEditar
    ? '/programas/' . urlencode((string) ($programa['id'] ?? '')) . '/editar'
    : '/programas/nuevo';

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
        <?= $esEditar ? 'Editar programa' : 'Nuevo programa' ?>
    </h1>
    <a href="/programas" class="btn btn-outline-secondary">Volver al listado</a>
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
                <label class="form-label" for="id">ID</label>
                <input type="number" class="form-control" id="id" name="id"
                       value="<?= v($programa['id'] ?? '') ?>"
                       <?= $esEditar ? 'readonly' : 'required' ?>>
                <?php if ($esEditar): ?>
                    <div class="form-text">El identificador no se puede cambiar.</div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                       value="<?= v($programa['nombre'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="tipo">Tipo</label>
                <input type="text" class="form-control" id="tipo" name="tipo"
                       value="<?= v($programa['tipo'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="nivel">Nivel</label>
                <input type="text" class="form-control" id="nivel" name="nivel"
                       value="<?= v($programa['nivel'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="fecha_creacion">Fecha de creación</label>
                <input type="text" class="form-control" id="fecha_creacion" name="fecha_creacion"
                       value="<?= v($programa['fecha_creacion'] ?? '') ?>"
                       placeholder="sin dato" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="fecha_cierre">Fecha de cierre</label>
                <input type="text" class="form-control" id="fecha_cierre" name="fecha_cierre"
                       value="<?= v($programa['fecha_cierre'] ?? '') ?>"
                       placeholder="si no aplica, deje vacío">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="numero_cohortes">Número de cohortes</label>
                <input type="text" class="form-control" id="numero_cohortes" name="numero_cohortes"
                       value="<?= v($programa['numero_cohortes'] ?? '') ?>"
                       placeholder="sin dato" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="cant_graduados">Cantidad de graduados</label>
                <input type="text" class="form-control" id="cant_graduados" name="cant_graduados"
                       value="<?= v($programa['cant_graduados'] ?? '') ?>"
                       placeholder="sin dato" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="fecha_actualizacion">Fecha de actualización</label>
                <input type="text" class="form-control" id="fecha_actualizacion" name="fecha_actualizacion"
                       value="<?= v($programa['fecha_actualizacion'] ?? '') ?>"
                       placeholder="sin dato" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="ciudad">Ciudad</label>
                <input type="text" class="form-control" id="ciudad" name="ciudad"
                       value="<?= v($programa['ciudad'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="facultad">Facultad</label>
                <input type="number" class="form-control" id="facultad" name="facultad"
                       value="<?= v($programa['facultad'] ?? '') ?>" required>
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