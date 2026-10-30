# Constitución del Proyecto PHP

> Principios **innegociables** que gobiernan todo el proyecto. Esta
> constitución es **permanente**: describe el sistema COMPLETO al que se llega
> al final, y no cambia entre versiones.
>
> El proyecto se construye **por versiones** (desarrollo incremental guiado por
> especificaciones): ver el [mapa de versiones](versiones/0_mapa_versiones.md).
> Cada artículo aplica desde la versión que introduce su alcance — por ejemplo,
> en la v1 la API solo conoce `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red` con un motor; en la v2 se suman las **11 tablas con claves foráneas** y la integridad referencial; en v3 y v4 se suman los otros motores. Los artículos sobre esos otros motores son la META, no el estado actual.
>
> **El front no está entre esas metas: existe desde la v1.** Por qué, en el
> Artículo 1.1.

---

## Artículo 1 — Propósito didáctico ante todo

Este proyecto existe para **enseñar PHP construyendo software real con
arquitectura** a estudiantes universitarios. Ante cualquier disyuntiva entre
"lo más profesional" y "lo más claro para aprender", gana la claridad:

- Todo el código, comentarios, mensajes y documentación se escriben en **español**.
- Cada archivo abre con un comentario que explica su papel en la arquitectura.
- Se prefiere código explícito y repetitivo-pero-legible sobre metaprogramación compacta.
- Cada que se termine un paso, esperas la directiva de un "Siguiente" para continuar con el código.

### Artículo 1.1 — Una versión incluye SU FRONT

Una versión **no está cerrada** si la API responde y la pantalla no. El front
de lo que esa versión construye se hace **al mismo tiempo** que la API, no
después.

Esto es una corrección a cómo estaba pensado antes este proyecto, y la razón
de haberlo cambiado vale más que la regla:

- **Un front al final llega tarde para corregir.** Los desajustes entre lo
  que la API devuelve y lo que la pantalla necesita —el número que viaja como
  texto, el campo que se llama distinto, el 204 que nadie sabía interpretar—
  aparecen el día que alguien pinta una tabla. Descubrirlos en la v5, con
  cuatro versiones de API encima, es rehacer; descubrirlos en la v1, con un
  CRUD de una tabla, es corregir en diez minutos.
- **La API sin pantalla se cree terminada sin estarlo.** Un `curl` que
  responde 200 no dice si el dato sirve. La pantalla sí: obliga a decidir qué
  se muestra, cómo se dice el error en español y qué pasa cuando no hay
  filas.
- **Y sobre todo: la pantalla es la única prueba de que son dos procesos.**
  Se apaga la API con la base encendida; si el front pudiera llegar a la base
  por su cuenta, seguiría mostrando datos. No los muestra: muestra su aviso.
  Eso no se puede demostrar sin front.

El front es la mitad del trabajo de la versión, no un adorno que se le cuelga
al final.

## Artículo 2 — PHP puro: sin framework y sin Composer

- **PHP 8.3+ "vanilla"**: cero dependencias externas, cero `composer.json`,
  cero `vendor/`. Lo que el estudiante ve es PHP del lenguaje, no magia de un
  framework.
- El enrutamiento vive en UN front controller (`index.php`) legible completo.
- Los contratos entre capas son **`interface` nativas de PHP** — el lenguaje
  las trae de fábrica; aquí se usan de verdad.
- El acceso a datos es **PDO** con *prepared statements*: el SQL queda
  **visible** (nada de ORM que lo esconda).

## Artículo 3 — Arquitectura de 3 capas estricta

- El front **no abre conexiones de base de datos**; solo habla HTTP con la API.
  En este proyecto la tentación es mayor que en otros, porque el front y la
  API están **los dos escritos en PHP**: un `require_once` de una clase de la
  API funcionaría. Y estaría mal — dejarían de ser dos procesos, y renombrar
  un método adentro de la API rompería la pantalla **sin que nadie tocara el
  contrato**. Lo único que comparten es el JSON.
- El compose hace cumplir esto y no solo lo pide: el servicio del front no
  recibe las credenciales de la base ni depende de ella.
- Las APIs no generan HTML; solo JSON.
- Dentro de cada API: controlador → servicio → repositorio, comunicados por
  interfaces; solo el ensamblador conoce clases concretas.

