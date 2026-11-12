# Contratos — Versión 3: los mismos, y eso es el punto

> **Versión 3** · En PHP puro no hay Swagger automático: **este documento ES
> el contrato publicado** (y el endpoint `/` lo enlaza).
>
> ## Lea esto antes de seguir
>
> **Este documento es, casi palabra por palabra, el de la versión 2.** Los
> endpoints, los códigos de estado, los formatos y las pantallas no se
> movieron al agregar el segundo motor.
>
> Y no es pereza de quien lo escribió: **es el resultado**. Si el contrato
> hubiera tenido que cambiar para que PostgreSQL entrara, el motor se habría
> estado filtrando hasta la frontera HTTP — y la arquitectura de las dos
> versiones anteriores habría estado mal cortada.
>
> Lo único que cambió está en §3: `GET /` ahora dice qué motor está activo.
>
> | Parte | Base | Secciones |
> |---|---|---|
> | **La API** (lo que devuelve) | `http://localhost:8122` | §0 a §5 |
> | **El front** (lo que se ve) | `http://localhost:8120` | §6 y §7 |
>
> Las dos partes están en el mismo documento a propósito: la §7 es la tabla
> de traducción entre una y otra, y separarlas haría que se desincronizaran.

---

## 0. Convenciones

### 0.1 Las tres vías de envío, y los cinco verbos

| Vía | Dónde viaja | Ejemplo | Se usa para |
|---|---|---|---|
| Parámetro de **ruta** | en la URL, parte del camino | `/api/docente/12345678` | Identificar UNA ficha |
| **Query string** | en la URL, después de `?` | `/api/programa?limite=3` | Opciones de la consulta |
| **Body** JSON | en el cuerpo de la petición | `{"nombre": "…"}` | Los datos de la ficha |

| Verbo | Semántica | Dónde se usa en la v3 |
|---|---|---|
| GET | Leer (nunca modifica) | listar · obtener · diagnóstico |
| POST | Crear | crear una ficha |
| PUT | **Reemplazar completo** | todas las fichas |
| PATCH | **Actualizar parcial** | todas las fichas — **el docente NO** |
| DELETE | Eliminar | todas (con restricción de dependencias) |

### 0.2 La envoltura de los listados

```json
{ "tabla": "docente", "limite": 1000, "total": 191, "datos": [ … ] }
```
Y **204 sin cuerpo** cuando la tabla está vacía. Un 204 no es un error: es la
respuesta correcta a «no hay filas».

### 0.3 El sobre de los errores

```json
{ "estado": 404, "mensaje": "Docente no encontrado.", "detalle": "…" }
{ "estado": 422, "mensaje": "Datos inválidos.", "errores": [ "…", "…" ] }
```

| Origen | HTTP |
|---|---|
| Body inválido según la validación del controlador | **422** con `errores: [ … ]` |
| Regla de negocio (`limite ≤ 0`, PATCH con body vacío, id no numérico) | 400 |
| La ficha pedida no existe | 404 |
| **La base rechaza por integridad** (llave repetida, referencia rota, dependencias) | **409** ← nuevo en la v2 |
| El verbo no aplica a esa ruta | 405 |
| Error del motor o falla real | 500 |

**El 409 es lo nuevo, y la diferencia con el 500 es la que importa:** un 500
dice «se nos dañó algo»; un 409 dice «su petición está bien escrita, pero no
cabe en los datos que hay». En la v1 estos casos daban 500, y estaba bien
decidido para esa versión — el porqué del cambio está en
[4_research.md](4_research.md) D1.

### 0.4 Los campos de cada ficha, en el nombre EXACTO del JSON

Esta tabla es la que hace falta para reconstruir el sistema sin leer el
código: dice cómo se llama cada campo **en el JSON**, que no siempre es obvio.

