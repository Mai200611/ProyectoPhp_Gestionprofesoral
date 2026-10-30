<?php
declare(strict_types=1);

require_once __DIR__ . '/ServicioDocente.php';
require_once __DIR__ . '/ServicioEstudiosRealizados.php';
require_once __DIR__ . '/ServicioDocenteDepartamento.php';
require_once __DIR__ . '/ServicioInteresesFuturos.php';
require_once __DIR__ . '/ServicioEvaluacionDocente.php';
require_once __DIR__ . '/ServicioReconocimiento.php';
require_once __DIR__ . '/ServicioExperiencia.php';
require_once __DIR__ . '/ServicioRedDocente.php';
require_once __DIR__ . '/ServicioEstudioAc.php';
require_once __DIR__ . '/ServicioApoyoProfesoral.php';
require_once __DIR__ . '/ServicioBeca.php';

require_once __DIR__ . '/../repositorios/RepositorioDocenteMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioEstudiosRealizadosMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioDocenteDepartamentoMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioInteresesFuturosMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioEvaluacionDocenteMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioReconocimientoMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioExperienciaMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioRedDocenteMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioEstudioAcMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioApoyoProfesoralMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioBecaMariaDB.php';

/**
 * ensamblador.php
 *
 * El ÚNICO lugar del proyecto donde se juntan las clases concretas
 * (servicios + repositorios MariaDB) con las interfaces. Cada controlador
 * pide aquí su servicio y no sabe qué implementación recibió.
 *
 * Se usan FUNCIONES EXPLÍCITAS, una por recurso, y no un switch ni nombres
 * de clase armados con texto como "Repositorio{$recurso}MariaDB". Las razones
 * están en 3_plan §4.7:
 *   - con funciones explícitas, PHP verifica tipos y el error sale al
 *     llamarlas;
 *   - con nombres armados en texto, el error sale en producción, cuando
 *     alguien pida el recurso que nadie probó;
 *   - y quien lea este archivo ve el inventario completo sin ejecutar nada.
 *
 * Cuando la v3 traiga el segundo motor, ESTE archivo —y solo éste— se
 * convierte en una fábrica de verdad.
 *
 * Cada función recibe la PDO ya construida (obtenerConexion()), para que
 * el controlador no tenga que saber cómo se arma la conexión.
 */

function crearServicioDocente(PDO $conexion): IServicioDocente
{
    return new ServicioDocente(new RepositorioDocenteMariaDB($conexion));
}

function crearServicioEstudiosRealizados(PDO $conexion): IServicioEstudiosRealizados
{
    return new ServicioEstudiosRealizados(new RepositorioEstudiosRealizadosMariaDB($conexion));
}

function crearServicioDocenteDepartamento(PDO $conexion): IServicioDocenteDepartamento
{
    return new ServicioDocenteDepartamento(new RepositorioDocenteDepartamentoMariaDB($conexion));
}

function crearServicioInteresesFuturos(PDO $conexion): IServicioInteresesFuturos
{
    return new ServicioInteresesFuturos(new RepositorioInteresesFuturosMariaDB($conexion));
}

function crearServicioEvaluacionDocente(PDO $conexion): IServicioEvaluacionDocente
{
    return new ServicioEvaluacionDocente(new RepositorioEvaluacionDocenteMariaDB($conexion));
}

function crearServicioReconocimiento(PDO $conexion): IServicioReconocimiento
{
    return new ServicioReconocimiento(new RepositorioReconocimientoMariaDB($conexion));
}

function crearServicioExperiencia(PDO $conexion): IServicioExperiencia
{
    return new ServicioExperiencia(new RepositorioExperienciaMariaDB($conexion));
}

function crearServicioRedDocente(PDO $conexion): IServicioRedDocente
{
    return new ServicioRedDocente(new RepositorioRedDocenteMariaDB($conexion));
}

function crearServicioEstudioAc(PDO $conexion): IServicioEstudioAc
{
    return new ServicioEstudioAc(new RepositorioEstudioAcMariaDB($conexion));
}

function crearServicioApoyoProfesoral(PDO $conexion): IServicioApoyoProfesoral
{
    return new ServicioApoyoProfesoral(new RepositorioApoyoProfesoralMariaDB($conexion));
}

function crearServicioBeca(PDO $conexion): IServicioBeca
{
    return new ServicioBeca(new RepositorioBecaMariaDB($conexion));
}