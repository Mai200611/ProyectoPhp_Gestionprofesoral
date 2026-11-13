<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Estudios Realizados</h1>
    <a href="/estudios/nuevo" class="btn btn-primary">Agregar</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-warning" role="alert">
        <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($estudios)): ?>
    <div class="alert alert-info" role="alert">
        Todavía no hay estudios registrados. 
        <a href="/estudios/nuevo" class="alert-link">Puede agregar el primero</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Universidad</th>
                    <th>Tipo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudios as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($e['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($e['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($e['universidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($e['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="/estudios/<?= urlencode((string) ($e['id'] ?? '')) ?>/editar">Editar</a>
                            <form method="post" action="/estudios/<?= urlencode((string) ($e['id'] ?? '')) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este estudio?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>