# Contratos — Versión 2: los 11 recursos con FK y el docente maestro-detalle

> **Versión 2** · En PHP puro no hay Swagger automático: **este documento ES
> el contrato publicado** (y el endpoint `/` lo enlaza).
>
> | Parte | Base | Secciones |
> |---|---|---|
> | **La API** (lo que devuelve) | `http://localhost:8022` | §0 a §8 |
> | **El front** (lo que se ve) | `http://localhost:8020` | §9 y §10 |
>
> Este documento cubre **los 11 recursos nuevos**. Los 5 recursos de la v1
> (`area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa`,
> `red`) no cambian ni una línea y se documentan en `../v1_catalogo_mariadb/6_contracts.md`.

---

## 0. Convenciones (y el HTTP que la v2 enseña)

La v2 usa los mismos verbos de la v1, pero agrega una regla nueva al
vocabulario: **el 409 (Conflicto)**. No es un error del cliente (422) ni una
caída del sistema (500). Es la base de datos diciendo «su petición no cabe en
los datos que hay»: una llave repetida, una FK que apunta a algo que no
existe, o un borrado que dejaría fichas huérfanas.

| Vía | Dónde viaja | Ejemplo | Se usa para |
|---|---|---|---|
| Parámetro de **ruta** | en la URL, parte del camino | `/api/docente/12345678` | Identificar UN recurso por su PK |
| **Query string** | después de `?` | `/api/docente?limite=3` | Opciones de la consulta |
| **Body** JSON | en el cuerpo de la petición | `{"nombre": "..."}` | Datos del recurso (POST/PUT/PATCH) |

| Verbo | Semántica | Endpoint en v2 |
|---|---|---|
| GET | Leer (nunca modifica) | listar · obtener por PK · docente completo |
| POST | Crear un recurso nuevo | crear ficha (y el docente con su detalle) |
| PUT | **Reemplazar completo** | reemplazar ficha |
| PATCH | **Actualizar parcial** | actualizar campos sueltos |
| DELETE | Eliminar | eliminar ficha (con restricción de dependencias) |

- Todas las respuestas son JSON (`Content-Type: application/json`).
- Errores SIEMPRE con esta envoltura: `{ "estado": 404, "mensaje": "...", "detalle": "..." }`

| Origen | HTTP |
|---|---|
| Body inválido según la validación del controlador | **422** con `errores: [ "...", ... ]` |
| Regla de negocio (`limite ≤ 0`, PATCH con body vacío, id no numérico) | 400 |
| Código inexistente (`NoEncontradoExcepcion`) | 404 |
| Integridad referencial (`ConflictoDeIntegridadExcepcion`) | **409** con `mensaje` y `detalle` |
| Error del motor no traducido (`PDOException`) | 500 con el mensaje en `detalle` |

---

## 1. GET /api/{recurso} — Listar (query string)

Aplica para los 11 recursos: `docente`, `estudios_realizados`,
`docente_departamento`, `intereses_futuros`, `evaluacion_docente`,
`reconocimiento`, `experiecia`, `red_docente`, `estudio_ac`,
`apoyo_profesoral`, `beca`.

```http
GET /api/docente
→ 200 { "tabla": "docente", "limite": 1000, "total": 191,
        "datos": [ { "cedula": "123", "nombre_completo": "...", ... }, … ] }

GET /api/docente?limite=3
→ 200 con exactamente 3 docentes (total: 3)
→ 400 si limite ≤ 0
→ 204 (cuerpo vacío) si no hay registros

GET /api/docente/{cedula}/completo
→ 200 { "cedula": "...", "nombre_completo": "...", 
        "estudios": [...], "intereses": [...], "redes": [...] }
→ 404 si la cédula no existe
```
## 2. GET /api/{recurso}/{pk} — Obtener uno (parámetro de ruta)
```http
GET /api/estudios_realizados/5
→ 200 { "id": 5, "titulo": "...", "universidad": "...", ... }

GET /api/estudios_realizados/abc
→ 400 { "estado": 400, "mensaje": "El id debe ser un número entero." }
```

## 3. POST /api/{recurso} — Crear (body completo)
```http
POST /api/estudios_realizados
body { "titulo": "Ingeniería", "universidad": "UIS", "tipo": "Pregrado", "cedula": "123" }
→ 200 { "estado": 200, "mensaje": "Estudio creado exitosamente.", "id": 5 }
```

