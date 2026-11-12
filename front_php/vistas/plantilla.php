<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Profesoral</title>
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
                <li class="nav-item"><a class="nav-link" href="/areas">Áreas</a></li>
                <li class="nav-item"><a class="nav-link" href="/terminos">Términos</a></li>
                <li class="nav-item"><a class="nav-link" href="/lineas">Líneas</a></li>
                <li class="nav-item"><a class="nav-link" href="/programas">Programas</a></li>
                <li class="nav-item"><a class="nav-link" href="/redes">Redes</a></li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Módulo Profesoral (v2)
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/docentes">Docentes</a></li>
                        <li><a class="dropdown-item" href="/estudios">Estudios Realizados</a></li>
                        <li><a class="dropdown-item" href="/departamentos">Departamentos</a></li>
                        <li><a class="dropdown-item" href="/intereses">Intereses</a></li>
                        <li><a class="dropdown-item" href="/evaluaciones">Evaluaciones</a></li>
                        <li><a class="dropdown-item" href="/reconocimientos">Reconocimientos</a></li>
                        <li><a class="dropdown-item" href="/experiencia">Experiencia</a></li>
                        <li><a class="dropdown-item" href="/redes-docente">Redes Docente</a></li>
                        <li><a class="dropdown-item" href="/estudios-area">Estudios Área</a></li>
                        <li><a class="dropdown-item" href="/apoyos">Apoyos</a></li>
                        <li><a class="dropdown-item" href="/becas">Becas</a></li>
                    </ul>
                </li>
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
    <?= $contenido ?>
</main>
<footer class="text-center text-muted py-4 mt-4">
    <small>Gestión Profesoral — v2</small>
</footer>
<script src="/publico/bootstrap.bundle.min.js"></script>
</body>
</html>