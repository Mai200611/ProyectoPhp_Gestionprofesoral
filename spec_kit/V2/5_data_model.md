# Modelo de datos — Versión 2: once tablas, sus relaciones y la lógica que vive adentro

> **Versión 2** · La base sigue siendo la misma que en la v1: se crea COMPLETA
> desde el inicio con `db/init.sql`. Lo que cambia es **cuánto de ella usa el
> código**: de cinco tabla a dieciséis, y por primera vez usando sus llaves foráneas y sus procedimientos.
>
> Este documento es el que hay que tener al lado mientras se construye:
> explica no solo qué columnas hay, sino **quién defiende qué**.

---

## 1. El script de la BD (artefacto de esta versión)

`db/init.sql` viene **provisto**: 16 tablas, restricciones, datos de ejemplo,
 y procedimientos en dialecto MariaDB. **Se copia tal cual.**

## 2. El mapa de las seis tablas que usa la v2

```
       [CATÁLOGOS / V1 SIN FK]                 [ENTIDAD CENTRAL]                 [DEPENDENCIAS DE ESTUDIOS]

          area_conocimiento                          docente                           estudios_realizados
         (gran_area, area...)                          (PK: cedula)                        (PK: id)
                 ▲                                        ▲                                    ▲
                 │                                        │                                    │
                 │                                        ├───────────────────────┐            ├── apoyo_profesoral
                 │                                        │                       │            │   (estudios FK/PK)
                 │                                        │                       │            │
          estudio_ac ─────────────────────────────────────┘                       │            ├── beca
        (estudio FK, area FK)                                                     │            │   (estudios FK/PK)
                                                                                  │            │
          termino_clave                                                           │            └── estudio_ac
         (PK: termino)                                                            │               (estudio FK, area FK)
                 ▲                                                                │
                 │                                                                ▼
          intereses_futuros                                              docente_departamento
     (docente FK, termino FK)                                            (docente FK, programa FK)
                                                                                  ▲
          linea_investigacion                                                     │
             (PK: id)                                                             │
                 ▲                                                                ▼
                 │                                                              programa
                 └────────────────────────────── docente                        (PK: id)
                                       (linea_investigacion_principal FK)


       [OTRAS DEPENDENCIAS DIRECTAS DEL DOCENTE]                 [REDES]

          evaluacion_docente                                      red
             (docente FK)                                        (PK: idr)
                                                                  ▲
          reconocimiento                                          │
             (docente FK)                                    red_docente
                                                          (red FK, docente FK)
          experiecia (sic)
             (docente FK)
```

Léalo de arriba abajo: **arriba están las tablas de las que otras dependen**,
abajo las que dependen de alguien. Esa forma decide dos cosas prácticas:

- **Para crear**, se va de arriba abajo. Al revés, la base rechaza.
- **Para borrar**, al revés. Al revés, la base rechaza.

Los dos rechazos son los que la API traduce a **409**.

## 3. Las tablas, una por una

### 3.1 `area_conocimiento` una tabla depende de area_conocimiento

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | VARCHAR(6) | **PK** | Identificador legible ('1A01'…) |
| `gran_area` | VARCHAR(60) | NOT NULL | Categoría gran área de conocimiento, hace parte del catálogo que es area_conocimiento |
| `area` | VARCHAR(60) | NOT NULL | Subcategoría de gran área de conocimiento |
| `disciplina` | VARCHAR(150) | NOT NULL | Subcategoría de área |

Datos de ejemplo: **8 area_conocimiento** (1A01, Ciencias Naturales, Matemáticas, Matemáticas puras … 1C03, Ciencias Naturales, Ciencias físicas, Física de partículas y campos).

### 3.2 `termino_clave` una tabla depende de termino_clave

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `termino` | VARCHAR(30) | **PK** | Palabra clave de busqueda |
| `termino_ingles` | VARCHAR(30) | NOT NULL | Palabra clave de busqueda en inglés |

### 3.3 `linea_investigacion` — una tabla depende de linea_investigacion_principal

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | INTEGER | **PK** | Identificador autoincremental |
| `nombre` | VARCHAR(45) | NOT NULL | Nombre de la linea de investigación |
| `descripción` | VARCHAR(256) | NOT NULL | Descripción de la linea de investigación |