## Artículo 4 — Un solo comando para arrancar

`docker compose up -d --build` debe dejar TODO funcionando. Sin pasos
manuales, sin instalar PHP ni PostgreSQL local más allá de Docker. Los
estudiantes tienen máquinas heterogéneas: el entorno vive completo en
contenedores.

## Artículo 5 — Independencia del motor de base de datos

- El motor activo se elige con **configuración**, nunca con cambios de código.
- Los tres motores contienen la **misma base de datos** (`bdgestion_*_local`):
  mismas tablas, mismos datos, mismos triggers y procedimientos. En la v1 son
  5 tablas; la v2 suma las 11 con FK; v3 y v4 completan las 19.
- Todo acceso a datos pasa por interfaces + un punto único de ensamblaje
  (inversión de dependencias — la D de SOLID).

## Artículo 6 — Persistencia y reproducibilidad

- Los datos viven en **volúmenes** Docker: sobreviven a `docker compose down`.
- `docker compose down -v` devuelve la BD a su estado original (`db/init.sql`
  se re-ejecuta sobre volumen vacío). Ese es el "botón de pánico" oficial.

## Artículo 7 — Desarrollo con recarga natural

El código se monta como volumen dentro del contenedor. En PHP no hay "reload"
que configurar: **cada petición HTTP reinterpreta los archivos** — guardar un
`.php` y refrescar el navegador ES el ciclo de desarrollo. Reconstruir la
imagen (`--build`) solo es necesario si cambia el `Dockerfile`.

## Artículo 8 — Convenciones fijas

| Cosa | Convención |
|---|---|
| Puertos públicos | front **8020** · API **8022** |
| Puertos de BD hacia el host | PostgreSQL **15452** · MariaDB **13326** · SQL Server **11453** |
| Hosts internos (entre contenedores) | `postgres:5432` · `mariadb:3306` · `sqlserver:1433` |
| Credenciales BD | usuario `paradigmas` / clave `paradigmas123` (SQL Server: `sa` / `Paradigmas123!`) |
| Bases de datos | `bdgestion_postgres_local` · `bdgestion_mariadb_local` · `bdgestion_sqlserver_local` |
| Nombres de código | Clases e interfaces en PascalCase con prefijo `I` para interfaces; métodos y variables en camelCase; archivos = nombre de su clase |
| Estructura por API | `index.php` · `modelos/` · `controladores/` · `servicios/` · `repositorios/` · `excepciones/` |
| Estructura del front | `index.php` (front controller) · `cliente_api.php` · `vistas/` · `publico/` |

## Artículo 9 — Seguridad en su justa medida académica

- Los valores SQL siempre van en **prepared statements** (nunca concatenados).
- Las contraseñas de usuarios de la aplicación se almacenan con **hash**
  (nunca texto plano en código nuevo).
- Las credenciales de infraestructura (paradigmas/paradigmas123) son públicas
  y didácticas **a propósito**: este entorno jamás se despliega a producción.

## Artículo 10 — La API es específica, nunca genérica

Cada recurso tiene **su propia ruta escrita a mano**: `/api/area_conocimiento`,
`/api/termino_clave`, `/api/linea_investigacion`, `/api/programa`, `/api/red`
— y, desde la v2, `/api/docente`, `/api/estudios_realizados`,
`/api/docente_departamento`, `/api/intereses_futuros`, `/api/evaluacion_docente`,
`/api/reconocimiento`, `/api/experiecia`, `/api/red_docente`, `/api/estudio_ac`,
`/api/apoyo_profesoral`, `/api/beca`. Nunca una ruta con el nombre de la tabla
como parámetro (`/api/{tabla}`) que sirva para todas.

**El porqué, que es lo que hay que entender —y con una excepción honesta.**

Una API genérica es **buena para prototipar**: se escribe una vez y de una vez
sirve para las dieciséis tablas. Ahí gana de verdad, y decir lo contrario sería
falso. Lo que pasa es que ese ahorro se cobra después. La tabla de abajo
compara las 5 tablas de la v1 y las 11 de la v2 contra lo que ofrecería una
ruta genérica:

