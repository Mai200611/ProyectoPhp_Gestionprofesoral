<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Vinculaciones Docente — Departamento</h1>
    <a class="btn btn-primary" href="/departamentos/nuevo">Agregar vinculación</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars((string) $e) ?></div><?php endforeach; ?></div>
<?php elseif (empty($datos)): ?>
    <div class="alert alert-info">Todavía no hay vinculaciones. <a href="/departamentos/nuevo">Cree la primera</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr><th>Docente</th><th>Departamento</th><th>Dedicación</th><th>Modalidad</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <?php $docente = (string)($fila['docente'] ?? ''); $departamento = (string)($fila['departamento'] ?? ''); ?>
                    <tr>
                        <td><?= htmlspecialchars($docente) ?></td>
                        <td><?= htmlspecialchars($departamento) ?></td>
                        <td><?= htmlspecialchars((string)($fila['dedicacion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['modalidad'] ?? '')) ?></td>
                        <td class="text-end">
                            <form method="post" action="/departamentos/<?= rawurlencode($docente) ?>/<?= rawurlencode($departamento) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar esta vinculación?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>