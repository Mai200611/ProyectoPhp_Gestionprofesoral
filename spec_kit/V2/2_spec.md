# Especificación — Versión 2: tablas con claves foráneas, integridad y maestro-detalle

> **Versión 2** del desarrollo incremental del **Módulo Gestión Profesoral** ([mapa de versiones](../0_mapa_versiones.md)).
> Rige la constitución del proyecto: [../../1_constitution.md](../../1_constitution.md).
> Parte del estado que dejó la v1: la API y el front de las **5 tablas sin FK** (`area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa`, `red`), funcionando.
> La API y el front siguen construyéndose **a la vez** (Artículo 1.1).
>
> | Documento de esta versión | Contenido |
> |---|---|
> | **2_spec.md** (este) | QUÉ construir en v2 y sus criterios de aceptación |
> | [3_plan.md](3_plan.md) | CÓMO: estructura, capas y las piezas nuevas |
> | [4_research.md](4_research.md) | Decisiones y alternativas *(lectura opcional)* |
> | [5_data_model.md](5_data_model.md) | Las 11 tablas con FK, sus llaves foráneas y la lógica que vive en la base |
> | [6_contracts.md](6_contracts.md) | Los endpoints y las pantallas, con formatos exactos |
> | [7_quickstart.md](7_quickstart.md) | Arranque y smoke test |
> | [8_tasks.md](8_tasks.md) | Orden de construcción por fases verificables |

---

## 1. Propósito de la v2

La v1 dejó el esqueleto probado con **cinco tablas que no dependían de nadie**. Esta versión mete al sistema en el problema que empieza cuando las tablas se relacionan entre sí:

1. **Que una ficha apunte a otra** — un `docente` tiene una `linea_investigacion_principal` (FK), un `estudios_realizados` apunta a un `docente`, un `intereses_futuros` es un puente entre `docente` y `termino_clave`. ¿Qué pasa cuando se apunta a algo que no existe? ¿Y cuando se quiere borrar algo de lo que otros dependen?
2. **Que la llave la ponga la base** — en v1 los catálogos (`area_conocimiento`, `programa`, etc.) tenían llaves naturales o autoincrementales sencillas. Aquí `docente` usa `cedula` como PK (natural), pero `estudios_realizados`, `evaluacion_docente`, `reconocimiento`, `experiecia` tienen `id` autoincremental que asigna la base. El cliente no lo manda al crear, y la API tiene que devolverlo.
3. **Que una operación sean varias escrituras** — crear un `docente` con sus `estudios_realizados`, sus `intereses_futuros`, sus `red_docente`… no es una sola inserción. Si se cae a la mitad, no puede quedar medio docente.
4. **Que parte del negocio ya esté escrita en la base** — los triggers y los procedimientos existen desde la v1, esperando. Aquí se usan.
5. **Que las FK se escojan, no se escriban** — el front debe ofrecer un desplegable con los nombres (de docentes, de líneas, de términos), no una casilla donde teclear una cédula que hay que ir a buscar a otra pantalla.

Y hay un descubrimiento que la v1 no podía tener: **no todo recurso es un CRUD plano**. Los puentes (`intereses_futuros`, `red_docente`, `estudio_ac`) y las tablas hijas de un estudio (`apoyo_profesoral`, `beca`) tienen reglas propias: no se pueden crear sin su padre, y su edición es más limitada.

## 2. Alcance

**Incluye:**

- Las **11 tablas con FK** en la API y en el front:
  **docente**, **estudios_realizados**, **docente_departamento**, **intereses_futuros**, **evaluacion_docente**, **reconocimiento**, **experiecia**, **red_docente**, **estudio_ac**, **apoyo_profesoral** y **beca** — más las 5 de la v1, que no se tocan.
- **La traducción de los errores de integridad del motor** a un mensaje en español y a un código **409**, en un solo archivo (`repositorios/errores_de_integridad.php`).
- **Llaves generadas por la base** en las tablas con `id` autoincremental (`estudios_realizados`, `evaluacion_docente`, `reconocimiento`, `experiecia`, `apoyo_profesoral`, `beca`, y los puentes si aplica): el cliente no las manda al crear, y la API le devuelve la que quedó.
- **Maestro-detalle**: el `docente` viaja con sus estudios, intereses y redes en una sola petición y en una sola respuesta (esto es la decisión de diseño clave de la v2).
- **El uso de los procedimientos almacenados** que ya trae la base para las operaciones complejas del docente.
- **Las pantallas** de los 11 recursos nuevos, incluida la de **ver un docente con su detalle completo** (estudios, intereses, redes).
- **Las FK como desplegables** en el front, con los nombres visibles.

