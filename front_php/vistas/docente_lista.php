<?php declare(strict_types=1); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Docentes</h1>
    <a href="/docentes/nuevo" class="btn btn-primary">Agregar</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-warning" role="alert">
        <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($docentes)): ?>
    <div class="alert alert-info" role="alert">
        Todavía no hay docentes registrados. <a href="/docentes/nuevo" class="alert-link">Puede agregar el primero</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>Cédula</th>
                    <th>Nombre completo</th>
                    <th>Línea de investigación</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($docentes as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($d['cedula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($d['nombre_completo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($d['linea_investigacion_principal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="/docentes/<?= urlencode((string) ($d['cedula'] ?? '')) ?>">Ver Ficha</a>
                            <a class="btn btn-sm btn-outline-secondary" href="/docentes/<?= urlencode((string) ($d['cedula'] ?? '')) ?>/editar">Editar</a>
                            <form method="post" action="/docentes/<?= urlencode((string) ($d['cedula'] ?? '')) ?>/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este docente?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>