| Recurso | Llave | La escribe | Campos del body |
|---|---|---|---|
| `producto` | `codigo` | el cliente | `nombre` texto(100) · `stock` entero ≥ 0 · `valorunitario` número ≥ 0 |
| `empresa` | `codigo` | el cliente | `nombre` texto(100) |
| `persona` | `codigo` | el cliente | `nombre` texto(100) · `email` texto(100) · `telefono` texto(20) |
| `cliente` | `id` | **la base** | `credito` número · `fkcodpersona` texto(10) · `fkcodempresa` texto(10) *(opcional, acepta `null`)* |
| `vendedor` | `id` | **la base** | `carnet` entero · `direccion` texto(100) · `fkcodpersona` texto(10) |
| `factura` | `numero` | **la base** | `fkidcliente` entero · `fkidvendedor` entero · `detalle` lista de `{codigo, cantidad}` |

**Lo que NINGÚN body lleva, aunque exista en la tabla:** `total` y `subtotal`
de las facturas (los calcula un trigger), `estado` (lo cambia la operación de
anular) y `fecha` (la pone la base). Si llegan, se ignoran.

---

## 1. `/api/area_conocimiento — Áreas de conocimiento

```
GET /api/area_conocimiento[?limite=N]
→ 200 { "tabla": "area_conocimiento", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si la tabla está vacía
→ 400 si limite <= 0

GET /api/area_conocimiento/1A01
→ 200 { "id": …, "gran_area": …, "area": …, "disciplina": … }
→ 404 si no existe

POST /api/area_conocimiento
body { "id": "1A01", "gran_area": "Ciencias Naturales", "area": "Matemáticas", "disciplina": "Matemáticas puras" }
→ 200 { "estado": 200, "mensaje": "Área creada exitosamente." }
→ 422 con la lista de errores si el body no cumple

PUT /api/area_conocimiento/1A01      body con TODOS los campos (menos id)
→ 200 { "estado": 200, "mensaje": "…", "filasAfectadas": 1 }
→ 422 si falta un obligatorio · 404 si la llave no existe

PATCH /api/area_conocimiento/1A01    body con SOLO los campos a cambiar
→ 200 · 400 si el body viene vacío · 404 si no existe

DELETE /api/area_conocimiento/1A01
→ 200 { "estado": 200, "mensaje": "…", "filasEliminadas": 1 }
→ 404 si no existe
```
- **409** si la llave ya existe.
- **409** al ELIMINAR, si otras fichas dependen de esta área.

## 2. `/api/termino_clave — Términos clave

```
GET /api/termino_clave[?limite=N]
→ 200 { "tabla": "termino_clave", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si la tabla está vacía
→ 400 si limite <= 0

GET /api/termino_clave/Machine%20Learning
→ 200 { "termino": …, "termino_ingles": … }
→ 404 si no existe

POST /api/termino_clave
body { "termino": "Machine Learning", "termino_ingles": "Machine Learning" }
→ 200 { "estado": 200, "mensaje": "Término creado exitosamente." }
→ 422 con la lista de errores si el body no cumple

PUT /api/termino_clave/Machine%20Learning      body con termino_ingles
→ 200 { "estado": 200, "mensaje": "…", "filasAfectadas": 1 }
→ 422 si falta · 404 si no existe

PATCH /api/termino_clave/Machine%20Learning    body con SOLO termino_ingles
→ 200 · 400 si vacío · 404 si no existe

DELETE /api/termino_clave/Machine%20Learning
→ 200 { "estado": 200, "mensaje": "…", "filasEliminadas": 1 }
→ 404 si no existe
```
- **409** si la llave ya existe.
- **409** al ELIMINAR, si otros términos dependen (ej. intereses_futuros).

## 3. `/api/linea_investigacion — Líneas de investigación