| | Genérica `/api/{tabla}` | v1: `area_conocimiento` | v1: `termino_clave` | v1: `linea_investigacion` | v1: `programa` | v1: `red` |
|---|---|---|---|---|---|---|
| **Escribirla** | Una vez, y sirve para todo | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso |
| **Documentación** | Dice «tabla: texto». No dice cuáles ni qué campos lleva cada una | Documenta `id`, `gran_area`, `area`, `disciplina` | Documenta `termino`, `termino_ingles` | Documenta `id`, `nombre`, `descripcion` | Documenta `id`, `nombre`, `tipo`, `nivel`, fechas, cohortes, graduados, ciudad y facultad | Documenta `idr`, `nombre`, `url`, `pais` |
| **Un campo que solo tiene una tabla** | No cabe: obligaría a un `if` adentro de lo genérico | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás |
| **Una regla que solo aplica a una tabla** | Igual: el `if` crece con cada excepción | Vive en la lógica de `area_conocimiento` | Vive en la lógica de `termino_clave` | Vive en la lógica de `linea_investigacion` | Vive en la lógica de `programa` | Vive en la lógica de `red` |
| **Un cambio en un recurso** | Toca el código de TODOS | Toca el código de `area_conocimiento` | Toca el código de `termino_clave` | Toca el código de `linea_investigacion` | Toca el código de `programa` | Toca el código de `red` |
| **Quien la consume** | Tiene que preguntar qué valores acepta el parámetro | Lee `/api/area_conocimiento` directamente | Lee `/api/termino_clave` directamente | Lee `/api/linea_investigacion` directamente | Lee `/api/programa` directamente | Lee `/api/red` directamente |
| **Identificador** | Depende de la tabla y debe descubrirlo dinámicamente | `id` | `termino` | `id` | `id` | `idr` |
| **Tipo de recurso** | Desconocido hasta consultar la tabla | Catálogo jerárquico del conocimiento | Catálogo de términos y traducciones | Catálogo de líneas de investigación | Catálogo de programas académicos | Catálogo de redes académicas |
| **Datos principales** | Desconocidos | `gran_area`, `area`, `disciplina` | `termino`, `termino_ingles` | `nombre`, `descripcion` | `nombre`, `tipo`, `nivel`, fechas, cohortes, graduados, ciudad, facultad | `nombre`, `url`, `pais` |
| **Claves foráneas en v1** | No puede determinarse de forma explícita | Ninguna | Ninguna | Ninguna | Ninguna | Ninguna |
| **Claves foráneas en v2** | No puede determinarse de forma explícita | Ninguna | Ninguna | Ninguna | Ninguna | Ninguna |
| **Cantidad inicial** | Depende de la tabla | 218 registros | — | — | 191 registros | — |

Y la misma comparación, ahora contra los 11 recursos que estrena la v2:

| | Genérica `/api/{tabla}` | `docente` | `estudios_realizados` | `docente_departamento` | `intereses_futuros` | `evaluacion_docente` |
|---|---|---|---|---|---|---|
| **Escribirla** | Una vez, y sirve para todo | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso |
| **Documentación** | Dice «tabla: texto». No dice cuáles ni qué campos lleva cada una | Documenta `cedula`, `nombre_completo`, `url_cvlac`, `escalafon`, `categoria_minciencias`, `perfil`, `linea_investigacion_principal` | Documenta `id`, `titulo`, `universidad`, `fecha`, `tipo`, `cedula` | Documenta `cedula`, `id_programa`, `dedicacion`, `modalidad`, `fecha_inicio`, `fecha_fin` | Documenta `cedula`, `termino` | Documenta `id`, `semestre`, `calificacion`, `cedula` |
| **Un campo que solo tiene una tabla** | No cabe: obligaría a un `if` adentro de lo genérico | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás |
| **Una regla que solo aplica a una tabla** | Igual: el `if` crece con cada excepción | Vive en la lógica de `docente` (maestro-detalle) | Vive en la lógica de `estudios_realizados` (hijo de docente) | Vive en la lógica de `docente_departamento` (puente) | Vive en la lógica de `intereses_futuros` (puente) | Vive en la lógica de `evaluacion_docente` (hijo de docente) |
| **Un cambio en un recurso** | Toca el código de TODOS | Toca el código de `docente` | Toca el código de `estudios_realizados` | Toca el código de `docente_departamento` | Toca el código de `intereses_futuros` | Toca el código de `evaluacion_docente` |
| **Quien la consume** | Tiene que preguntar qué valores acepta el parámetro | Lee `/api/docente` directamente | Lee `/api/estudios_realizados` directamente | Lee `/api/docente_departamento` directamente | Lee `/api/intereses_futuros` directamente | Lee `/api/evaluacion_docente` directamente |
| **Identificador** | Depende de la tabla y debe descubrirlo dinámicamente | `cedula` (string, natural) | `id` (autoincremental) | `(cedula, id_programa)` (compuesta) | `(cedula, termino)` (compuesta) | `id` (autoincremental) |
| **Tipo de recurso** | Desconocido hasta consultar la tabla | Ficha maestra con detalle (estudios, intereses, redes) | Ficha hija del docente | Tabla puente docente↔programa | Tabla puente docente↔término | Ficha hija del docente |
| **Datos principales** | Desconocidos | Datos personales y académicos del docente | Título, universidad, tipo, fecha | Dedicación, modalidad, fechas | Solo la relación | Semestre, calificación |
| **Claves foráneas en v2** | No puede determinarse de forma explícita | `linea_investigacion_principal` → `linea_investigacion.id` | `cedula` → `docente.cedula` | `cedula` → `docente.cedula`; `id_programa` → `programa.id` | `cedula` → `docente.cedula`; `termino` → `termino_clave.termino` | `cedula` → `docente.cedula` |
| **Cantidad inicial** | Depende de la tabla | — | — | — | — | — |

