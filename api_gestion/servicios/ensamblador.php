<?php
declare(strict_types=1);

// Incluir todos los repositorios MariaDB
require_once __DIR__ . '/../repositorios/RepositorioAreaConocimientoMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioTerminoClaveMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioLineaInvestigacionMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioProgramaMariaDB.php';
require_once __DIR__ . '/../repositorios/RepositorioRedMariaDB.php';
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

// Incluir todos los servicios
require_once __DIR__ . '/ServicioAreaConocimiento.php';
require_once __DIR__ . '/ServicioTerminoClave.php';
require_once __DIR__ . '/ServicioLineaInvestigacion.php';
require_once __DIR__ . '/ServicioPrograma.php';
require_once __DIR__ . '/ServicioRed.php';
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

// V1
function crearServicioAreaConocimiento(): ServicioAreaConocimiento {
    return new ServicioAreaConocimiento(new RepositorioAreaConocimientoMariaDB());
}
function crearServicioTerminoClave(): ServicioTerminoClave {
    return new ServicioTerminoClave(new RepositorioTerminoClaveMariaDB());
}
function crearServicioLineaInvestigacion(): ServicioLineaInvestigacion {
    return new ServicioLineaInvestigacion(new RepositorioLineaInvestigacionMariaDB());
}
function crearServicioPrograma(): ServicioPrograma {
    return new ServicioPrograma(new RepositorioProgramaMariaDB());
}
function crearServicioRed(): ServicioRed {
    return new ServicioRed(new RepositorioRedMariaDB());
}

// V2
function crearServicioDocente(): ServicioDocente {
    return new ServicioDocente(new RepositorioDocenteMariaDB());
}
function crearServicioEstudiosRealizados(): ServicioEstudiosRealizados {
    return new ServicioEstudiosRealizados(new RepositorioEstudiosRealizadosMariaDB());
}
function crearServicioDocenteDepartamento(): ServicioDocenteDepartamento {
    return new ServicioDocenteDepartamento(new RepositorioDocenteDepartamentoMariaDB());
}
function crearServicioInteresesFuturos(): ServicioInteresesFuturos {
    return new ServicioInteresesFuturos(new RepositorioInteresesFuturosMariaDB());
}
function crearServicioEvaluacionDocente(): ServicioEvaluacionDocente {
    return new ServicioEvaluacionDocente(new RepositorioEvaluacionDocenteMariaDB());
}
function crearServicioReconocimiento(): ServicioReconocimiento {
    return new ServicioReconocimiento(new RepositorioReconocimientoMariaDB());
}
function crearServicioExperiencia(): ServicioExperiencia {
    return new ServicioExperiencia(new RepositorioExperienciaMariaDB());
}
function crearServicioRedDocente(): ServicioRedDocente {
    return new ServicioRedDocente(new RepositorioRedDocenteMariaDB());
}
function crearServicioEstudioAc(): ServicioEstudioAc {
    return new ServicioEstudioAc(new RepositorioEstudioAcMariaDB());
}
function crearServicioApoyoProfesoral(): ServicioApoyoProfesoral {
    return new ServicioApoyoProfesoral(new RepositorioApoyoProfesoralMariaDB());
}
function crearServicioBeca(): ServicioBeca {
    return new ServicioBeca(new RepositorioBecaMariaDB());
}