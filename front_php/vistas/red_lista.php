<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del listado de `red`.
 *   Muestra la tabla con las redes existentes y los botones para editar
 *   o eliminar.
 * ---------------------------------------------------------------------------
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Redes académicas</h1>
    <a href="/redes/nuevo" class="btn btn-primary">Agregar</a>
</div>

<?php if (!$ok): ?>
    <!-- Si hubo un error al consultar la API -->
    <div class="alert alert-warning" role="alert">
        <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($redes)): ?>
    <!-- Si no hay registros -->
    <div class="alert alert-info" role="alert">
        Todavía no hay redes registradas.
        <a href="/redes/nuevo" class="alert-link">Puede agregar la primera</a>.
    </div>
<?php else: ?>
    <!-- Si hay registros, mostramos la tabla -->
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>IDR</th>
                    <th>Nombre</th>
                    <th>URL</th>
                    <th>País</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redes as $r): ?>
                    <tr>
                        <!-- Se usa htmlspecialchars para evitar problemas de seguridad (XSS) -->
                        <td><?= htmlspecialchars((string) ($r['idr'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($r['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($r['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($r['pais'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <!-- Botón Editar -->
                            <a class="btn btn-sm btn-outline-secondary"
                               href="/redes/<?= urlencode((string) ($r['idr'] ?? '')) ?>/editar">
                                Editar
                            </a>
                            
                            <!-- Botón Eliminar (dentro de un formulario POST por seguridad) -->
                            <form method="post"
                                  action="/redes/<?= urlencode((string) ($r['idr'] ?? '')) ?>/eliminar"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar este registro?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>