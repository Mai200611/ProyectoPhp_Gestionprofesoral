<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Estudios y Áreas de Conocimiento</h1>
    <a class="btn btn-primary" href="/estudios-area/nuevo">Agregar vínculo</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-danger"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars((string) $e) ?></div><?php endforeach; ?></div>
<?php elseif (empty($datos)): ?>
    <div class="alert alert-info">Todavía no hay vínculos. <a href="/estudios-area/nuevo">Cree el primero</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr><th>Estudio</th><th>Área de conocimiento</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <?php $estudio = (string)($fila['estudio'] ?? ''); $area = (string)($fila['area_conocimiento'] ?? ''); ?>
                    <tr>
                        <td><?= htmlspecialchars($estudio) ?></td>
                        <td><?= htmlspecialchars($area) ?></td>
                        <td class="text-end">
                            <form method="post" action="/estudios-area/<?= rawurlencode($estudio) ?>/<?= rawurlencode($area) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este vínculo?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>