### 3.4 `programa` — una tabla depende de programa

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | INTEGER | **PK** | Identificador del programa académico |
| `nombre` | VARCHAR(150) | NOT NULL | Nombre del programa académico |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de programa académico |
| `nivel` | VARCHAR(45) | NOT NULL | Nivel del programa académico |
| `fecha_creación` | VARCHAR(45) | NOT NULL | Fecha de creación del programa académico |
| `fecha_cierre` | VARCHAR(45) | NOT NULL | Fecha de cierre del programa académico |
| `numero_cohortes` | VARCHAR(45) | NOT NULL | Número de cohortes del programa académico |
| `cant_graduados` | VARCHAR(45) | NOT NULL | Cantidad de graduados del programa académico |
| `fecha_actualización` | VARCHAR(45) | NOT NULL | Fecha de actualización del programa académico |
| `ciudad` | VARCHAR(45) | NOT NULL | Ciudad del programa académico |
| `facultad` | INTEGER | NOT NULL | Facultad del programa académico |

Datos de ejemplo: **8 programas** (10101, Administración de Empresas, Programa Académico, Pregrado, sin dato, NULL, sin dato, sin dato, sin dato, Bogotá, 101 … 0204, Maestría en Derecho y Administración de Justicia, Programa Académico, Maestría, sin dato, NULL, sin dato, sin dato, sin dato, Bogotá, 102).

### 3.5 `red` — una tabla depende de red

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `idr` | INTEGER | **PK** | Identificador de las redes académicas |
| `nombre` | VARCHAR(45) | NOT NULL | Nombre de las redes académicas |
| `url` | VARCHAR(45) | NOT NULL | URL de las redes académicas |
| `pais` | VARCHAR(45) | NOT NULL | Pais de las redes académicas |

### 3.6 `docente` — depende de linea_investigacion_principal y 7 tablas dependen de docente

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `cedula` | INTEGER | **PK** | Cédula del docente |
| `nombres` | VARCHAR(60) | NOT NULL | Nombre del docente |
| `apellidos` | VARCHAR(60) | NOT NULL | Apellidos del docente |
| `genero` | VARCHAR(12) | NOT NULL | Género del docente |
| `cargo` | VARCHAR(30) | NOT NULL | Cargo del docente |
| `fecha_nacimiento` | DATE | NOT NULL | Fecha de nacimiento del docente |
| `correo` | VARCHAR(70) | NOT NULL | Correo del docente |
| `telefono` | VARCHAR(20) | NOT NULL | Télefono del docente |
| `url_cvlac` | VARCHAR(128) | NOT NULL | URL del cvlac del docente |
| `fecha_actualizacion` | DATE | NOT NULL | Fecha de actualización del docente |
| `escalafon` | VARCHAR(45) | NOT NULL | Escalafón del docente |
| `perfil` | LONGTEXT | NOT NULL | Perfil del docente |
| `cat_minciencia` | VARCHAR(45) | NOT NULL | Categoría Minciencias del docente |
| `nacionalidad` | VARCHAR(45) | NOT NULL | Nacionalidad del docente |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad del docente |
| `linea_investigacion_principal` | INTEGER | **FK → linea_investigacion** y NOT NULL | Llave foranea de linea investigación para docente |

### 3.7 `estudios_realizados` — depende de docente y 3 tablas dependen de estudios_realizados

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | INTEGER | **PK** | Id de los estudios realizados del docente |
| `titulo` | VARCHAR(45) | NOT NULL | Titulo de los estudios realizados del docente |
| `universidad` | VARCHAR(50) | NOT NULL | Universidad de los estudios realizados del docente |
| `fecha` | DATE | NOT NULL | Fecha de los estudios realizados del docente |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de estudios realizados del docente (Pregrado/Maestría/Doctorado…) |
| `ciudad` | VARCHAR(45) | NOT NULL | Ciudad de los estudios realizados del docente |
| `ins_acreditada` | TINYINT | NOT NULL | Institución acreditada de los estudios realizados del docente |
| `metodologia` | VARCHAR(45) | NOT NULL | Metodología de los estudios realizados del docente |
| `perfil_egresado` | LONGTEXT | NOT NULL | Perfil de egresado de los estudios realizados del docente |
| `pais` | VARCHAR(45) | NOT NULL | País de los estudios realizados del docente |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de los estudios realizados del docente |
| `docente` | VARCHAR(70) | **FK → docente** y NOT NULL | Llave foranea de docente para estudios_realizados |