```
GET /api/linea_investigacion[?limite=N]
→ 200 { "tabla": "linea_investigacion", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si vacía
→ 400 si limite <= 0

GET /api/linea_investigacion/1
→ 200 { "id": …, "nombre": …, "descripcion": … }
→ 404 si no existe

POST /api/linea_investigacion
body { "nombre": "Inteligencia Artificial", "descripcion": "Línea enfocada en IA y ML" }
→ 200 { "estado": 200, "mensaje": "Línea creada exitosamente.", "id": N }   ← id generado por la base
→ 422 si el body no cumple

PUT /api/linea_investigacion/1      body con nombre y descripcion
→ 200 { … "filasAfectadas": 1 }
→ 422 si falta · 404 si no existe

PATCH /api/linea_investigacion/1    body con SOLO los campos a cambiar
→ 200 · 400 si vacío · 404 si no existe

DELETE /api/linea_investigacion/1
→ 200 { … "filasEliminadas": 1 }
→ 404 si no existe
```
- **409** si la llave ya existe (no aplica, es autoincremental).
- **409** al ELIMINAR, si hay docentes que la usan como línea principal.

## 4. `/api/programa — Programas académicos

```
GET /api/programa[?limite=N]
→ 200 { "tabla": "programa", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si vacía · 400 si limite <= 0

GET /api/programa/10101
→ 200 { "id": …, "nombre": …, "tipo": …, "nivel": …, "fecha_creacion": …, "fecha_cierre": …, "numero_cohortes": …, "cant_graduados": …, "fecha_actualizacion": …, "ciudad": …, "facultad": … }
→ 404 si no existe

POST /api/programa
body { "id": 10101, "nombre": "…", "tipo": "…", "nivel": "…", "fecha_creacion": "…", "fecha_cierre": null, "numero_cohortes": "…", "cant_graduados": "…", "fecha_actualizacion": "…", "ciudad": "…", "facultad": 101 }
→ 200 { "estado": 200, "mensaje": "Programa creado exitosamente." }
→ 422 si falta un obligatorio (fecha_cierre puede ser null)

PUT /api/programa/10101      body con TODOS los campos (excepto id)
→ 200 { … "filasAfectadas": 1 }
→ 422 si falta · 404 si no existe

PATCH /api/programa/10101    body con SOLO los campos a cambiar
→ 200 · 400 si vacío · 404 si no existe

DELETE /api/programa/10101
→ 200 { … "filasEliminadas": 1 }
→ 404 si no existe
```
- **409**si el id ya existe.
- **409** al ELIMINAR, si hay docente_departamento que lo referencia.

## 5. `/api/red — Redes académicas

```
GET /api/red[?limite=N]
→ 200 { "tabla": "red", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si vacía · 400 si limite <= 0

GET /api/red/1
→ 200 { "idr": …, "nombre": …, "url": …, "pais": … }
→ 404 si no existe

POST /api/red
body { "idr": 1, "nombre": "Red de Investigación", "url": "https://…", "pais": "Colombia" }
→ 200 { "estado": 200, "mensaje": "Red creada exitosamente." }
→ 422 si falta · 409 si idr ya existe

PUT /api/red/1      body con TODOS los campos (excepto idr)
→ 200 { … "filasAfectadas": 1 }
→ 422 si falta · 404 si no existe

PATCH /api/red/1    body con SOLO los campos a cambiar
→ 200 · 400 si vacío · 404 si no existe

DELETE /api/red/1
→ 200 { … "filasEliminadas": 1 }
→ 404 si no existe
```
- **409** al ELIMINAR, si hay red_docente que la referencia.



