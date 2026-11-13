<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA del listado de `termino_clave`.
 *
 * ¿Qué recibe?
 *   - $ok:       bool, true si la consulta salió bien.
 *   - $terminos: array de arreglos con los registros (termino,
 *                termino_ingles).
 *   - $errores:  array de mensajes en español si algo falló.
 *
 * ¿Qué muestra?
 *   - Aviso si la API no respondió.
 *   - Aviso de "todavía no hay registros" con enlace a crear (RF37).
 *   - Tabla con `termino` y `termino_ingles` y acciones.
 *
 * Nota sobre el identificador:
 *   Aquí la clave primaria es `termino` (texto), no un `id`. Las URLs
 *   usan ese valor: /terminos/{termino}/editar. Como puede llevar
 *   espacios o caracteres especiales, se codifica con urlencode().
 * ---------------------------------------------------------------------------
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 m-0">Términos clave</h1>
    <a href="/terminos/nuevo" class="btn btn-primary">Agregar</a>
</div>

<?php if (!$ok): ?>
    <div class="alert alert-warning" role="alert">
        <?php foreach ($errores as $e): ?>
            <div><?= htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($terminos)): ?>
    <div class="alert alert-info" role="alert">
        Todavía no hay términos clave registrados.
        <a href="/terminos/nuevo" class="alert-link">Puede agregar el primero</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>Término</th>
                    <th>Término en inglés</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($terminos as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($t['termino'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($t['termino_ingles'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary"
                               href="/terminos/<?= urlencode((string) ($t['termino'] ?? '')) ?>/editar">
                                Editar
                            </a>
                            <form method="post"
                                  action="/terminos/<?= urlencode((string) ($t['termino'] ?? '')) ?>/eliminar"
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