### 3.8 `docente_departamento` — depende de docente y departamento, tiene una PK compuesta

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `docente` | INTEGER | **PK** y **FK → docente** | Id del docente |
| `departamento` | INTEGER | **PK** y **FK → programa** | Id del departamento |
| `dedicacion` | VARCHAR(15) | NOT NULL | Dedicación del docente en el departamento |
| `modalidad` | VARCHAR(45) | NOT NULL | Modalidad del docente en el departamento |
| `fecha_ingreso` | DATE | NOT NULL | Fecha de ingreso del docente en el departamento |
| `fecha_salida` | DATE |  | Fecha de salida del docente en el departamento  |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad del docente en el departamento |

Fíjese en la llave primaria: es **compuesta**. O sea que NO puede aparecer
dos veces en el mismo formulario. Esa regla no está en ninguna línea de PHP.


### 3.9 `intereses_futuros` — depende de docente y termino_clave, tiene una PK compuesta

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `docente` | INTEGER | **PK** y **FK → docente** | Id del docente |
| `termino_clave` | VARCHAR(30) | **PK**, **FK → termino_clave** y NOT NULL | Id de término clave |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad del docente en el departamento |

Fíjese en la llave primaria: es **compuesta**. O sea que NO puede aparecer
dos veces en el mismo formulario. Esa regla no está en ninguna línea de PHP.

### 3.10 `evaluacion_docente` — depende de docente

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | INTEGER | **PK, AUTO_INCREMENT** | Id de la calificación por semestre del docente |
| `calificacion` | FLOAT | NOT NULL | Calificación por semestre del docente |
| `semestre` | VARCHAR(45) | NOT NULL | Semestre de la calificación del docente |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de la calificación por semestre del docente |
| `docente` | INTEGER | **FK → docente** y NOT NULL | Id del docente |

### 3.11 `reconocimiento` — depende de docente

| Columna | Tipo | Restricción | Quién lo escribe |
|---|---|---|---|
| `id` | INTEGER | **PK, AUTO_INCREMENT** | Id del reconocimiento |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de reconocimiento |
| `fecha` | DATE | NOT NULL | Fecha del reconocimiento |
| `institucion` | VARCHAR(45) | NOT NULL | Institución del reconocimiento |
| `nombre` | VARCHAR(45) | NOT NULL | Nombre del reconocimiento |
| `ambito` | VARCHAR(45) | NOT NULL | Ambito del reconocimiento |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado del reconocimiento |
| `docente` | INTEGER | **FK → docente** y NOT NULL | Id del docente |

### 3.12 `experiencia` — depende de docente

| Columna | Tipo | Restricción | Quién lo escribe |
|---|---|---|---|
| `id` | INTEGER | **PK, AUTO_INCREMENT** | Id de la experiencia |
| `nombre_cargo` | VARCHAR(45) | NOT NULL | Nombre del cargo de la experiencia |
| `institucion` | VARCHAR(45) | NOT NULL | Institución de la experiencia |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de experiencia |
| `fecha_inicio` | DATE | NOT NULL | Fecha de inico de la experiencia |
| `fecha_fin` | DATE) | | Fecha de fin de la experiencia |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de la experiencia |
| `docente` | INTEGER | **FK → docente** y NOT NULL | Id del docente |

### 3.13 `red_docente` — depende de docente y red, tiene una PK compuesta

| Columna | Tipo | Restricción | Quién lo escribe |
|---|---|---|---|
| `red` | INTEGER | **PK** y **FK → red** | Id de la red |
| `docente` | INTEGER | **PK** y **FK → docente** | Id del docente |
| `fecha_inicio` | DATE | NOT NULL | Fecha de inico del docente en la red |
| `fecha_fin` | DATE | | Fecha de fin del docente en la red |
| `act_destacadas` | LONGTEXT | NOT NULL | Actividades destacadas del docente en la red |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de red_docente |

Fíjese en la llave primaria: es **compuesta**. O sea que NO puede aparecer
dos veces en el mismo formulario. Esa regla no está en ninguna línea de PHP.

