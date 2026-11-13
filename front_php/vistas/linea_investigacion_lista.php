<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del listado de `linea_investigacion`.
 *
 * ¿Qué recibe?
 *   - $ok:      bool, true si la consulta salió bien.
 *   - $lineas:  array de arreglos con los registros (id, nombre,
 *               descripcion).
 *   - $errores: array de mensajes en español si algo falló.
 *
 * ¿Qué muestra?
 *   - Aviso si la API no respondió.
 *   - Aviso de "todavía no hay registros" con enlace a crear (RF37).
 *   - Tabla con id, nombre, descripcion y acciones.
 *
 * Nota sobre el identificador:
 *   En esta tabla el id es entero, así que las URLs usan ese número:
 *   /lineas/{id}/editar.
 * ---------------------------------------------------------------------------
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Líneas de investigación</h1>
    <a href="/lineas/nuevo" class="btn btn-primary">Agregar</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-warning" role="alert">
        <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($lineas)): ?>
    <div class="alert alert-info" role="alert">
        Todavía no hay líneas de investigación registradas.
        <a href="/lineas/nuevo" class="alert-link">Puede agregar la primera</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lineas as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($l['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($l['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($l['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary"
                               href="/lineas/<?= urlencode((string) ($l['id'] ?? '')) ?>/editar">
                                Editar
                            </a>
                            <form method="post"
                                  action="/lineas/<?= urlencode((string) ($l['id'] ?? '')) ?>/eliminar"
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