## 6. `/api/docente — Docentes (maestro-detalle)
El docente es el único recurso que no admite PATCH. Además, su POST/PUT
manejan el detalle (estudios, intereses, redes) en el mismo body
```
GET /api/docente[?limite=N]
→ 200 { "tabla": "docente", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si vacía · 400 si limite <= 0

GET /api/docente/12345678
→ 200 { "cedula": …, "nombre_completo": …, "url_cvlac": …, "escalafon": …, "categoria_minciencias": …, "perfil": …, "linea_investigacion_principal": … }
→ 404 si no existe

GET /api/docente/12345678/completo
→ 200 { "cedula": …, "nombre_completo": …, …, "estudios": [ … ], "intereses": [ … ], "redes": [ … ], "evaluaciones": [ … ], "reconocimientos": [ … ], "experiencia": [ … ] }
→ 404 si no existe

POST /api/docente
body { "cedula": "12345678", "nombre_completo": "…", "linea_investigacion_principal": 5,
       "estudios": [ { "titulo": "…", "universidad": "…", "tipo": "…" }, … ],
       "intereses": [ { "termino": "…" }, … ],
       "redes": [ { "idr": 5, "actividades_destacadas": "…" }, … ] }
→ 200 { "estado": 200, "mensaje": "Docente creado exitosamente.", "datos": { …docente completo… } }
→ 422 si falta un campo del docente o de alguna lista (indica cuál elemento)
→ 409 si la cédula ya existe o si alguna FK no existe

PUT /api/docente/12345678      body con TODOS los campos y el detalle (reemplaza entero)
→ 200 { "estado": 200, "mensaje": "Docente reemplazado exitosamente." }
→ 422 si falta · 404 si no existe · 409 si FK inválida

PATCH /api/docente/12345678    → 405 Método no permitido

DELETE /api/docente/12345678
→ 200 si no tiene dependencias
→ 409 si tiene estudios, evaluaciones, reconocimientos o experiencia
→ 404 si no existe
```
El detalle de estudios en el POST/PUT se ofrece en cinco renglones fijos
(los vacíos se ignoran). Es una limitación del front, no de la API.

## 7. `/api/estudios_realizados — Estudios realizados
```
GET /api/estudios_realizados[?limite=N]
→ 200 { "tabla": "estudios_realizados", "limite": 1000, "total": N, "datos": [ … ] }
→ 204 si vacía · 400 si limite <= 0

GET /api/estudios_realizados/5
→ 200 { "id": …, "titulo": …, "universidad": …, "fecha": …, "tipo": …, "cedula": … }
→ 404 si no existe

POST /api/estudios_realizados
body { "titulo": "Ingeniería", "universidad": "UIS", "fecha": "2020-01-01", "tipo": "Pregrado", "cedula": "123" }
→ 200 { "estado": 200, "mensaje": "Estudio creado exitosamente.", "id": N }
→ 422 si falta · 409 si la cédula no existe

PUT /api/estudios_realizados/5      body con TODOS los campos (excepto id)
→ 200 { … "filasAfectadas": 1 }
→ 422 si falta · 404 si no existe · 409 si FK inválida

PATCH /api/estudios_realizados/5    body con SOLO los campos a cambiar
→ 200 · 400 si vacío · 404 si no existe

DELETE /api/estudios_realizados/5
→ 200 { … "filasEliminadas": 1 }
→ 404 si no existe
```
- **409** al crear con cedula inexistente.
- **409** al ELIMINAR, si hay apoyo_profesoral o beca que dependen de este estudio.

## 8. '/api/docente_departamento — Vinculaciones docente↔programa

```
GET /api/docente_departamento[?limite=N]
→ 200 { … "datos": [ … ] } → 204 si vacía · 400 si limite <= 0

GET /api/docente_departamento/123/101
→ 200 { "cedula": …, "id_programa": …, "dedicacion": …, "modalidad": …, "fecha_inicio": …, "fecha_fin": … }
→ 404 si no existe

POST /api/docente_departamento
body { "cedula": "123", "id_programa": 101, "dedicacion": "Tiempo completo", "modalidad": "Presencial", "fecha_inicio": "2020-01-01", "fecha_fin": null }
→ 200 { "estado": 200, "mensaje": "Vinculación creada exitosamente." }
→ 422 si falta · 409 si FK inválida

PUT /api/docente_departamento/123/101      body con TODOS los campos editables
→ 200 { … "filasAfectadas": 1 }

PATCH /api/docente_departamento/123/101    body con SOLO los campos a cambiar
→ 200 · 400 si vacío · 404 si no existe

DELETE /api/docente_departamento/123/101
→ 200 { … "filasEliminadas": 1 }
```
---

