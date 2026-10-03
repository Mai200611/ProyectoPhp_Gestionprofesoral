<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la VISTA que se muestra cuando el usuario entra a una dirección que
 *   el front no conoce. Se pinta DENTRO de la plantilla, así que el menú
 *   y el marco de la aplicación siguen visibles: no es una página de
 *   error del servidor, es una pantalla del propio sistema (así lo pide
 *   la última fila de la sección 9 de 6_contracts.md).
 *
 * ¿Qué recibe?
 *   Nada. Solo se pinta un mensaje y un botón para volver al inicio.
 *
 * ¿Qué NO hace?
 *   - NO muestra códigos HTTP ni rutas de la API. Al usuario le habla en
 *     español, sin jerga técnica.
 *   - NO llama a la API.
 * ---------------------------------------------------------------------------
 */
?>
<div class="card bg-white">
    <div class="card-body text-center py-5">
        <h1 class="h3 mb-3">Esa página no existe</h1>
        <p class="text-muted mb-4">
            La dirección que escribió no corresponde a ninguna pantalla del sistema.
        </p>
        <a href="/" class="btn btn-primary">Volver al inicio</a>
    </div>
</div>