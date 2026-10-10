-- ============================================================
-- Base de datos de la v1 — Gestión Profesoral
--
-- ¿Qué es este archivo?
--   Es el script que crea la base de datos con sus 5 tablas y sus
--   datos de ejemplo. MariaDB lo ejecuta automáticamente la PRIMERA
--   vez que arranca con el volumen vacío.
--
-- ¿Cómo se vuelve a ejecutar?
--   Con `docker compose down -v` seguido de `docker compose up -d`.
--   El `-v` borra el volumen: al estar vacío, MariaDB ejecuta este
--   script otra vez desde cero.
--
-- Las 5 tablas de la v1 son:
--   area_conocimiento
--   termino_clave
--   linea_investigacion
--   programa
--   red
--
-- Nota sobre la columna `activo`:
--   Las tablas la traen (borrado lógico, del esquema original del
--   curso), pero la API de la v1 NO la usa: los modelos no la
--   conocen. Vive en la tabla, no en el código.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `area_conocimiento`;
DROP TABLE IF EXISTS `termino_clave`;
DROP TABLE IF EXISTS `linea_investigacion`;
DROP TABLE IF EXISTS `programa`;
DROP TABLE IF EXISTS `red`;

-- =============================================
-- TABLA: area_conocimiento
-- =============================================
CREATE TABLE `area_conocimiento` (
    `id`         VARCHAR(6)   NOT NULL,
    `gran_area`  VARCHAR(60)  NOT NULL,
    `area`       VARCHAR(60)  NOT NULL,
    `disciplina` VARCHAR(150) NOT NULL,
    `activo`     BOOLEAN      NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: termino_clave
-- =============================================
CREATE TABLE `termino_clave` (
    `termino`        VARCHAR(30) NOT NULL,
    `termino_ingles` VARCHAR(30) NOT NULL,
    `activo`         BOOLEAN     NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`termino`)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: linea_investigacion
-- =============================================
CREATE TABLE `linea_investigacion` (
    `id`          INT          NOT NULL AUTO_INCREMENT,
    `nombre`      VARCHAR(45)  NOT NULL,
    `descripcion` VARCHAR(256) NOT NULL,
    `activo`      BOOLEAN      NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: programa
-- =============================================
CREATE TABLE `programa` (
    `id`                  INT          NOT NULL,
    `nombre`              VARCHAR(150) NOT NULL,
    `tipo`                VARCHAR(45)  NOT NULL,
    `nivel`               VARCHAR(45)  NOT NULL,
    `fecha_creacion`      VARCHAR(45)  NOT NULL,
    `fecha_cierre`        VARCHAR(45)  NULL,
    `numero_cohortes`     VARCHAR(45)  NOT NULL,
    `cant_graduados`      VARCHAR(45)  NOT NULL,
    `fecha_actualizacion` VARCHAR(45)  NOT NULL,
    `ciudad`              VARCHAR(45)  NOT NULL,
    `facultad`            INT          NOT NULL,
    `activo`              BOOLEAN      NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- =============================================
-- TABLA: red
-- =============================================
CREATE TABLE `red` (
    `idr`    INT         NOT NULL,
    `nombre` VARCHAR(45) NOT NULL,
    `url`    VARCHAR(45) NOT NULL,
    `pais`   VARCHAR(45) NOT NULL,
    `activo` BOOLEAN     NOT NULL DEFAULT TRUE,
    PRIMARY KEY (`idr`)
) ENGINE=InnoDB;

-- =============================================
-- DATOS DE EJEMPLO
-- =============================================

-- area_conocimiento: 8 filas de ejemplo
INSERT INTO `area_conocimiento` (id, gran_area, area, disciplina) VALUES
    ('1A01', 'Ciencias Naturales', 'Matemáticas', 'Matemáticas puras'),
    ('1A02', 'Ciencias Naturales', 'Matemáticas', 'Matemáticas aplicadas'),
    ('1A03', 'Ciencias Naturales', 'Matemáticas', 'Estadística y probabilidades'),
    ('1B01', 'Ciencias Naturales', 'Computación y ciencias de la información', 'Ciencias de la Computación'),
    ('1C01', 'Ciencias Naturales', 'Ciencias físicas', 'Física Atómica, molecular y química'),
    ('1C02', 'Ciencias Naturales', 'Ciencias físicas', 'Física de la materia'),
    ('1D01', 'Ciencias Naturales', 'Ciencias químicas', 'Química orgánica'),
    ('2A01', 'Ingeniería y Tecnología', 'Ingeniería civil', 'Ingeniería civil');

-- termino_clave: 5 filas de ejemplo
INSERT INTO `termino_clave` (termino, termino_ingles) VALUES
    ('Inteligencia Artificial', 'Artificial Intelligence'),
    ('Machine Learning',        'Machine Learning'),
    ('Base de datos',           'Database'),
    ('Programación',            'Programming'),
    ('Redes',                   'Networks');

-- linea_investigacion: 4 filas de ejemplo
INSERT INTO `linea_investigacion` (nombre, descripcion) VALUES
    ('Inteligencia Artificial',    'Línea enfocada en IA y aprendizaje automático'),
    ('Ingeniería de Software',     'Estudio y aplicación de metodologías de software'),
    ('Bases de Datos',             'Gestión, modelado y consulta de datos'),
    ('Seguridad Informática',      'Protección de sistemas y redes');

-- programa: 4 filas de ejemplo
INSERT INTO `programa`
    (id, nombre, tipo, nivel, fecha_creacion, fecha_cierre,
     numero_cohortes, cant_graduados, fecha_actualizacion, ciudad, facultad)
VALUES
    (10101, 'Administración de Empresas',               'Programa Académico', 'Pregrado', 'sin dato', NULL, 'sin dato', 'sin dato', 'sin dato', 'Bogotá', 101),
    (10102, 'Ingeniería de Sistemas',                   'Programa Académico', 'Pregrado', 'sin dato', NULL, 'sin dato', 'sin dato', 'sin dato', 'Bogotá', 101),
    (10201, 'Maestría en Ingeniería de Sistemas',       'Programa Académico', 'Maestría', 'sin dato', NULL, 'sin dato', 'sin dato', 'sin dato', 'Medellín', 102),
    (0204,  'Maestría en Derecho y Administración',     'Programa Académico', 'Maestría', 'sin dato', NULL, 'sin dato', 'sin dato', 'sin dato', 'Bogotá', 102);

-- red: 3 filas de ejemplo
INSERT INTO `red` (idr, nombre, url, pais) VALUES
    (1, 'Red de Investigación Avanzada',   'https://red-ia.org',       'Colombia'),
    (2, 'Red Colombiana de Ingeniería',    'https://red-ingenieria.co','Colombia'),
    (3, 'Red Latinoamericana de Software', 'https://rela-software.org', 'México');

SET FOREIGN_KEY_CHECKS = 1;