## 9. '/api/intereses_futuros — Intereses (puente docente↔término))
```
GET /api/intereses_futuros[?limite=N] → 200 { … "datos": [ … ] } · 204 · 400
GET /api/intereses_futuros/123/machine_learning → 200 { "cedula": …, "termino": … } · 404
POST /api/intereses_futuros body { "cedula": "123", "termino": "machine_learning" }
→ 200 { "estado": 200, "mensaje": "Interés creado exitosamente." }
→ 422 si falta · 409 si FK inválida
DELETE /api/intereses_futuros/123/machine_learning → 200 { … "filasEliminadas": 1 }
```

## 10. '/api/evaluacion_docente — Evaluaciones docentes

```
GET /api/evaluacion_docente[?limite=N] → 200 · 204 · 400
GET /api/evaluacion_docente/5 → 200 { "id": …, "semestre": …, "calificacion": …, "cedula": … } · 404
POST /api/evaluacion_docente body { "semestre": "2024-1", "calificacion": 4.5, "cedula": "123" }
→ 200 { …, "id": N } · 422 · 409 si cédula no existe
PUT /api/evaluacion_docente/5 body con TODOS los campos (excepto id) → 200 · 422 · 404
PATCH /api/evaluacion_docente/5 body parcial → 200 · 400 · 404
DELETE /api/evaluacion_docente/5 → 200 { … "filasEliminadas": 1 } · 404
```

## 11. '/api/reconocimiento — Reconocimientos

```
GET /api/reconocimiento[?limite=N] → 200 · 204 · 400
GET /api/reconocimiento/5 → 200 { "id": …, "tipo": …, "fecha": …, "institucion": …, "ambito": …, "cedula": … } · 404
POST /api/reconocimiento body { "tipo": "Premio", "fecha": "2024-05-01", "institucion": "UIS", "ambito": "Nacional", "cedula": "123" }
→ 200 { …, "id": N } · 422 · 409 si cédula no existe
PUT /api/reconocimiento/5 body con TODOS los campos → 200 · 422 · 404
PATCH /api/reconocimiento/5 body parcial → 200 · 400 · 404
DELETE /api/reconocimiento/5 → 200 { … "filasEliminadas": 1 } · 404
```

## 12. '/api/experiecia — Experiencia laboral (errata histórica)

```
GET /api/experiecia[?limite=N] → 200 · 204 · 400
GET /api/experiecia/5 → 200 { "id": …, "cargo": …, "institucion": …, "tipo": …, "fecha_inicio": …, "fecha_fin": …, "cedula": … } · 404
POST /api/experiecia body { "cargo": "Profesor", "institucion": "UIS", "tipo": "Docencia", "fecha_inicio": "2020-01-01", "fecha_fin": null, "cedula": "123" }
→ 200 { …, "id": N } · 422 · 409 si cédula no existe
PUT /api/experiecia/5 body con TODOS los campos → 200 · 422 · 404
PATCH /api/experiecia/5 body parcial → 200 · 400 · 404
DELETE /api/experiecia/5 → 200 { … "filasEliminadas": 1 } · 404
```
## 13. '/api/red_docente — Vínculos red↔docente

```
GET /api/red_docente[?limite=N] → 200 · 204 · 400
GET /api/red_docente/1/123 → 200 { "idr": …, "cedula": …, "fecha_vinculacion": …, "actividades_destacadas": … } · 404
POST /api/red_docente body { "idr": 1, "cedula": "123", "fecha_vinculacion": "2024-01-01", "actividades_destacadas": "…" }
→ 200 · 422 · 409 si FK inválida
PUT /api/red_docente/1/123 body con TODOS los campos editables → 200 · 422 · 404
PATCH /api/red_docente/1/123 body parcial → 200 · 400 · 404
DELETE /api/red_docente/1/123 → 200 { … "filasEliminadas": 1 } · 404
```
## 14. '/api/estudio_ac — Puente estudio↔área

