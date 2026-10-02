# Especificación — Versión 1 del proyecto: producto de punta a punta (front + API + MariaDB)

> **Versión 1** del desarrollo incremental ([mapa de versiones](../0_mapa_versiones.md)).
> Rige la constitución del proyecto: [../../1_constitution.md](../../1_constitution.md).
> En v1 el sistema completo ES esto: **5 tablas, un motor — y su pantalla.**
> La API y el front se construyen **a la vez** (Artículo 1.1 de la
> constitución)
> 
>
> | Documento de esta versión | Contenido |
> |---|---|
> | **2_spec.md** (este) | QUÉ construir en v1 y sus criterios de aceptación |
> | [3_plan.md](3_plan.md) | CÓMO: stack, estructura y diseño de las capas |
> | [4_research.md](4_research.md) | Decisiones y alternativas *(lectura opcional)* |
> | [5_data_model.md](5_data_model.md) | La BD completa (dada) y las 5 tablas ya dadas |
> | [6_contracts.md](6_contracts.md) | Los 7 endpoints y las 5 pantallas, con formatos exactos |
> | [7_quickstart.md](7_quickstart.md) | Arranque y smoke test |
> | [8_tasks.md](8_tasks.md) | Orden de construcción por fases verificables |

---

## 1. Propósito de la v1

Construir la **primera rebanada vertical (corte vertical)** del sistema de
facturación en **PHP puro**: el CRUD completo de **las 5 entidades
(`area_conocimiento`,`termino_clave`,`linea_investigacion`,`programa`y `red`)** contra **un solo motor (MariaDB)** — con la **arquitectura en
capas completa desde el primer día** (controlador → servicio → repositorio,
comunicados por **interfaces nativas de PHP**) **y con la pantalla desde la
que se usa**.

La rebanada llega hasta arriba a propósito. Una que se detuviera en la API
dejaría sin comprobar justo lo que la constitución promete: que el front y la
API son **dos procesos separados**, y que el de la pantalla no puede llegar a
la base de datos ni queriendo.

