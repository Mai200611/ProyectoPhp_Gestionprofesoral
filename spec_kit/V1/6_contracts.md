# Contratos — Versión 1: los endpoints de las 5 entidades y las 5 pantallas del catálogo

> **Versión 1** · En PHP puro no hay Swagger automático: **este documento ES
> el contrato publicado** (y el endpoint `/` lo enlaza).
>
> | Parte | Base | Secciones |
> |---|---|---|
> | **La API** (lo que devuelve) | `http://localhost:8022` | §0 a §8 |
> | **El front** (lo que se ve) | `http://localhost:8020` | §9 y §10 |
>
> Las dos partes están en el mismo documento a propósito: la §9 es la
> **tabla de traducción** entre una y otra, y separarlas haría que se
> desincronizaran.

---

## 0. Convenciones (y el HTTP que la v1 enseña)

La v1 usa **los cinco verbos** y las **tres vías de envío de datos**:

| Vía | Dónde viaja | Ejemplo | Se usa para |
|---|---|---|---|
| Parámetro de **ruta** | en la URL, parte del camino | `/api/programa/101` · `/api/termino_clave/IA` | Identificar UN recurso por su PK (`id`, `termino`, `idr`) |
| **Query string** | en la URL, después de `?` | `/api/programa?limite=3` | Opciones de la consulta[cite: 1] |
| **Body** JSON | en el cuerpo de la petición | `{"nombre": "…", "tipo": "Pregrado"}` | Los datos del recurso (POST/PUT/PATCH)[cite: 1] |

| Verbo | Semántica | Endpoint en v1 |
|---|---|---|
| GET | Leer (nunca modifica) | listar · obtener por PK · diagnóstico |
| POST | Crear un recurso nuevo | crear registro en el catálogo |
| PUT | **Reemplazar completo** (todos los campos) | reemplazar registro |
| PATCH | **Actualizar parcial** (solo los enviados) | actualizar campos sueltos |
| DELETE | Eliminar | eliminar registro |

- Todas las respuestas son JSON (`Content-Type: application/json`).
- Errores SIEMPRE con esta envoltura:

```json
{ "estado": 404, "mensaje": "Programa no encontrado.", "detalle": "..." }
```
| Origen | HTTP |
|---|---|
| Body inválido según la **validación del controlador** | **422** con `errores: [ "...", ... ]` |
| Regla de negocio (`limite ≤ 0`, PATCH con body vacío) | 400 |
| Código inexistente (`NoEncontradoExcepcion`) | 404 |
| Error del motor (PK duplicada, BD caída — `PDOException`) | 500 con el mensaje en `detalle` |

## 1.GET /api/{recurso} — Listar (query string)
Aplica para los 5 recursos: area_conocimiento, termino_clave, linea_investigacion, programa, red
Query param opcional: limite (entero > 0, default 1000)
```
GET /api/programa
→ 200 { "tabla": "programa", "limite": 1000, "total": 191,
        "datos": [ { "id": 1, "nombre": "Ingeniería de Sistemas",
                     "tipo": "Pregrado", "nivel": "Universitario", 
                     "graduados": 1200, "ciudad": "Medellín", ... }, … ] }

GET /api/programa?limite=3
→ 200 con exactamente 3 programas (total: 3)
→ 400 si limite ≤ 0
→ 204 (cuerpo vacío) si no hay registros
```

## 2. `GET /api/{recurso}/{pk}` — Obtener uno (parámetro de ruta)

El parámetro depende de la tabla: `id` (Área, Línea, Programa), `termino` (Término Clave), o `idr` (Red).
GET /api/termino_clave/Machine%20Learning
→ 200 { "termino": "Machine Learning", "termino_ingles": "Machine Learning" }
```
GET /api/red/R999
→ 404 { "estado": 404, "mensaje": "Red no encontrada.",
"detalle": "No existe una red con idr = R999" }
```
## 3. `POST /api/{recurso}` — Crear (body completo)

Body (todos obligatorios según el recurso; los valida el controlador)
```
POST /api/linea_investigacion
body { "nombre": "Inteligencia Artificial", "descripcion": "Línea enfocada en IA y ML" }
→ 200 { "estado": 200, "mensaje": "Línea de investigación creada exitosamente." }

→ 422 { "estado": 422, "mensaje": "Datos inválidos.",
"errores": [ "El campo descripcion es obligatorio." ] }
→ 500 si la clave primaria ya existe (error del motor en detalle)
```
## 4. `PUT /api/{recurso}/{pk}` — Reemplazar completo

Body: todos los campos editables del recurso — **todos obligatorios** (la PK va en la ruta y no cambia). Valida `validarReemplazo`
```
PUT /api/red/R01
body { "nombre": "Red de Investigación Avanzada", "url": "https://red-ia.org", "pais": "Colombia" }
→ 200 { "estado": 200, "mensaje": "Red reemplazada exitosamente.",
"filasAfectadas": 1 }
→ 422 si falta CUALQUIER campo — PUT reemplaza el recurso entero,
no existe "dejar el que estaba"
→ 404 si el idr no existe
```
## 5. `PATCH /api/{recurso}/{pk}` — Actualizar parcial

Body: los mismos campos del recurso pero **todos opcionales** — solo se modifican los enviados (cada uno validado si llega, con `validarParcial`)
```
PATCH /api/programa/15      body { "ciudad": "Bogotá", "graduados": 1500 }
→ 200 { "estado": 200, "mensaje": "Programa actualizado exitosamente.",
"filasAfectadas": 1 }
→ 400 si el body viene vacío (no hay nada que actualizar)
→ 422 si algún campo enviado viola la validación (ej. graduados < 0)
→ 404 si el id no existe
```
**El contraste didáctico:** el body `{ "ciudad": "Bogotá" }` en PATCH es 200; ese mismo body en PUT es 422 (le faltan `nombre`, `tipo`, `nivel`, etc.).