### 3.14 `estudios_ac` — depende de estudio y area_conocimiento, tiene una PK compuesta

| Columna | Tipo | Restricción | Quién lo escribe |
|---|---|---|---|
| `estudio` | INTEGER | **PK** y **FK → estudios_realizados** | Id de estudios_realizados |
| `area_conocimiento` | VARCHAR(6) | **PK** y **FK → area_conocimiento** | Id del area_conocimiento |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de estudios_ac |

Fíjese en la llave primaria: es **compuesta**. O sea que NO puede aparecer
dos veces en el mismo formulario. Esa regla no está en ninguna línea de PHP.

### 3.15 `apoyo_profesoral` — depende de estudio

| Columna | Tipo | Restricción | Quién lo escribe |
|---|---|---|---|
| `estudios` | INTEGER | **PK** y **FK → estudios_realizados** | Id de estudios_realizados |
| `con_apoyo` | TINYINT | NOT NULL | Apoyo institucional por estudios |
| `institucion` | VARCHAR(45) | NOT NULL | Institución del apoyo profesoral |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de apoyo profesoral |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de apoyo_profesoral |

### 3.16 `beca` — depende de estudios

| Columna | Tipo | Restricción | Quién lo escribe |
|---|---|---|---|
| `estudios` | INTEGER | **PK** y **FK → estudios_realizados** | Id de estudios_realizados |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de beca |
| `institucion` | VARCHAR(80) | NOT NULL | Institución de la beca |
| `fecha_inicio` | DATE | NOT NULL | Fecha de inico de la beca |
| `fecha_fin` | DATE | | Fecha de fin de la beca |
| `activo` | BOOLEAN | NOT NULL DEFAULT TRUE | Estado de actividad de apoyo_profesoral |

## 4. La lógica que vive en la base

### 4.1 El procedimiento de docente

| Procedimiento | Qué hace | Lo llama |
|---|---|---|
| `sp_consultar_docente_y_estudios_realizados_por_docente` | Devuelve el docente con sus estudios realizados | `obtenerPorDocenteEstudios()` |

Devuelve su resultado como **JSON en un parámetro `OUT`**, y los
rechaza con `SIGNAL SQLSTATE '45000'` cuando algo no cumple. Cómo se
leen desde PHP está en [3_plan.md](3_plan.md) §4.4.

### 4.2 Las otras 3 tablas: existen y la v2 no las toca

`rol`, `usuario` y `rol_usuario` sus procedimientos de
control de acceso están en la base desde la v1. **El código de la v2 no puede
nombrarlas.** Que estén a la vista no es invitación a usarlas.

## 5. Los datos de ejemplo de los que dependen las pruebas

El smoke test de [7_quickstart.md](7_quickstart.md) cuenta con esto, así que
si alguien cambia los datos hay que actualizarlo:

| Tabla | Cuántos | Un ejemplo que se usa en las pruebas |
|---|---|---|
| `area_conocimiento` | 3 | `1A01` — crear otra 1A01 debe dar 409 |
| `programa` | 6 | `id = 10305`, programa 10305 |

## 6. Montar la BD

```powershell
docker compose up -d mariadb      # desde la raíz del proyecto
```

Y el botón de pánico, que en esta versión se usa más que en la v1 porque las
pruebas mueven stock y crean docentes:

```powershell
docker compose down -v && docker compose up -d --build
```

`down -v` borra el volumen, así que `init.sql` vuelve a ejecutarse y la base
queda como recién nacida. Es la forma de volver al punto de partida cuando
una prueba dejó el inventario en un estado raro.

## 7. Qué toca el código en cada versión (la BD no cambia)

| Versión | Tablas que usa el código (API **y** pantalla) |
|---|---|
| v1 | `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red` — nada más |
| **v2** | **+ docente, estudios_reaalizados, docente_departamento, intereses_futuros, evaluacion_docente, reconocimiento, experiencia, red_docente, estudio_ac, apoyo_profesoral, beca** |
| v3–v4 | las mismas, contra más motores |

Las tres restantes (`rol`, `usuario`, `rol_usuario` y lo
que cuelga de ellas) siguen esperando: son control de acceso, y eso no es
alcance de esta ruta de versiones.