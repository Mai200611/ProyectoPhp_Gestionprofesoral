<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Apoyos Profesorales</h1>
    <a class="btn btn-primary" href="/apoyos/nuevo">Agregar apoyo</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars((string) $e) ?></div><?php endforeach; ?></div>
<?php elseif (empty($datos)): ?>
    <div class="alert alert-info">Todavía no hay apoyos. <a href="/apoyos/nuevo">Cree el primero</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr><th>Estudio</th><th>Con apoyo</th><th>Institución</th><th>Tipo</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <?php $estudios = (string)($fila['estudios'] ?? ''); ?>
                    <tr>
                        <td><?= htmlspecialchars($estudios) ?></td>
                        <td><?= htmlspecialchars((string)($fila['con_apoyo'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['institucion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($fila['tipo'] ?? '')) ?></td>
                        <td class="text-end">
                            <form method="post" action="/apoyos/<?= rawurlencode($estudios) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este apoyo?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>