**No incluye (y es deliberado):**

- Las **3 tablas de gestión de usuarios** (`rol`, `usuario`, `rol_usuario`). Existen en la base (v3 del módulo), y el código de la v2 no puede nombrarlas.
- **Autenticación y control de acceso** — eso es la v3 del módulo. No es alcance de esta versión.
- **Las 10 consultas multitabla**, el dashboard y la publicación — eso es la v4.
- **JavaScript de aplicación**: las pantallas siguen siendo formularios corrientes. Los únicos usos de JS son el `confirm()` antes de borrar y el guion de Bootstrap que cierra los avisos; quítelos y todo sigue andando.

## 3. Requisitos funcionales

### 3.A — La API (`api_gestion`, puerto 8022)

> **Nota de nomenclatura**: el servicio de la API se llamará `api_gestion` en el compose, y escuchará en **8022** (Artículo 8 de la Constitución). El nombre `api_facturas` del ejemplo de clase queda reemplazado.

#### RF1 a RF7 — Las 5 tablas de la v1
Sin cambios respecto de la v1: los endpoints de `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red` siguen respondiendo igual. Que sigan pasando sus pruebas es parte de los criterios de esta versión.

#### RF8 — Las 11 fichas nuevas
Cada una de las 11 tablas con FK ofrece las operaciones de ficha que le correspondan, cada una en **su propia ruta**:

- `/api/docente`
- `/api/estudios_realizados`
- `/api/docente_departamento`
- `/api/intereses_futuros`
- `/api/evaluacion_docente`
- `/api/reconocimiento`
- `/api/experiecia` *(así, con su errata histórica)*
- `/api/red_docente`
- `/api/estudio_ac`
- `/api/apoyo_profesoral`
- `/api/beca`

Las operaciones base son: listar (`GET`, con `?limite`), obtener (`GET /{llave}`), crear (`POST`), reemplazar (`PUT`), actualizar parcial (`PATCH`) y eliminar (`DELETE`). Envoltura de listado `{tabla, limite, total, datos}` y **204** cuando la tabla está vacía, igual que en la v1.

**Excepción de maestro-detalle**: `docente` tiene además un endpoint `GET /api/docente/{cedula}/completo` que devuelve el docente con **todos** sus estudios, intereses, redes, evaluaciones, reconocimientos y experiencia en una sola respuesta. Es la vista de "ver el docente con su detalle" del front.

#### RF9 — Las llaves que genera la base
En `estudios_realizados`, `evaluacion_docente`, `reconocimiento`, `experiecia`, `apoyo_profesoral` y `beca` la llave es un `id` numérico que asigna la base:

- el `POST` **no lo lleva** en el body, y si llega se ignora;
- la respuesta del `POST` **sí lo devuelve**, porque quien creó la ficha no tiene otra forma de saber con qué llave quedó;
- un identificador que no sea un entero mayor que cero es **400**, no 404: no es que no se haya encontrado, es que eso no es un identificador.

En `docente` la PK es `cedula` (natural, la manda el cliente). En los puentes (`intereses_futuros`, `red_docente`, `estudio_ac`, `docente_departamento`) la PK es compuesta y se documenta en `5_data_model.md`.

#### RF10 — La integridad la defiende la base, y la API la explica
Cuando el motor rechaza una escritura por una regla de integridad, la API responde **409** con el motivo en español. Los tres casos:

| Lo que se intentó | Lo que responde |
|---|---|
| Crear una ficha con una llave que ya existe | 409 «Ya existe … con esa llave.» |
| Apuntar a un código que no existe (`linea_investigacion_principal`, `cedula` en `estudios_realizados`, etc.) | 409 «Alguno de los códigos a los que apunta … no existe.» |
| Eliminar una ficha de la que otras dependen | 409 «No se puede eliminar … porque hay otras fichas que dependen de ella.» |

La API **no comprueba antes**: intenta, y traduce el veredicto. El porqué está en [4_research.md](4_research.md) D2.

#### RF11 — El docente se crea con su detalle, de una vez
`POST /api/docente` recibe el encabezado del docente y, opcionalmente, sus listas de estudios, intereses y redes en el mismo body, y devuelve **el docente completo**: su cédula, sus datos y su detalle.

