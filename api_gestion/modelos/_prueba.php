<?php
declare(strict_types=1);

require __DIR__ . '/AreaConocimiento.php';
require __DIR__ . '/TerminoClave.php';
require __DIR__ . '/LineaInvestigacion.php';
require __DIR__ . '/Programa.php';
require __DIR__ . '/Red.php';

$p = new Programa(
    1,
    'Ingeniería de Sistemas',
    'Pregrado',
    'Universitario',
    '2000-01-01',
    null,
    '10',
    '1200',
    '2024-01-01',
    'Medellín',
    101
);

echo "Graduados antes: " . $p->getGraduados() . PHP_EOL;
$p->setCantGraduados('1250');
echo "Graduados después: " . $p->getCantGraduados() . PHP_EOL;
echo json_encode($p->toArray(), JSON_UNESCAPED_UNICODE) . PHP_EOL;

$a = new AreaConocimiento('1A01', 'Ciencias Naturales', 'Matemáticas', 'Matemáticas puras');
echo json_encode($a->toArray(), JSON_UNESCAPED_UNICODE) . PHP_EOL;

$t = new TerminoClave('Machine Learning', 'Machine Learning');
echo json_encode($t->toArray(), JSON_UNESCAPED_UNICODE) . PHP_EOL;

$l = new LineaInvestigacion(1, 'Inteligencia Artificial', 'Línea enfocada en IA y ML');
echo json_encode($l->toArray(), JSON_UNESCAPED_UNICODE) . PHP_EOL;

$r = new Red(1, 'Red de Investigación Avanzada', 'https://red-ia.org', 'Colombia');
echo json_encode($r->toArray(), JSON_UNESCAPED_UNICODE) . PHP_EOL;