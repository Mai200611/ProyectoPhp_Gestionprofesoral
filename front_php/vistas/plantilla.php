<?php
declare(strict_types=1);

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es la PLANTILLA común del front: el marco que envuelve TODAS las
 *   pantallas. Cabecera, menú de navegación, contenedor del contenido,
 *   pie de página y avisos.
 *
 * ¿Cómo se usa?
 *   No se abre directamente desde el navegador. La función pintar() del
 *   front controller carga la vista solicitada en un buffer, la deja en
 *   la variable $contenido y luego hace require de este archivo. Aquí se
 *   imprime $contenido dentro del bloque principal.
 *
 * ¿Qué recibe?
 *   - $contenido: el HTML ya renderizado de la vista hija.
 *   - $aviso:     el mensaje pendiente (o null), que se muestra en un
 *                 bloque de alerta si existe.
 *
 * ¿Qué NO hace?
 *   - NO consulta la base.
 *   - NO llama a la API.
 *   - Solo pinta HTML y enlaza los archivos estáticos locales.
 *
 * Nota sobre los estilos:
 *   Se enlazan SIEMPRE desde /publico/ (nunca un CDN). Es la decisión
 *   D12 del research: los archivos viven en el repositorio para que la
 *   pantalla se vea bien aunque no haya internet.
 * ---------------------------------------------------------------------------
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Profesoral — v1</title>
    <link rel="stylesheet" href="/publico/bootstrap.min.css">
    <link rel="stylesheet" href="/publico/estilos.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/">Gestión Profesoral</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/areas">Áreas de conocimiento</a></li>
                <li class="nav-item"><a class="nav-link" href="/terminos">Términos clave</a></li>
                <li class="nav-item"><a class="nav-link" href="/lineas">Líneas de investigación</a></li>
                <li class="nav-item"><a class="nav-link" href="/programas">Programas</a></li>
                <li class="nav-item"><a class="nav-link" href="/redes">Redes</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container">
    <?php if (!empty($aviso)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars((string) $aviso, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?= $contenido /* HTML ya escapado por cada vista */ ?>
</main>

<footer class="text-center text-muted py-4 mt-4">
    <small>Gestión Profesoral — versión 1</small>
</footer>

<script src="/publico/bootstrap.bundle.min.js"></script>
</body>
</html>