- El body no lleva campos calculados por la base. Si llegan, se ignoran.
- Un docente sin estudios se acepta (un docente puede no tener estudios registrados aún), pero se advierte en la respuesta.
- Cada estudio, interés o red se valida por separado, y el error dice **cuál** elemento falla.

#### RF12 — El docente se reemplaza entero, no por partes
`PUT /api/docente/{cedula}` reemplaza los datos personales y **todo** el detalle (estudios, intereses, redes). **No existe `PATCH` de docente**: cambiar un estudio cambia la línea de investigación principal y los puentes, así que no hay «cambiar solo un pedacito». Un `PATCH` responde **405**, y ese 405 sale del enrutador sin que nadie lo escriba.

Para editar un estudio suelto, se usa el endpoint específico de `estudios_realizados`.

#### RF13 — Eliminar un docente no es borrar su historia
`DELETE /api/docente/{cedula}` **no borra en cascada** por defecto. Si el docente tiene estudios, evaluaciones, reconocimientos o experiencia, responde **409** con el motivo en español. Para borrarlo hay que eliminar primero sus dependencias, o usar un parámetro explícito que no existe en v2 (se documenta en `5_data_model.md` como decisión para v3).

- Eliminar un docente sin dependencias sí lo borra.
- Eliminar un estudio, una evaluación o un reconocimiento sí los borra (son hijos directos, sin más dependencias).

#### RF14 — Diagnóstico
`GET /` → JSON con mensaje, versión (`"v2"`), **la lista de los 11 recursos nuevos más los 5 de la v1** y la ruta de los contratos.

### 3.B — El front (`front_php`, puerto 8020)

> Aquí no hay verbos ni códigos de estado: **el usuario no sabe qué es un 409**. La traducción entre las dos cosas está en [6_contracts.md](6_contracts.md) §10.

#### RF15 — Once secciones nuevas, cada una con su dirección
`/docentes`, `/estudios`, `/departamentos`, `/intereses`, `/evaluaciones`, `/reconocimientos`, `/experiencia`, `/redes-docente`, `/estudios-area`, `/apoyos`, `/becas`, y el menú lleva a las once desde cualquier pantalla. Las cinco secciones de la v1 (`/areas`, `/terminos`, `/lineas`, `/programas`, `/redes`) siguen existiendo. Ninguna dirección lleva el nombre de la tabla como parámetro.

#### RF16 — Las fichas se manejan igual que en la v1
Listado, agregar, editar con **los dos botones** («Guardar la ficha completa» / «Guardar solo lo que cambié») y eliminar con confirmación. Donde la llave la genera la base, el formulario **no la muestra como campo**: se ve como dato, porque no se puede escribir ni cambiar.

#### RF17 — Las llaves foráneas se escogen, no se escriben
Los campos que apuntan a otra tabla se ofrecen en un **desplegable** con los nombres, no como una casilla donde haya que teclear un código que hay que ir a buscar a otra pantalla.

Ejemplos concretos:
- En `estudios_realizados`, el campo `docente` es un desplegable con `cedula — nombre_completo` de los docentes existentes.
- En `docente`, el campo `linea_investigacion_principal` es un desplegable con los nombres de las líneas.
- En `intereses_futuros`, el campo `termino_clave` es un desplegable con los términos.
- En `red_docente`, el campo `red` es un desplegable con los nombres de las redes.

El desplegable **no valida nada**, y la pantalla no debe hacer creer que sí: la lista pudo cargarse hace un minuto y esa ficha pudo borrarse entretanto.

#### RF18 — Los rechazos de la base se ven en español
Cuando la API responde 409, la pantalla muestra el motivo tal como viene — «No se puede eliminar el docente porque hay otras fichas que dependen de ella» — dentro de la aplicación, sin números de estado y sin jerga.

#### RF19 — El docente se arma en una pantalla
Una sola pantalla con el encabezado del docente y, en pestañas o secciones, sus estudios, intereses y redes. Al guardar, se va a la pantalla de **ver el docente**, que muestra los estudios con sus apoyos y becas, los intereses, las redes y las evaluaciones.

El detalle de estudios se ofrece en **cinco renglones fijos** y los vacíos se ignoran: un formulario que crezca solo necesita JavaScript, y esta versión no lo usa. Es una limitación de la pantalla, no de la API, y se declara aquí en vez de esconderse.