## 6. `DELETE /api/{recurso}/{pk}` — Eliminar
```
DELETE /api/area_conocimiento/45
→ 200 { "estado": 200, "mensaje": "Área de conocimiento eliminada exitosamente.",
"filasEliminadas": 1 }
→ 404 si el id no existe
```

## 7. `GET /` — Diagnóstico
```
→ 200 { "mensaje": "API Gestión funcionando", "version": "v1",
"contratos": "docs/spec_kit/versiones/v1_catalogo_mariadb/6_contracts.md" }
```
## 8. Estabilidad de este contrato

Estos 7 endpoints para las **5 entidades** no cambian en las versiones siguientes: v2 agrega entidades nuevas (rutas nuevas con este mismo patrón), v3/v4 cambian el motor por configuración (PostgreSQL / SQL Server) — si algún cambio futuro rompiera este contrato, es una decisión mayor que debe quedar registrada en la spec de esa versión[cite: 1].

Y hay una consecuencia que ahora se puede comprobar: **v3 y v4 no deberían
tocar ni una línea del front.** Cambiar de motor sin que la pantalla se entere
es la promesa fuerte del Artículo 5, y hasta ahora era imposible de verificar
porque no había pantalla.

---

# El front (`http://localhost:8020`)

## 9. Las cinco pantallas, y la traducción

Aquí no hay verbos ni códigos de estado: **el usuario no sabe qué es un 422**.
Esta tabla es el contrato de la pantalla para CADA UNA de las 5 entidades (`programas`, `redes`, `terminos`, `lineas`, `areas`). Leída de izquierda a derecha, es también la lista de lo que el front traduce.

*(Nota: `{recursos}` representa la ruta en plural en el front, `{recurso}` la ruta singular en la API, y `{pk}` el identificador `id`, `termino` o `idr`)*.

| Dirección | Qué ve la persona | Qué le pide a la API |
|---|---|---|
| `GET /` | El inicio, con el menú de los 5 catálogos | *(nada)* |
| `GET /{recursos}` | La tabla con los campos (ej. gran_area, area, disciplina) | `GET /api/{recurso}?limite=1000` |
| `GET /{recursos}/nuevo` | El formulario vacío | *(nada)* |
| `POST /{recursos}/nuevo` | Vuelve al listado con «Se agregó…», o el formulario con lo escrito y los motivos | `POST /api/{recurso}` |
| `GET /{recursos}/{pk}/editar` | El formulario con la ficha; la **PK de solo lectura** | `GET /api/{recurso}/{pk}` |
| `POST /{recursos}/{pk}/editar` con **«Guardar la ficha completa»** | Vuelve al listado con «Se guardó…» | `PUT /api/{recurso}/{pk}` con **todos** los campos |
| `POST /{recursos}/{pk}/editar` con **«Guardar solo lo que cambié»** | Igual | `PATCH /api/{recurso}/{pk}` con **solo lo diligenciado**|
| `POST /{recursos}/{pk}/eliminar` | Confirma, y vuelve al listado con «Se eliminó…» | `DELETE /api/{recurso}/{pk}` |
| `GET /publico/…` | Las hojas de estilo y el guion de los avisos | *(nada: los sirve el propio front desde el disco)* |
| cualquier otra | «Esa página no existe» **con el marco de la aplicación** | *(nada) |

**Fíjese en las dos filas del medio.** Son la misma dirección y el mismo formulario; lo único distinto es el botón que se oprimió — y de ahí sale un PUT o un PATCH. La diferencia entre el guardado completo o parcial no está en una regla de negocio: está en **qué se envía**.

**Y en la última:** un `POST` para eliminar, no un enlace. Un enlace que borra lo puede disparar el navegador solo al precargar la página.

## 10. Cómo se traduce lo que responde la API

Un solo archivo del front (`cliente_api.php`) conoce esta tabla. Ninguna vista la conoce, y por eso el día que la API cambie el sobre se cambia en un sitio.

| Lo que responde la API | Lo que hace el front | Lo que ve la persona |
|---|---|---|
| `200` con `{tabla, limite, total, datos}` | Se queda con `datos` | La tabla llena |
| **`204`** (tabla vacía) | `ok = true` con la lista **vacía** | «Todavía no hay registros» y el botón de agregar |
| `200` de una escritura | `ok = true` | El aviso verde en el listado |
| `400` / `404` / `500` con `{estado, mensaje, detalle}` | Junta `mensaje` y `detalle` | El aviso rojo, en español|
| `422` con `{estado, mensaje, errores:[…]}` | Se queda con la lista `errores` | Los motivos, uno por línea, junto al formulario |
| **Nada** (la API no responde) | `null`, que no es lo mismo que un error | «El servicio no está disponible» — y la pantalla sigue en pie |

**Las dos filas en negrita son las que se equivocan casi siempre:

- Un **204 no es un error**: es la API diciendo que no hay filas[cite: 1]. Confundirlo con un fallo es el error más común al consumir una API.
- **No responder no es responder mal.** Un 404 es la API funcionando y diciendo que ese registro no existe; que no llegue nada es que no hay con quién hablar.
 De esa distinción sale el aviso del criterio 10, y por eso `llamar_api()` devuelve `null` en ese caso y no un código inventado