| | Genérica `/api/{tabla}` | `reconocimiento` | `experiecia` | `red_docente` | `estudio_ac` | `apoyo_profesoral` | `beca` |
|---|---|---|---|---|---|---|---|
| **Escribirla** | Una vez, y sirve para todo | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso | Una vez por recurso |
| **Documentación** | Dice «tabla: texto». No dice cuáles ni qué campos lleva cada una | Documenta `id`, `tipo`, `fecha`, `institucion`, `ambito`, `cedula` | Documenta `id`, `cargo`, `institucion`, `tipo`, `fecha_inicio`, `fecha_fin`, `cedula` | Documenta `idr`, `cedula`, `fecha_vinculacion`, `actividades_destacadas` | Documenta `id_estudio`, `id_area` | Documenta `id_estudio`, `tipo_apoyo`, `institucion`, `monto` | Documenta `id_estudio`, `tipo_beca`, `institucion`, `fecha_inicio`, `fecha_fin` |
| **Un campo que solo tiene una tabla** | No cabe: obligaría a un `if` adentro de lo genérico | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás | Se agrega en su recurso y no afecta a los demás |
| **Una regla que solo aplica a una tabla** | Igual: el `if` crece con cada excepción | Vive en la lógica de `reconocimiento` | Vive en la lógica de `experiecia` (errata histórica) | Vive en la lógica de `red_docente` (puente) | Vive en la lógica de `estudio_ac` (puente) | Vive en la lógica de `apoyo_profesoral` (hijo de estudio) | Vive en la lógica de `beca` (hijo de estudio) |
| **Un cambio en un recurso** | Toca el código de TODOS | Toca el código de `reconocimiento` | Toca el código de `experiecia` | Toca el código de `red_docente` | Toca el código de `estudio_ac` | Toca el código de `apoyo_profesoral` | Toca el código de `beca` |
| **Quien la consume** | Tiene que preguntar qué valores acepta el parámetro | Lee `/api/reconocimiento` directamente | Lee `/api/experiecia` directamente | Lee `/api/red_docente` directamente | Lee `/api/estudio_ac` directamente | Lee `/api/apoyo_profesoral` directamente | Lee `/api/beca` directamente |
| **Identificador** | Depende de la tabla y debe descubrirlo dinámicamente | `id` (autoincremental) | `id` (autoincremental) | `(idr, cedula)` (compuesta) | `(id_estudio, id_area)` (compuesta) | `(id_estudio, tipo_apoyo)` (compuesta) | `(id_estudio, tipo_beca)` (compuesta) |
| **Tipo de recurso** | Desconocido hasta consultar la tabla | Ficha hija del docente | Ficha hija del docente | Tabla puente red↔docente | Tabla puente estudio↔área | Ficha hija de estudio | Ficha hija de estudio |
| **Datos principales** | Desconocidos | Tipo, fecha, institución, ámbito | Cargo, institución, tipo, fechas | Fecha de vinculación, actividades | Solo la relación | Institución, monto | Institución, fechas |
| **Claves foráneas en v2** | No puede determinarse de forma explícita | `cedula` → `docente.cedula` | `cedula` → `docente.cedula` | `idr` → `red.idr`; `cedula` → `docente.cedula` | `id_estudio` → `estudios_realizados.id`; `id_area` → `area_conocimiento.id` | `id_estudio` → `estudios_realizados.id` | `id_estudio` → `estudios_realizados.id` |
| **Cantidad inicial** | Depende de la tabla | — | — | — | — | — | — |