#### RF20 — Eliminar se ve distinto de anular (no hay anular en v2)
En el listado de docentes, «Eliminar» solo aparece si el docente no tiene dependencias —una acción que no se puede hacer no se muestra apagada: no se muestra— y su confirmación dice qué va a pasar (si tiene dependencias, la API responderá 409 y la pantalla mostrará el motivo). No hay "anular" en v2: eso era específico de facturas y aquí no aplica.

#### RF21 — La pantalla sobrevive a la API caída
Igual que en la v1: con la API apagada las pantallas siguen respondiendo, con su menú y su aviso, y **sin una sola fila**.

## 4. Requisitos no funcionales

- **RNF1 — Capas estrictas:** el controlador no toca SQL; el servicio no conoce HTTP ni el motor; el repositorio no conoce HTTP.
- **RNF2 — PHP puro:** sin framework, sin Composer, sin `vendor/`.
- **RNF3 — SQL SIEMPRE en prepared statements de PDO.**
- **RNF4 — Errores uniformes:** el mismo sobre de la v1, más el 409.
- **RNF5 — El dialecto de MariaDB vive SOLO en las clases `*MariaDB`.** Ni un `SELECT`, ni un nombre de procedimiento, ni un número de error del motor fuera de ellas. Es lo que la v3 va a poner a prueba.
- **RNF6 — El front no toca la base ni el código de la API.**
- **RNF7 — El front no le habla al usuario en jerga:** ninguna pantalla muestra verbos HTTP, números de estado, rutas de la API, nombres de columna (`fkcodpersona`, `cedula`, `idlinea`) ni nombres de motores.
- **RNF8 — Sin anticipación:** ni fábrica multi-motor ni selección de motor en v2.
- **RNF9 — Español en todo:** código, comentarios, mensajes y documentación (Artículo 1 de la Constitución).

## 5. Criterios de aceptación

1. **`docker compose up -d --build` — un solo comando —** deja corriendo la BD, la API y el front; `GET /` lista los 11 recursos nuevos más los 5 de la v1, y `http://localhost:8020` abre el inicio con sus secciones.
2. Los endpoints de las 5 tablas de la v1 **siguen respondiendo como en la v1**.
3. Los 11 recursos nuevos listan sus datos de ejemplo con la envoltura `{tabla, limite, total, datos}`, y `?limite=2` devuelve 2.
4. `POST /api/estudios_realizados` **sin `id` en el body** crea la ficha y **devuelve el `id`** que generó la base; `GET /api/estudios_realizados/{ese id}` la trae. `GET /api/estudios_realizados/abc` responde 400.
5. Los tres rechazos de integridad responden **409** con su motivo en español, y ninguno responde 500:
   - crear un `docente` con una cédula que ya existe;
   - crear un `estudios_realizados` con una `cedula` de docente que no existe;
   - eliminar un `docente` que ya tiene estudios.
6. `POST /api/docente` con dos estudios devuelve el docente con su cédula, **el detalle completo** y sus dos estudios; y la `linea_investigacion_principal` del docente quedó actualizada si se especificó.
7. `PATCH /api/docente/{cedula}` responde **405**. `POST /api/docente` sin datos obligatorios responde 422 diciendo cuál es el problema.
8. `DELETE /api/docente/{cedula}` de un docente con estudios responde 409; de un docente sin dependencias, 204 y lo borra.
9. Prueba de capas: los servicios se pueden probar con repositorios **falsos** en memoria, sin MariaDB corriendo.
10. Todas las pantallas responden por su dirección propia y **se ven con sus estilos** (`/publico/bootstrap.min.css` y `/publico/estilos.css` responden 200 con tipo `text/css`); una dirección inventada da 404 con el marco de la aplicación.
11. El recorrido completo **desde la pantalla**: agregar una ficha, los dos botones de guardar, y los dos rechazos de integridad explicados en español, sin números de estado.
12. Desde la pantalla: crear un docente con dos estudios, verlo con su detalle completo, y comprobar que el desplegable de línea de investigación principal muestra solo las líneas existentes.
13. Ninguna pantalla muestra `PUT`, `PATCH`, `DELETE`, `/api/`, `PDO`, `SELECT`, `MariaDB` ni nombres de columna como `cedula` o `idlinea`.
14. **La prueba de los dos procesos:** con `docker compose stop api_gestion` —la base **encendida**—, las pantallas siguen respondiendo, con su menú y su aviso, y sin una sola fila.