## 4. PUT /api/{recurso}/{pk} — Reemplazar completo
```http
PUT /api/red_docente/5/10
body { "actividades_destacadas": "..." }
→ 200 { "estado": 200, "mensaje": "Vínculo reemplazado exitosamente.", "filasAfectadas": 1 }
→ 422 si falta CUALQUIER campo
→ 404 si la llave no existe
→ 409 si se intenta cambiar una FK a un valor que no existe
```

5. PATCH /api/{recurso}/{pk} — Actualizar parcial
```http
PATCH /api/estudios_realizados/5
body { "titulo": "Ingeniería de Sistemas" }
→ 200 { "estado": 200, "mensaje": "Estudio actualizado exitosamente.", "filasAfectadas": 1 }
→ 400 si el body viene vacío
→ 404 si la llave no existe
```
## 6. DELETE /api/{recurso}/{pk} — Eliminar con restricción
```http
DELETE /api/estudios_realizados/5
→ 200 { "estado": 200, "mensaje": "Estudio eliminado exitosamente.", "filasEliminadas": 1 }
→ 404 si la llave no existe

DELETE /api/docente/12345678
→ 409 { "estado": 409, "mensaje": "No se puede eliminar el docente.",
        "detalle": "Tiene estudios registrados." }
```

## 7. El Docente: el maestro-detalle (POST y PUT especiales)

El docente no es una ficha plana: es un **maestro** con **detalles** (sus
estudios, intereses y redes). Por eso sus endpoints `POST` y `PUT` tienen una
forma distinta a la de los otros recursos.

### 7.1 `POST /api/docente` — Crear docente con su detalle

El body lleva los datos personales del docente y, opcionalmente, las listas
de estudios, intereses y redes.

```http
POST /api/docente
body {
  "cedula": "12345678",
  "nombre_completo": "Ana Pérez",
  "linea_investigacion_principal": 5,
  "estudios": [
    { "titulo": "Ingeniería", "universidad": "UIS", "tipo": "Pregrado" },
    { "titulo": "Maestría", "universidad": "UNAL", "tipo": "Maestría" }
  ],
  "intereses": [
    { "termino": "machine learning" }
  ],
  "redes": [
    { "idr": 5, "actividades_destacadas": "..." }
  ]
}

→ 200 {
  "estado": 200,
  "mensaje": "Docente creado exitosamente.",
  "datos": {
    "cedula": "12345678",
    "nombre_completo": "Ana Pérez",
    "estudios": [
      { "id": 1, "titulo": "Ingeniería", ... },
      { "id": 2, "titulo": "Maestría", ... }
    ],
    "intereses": [ ... ],
    "redes": [ ... ]
  }
}
→ 422 si falta un campo obligatorio del docente o de alguna de sus listas.
→ 409 si la cédula ya existe, o si alguna FK (línea, término, red) no existe.
```
## 7.2 PUT /api/docente/{cedula} — Reemplazar el docente entero
``` http
PUT /api/docente/12345678
body {
  "nombre_completo": "Ana Pérez (Actualizada)",
  "estudios": [ { "titulo": "Doctorado", ... } ],
  "intereses": [],
  "redes": []
}
→ 200 { "estado": 200, "mensaje": "Docente reemplazado exitosamente." }
→ 422 si falta algún campo.
→ 404 si la cédula no existe.
→ 409 si alguna FK nueva no existe.
```
7.3 El 405 del PATCH en Docente
```http
PATCH /api/docente/12345678
→ 405 { "estado": 405, "mensaje": "Método no permitido para esta ruta." }
```
## 8. Los 11 recursos (resumen de columnas y llaves)

Esta tabla sirve para que quien consuma la API sepa qué campos enviar en cada POST y qué tipo de llave maneja.