En corto: **lo genérico es barato de escribir y caro de vivir; lo específico
es caro de escribir una vez y barato de vivir.** Un prototipo se tira a los
dos meses, así que solo paga la primera columna. Un sistema que se mantiene
paga la segunda todos los días.

**La prueba concreta de que hacía falta ser específico en la v2:** el
`docente` no admite `PATCH` (405) y sí admite un `GET /api/docente/{cedula}/completo`
que devuelve el detalle anidado. Ninguna ruta genérica habría podido decir
eso sin un `if` adentro que crece con cada excepción.

**La excepción honesta:** PostgREST, Hasura y compañía son genéricos y son
excelentes. La diferencia es que ahí **lo genérico es el producto entero**:
lo mantiene un equipo dedicado, y quien lo usa no escribe endpoints, escribe
consultas. Eso no es «una API genérica hecha en casa» — es otra cosa.

En este curso, entonces: una ruta por recurso, con sus campos escritos.


## Artículo 11 — La base también es parte del sistema

> Este artículo se estrena con la v2, cuando el sistema empieza a tener
> relaciones entre tablas y la base deja de ser solo un almacén.

- **La integridad referencial la defiende la base, no la API.** Si una ficha
  apunta a un código que no existe, o si se intenta borrar algo de lo que
  otros dependen, el motor lo rechaza. La API **no comprueba antes**:
  intenta la operación y **traduce el veredicto del motor** a un mensaje en
  español.
- **El resultado de esa traducción es un 409 (Conflicto).** No es un 422 (el
  cliente escribió mal) ni un 500 (se rompió algo): es «su petición no cabe
  en los datos que hay». La traducción vive en **un solo archivo**
  (`repositorios/errores_de_integridad.php`), que es el único que tiene
  derecho a conocer los números de error del motor (1062, 1452, 1451 en
  MariaDB). Ninguna otra capa los conoce.
- **Lo que la base calcula no se recalcula en PHP.** Si un trigger o un
  procedimiento actualiza un campo (por ejemplo, la línea de investigación
  principal de un docente a partir de sus estudios), la API **no repite ese
  cálculo**. Lo lee y lo devuelve. La base es la única fuente de verdad de lo
  que la base calcula.
- **Los procedimientos almacenados se llaman, no se reescriben.** Cuando la
  base ya trae una operación compleja escrita (el alta de un docente con su
  detalle, el listado con agregados), el repositorio la **llama** y lee su
  resultado. No la reimplementa en SQL suelto.
- **El maestro-detalle se maneja como una unidad.** Un docente con sus
  estudios, intereses y redes es una sola operación de negocio: o se guarda
  completo, o no se guarda. La API no ofrece un `PATCH` que modifique «solo
  un pedacito» del detalle, porque eso dejaría la ficha a medio construir. El
  reemplazo es entero (`PUT`) y la edición de una pieza suelta se hace por el
  endpoint específico de esa pieza.

**El porqué, en corto:** una base de datos relacional no es un almacén tonto.
Tiene reglas, tiene procedimientos, tiene triggers, y sabe decir «no». Una
API que ignora eso y repite las reglas en PHP termina con dos fuentes de
verdad que se desincronizan al primer cambio. Este artículo fija la única
dirección posible: **la base defiende, la API traduce, el front muestra**.

En la v2 este artículo se aplica a las 11 tablas con claves foráneas. En v3 y
v4 se aplicará igual contra los otros motores: los números de error del motor
cambiarán, pero la traducción seguirá viviendo en el mismo archivo del
repositorio.
