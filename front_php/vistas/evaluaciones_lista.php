<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Evaluaciones Docentes</h1>
    <a class="btn btn-primary" href="/evaluaciones/nuevo">Agregar evaluación</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars((string) $e) ?></div><?php endforeach; ?></div>
<?php elseif (empty($datos)): ?>
    <div class="alert alert-info">Todavía no hay evaluaciones. <a href="/evaluaciones/nuevo">Cree la primera</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr><th>ID</th><th>Semestre</th><th>Calificación</th><th>Docente</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <?php $id = (string)($fila['id'] ?? ''); ?>
                    <tr>
                        <td><?= htmlspecialchars($id) ?></td>
                        <td><?= htmlspecialchars((string)($fila['semestre'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['calificacion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['docente'] ?? '')) ?></td>
                        <td class="text-end">
                            <form method="post" action="/evaluaciones/<?= rawurlencode($id) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar esta evaluación?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>