| Recurso | Llave (PK) | Campos editables (para POST/PUT) | Llaves foráneas (desplegables en el front) |
|---|---|---|---|
| `docente` | `cedula` (string) | `nombre_completo`, `url_cvlac`, `escalafon`, `categoria_minciencias`, `perfil`, `linea_investigacion_principal` | `linea_investigacion_principal` → `linea_investigacion.id` |
| `estudios_realizados` | `id` (autoincremental) | `titulo`, `universidad`, `fecha`, `tipo`, `cedula` | `cedula` → `docente.cedula` |
| `docente_departamento` | compuesta (`cedula`, `id_programa`) | `dedicacion`, `modalidad`, `fecha_inicio`, `fecha_fin` | `cedula` → `docente.cedula`; `id_programa` → `programa.id` |
| `intereses_futuros` | compuesta (`cedula`, `termino`) | (no tiene campos editables, solo la relación) | `cedula` → `docente.cedula`; `termino` → `termino_clave.termino` |
| `evaluacion_docente` | `id` (autoincremental) | `semestre`, `calificacion`, `cedula` | `cedula` → `docente.cedula` |
| `reconocimiento` | `id` (autoincremental) | `tipo`, `fecha`, `institucion`, `ambito`, `cedula` | `cedula` → `docente.cedula` |
| `experiecia` | `id` (autoincremental) | `cargo`, `institucion`, `tipo`, `fecha_inicio`, `fecha_fin`, `cedula` | `cedula` → `docente.cedula` |
| `red_docente` | compuesta (`idr`, `cedula`) | `fecha_vinculacion`, `actividades_destacadas` | `idr` → `red.idr`; `cedula` → `docente.cedula` |
| `estudio_ac` | compuesta (`id_estudio`, `id_area`) | (no tiene campos editables, solo la relación) | `id_estudio` → `estudios_realizados.id`; `id_area` → `area_conocimiento.id` |
| `apoyo_profesoral` | compuesta (`id_estudio`, `tipo_apoyo`) | `institucion`, `monto` | `id_estudio` → `estudios_realizados.id` |
| `beca` | compuesta (`id_estudio`, `tipo_beca`) | `institucion`, `fecha_inicio`, `fecha_fin` | `id_estudio` → `estudios_realizados.id` |

9. GET / — Diagnóstico
```http
GET /
→ 200 {
  "mensaje": "API Gestión funcionando",
  "version": "v2",
  "recursos": [
    "area_conocimiento", "termino_clave", "linea_investigacion", "programa", "red",
    "docente", "estudios_realizados", "docente_departamento", "intereses_futuros",
    "evaluacion_docente", "reconocimiento", "experiecia", "red_docente",
    "estudio_ac", "apoyo_profesoral", "beca"
  ],
  "contratos": "docs/spec_kit/versiones/v2_tablas_con_fk/6_contracts.md"
}
```
10. Estabilidad de este contrato

Estos endpoints para los 11 recursos nuevos no cambian en las versiones
siguientes. La v3 agregará autenticación y roles, y la v4 agregará consultas
multitabla, pero las rutas y la envoltura de los recursos existentes se
mantienen.

---

# El front (`http://localhost:8020`)

## 11. Las once pantallas nuevas, y la traducción

Aquí no hay verbos ni códigos de estado: **el usuario no sabe qué es un 409**. Esta tabla es el contrato de la pantalla para CADA UNO de los 11 recursos nuevos. Leída de izquierda a derecha, es también la lista de lo que el front traduce.

*(Nota: `{recursos}` representa la ruta en plural en el front, `{recurso}` la ruta singular en la API, y `{pk}` el identificador `cedula`, `id` o la llave compuesta).*

| Dirección | Qué ve la persona | Qué le pide a la API |
|---|---|---|
| `GET /docentes` | La tabla con los docentes (`cedula`, `nombre_completo`, `linea_principal`) | `GET /api/docente?limite=1000` |
| `GET /docentes/nuevo` | El formulario vacío con secciones para estudios, intereses y redes | *(nada)* |
| `POST /docentes/nuevo` | Vuelve al listado con «Se agregó…», o el formulario con lo escrito y los motivos | `POST /api/docente` (con las listas anidadas) |
| `GET /docentes/{cedula}` | La pantalla de detalle: datos del docente, sus estudios, intereses y redes | `GET /api/docente/{cedula}/completo` |
| `GET /docentes/{cedula}/editar` | El formulario con la ficha completa y el detalle precargado | `GET /api/docente/{cedula}/completo` |
| `POST /docentes/{cedula}/editar` | Vuelve al listado con «Se guardó…» (reemplaza todo el detalle) | `PUT /api/docente/{cedula}` |
| `POST /docentes/{cedula}/eliminar` | Confirma; si tiene dependencias, muestra el motivo en español | `DELETE /api/docente/{cedula}` (puede dar 409) |
| `GET /estudios` | La tabla con los estudios (`id`, `titulo`, `universidad`, `tipo`) | `GET /api/estudios_realizados` |
| `GET /estudios/nuevo` | El formulario vacío; el campo `docente` es un desplegable | *(nada)* |
| `POST /estudios/nuevo` | Vuelve al listado con «Se agregó…» y el `id` que asignó la base | `POST /api/estudios_realizados` |
| `GET /estudios/{id}/editar` | El formulario con la ficha; el `id` es de solo lectura | `GET /api/estudios_realizados/{id}` |
| `POST /estudios/{id}/editar` | Con **«Guardar la ficha completa»** → PUT; con **«Guardar solo lo que cambié»** → PATCH | `PUT` o `PATCH /api/estudios_realizados/{id}` |
| `POST /estudios/{id}/eliminar` | Confirma, y vuelve al listado con «Se eliminó…» | `DELETE /api/estudios_realizados/{id}` |
| ... | *(El mismo patrón se repite para las demás fichas)* | ... |
| `GET /departamentos` | Vinculaciones docente↔programa | `GET /api/docente_departamento` |
| `GET /intereses` | Puentes docente↔término | `GET /api/intereses_futuros` |
| `GET /evaluaciones` | Evaluaciones por semestre | `GET /api/evaluacion_docente` |
| `GET /reconocimientos` | Reconocimientos docentes | `GET /api/reconocimiento` |
| `GET /experiencia` | Experiencia laboral | `GET /api/experiecia` |
| `GET /redes-docente` | Vínculos red↔docente | `GET /api/red_docente` |
| `GET /estudios-area` | Puentes estudio↔área | `GET /api/estudio_ac` |
| `GET /apoyos` | Apoyos por estudio | `GET /api/apoyo_profesoral` |
| `GET /becas` | Becas por estudio | `GET /api/beca` |
| cualquier otra | «Esa página no existe» **con el marco de la aplicación** | *(nada)* |