```
GET /api/estudio_ac[?limite=N] → 200 · 204 · 400
GET /api/estudio_ac/5/1A01 → 200 { "id_estudio": …, "id_area": … } · 404
POST /api/estudio_ac body { "id_estudio": 5, "id_area": "1A01" }
→ 200 · 422 · 409 si FK inválida
DELETE /api/estudio_ac/5/1A01 → 200 { … "filasEliminadas": 1 } · 404
```
## 15. '/api/apoyo_profesoral — Apoyos por estudio

```
GET /api/apoyo_profesoral[?limite=N] → 200 · 204 · 400
GET /api/apoyo_profesoral/5/beca → 200 { "id_estudio": …, "tipo_apoyo": …, "institucion": …, "monto": … } · 404
POST /api/apoyo_profesoral body { "id_estudio": 5, "tipo_apoyo": "Beca", "institucion": "UIS", "monto": 500000 }
→ 200 · 422 · 409 si FK inválida
PUT /api/apoyo_profesoral/5/beca body con TODOS los campos editables → 200 · 422 · 404
PATCH /api/apoyo_profesoral/5/beca body parcial → 200 · 400 · 404
DELETE /api/apoyo_profesoral/5/beca → 200 { … "filasEliminadas": 1 } · 404
```

## 16. '/api/beca — Becas por estudio

```
GET /api/beca[?limite=N] → 200 · 204 · 400
GET /api/beca/5/Excelencia → 200 { "id_estudio": …, "tipo_beca": …, "institucion": …, "fecha_inicio": …, "fecha_fin": … } · 404
POST /api/beca body { "id_estudio": 5, "tipo_beca": "Excelencia", "institucion": "UIS", "fecha_inicio": "2024-01-01", "fecha_fin": "2024-12-31" }
→ 200 · 422 · 409 si FK inválida
PUT /api/beca/5/Excelencia body con TODOS los campos editables → 200 · 422 · 404
PATCH /api/beca/5/Excelencia body parcial → 200 · 400 · 404
DELETE /api/beca/5/Excelencia → 200 { … "filasEliminadas": 1 } · 404
```
## 17. Los 405 que salen gratis

Ninguna ruta declara qué verbos NO admite. El 405 aparece solo, porque el
enrutador tiene un `else` al final de cada bloque:

| Petición | Respuesta | Por qué |
|---|---|---|
| `PUT /api/docente` (la colección) | 405 | Reemplazar «todos los docentes» no significa nada |
| `PATCH /api/docente/123` | 405 | El docente no se actualiza por partes |
| `POST /api/docente/123/completo` | 405 | El detalle se crea con el docente, no aparte |

La lista de verbos que un recurso admite **es** la lista de sus `elseif`.

---

# El front (`http://localhost:8120`)

## 18. GET / — Diagnóstico (LO ÚNICO QUE CAMBIÓ)

```
→ 200 { "mensaje": "API Gestión funcionando", "version": "v3",
        "motor": "mariadb",   ← NUEVO en la v3: "mariadb" o "postgres"
        "recursos": [
          "area_conocimiento", "termino_clave", "linea_investigacion",
          "programa", "red", "docente", "estudios_realizados",
          "docente_departamento", "intereses_futuros", "evaluacion_docente",
          "reconocimiento", "experiecia", "red_docente", "estudio_ac",
          "apoyo_profesoral", "beca"
        ],
        "contratos": "docs/spec_kit/versiones/v3_segundo_motor/6_contracts.md" }
```
Es el único sitio de toda la API donde se menciona el motor. Ninguna otra
respuesta lo nombra ni cambia de forma según cuál sea — compruébelo pidiendo
cualquier recurso con los dos y comparando las respuestas.

## 19. Estabilidad de este contrato: la promesa se cumplió
La v2 cerraba con esta frase:

«Cambiar de motor no debería tocar ni una línea de este documento. Si la v3
tiene que cambiar un contrato para que PostgreSQL funcione, es que el motor
se había filtrado hacia arriba.»