## 6. Glosario mínimo

| Término | Significado |
|---|---|
| **Llave foránea** | Una columna que solo acepta valores que existen en otra tabla. La defiende la base, no el programa |
| **Integridad referencial** | Que no queden referencias rotas: ningún estudio apuntando a un docente que ya no está |
| **Maestro-detalle** | Dos tablas que no tienen sentido por separado: el docente y sus estudios, sus intereses, sus redes |
| **Tabla puente** | Una tabla que solo existe para relacionar dos tablas: `intereses_futuros` relaciona `docente` y `termino_clave` |
| **Trigger** | Código que la base ejecuta sola cuando una tabla cambia |
| **Procedimiento almacenado** | Una operación escrita y guardada dentro de la base, que se llama por su nombre |
| **AUTO_INCREMENT** | Que la base ponga la llave, y no quien crea la ficha |
| **409 Conflicto** | «Su petición está bien escrita, pero no cabe en los datos que hay». Distinto de un 500, que es «se rompió algo» |

## 7. Definición de TERMINADA

Los 14 criterios pasan → commit + tag `v2` → recién entonces se escribe la spec de la v3 ([mapa](../0_mapa_versiones.md)).

## 8. Clarificaciones

> **Qué es esta sección:** el registro de las ambigüedades detectadas ANTES de planear, con la respuesta que se acordó y su razón. Es **la compuerta 1** del método (ver [SDD_SPECKIT](../../../SDD_SPECKIT.md)): mientras quede un `[NECESITA ACLARACIÓN: …]` en los requisitos de arriba, esta versión no pasa a la planeación.

| # | La pregunta | La respuesta acordada, con su razón | Dónde quedó |
|---|---|---|---|
| C1 | Cuando la base rechaza por una llave foránea, ¿eso es un 500 como en la v1? | **No: 409.** En la v1 se decidió 500 porque la integridad no era tema de esa versión y traducirla habría sido lógica que nadie pidió. En la v2 la integridad **es** el tema, así que se traduce. Un 500 le dice al usuario «se dañó algo»; un 409 le dice «su petición no cabe en los datos que hay», que es la verdad. | RF10 · 4_research D1 |
| C2 | ¿La API comprueba que el docente exista antes de crear el estudio? | **No.** Se intenta y se traduce el veredicto. Comprobar antes no evita el problema —entre la comprobación y la escritura otro puede borrar ese docente— y obliga a mantener la misma regla en PHP y en la base. | RF10 · Artículo 11 |
| C3 | Un `POST` de estudio que traiga `id`, ¿es 422 o se ignora? | **Se ignora**, con la lista blanca. No es un error del cliente: es un campo que esta API no acepta de nadie. Rechazar la petición entera por un campo de más sería severo sin ganar nada. | RF9 · 3_plan §4.3 |
| C4 | ¿El docente tiene `PATCH`? | **No.** Cambiar un estudio cambia el detalle y la línea de investigación principal; el detalle se reemplaza entero. El 405 lo da el enrutador sin escribir el número en ninguna parte. | RF12 |
| C5 | ¿Eliminar un docente borra sus estudios en cascada? | **No por defecto: 409.** La cascada silenciosa es peligrosa: borra historia que quizás se quería conservar. En v2 se exige eliminar primero las dependencias. La cascada explícita se decide en v3. | RF13 · 4_research D6 |
| C6 | El formulario de docente, ¿cuántos estudios ofrece? | **Cinco fijos**, y los vacíos se ignoran. Un formulario que crezca solo necesita JavaScript, que esta versión no usa. Es una limitación de la pantalla y se declara; la API acepta los estudios que sean. | RF19 · 4_research D5 |
| C7 | Cuando el detalle llega vacío, ¿quién lo rechaza? | **Nadie lo rechaza**: un docente sin estudios es válido. Un estudio sin docente **sí** se rechaza (FK), y eso lo defiende la base con 409. | RF11 · 7_quickstart |
| C8 | ¿Se renombra el servicio `api_gestion`? | **Sí.** El compose, el Dockerfile y las rutas internas usan el nombre del módulo, no el del ejemplo de clase. La Constitución (Artículo 8) fija puertos, pero el nombre del servicio debe ser coherente con el proyecto de aula. | RF1 · 3_plan §2 |