<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// REQUIRES
// El ensamblador necesita conocer TODAS las clases concretas (los servicios
// y los repositorios MariaDB) porque es el único punto del sistema que las
// instancia. Por eso los carga todos aquí.
// ---------------------------------------------------------------------------

// Repositorios concretos
require_once __DIR__ . '/../repositorios/RepositorioAreaConocimientoMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioTerminoClaveMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioLineaInvestigacionMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioProgramaMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioRedMariaDB.php';

// Servicios concretos
require_once __DIR__ . '/ServicioAreaConocimiento.php';
require_once __DIR__ . '/ServicioTerminoClave.php';
require_once __DIR__ . '/ServicioLineaInvestigacion.php';
require_once __DIR__ . '/ServicioPrograma.php';
require_once __DIR__ . '/ServicioRed.php';

/**
 * ---------------------------------------------------------------------------
 * ¿Qué es este archivo?
 *   Es el ENSAMBLADOR: el único lugar del proyecto donde se juntan las
 *   interfaces con sus implementaciones concretas. Es la pieza que hace
 *   realidad la inversión de dependencias del Artículo 5 de la
 *   constitución.
 *
 * ¿Qué papel cumple en la arquitectura?
 *   Los controladores (Fase 5) no instancian repositorios MariaDB ni
 *   servicios concretos: le piden al ensamblador el servicio que
 *   necesitan. El ensamblador es quien decide qué repositorio concreto
 *   usar (hoy MariaDB) y se lo inyecta al servicio.
 *
 *   En v1 solo existe un motor, así que el ensamblador es sencillo: una
 *   función por servicio que crea el repositorio MariaDB correspondiente
 *   y lo enchufa en el servicio. Cuando llegue v3 con PostgreSQL, este
 *   archivo será el único que cambie — esa es exactamente la promesa del
 *   principio abierto/cerrado.
 *
 * ¿Por qué una función por servicio y no una función genérica?
 *   Porque la API es específica (Artículo 10): cada recurso tiene su
 *   propio ensamblaje, con su propio repositorio concreto. Una función
 *   genérica tendría que recibir el nombre del recurso como parámetro y
 *   hacer magia adentro — justo lo que el proyecto evita.
 * ---------------------------------------------------------------------------
 */

/**
 * Devuelve un ServicioAreaConocimiento ya ensamblado con su repositorio
 * MariaDB. Cada llamada crea un repositorio nuevo, pero la conexión PDO
 * de adentro se reutiliza (Conexion::obtener() es perezosa y estática
 * dentro del proceso).
 */
function crearServicioAreaConocimiento(): ServicioAreaConocimiento
{
    $repositorio = new RepositorioAreaConocimientoMariaDB();
    return new ServicioAreaConocimiento($repositorio);
}

/**
 * Devuelve un ServicioTerminoClave ensamblado con su repositorio MariaDB.
 */
function crearServicioTerminoClave(): ServicioTerminoClave
{
    $repositorio = new RepositorioTerminoClaveMariaDB();
    return new ServicioTerminoClave($repositorio);
}

/**
 * Devuelve un ServicioLineaInvestigacion ensamblado con su repositorio
 * MariaDB.
 */
function crearServicioLineaInvestigacion(): ServicioLineaInvestigacion
{
    $repositorio = new RepositorioLineaInvestigacionMariaDB();
    return new ServicioLineaInvestigacion($repositorio);
}

/**
 * Devuelve un ServicioPrograma ensamblado con su repositorio MariaDB.
 */
function crearServicioPrograma(): ServicioPrograma
{
    $repositorio = new RepositorioProgramaMariaDB();
    return new ServicioPrograma($repositorio);
}

/**
 * Devuelve un ServicioRed ensamblado con su repositorio MariaDB.
 */
function crearServicioRed(): ServicioRed
{
    $repositorio = new RepositorioRedMariaDB();
    return new ServicioRed($repositorio);
}