**Fíjese en las filas de `docentes`:** es la única pantalla que no tiene «Guardar solo lo que cambié» (no hay PATCH para el docente) y la única que tiene una pantalla de detalle (`GET /docentes/{cedula}`). El resto de fichas siguen el patrón de la v1.

---

## 12. Cómo se traduce lo que responde la API

Un solo archivo del front (`cliente_api.php`) conoce esta tabla. Ninguna vista la conoce, y por eso el día que la API cambie el sobre se cambia en un sitio.

| Lo que responde la API | Lo que hace el front | Lo que ve la persona |
|---|---|---|
| `200` con `{tabla, limite, total, datos}` | Se queda con `datos` | La tabla llena |
| **`204`** (tabla vacía) | `ok = true` con la lista **vacía** | «Todavía no hay registros» y el botón de agregar |
| `200` de una escritura (con `id` si la base lo generó) | `ok = true` | El aviso verde en el listado y la ficha nueva |
| `400` / `404` con `{estado, mensaje, detalle}` | Junta `mensaje` y `detalle` | El aviso rojo, en español |
| `422` con `{estado, mensaje, errores:[…]}` | Se queda con la lista `errores` | Los motivos, uno por línea, junto al formulario |
| **`409`** con `{estado, mensaje, detalle}` | Junta `mensaje` y `detalle` tal cual | «No se puede eliminar el docente porque hay otras fichas que dependen de ella» |
| **Nada** (la API no responde) | `null`, que no es lo mismo que un error | «El servicio no está disponible» — y la pantalla sigue en pie |

**La fila nueva de la v2 es la del 409.** En la v1, un error de integridad se veía como un 500 (la pantalla decía «Ocurrió un error inesperado»). En la v2, el 409 trae un mensaje en español que explica *por qué* no se pudo hacer la operación (llave repetida, FK inexistente, o dependencias al borrar), y la pantalla lo muestra tal cual.

---

## 13. Los desplegables de las llaves foráneas

Los formularios que tienen FKs (estudios, intereses, redes, etc.) usan `<select>` para elegir el valor, no un campo de texto libre. Para llenar esos `<select>`, el front hace peticiones extra a la API al abrir la pantalla:

| Formulario | Desplegable | Se llena con |
|---|---|---|
| `estudios_realizados` | Docente | `GET /api/docente` (muestra `cedula — nombre_completo`) |
| `docente` | Línea principal | `GET /api/linea_investigacion` (muestra `nombre`) |
| `intereses_futuros` | Término | `GET /api/termino_clave` (muestra `termino`) |
| `red_docente` | Red | `GET /api/red` (muestra `nombre`) |
| `docente_departamento` | Programa | `GET /api/programa` (muestra `nombre`) |
| `estudio_ac` | Área | `GET /api/area_conocimiento` (muestra `gran_area — area`) |
| `apoyo_profesoral`, `beca` | Estudio | `GET /api/estudios_realizados` (muestra `titulo`) |

*Nota:* El desplegable **no valida nada**. Si la ficha que se eligió fue borrada por otra persona en el intermedio, la API responderá con un 409 («Alguno de los códigos a los que apunta … no existe») y la pantalla mostrará ese mensaje en español.
