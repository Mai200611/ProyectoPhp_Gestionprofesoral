<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Intereses Futuros</h1>
    <a class="btn btn-primary" href="/intereses/nuevo">Agregar interés</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars((string) $e) ?></div><?php endforeach; ?></div>
<?php elseif (empty($datos)): ?>
    <div class="alert alert-info">Todavía no hay intereses. <a href="/intereses/nuevo">Cree el primero</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr><th>Docente</th><th>Término</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <?php $docente = (string)($fila['docente'] ?? ''); $termino = (string)($fila['termino_clave'] ?? ''); ?>
                    <tr>
                        <td><?= htmlspecialchars($docente) ?></td>
                        <td><?= htmlspecialchars($termino) ?></td>
                        <td class="text-end">
                            <form method="post" action="/intereses/<?= rawurlencode($docente) ?>/<?= rawurlencode($termino) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este interés?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>