Se cumplió. Lo único que se agregó fue el campo motor del diagnóstico, y
ni siquiera hacía falta para que el sistema funcionara: se puso para poder
comprobar la promesa.

Compruébelo usted mismo:

```
docker compose up -d --build
curl.exe http://localhost:8122/api/docente/1/completo > mariadb.json

$env:DB_MOTOR = "postgres"; docker compose up -d --no-deps --force-recreate api-gestion
curl.exe http://localhost:8122/api/docente/1/completo > postgres.json

Compare-Object (Get-Content mariadb.json) (Get-Content postgres.json)
```
El front (http://localhost:8120)

## 20. Las pantallas del front

Las pantallas de los 16 recursos **siguen existiendo y funcionando igual que
en la v2**. La única diferencia visual es una **etiqueta en el pie** que dice
el motor activo.

| Dirección | Qué ve la persona | Qué le pide a la API |
|---|---|---|
| `GET /` | El inicio con las 16 secciones | *(nada)* |
| `GET /{recurso}` | La tabla del recurso | `GET /api/{recurso}?limite=1000` |
| `GET /{recurso}/nuevo` | El formulario vacío | los catálogos, si tiene FKs |
| `POST /{recurso}/nuevo` | Vuelve al listado con «Se agregó», o el formulario con lo escrito y los motivos | `POST /api/{recurso}` |
| `GET /{recurso}/{llave}/editar` | El formulario con la ficha | `GET /api/{recurso}/{llave}` |
| `POST …/editar` con **«Guardar la ficha completa»** | Vuelve al listado | `PUT` con **todos** los campos |
| `POST …/editar` con **«Guardar solo lo que cambié»** | Igual | `PATCH` con **solo lo diligenciado** |
| `POST /{recurso}/{llave}/eliminar` | Confirma y vuelve al listado | `DELETE /api/{recurso}/{llave}` |
| `GET /docentes/{cedula}` | El detalle del docente | `GET /api/docente/{cedula}/completo` |
| `GET /publico/…` | Las hojas de estilo | *(las sirve el propio front desde el disco)* |
| cualquier otra | «Esa página no existe», con el marco puesto | *(nada)* |

**La excepción de la v3: el pie de página dice el motor.** Es la única
mención de un motor en toda la interfaz. Aparece en todas las pantallas, una
sola vez, y no hay ninguna otra parte del HTML que lo nombre.

## 21. Cómo traduce el front lo que responde la API

Un solo archivo del front (`cliente_api.php`) conoce esta tabla. Ninguna
vista la conoce, y por eso el día que la API cambie el sobre se cambia en un
sitio.

| Lo que responde la API | Lo que hace el front | Lo que ve la persona |
|---|---|---|
| `200` con `{tabla, limite, total, datos}` | Se queda con `datos` | La tabla llena |
| **`204`** (tabla vacía) | `ok = true` con la lista **vacía** | «Todavía no hay …» y el botón de agregar |
| `200` de una escritura | `ok = true` | El aviso verde en el listado |
| `400` / `404` / `500` con `{estado, mensaje, detalle}` | Junta `mensaje` y `detalle` | El aviso rojo, en español |
| **`409`** con su `detalle` | **Lo mismo que un 400** | «No se puede eliminar el docente porque hay otras fichas que dependen de ella» |
| `422` con `{estado, mensaje, errores:[…]}` | Se queda con la lista `errores` | Los motivos, uno por línea |
| **Nada** (la API no responde) | `null`, que no es lo mismo que un error | «El servicio no está disponible» — y la pantalla sigue en pie |

**Fíjese en la fila del 409: el front NO la distingue de un 400.** Para la
persona que está usando la pantalla, lo que importa es el texto —«hay otras
fichas que dependen de ella»—, no el número.

**La etiqueta del motor en el pie no se traduce**: se muestra tal cual llega
del `GET /` de la API. Es un dato de diagnóstico, no un mensaje de negocio.

