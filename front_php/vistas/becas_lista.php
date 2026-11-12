<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Becas</h1>
    <a class="btn btn-primary" href="/becas/nuevo">Agregar beca</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars((string) $e) ?></div><?php endforeach; ?></div>
<?php elseif (empty($datos)): ?>
    <div class="alert alert-info">Todavía no hay becas. <a href="/becas/nuevo">Cree la primera</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr><th>Estudio</th><th>Tipo</th><th>Institución</th><th>Inicio</th><th>Fin</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <?php $estudios = (string)($fila['estudios'] ?? ''); ?>
                    <tr>
                        <td><?= htmlspecialchars($estudios) ?></td>
                        <td><?= htmlspecialchars((string)($fila['tipo'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['institucion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['fecha_inicio'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['fecha_fin'] ?? '')) ?></td>
                        <td class="text-end">
                            <form method="post" action="/becas/<?= rawurlencode($estudios) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar esta beca?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>