> **¿Qué es una "rebanada vertical"?** En lugar de construir el sistema por
> capas horizontales ("primero TODOS los repositorios, luego TODOS los
> servicios…" — donde nada funciona hasta el final), se construye un corte
> que **atraviesa todas las capas de arriba a abajo** para UNA funcionalidad.
> Como una rebanada de pastel: un solo corte, pero con todas las capas.
>
> ```
> ┌─────────────────────────── el sistema completo ──────────────────────────────────────────────────────┐
> │  PANTALLA    │ area_conocimiento █ │ termino_clave   │ linea_investigacion    │ Programa  | Red |...las demás (v2)   │
> │  CONTROLADOR │ area_conocimiento █ │ termino_clave   │ linea_investigacion    │ Programa  | Red |...                 │
> │  SERVICIO    │ area_conocimiento █ │ termino_clave   │ linea_investigacion    │ Programa  | Red |...                 │
> │  REPOSITORIO │ area_conocimiento █ │ termino_clave   │ linea_investigacion    │ Programa  | Red |...                 │
> │  BD          │ area_conocimiento █ │ termino_clave   │ linea_investigacion    │ Programa  | Red |...                 │
> └──────────────┴─────▲──────┴────────────┴────────────┴────────────────────────────────────────────────┘
>                      └── la v1 ES esta rebanada: del navegador a la tabla
> ```
>
> La fila de arriba es la que suele faltar. Sin ella el corte no llega hasta
> el usuario, y lo que se valida es media arquitectura.
>
> Ventaja: algo funciona **desde la v1** y la arquitectura queda validada —
> si las capas encajan para `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa`,`red` las siguientes rebanadas (v2) caen en
> surcos ya hechos.

La v1 es pequeña a propósito: su valor no está en la funcionalidad sino en
dejar el **esqueleto arquitectónico correcto** —el de la API **y el del
front**— sobre el que las versiones siguientes agregan tablas (v2) y motores
(v3, v4) **sin reescribir lo construido**.

## 2. Alcance

## 2. Alcance — `area_conocimiento`

****Incluye:****

* CRUD de `area_conocimiento`: listar, obtener por `id`, crear, reemplazar, actualizar parcialmente y eliminar.
* ****Modelo clásico****: la clase `AreaConocimiento` representa cada registro mediante las propiedades correspondientes a `id`, `gran_area`, `area` y `disciplina`, utilizando propiedades ****privadas****, getters, setters y `toArray()` para construir la respuesta JSON.
* ****Identificador propio****: `id` identifica de manera única cada registro de la tabla.
* ****Validación en el controlador****: se valida en la frontera HTTP que los datos recibidos correspondan a la estructura propia de `area_conocimiento`.
* ****Validación de los campos****: `gran_area`, `area` y `disciplina` deben corresponder a los datos esperados por el catálogo.
* Capas con interfaces: `IARepositorioAreaConocimiento` como contrato PHP, implementada por el repositorio concreto del motor de BD utilizado en la versión.
* Configuración de conexión mediante variables de entorno y PDO con *prepared statements*.
* Endpoint `/api/area_conocimiento` específico para este recurso.
* Endpoint `GET /api/area_conocimiento/{id}` para consultar un registro por su identificador.
* El recurso debe responder exclusivamente **JSON**; no genera HTML.
* ****Front `front_php`**** con las pantallas correspondientes a `area_conocimiento`: inicio, listado, agregar y editar.
* El front se comunica con la API mediante HTTP y ****no abre ninguna conexión a la base de datos****.
* Carga inicial del catálogo de **218 registros** provenientes de los datos definidos para la v1.
* Los campos `gran_area`, `area` y `disciplina` se manejan como información propia del registro y no como claves foráneas en esta versión.

****No incluye (y es deliberado — ver [mapa de versiones](../0_mapa_versiones.md)):****

* Relaciones mediante claves foráneas con otras tablas.
* Endpoints ni pantallas para entidades diferentes de `area_conocimiento` dentro de este recurso.
* Autenticación de usuarios en el front.
* Lógica de aplicación en JavaScript.
* Frameworks de front como React, Vue o Blazor.
* Frameworks PHP, Composer o librerías externas.
* Cambios sobre la estructura jerárquica mediante otras tablas relacionadas.
* Funcionalidades pertenecientes a versiones posteriores.

## 2.1 Alcance — `termino_clave`

****Incluye:****

* CRUD de `termino_clave`: listar, obtener por `termino`, crear, reemplazar, actualizar parcialmente y eliminar.
* ****Modelo clásico****: la clase `TerminoClave` representa un registro mediante las propiedades ****privadas**** `termino` y `termino_ingles`, con getters, setters y `toArray()` para construir el JSON.
* ****Identificador propio****: `termino` funciona como clave primaria del recurso y permite identificar un término individual.
* ****Validación en el controlador****: se validan los datos recibidos en cada operación HTTP antes de enviarlos al servicio.
* ****Reglas propias del recurso****: `termino` debe identificar de manera única el registro y `termino_ingles` contiene su traducción correspondiente.
* Capas con interfaces: `IRepositorioTerminoClave` como contrato PHP, implementada por el repositorio concreto del motor de BD utilizado en la versión.
* Configuración de conexión mediante variables de entorno y PDO con *prepared statements*.
* Endpoint `/api/termino_clave` específico para este recurso.
* Endpoint `GET /api/termino_clave/{termino}` para consultar un término específico.
* Respuestas exclusivamente en **JSON**.
* ****Front `front_php`**** con las pantallas correspondientes a `termino_clave`: inicio, listado, agregar y editar.
* El front se comunica con la API mediante HTTP y ****no abre ninguna conexión a la base de datos****.
* Los términos y sus traducciones se manejan como registros independientes.
* No se establecen relaciones mediante claves foráneas en la v1.

****No incluye (y es deliberado — ver [mapa de versiones](../0_mapa_versiones.md)):****

* Relaciones mediante claves foráneas con otras tablas.
* Asociación de un término con una línea de investigación, programa, red o área de conocimiento.
* Traducción automática.
* Integración con servicios externos de traducción.
* Búsqueda semántica o procesamiento lingüístico.
* Autenticación de usuarios en el front.
* Lógica de aplicación en JavaScript.
* Frameworks de front.
* Frameworks PHP, Composer o librerías externas.
* Funcionalidades pertenecientes a versiones posteriores.

## 2.2 Alcance — `linea_investigacion`

****Incluye:****

* CRUD de `linea_investigacion`: listar, obtener por `id`, crear, reemplazar, actualizar parcialmente y eliminar.
* ****Modelo clásico****: la clase `LineaInvestigacion` representa cada registro mediante las propiedades ****privadas**** `id`, `nombre` y `descripcion`, con getters, setters y `toArray()` para construir el JSON.
* ****Identificador propio****: `id` es un identificador autoincremental generado por la base de datos.
* ****Validación en el controlador****: cada operación HTTP valida los datos antes de enviarlos al servicio.
* ****Reglas propias del recurso****: `nombre` identifica el nombre de la línea y `descripcion` contiene la explicación de su temática o propósito.
* En las operaciones de creación, el `id` es generado por la base de datos y no debe ser proporcionado obligatoriamente por el cliente.
* Capas con interfaces: `IRepositorioLineaInvestigacion` como contrato PHP, implementada por el repositorio concreto del motor de BD utilizado en la versión.
* Configuración de conexión mediante variables de entorno y PDO con *prepared statements*.
* Endpoint `/api/linea_investigacion` específico para este recurso.
* Endpoint `GET /api/linea_investigacion/{id}` para consultar una línea específica.
* Respuestas exclusivamente en **JSON**.
* ****Front `front_php`**** con las pantallas correspondientes a `linea_investigacion`: inicio, listado, agregar y editar.
* El front se comunica con la API mediante HTTP y ****no abre ninguna conexión a la base de datos****.
* La tabla funciona como recurso independiente durante la v1.

****No incluye (y es deliberado — ver [mapa de versiones](../0_mapa_versiones.md)):****

* Relaciones mediante claves foráneas con otras tablas.
* Asociación de una línea de investigación con programas, áreas de conocimiento, términos clave o redes.
* Generación automática de relaciones entre líneas.
* Autenticación de usuarios en el front.
* Lógica de aplicación en JavaScript.
* Frameworks de front.
* Frameworks PHP, Composer o librerías externas.
* Otros motores de base de datos o fábrica de repositorios si corresponden a versiones posteriores.
* Funcionalidades pertenecientes a versiones posteriores.

## 2.3 Alcance — `programa`

****Incluye:****

* CRUD de `programa`: listar, obtener por `id`, crear, reemplazar, actualizar parcialmente y eliminar.
* ****Modelo clásico****: la clase `Programa` representa cada registro mediante propiedades ****privadas**** correspondientes a `id`, `nombre`, `tipo`, `nivel`, fechas, cohortes, graduados, ciudad y facultad, con getters, setters y `toArray()` para construir el JSON.
* ****Identificador propio****: `id` identifica de manera única cada programa académico.
* ****Validación en el controlador****: se valida la información recibida en cada operación HTTP antes de enviarla al servicio.
* ****Reglas propias del recurso****: los datos del programa se validan de acuerdo con la información académica que representa: nombre, tipo, nivel, fechas, cohortes, graduados, ciudad y facultad.
* Capas con interfaces: `IRepositorioPrograma` como contrato PHP, implementada por el repositorio concreto del motor de BD utilizado en la versión.
* Configuración de conexión mediante variables de entorno y PDO con *prepared statements*.
* Endpoint `/api/programa` específico para este recurso.
* Endpoint `GET /api/programa/{id}` para consultar un programa específico.
* Respuestas exclusivamente en **JSON**.
* ****Front `front_php`**** con las pantallas correspondientes a `programa`: inicio, listado, agregar y editar.
* El front se comunica con la API mediante HTTP y ****no abre ninguna conexión a la base de datos****.
* Carga inicial de los **191 registros** definidos para la versión 1.
* `ciudad` y `facultad` se manejan como datos propios del registro y no como claves foráneas en la v1.

****No incluye (y es deliberado — ver [mapa de versiones](../0_mapa_versiones.md)):****

* Relaciones mediante claves foráneas con otras tablas.
* Asociación de un programa con un área de conocimiento, línea de investigación, red o término clave.
* Gestión independiente de ciudades.
* Gestión independiente de facultades.
* Gestión independiente de cohortes.
* Cálculos o procesos externos relacionados con graduados.
* Autenticación de usuarios en el front.
* Lógica de aplicación en JavaScript.
* Frameworks de front.
* Frameworks PHP, Composer o librerías externas.
* Funcionalidades pertenecientes a versiones posteriores.

## 2.4 Alcance — `red`

****Incluye:****

* CRUD de `red`: listar, obtener por `idr`, crear, reemplazar, actualizar parcialmente y eliminar.
* ****Modelo clásico****: la clase `Red` representa cada registro mediante las propiedades ****privadas**** `idr`, `nombre`, `url` y `pais`, con getters, setters y `toArray()` para construir el JSON.
* ****Identificador propio****: `idr` identifica de manera única cada red académica.
* ****Validación en el controlador****: cada operación HTTP valida los datos recibidos antes de enviarlos al servicio.
* ****Reglas propias del recurso****: `nombre` representa el nombre de la red, `url` su dirección web y `pais` el país asociado.
* Capas con interfaces: `IRepositorioRed` como contrato PHP, implementada por el repositorio concreto del motor de BD utilizado en la versión.
* Configuración de conexión mediante variables de entorno y PDO con *prepared statements*.
* Endpoint `/api/red` específico para este recurso.
* Endpoint `GET /api/red/{idr}` para consultar una red específica.
* Respuestas exclusivamente en **JSON**.
* ****Front `front_php`**** con las pantallas correspondientes a `red`: inicio, listado, agregar y editar.
* El front se comunica con la API mediante HTTP y ****no abre ninguna conexión a la base de datos****.
* `url` se almacena como información de la red y no implica una conexión automática con el sitio externo.
* `pais` se maneja como información propia del registro y no como clave foránea en la v1.

****No incluye (y es deliberado — ver [mapa de versiones](../0_mapa_versiones.md)):****

* Relaciones mediante claves foráneas con otras tablas.
* Asociación de una red con programas, líneas de investigación, áreas de conocimiento o términos clave.
* Consumo automático del contenido de las URL almacenadas.
* Validación mediante conexión al sitio externo indicado en `url`.
* Gestión independiente de países.
* Autenticación de usuarios en el front.
* Lógica de aplicación en JavaScript.
* Frameworks de front.
* Frameworks PHP, Composer o librerías externas.
* Funcionalidades pertenecientes a versiones posteriores.


## 3. Requisitos funcionales

### 3.A — La API (`api_facturas`, puerto 8022)

> La v1 utiliza **los cinco verbos HTTP** (GET, POST, PUT, PATCH, DELETE)
> para los cinco recursos. También utiliza las tres vías de envío de datos:
> parámetro de ruta (`/{identificador}`), query string (`?limite=N`) y body
> JSON. La forma concreta de identificar cada recurso depende de su clave
> primaria.
>
> Los cinco recursos de esta versión son:
>
> * `area_conocimiento`
> * `termino_clave`
> * `linea_investigacion`
> * `programa`
> * `red`
>
> Cada recurso posee su propia ruta, controlador, servicio y repositorio.

---

### RF1 — Listar `area_conocimiento`

`GET /api/area_conocimiento` → 200 con la colección de registros.

* Query param opcional `limite` (entero > 0, por defecto 1000).
* La respuesta identifica la tabla y contiene los registros de
  `area_conocimiento`.
* Cada registro contiene `id`, `gran_area`, `area` y `disciplina`.
* Tabla vacía → **204** sin cuerpo.

### RF2 — Obtener `area_conocimiento` por `id`

`GET /api/area_conocimiento/{id}` → 200 con el registro solicitado.

* El parámetro de ruta corresponde al campo `id`.
* Si el registro no existe → **404**.

### RF3 — Crear `area_conocimiento`

`POST /api/area_conocimiento` con body JSON.

El body contiene los campos correspondientes al recurso:

* `gran_area`
* `area`
* `disciplina`

La validación se realiza en el controlador.

* Body válido → **200** con `{estado, mensaje}`.
* Body inválido → **422** con la lista de errores.
* Un `id` generado o suministrado de acuerdo con la definición de la BD no
  debe permitir duplicar un registro existente.

### RF4 — Reemplazar `area_conocimiento`

`PUT /api/area_conocimiento/{id}` con el body completo del recurso.

El body debe contener:

* `gran_area`
* `area`
* `disciplina`

PUT reemplaza el recurso completo.

* Omitir un campo obligatorio → **422**.
* Registro inexistente → **404**.
* Devuelve `filasAfectadas`.

### RF5 — Actualizar parcialmente `area_conocimiento`

`PATCH /api/area_conocimiento/{id}` con body parcial.

* Los campos son opcionales.
* Solo se modifican los campos enviados.
* Cada campo recibido se valida.
* Body vacío → **400**.
* Registro inexistente → **404**.
* Devuelve `filasAfectadas`.

---

### RF6 — Listar `termino_clave`

`GET /api/termino_clave` → 200 con la colección de términos.

* Query param opcional `limite` (entero > 0, por defecto 1000).
* Cada registro contiene `termino` y `termino_ingles`.
* Tabla vacía → **204** sin cuerpo.

### RF7 — Obtener `termino_clave` por término

`GET /api/termino_clave/{termino}` → 200 con el término solicitado.

* El parámetro de ruta corresponde a `termino`, que es la clave primaria.
* Si el término no existe → **404**.

### RF8 — Crear `termino_clave`

`POST /api/termino_clave` con body JSON.

El body contiene:

* `termino`
* `termino_ingles`

La validación se realiza en el controlador.

* Body válido → **200** con `{estado, mensaje}`.
* Body inválido → **422** con la lista de errores.
* Si `termino` ya existe → **500** con el error del motor en `detalle`.

### RF9 — Reemplazar `termino_clave`

`PUT /api/termino_clave/{termino}` con el body completo.

El body debe contener:

* `termino_ingles`

El identificador `termino` pertenece a la ruta.

* Omitir `termino_ingles` → **422**.
* Término inexistente → **404**.
* Devuelve `filasAfectadas`.

### RF10 — Actualizar parcialmente `termino_clave`

`PATCH /api/termino_clave/{termino}` con body parcial.

* Solo se modifica `termino_ingles` si fue enviado.
* El campo recibido se valida.
* Body vacío → **400**.
* Término inexistente → **404**.
* Devuelve `filasAfectadas`.

---

### RF11 — Listar `linea_investigacion`

`GET /api/linea_investigacion` → 200 con la colección de líneas.

* Query param opcional `limite` (entero > 0, por defecto 1000).
* Cada registro contiene `id`, `nombre` y `descripcion`.
* Tabla vacía → **204** sin cuerpo.

### RF12 — Obtener `linea_investigacion` por `id`

`GET /api/linea_investigacion/{id}` → 200 con la línea solicitada.

* El parámetro de ruta corresponde a `id`.
* Si el registro no existe → **404**.

### RF13 — Crear `linea_investigacion`

`POST /api/linea_investigacion` con body JSON.

El body contiene:

* `nombre`
* `descripcion`

El `id` es autoincremental y es generado por la base de datos.

* Body válido → **200** con `{estado, mensaje}`.
* Body inválido → **422** con la lista de errores.

### RF14 — Reemplazar `linea_investigacion`

`PUT /api/linea_investigacion/{id}` con body completo.

El body debe contener:

* `nombre`
* `descripcion`

PUT reemplaza el recurso completo.

* Omitir un campo obligatorio → **422**.
* Registro inexistente → **404**.
* Devuelve `filasAfectadas`.

### RF15 — Actualizar parcialmente `linea_investigacion`

`PATCH /api/linea_investigacion/{id}` con body parcial.

* `nombre` y `descripcion` son opcionales.
* Solo se modifican los campos enviados.
* Cada campo recibido se valida.
* Body vacío → **400**.
* Registro inexistente → **404**.
* Devuelve `filasAfectadas`.

---

### RF16 — Listar `programa`

`GET /api/programa` → 200 con la colección de programas.

* Query param opcional `limite` (entero > 0, por defecto 1000).
* Cada registro contiene los atributos propios de `programa`.
* La respuesta permite identificar el total y los datos devueltos.
* Tabla vacía → **204** sin cuerpo.

Los campos del recurso son:

* `id`
* `nombre`
* `tipo`
* `nivel`
* fechas
* `cohortes`
* `graduados`
* `ciudad`
* `facultad`

### RF17 — Obtener `programa` por `id`

`GET /api/programa/{id}` → 200 con el programa solicitado.

* El parámetro de ruta corresponde a `id`.
* Si el programa no existe → **404**.

### RF18 — Crear `programa`

`POST /api/programa` con body JSON.

El body contiene los atributos propios del programa:

* `nombre`
* `tipo`
* `nivel`
* fechas
* `cohortes`
* `graduados`
* `ciudad`
* `facultad`

La validación se realiza en el controlador.

* Body válido → **200** con `{estado, mensaje}`.
* Body inválido → **422** con la lista de errores.

### RF19 — Reemplazar `programa`

`PUT /api/programa/{id}` con body completo.

El body debe contener todos los campos obligatorios definidos para
`programa`.

* Omitir un campo obligatorio → **422**.
* Programa inexistente → **404**.
* Devuelve `filasAfectadas`.

### RF20 — Actualizar parcialmente `programa`

`PATCH /api/programa/{id}` con body parcial.

* Los campos son opcionales.
* Solo se modifican los campos enviados.
* Cada campo recibido se valida.
* Body vacío → **400**.
* Programa inexistente → **404**.
* Devuelve `filasAfectadas`.

---

### RF21 — Listar `red`

`GET /api/red` → 200 con la colección de redes.

* Query param opcional `limite` (entero > 0, por defecto 1000).
* Cada registro contiene `idr`, `nombre`, `url` y `pais`.
* Tabla vacía → **204** sin cuerpo.

### RF22 — Obtener `red` por `idr`

`GET /api/red/{idr}` → 200 con la red solicitada.

* El parámetro de ruta corresponde a `idr`.
* Si la red no existe → **404**.

### RF23 — Crear `red`

`POST /api/red` con body JSON.

El body contiene:

* `idr`
* `nombre`
* `url`
* `pais`

La validación se realiza en el controlador.

* Body válido → **200** con `{estado, mensaje}`.
* Body inválido → **422** con la lista de errores.
* `idr` duplicado → **500** con el error del motor en `detalle`.

### RF24 — Reemplazar `red`

`PUT /api/red/{idr}` con body completo.

El identificador `idr` pertenece a la ruta.

El body debe contener:

* `nombre`
* `url`
* `pais`

PUT reemplaza el recurso completo.

* Omitir un campo obligatorio → **422**.
* Red inexistente → **404**.
* Devuelve `filasAfectadas`.

### RF25 — Actualizar parcialmente `red`

`PATCH /api/red/{idr}` con body parcial.

* `nombre`, `url` y `pais` son opcionales.
* Solo se modifican los campos enviados.
* Cada campo recibido se valida.
* Body vacío → **400**.
* Red inexistente → **404**.
* Devuelve `filasAfectadas`.

---

### RF26 — Eliminar `area_conocimiento`

`DELETE /api/area_conocimiento/{id}`.

* Elimina el registro identificado por `id`.
* Devuelve `filasEliminadas`.
* Registro inexistente → **404**.

### RF27 — Eliminar `termino_clave`

`DELETE /api/termino_clave/{termino}`.

* Elimina el término identificado por `termino`.
* Devuelve `filasEliminadas`.
* Término inexistente → **404**.

### RF28 — Eliminar `linea_investigacion`

`DELETE /api/linea_investigacion/{id}`.

* Elimina la línea identificada por `id`.
* Devuelve `filasEliminadas`.
* Registro inexistente → **404**.

### RF29 — Eliminar `programa`

`DELETE /api/programa/{id}`.

* Elimina el programa identificado por `id`.
* Devuelve `filasEliminadas`.
* Programa inexistente → **404**.

### RF30 — Eliminar `red`

`DELETE /api/red/{idr}`.

* Elimina la red identificada por `idr`.
* Devuelve `filasEliminadas`.
* Red inexistente → **404**.

---

### RF31 — Diagnóstico

`GET /` → JSON con:

* mensaje de diagnóstico;
* versión (`"v1"`);
* ruta de los contratos.

---

## 3.B — El front (`front_php`, puerto 8020)

> En el front los requisitos se expresan desde lo que la persona ve y hace.
> El usuario no necesita conocer los verbos HTTP, los códigos de estado ni
> las rutas internas de la API.
>
> La traducción entre las acciones de la pantalla y los contratos de la API
> corresponde al front.

### RF32 — Pantallas de `area_conocimiento`

El recurso debe disponer de:

```text
/area_conocimiento
/area_conocimiento/nuevo
/area_conocimiento/{id}/editar
```

La pantalla de listado muestra:

* `id`
* `gran_area`
* `area`
* `disciplina`

La pantalla de creación permite diligenciar:

* `gran_area`
* `area`
* `disciplina`

La pantalla de edición permite modificar los datos del registro.

Cada fila permite eliminar el registro con confirmación.

### RF33 — Pantallas de `termino_clave`

El recurso debe disponer de:

```text
/terminos_clave
/terminos_clave/nuevo
/terminos_clave/{termino}/editar
```

La pantalla de listado muestra:

* `termino`
* `termino_ingles`

La pantalla de creación permite diligenciar el término y su traducción.

La pantalla de edición permite modificar la traducción del término.

Cada fila permite eliminar el término con confirmación.

### RF34 — Pantallas de `linea_investigacion`

El recurso debe disponer de:

```text
/lineas_investigacion
/lineas_investigacion/nuevo
/lineas_investigacion/{id}/editar
```

La pantalla de listado muestra:

* `id`
* `nombre`
* `descripcion`

La pantalla de creación permite diligenciar:

* `nombre`
* `descripcion`

La pantalla de edición permite modificar los datos de la línea.

Cada fila permite eliminar la línea con confirmación.

### RF35 — Pantallas de `programa`

El recurso debe disponer de:

```text
/programas
/programas/nuevo
/programas/{id}/editar
```

La pantalla de listado muestra los atributos principales del programa.

La pantalla de creación permite diligenciar los campos propios de
`programa`.

La pantalla de edición permite modificar los datos del programa.

Cada fila permite eliminar el programa con confirmación.

### RF36 — Pantallas de `red`

El recurso debe disponer de:

```text
/redes
/redes/nuevo
/redes/{idr}/editar
```

La pantalla de listado muestra:

* `idr`
* `nombre`
* `url`
* `pais`

La pantalla de creación permite diligenciar los datos de la red.

La pantalla de edición permite modificar los datos de la red.

Cada fila permite eliminar la red con confirmación.

### RF37 — Estados vacíos

Cuando cualquiera de las cinco tablas no tenga registros:

* La pantalla debe informar que no existen registros.
* Debe ofrecer la posibilidad de crear el primero.
* Una tabla vacía no se considera un error.

### RF38 — Errores de la API

Cuando la API rechace una operación:

* El front conserva los datos que la persona había diligenciado.
* Los errores se presentan en español.
* El usuario no debe visualizar códigos HTTP, rutas de API ni excepciones
  del motor de base de datos.

### RF39 — Front independiente de la API

Con la API apagada:

* Las pantallas del front continúan respondiendo.
* El menú y el marco de la aplicación continúan visibles.
* Se muestra un aviso indicando que el servicio no está disponible.
* No se muestran registros porque el front no puede obtenerlos de la API.

Cuando la API vuelve a estar disponible, las pantallas vuelven a mostrar los
datos sin modificar el código del front.

---

## 4. Requisitos no funcionales

* **RNF1 — Capas estrictas:** el controlador no toca SQL; el servicio no
  conoce HTTP ni el motor; el repositorio no conoce HTTP. Los contratos se
  definen mediante `interface` de PHP.

* **RNF2 — PHP puro:** sin framework, sin Composer y sin `vendor/`.

* **RNF3 — SQL siempre en prepared statements de PDO:** ningún valor recibido
  desde HTTP se concatena directamente al SQL.

* **RNF4 — Errores uniformes:** las respuestas de error utilizan
  `{estado, mensaje, detalle}` y, para los errores de validación, `errores:[…]`.

* **RNF5 — Sin anticipación:** la v1 no incorpora fábrica multi-motor ni
  selección de motor. Esas capacidades corresponden a versiones posteriores.

* **RNF6 — El front no toca la base ni el código de la API:** en
  `front_php` no existe `new PDO(...)` ni un `require` de clases pertenecientes
  a `api_facturas`. Lo único que comparten ambos procesos es el JSON.

* **RNF7 — El front no habla al usuario en jerga técnica:** ninguna pantalla
  muestra verbos HTTP, códigos de estado, rutas internas de la API, SQL, PDO
  ni nombres de motores.

* **RNF8 — Cada recurso posee su contrato independiente:** las reglas,
  atributos e identificadores de `area_conocimiento`, `termino_clave`,
  `linea_investigacion`, `programa` y `red` no se resuelven mediante una ruta
  genérica `/api/{tabla}`.

---

## 5. Criterios de aceptación

1. **`docker compose up -d --build` — un solo comando** deja corriendo la BD,
   la API y el front. `GET /` responde el JSON de diagnóstico y
   `http://localhost:8020` abre la pantalla de inicio.

2. `GET /api/area_conocimiento` devuelve los registros del catálogo y cada
   registro contiene `id`, `gran_area`, `area` y `disciplina`.

3. `GET /api/termino_clave` devuelve los términos con `termino` y
   `termino_ingles`.

4. `GET /api/linea_investigacion` devuelve las líneas con `id`, `nombre` y
   `descripcion`.

5. `GET /api/programa` devuelve los programas con los atributos definidos para
   este recurso.

6. `GET /api/red` devuelve las redes con `idr`, `nombre`, `url` y `pais`.

7. Cada recurso permite completar su ciclo CRUD: crear → consultar →
   reemplazar → actualizar parcialmente → eliminar.

8. En cada recurso, `PUT` exige el cuerpo completo mientras `PATCH` permite
   modificar únicamente los campos enviados.

9. Una consulta a un identificador inexistente responde **404**.

10. Un body que no cumple las reglas de validación responde **422** con la
    lista de errores.

11. Un `PATCH` con body vacío responde **400**.

12. Un listado sin registros responde **204** en la API y el front muestra
    un mensaje comprensible indicando que no existen registros.

13. El servicio puede probarse con un repositorio falso que implemente la
    interfaz correspondiente, sin necesidad de tener la base de datos
    encendida.

14. Cada recurso tiene su propia dirección en el front y sus pantallas
    permiten listar, crear, editar y eliminar sus registros.

15. Ninguna pantalla muestra `PUT`, `PATCH`, `DELETE`, `/api/`, `PDO`,
    `SELECT` ni el nombre del motor de base de datos al usuario.

16. Con la API detenida y la base de datos encendida, el front continúa
    mostrando su estructura y un aviso de servicio no disponible, pero no
    muestra registros.

17. Al volver a encender la API, los datos aparecen nuevamente sin modificar
    el front.

---

## 6. Glosario mínimo

| Término                    | Significado                                                                                                                                       |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Documento**              | Texto que describe qué y cómo construir; de él sale el código.                                                                                    |
| **Artefacto**              | Archivo que se entrega listo y se utiliza tal cual.                                                                                               |
| **Criterio de aceptación** | Prueba verificable que determina si la versión está terminada.                                                                                    |
| **Rebanada vertical**      | Recorte del sistema que atraviesa todas las capas (HTTP → negocio → datos) aunque cubra una sola entidad.                                         |
| **Front controller**       | Único `.php` que recibe las peticiones y las enruta al controlador correspondiente. Lo tienen tanto la API como el front, cada uno en su proceso. |
| **Front**                  | Programa que presenta las pantallas al usuario. No debe confundirse con `front controller`.                                                       |
| **CRUD**                   | Crear, consultar, actualizar y eliminar registros.                                                                                                |
| **PUT**                    | Operación que reemplaza un recurso completo.                                                                                                      |
| **PATCH**                  | Operación que modifica parcialmente un recurso.                                                                                                   |
| **Clave primaria**         | Campo o conjunto de campos que identifica de manera única un registro.                                                                            |
| **Prepared statement**     | Forma de ejecutar SQL mediante parámetros separados de los valores recibidos.                                                                     |


## 7. Definición de TERMINADA

Los 10 criterios pasan → commit + tag `v1` → recién entonces se escribe la spec
de la v2 ([mapa](../0_mapa_versiones.md)).

## 8. Clarificaciones

> **Qué es esta sección:** el registro de las ambigüedades detectadas ANTES
> de planear, con la respuesta que se acordó y su razón. Es **la compuerta
> 1** del método (ver [SDD_SPECKIT](../../../SDD_SPECKIT.md)): mientras
> quede un `[NECESITA ACLARACIÓN: …]` en los requisitos de arriba, esta
> versión no pasa a la planeación.
>
> Las entradas de abajo se reconstruyeron **al cerrar la versión**, a
> partir de las decisiones que sus propios contratos ya dejaban fijadas.
> De aquí en adelante esta sección se llena **en vivo**, antes del
> `3_plan.md` — que es como debe ser.

| # | La pregunta | La respuesta acordada, con su razón | Dónde quedó |
|---|---|---|---|
| C1 | El listado sin filas, ¿es un error o un resultado? | Un resultado: **204 sin cuerpo**. Vacío no es error. | RF de listar · contrato del `GET` |
| C2 | `?limite=0` o negativo, ¿422 o 400? | **400**: la FORMA del dato es correcta (sí es un entero); lo que se rompe es una regla de negocio. El 422 se reserva para el body mal formado. | Contrato del `GET` · convenciones |
| C3 | Crear con una llave que ya existe, ¿409 o 500? | **500**, con el error del motor en `detalle`: la llave la defiende la BD, no la API. Convertirlo en 409 sería lógica de negocio que esta versión no pide. | Convenciones de error · contrato del `POST` |
| C4 | `PATCH` con el body vacío, ¿200 sin hacer nada, o error? | **400**: pedir una actualización sin decir qué actualizar es una regla de negocio rota. | Contrato del `PATCH` |

| C5 | El front y la API son los dos PHP: ¿el front puede usar la clase `area_conocimiento` de la API con un `require_once`? | **No.** Funcionaría, y por eso hay que decirlo explícito: dejarían de ser dos procesos, y renombrar un método adentro de la API rompería la pantalla sin que nadie tocara el contrato. El front trabaja con **arrays**, que es lo que el JSON trae. |
| C6 | Cuando la API no responde, ¿el front muestra una página de error o su pantalla? | **Su pantalla**, con el aviso adentro y sin datos. Una página de error del servidor no demuestra nada; una pantalla en pie con la tabla vacía demuestra que son dos procesos. | RF13 · criterio 10 |
| C8 | ¿Los estilos se traen de un CDN? | **No: se guardan en `front_php/publico/`.** Un CDN es una línea más corta, pero el día que el salón se quede sin internet la pantalla se ve como en 1995 — y eso pasa en clase, no en teoría. Dos archivos en el repositorio lo resuelven para siempre. | `vistas/plantilla.php` · 4_research D13 |
| C7 | El formulario manda texto y la API pide números. ¿Quién convierte? | **El front convierte la FORMA** (`"12"` → `12`) porque un formulario HTML solo produce texto, y mandarlo entre comillas haría que la API lo rechazara **siendo correcto**. Lo que el front NO hace es juzgar el VALOR: si alguien escribió «doce», eso viaja como «doce» y la API dice que no sirve. | `front_php/index.php` · 6_contracts §9 |

**Cómo se escribe una entrada nueva:** la pregunta tal como se hizo (no
"revisar el borrado", sino "¿físico o lógico?"), la respuesta **con su
razón**, y el documento donde quedó plasmada. Si la respuesta cambia un
requisito, se corrige el requisito allá arriba: esta sección lo registra,
no lo reemplaza.
