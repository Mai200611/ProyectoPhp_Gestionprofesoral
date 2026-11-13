<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del listado de `programa`.
 *
 * ¿Qué recibe?
 *   - $ok:        bool, true si la consulta salió bien.
 *   - $programas: array de arreglos con los registros (id, nombre, tipo,
 *                 nivel, fecha_creacion, fecha_cierre, numero_cohortes,
 *                 cant_graduados, fecha_actualizacion, ciudad, facultad).
 *   - $errores:   array de mensajes en español si algo falló.
 *
 * ¿Qué muestra?
 *   - Aviso si la API no respondió.
 *   - Aviso de "todavía no hay registros" con enlace a crear (RF37).
 *   - Tabla con los campos principales y acciones.
 *
 * Nota sobre los campos mostrados:
 *   El programa tiene muchos campos; para que la tabla sea legible, se
 *   muestran los más representativos. Los otros se ven en el formulario
 *   al editar.
 * ---------------------------------------------------------------------------
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Programas</h1>
    <a href="/programas/nuevo" class="btn btn-primary">Agregar</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-warning" role="alert">
        <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($programas)): ?>
    <div class="alert alert-info" role="alert">
        Todavía no hay programas registrados.
        <a href="/programas/nuevo" class="alert-link">Puede agregar el primero</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Nivel</th>
                    <th>Ciudad</th>
                    <th>Facultad</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($programas as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($p['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($p['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($p['nivel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($p['ciudad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($p['facultad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary"
                               href="/programas/<?= urlencode((string) ($p['id'] ?? '')) ?>/editar">
                                Editar
                            </a>
                            <form method="post"
                                  action="/programas/<?= urlencode((string) ($p['id'] ?? '')) ?>/eliminar"
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