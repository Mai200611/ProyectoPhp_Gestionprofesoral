# Lista de chequeo de requisitos — Versión 2

> **La compuerta 3** del método (ver [SDD_SPECKIT](../../../SDD_SPECKIT.md)). Esta lista
> revisa **la ESPECIFICACIÓN, no el código**: se pasa cuando los documentos
> 2 a 8 de esta versión están escritos y ANTES de programar la primera
> línea. Es el equivalente a mano de `checklists/requirements.md`, que en
> Spec Kit genera `/speckit.checklist`.

## Cómo se usa

- **Las casillas las marca una persona.** Una IA puede ayudar a evaluar y a
  señalar dudas, pero **no puede auto-aprobarse**: quien firma es quien
  responde por la versión.
- Se marca `[x]` solo cuando el criterio se cumple **hoy, en el documento**
  — no "cuando lo arregle".
- **Con una sola casilla en rojo no se escribe código.** Se vuelve al
  documento que la causó, se corrige, y se pasa la lista otra vez.
- Trabaja bien en pareja: un estudiante revisa la spec del otro. Las
  ambigüedades que uno no ve, el otro las tropieza de una.

---

## A. Claridad — ¿dice UNA sola cosa?

- [X] Ningún requisito usa palabras sin definir: *rápido, amigable,
      eficiente, correcto, adecuado, robusto*.
- [X] No queda ningún marcador `[NECESITA ACLARACIÓN: …]` sin resolver en
      la sección de **Clarificaciones** de [2_spec.md](2_spec.md).
- [X] Cada RF explica UNA cosa. Si uno necesita un "y" para entenderse, se
      partió en dos.
- [X] Los RF no mencionan tecnología ni nombres de clase: el QUÉ está
      separado del CÓMO, que vive en [3_plan.md](3_plan.md).

## B. Medible — ¿se puede verificar?

- [X] Cada criterio de aceptación de [2_spec.md](2_spec.md) dice un **valor
      concreto**: un número, un código de estado o un texto exacto.
- [X] Cada criterio se puede comprobar con **un comando** de
      [7_quickstart.md](7_quickstart.md). Si no hay comando posible, no es
      criterio.
- [X] Los códigos de error están dichos por su número (400, 404, 422,
      500), no como "responde con un error".

## C. Completitud — ¿falta algo?

- [X] Los RF cubren todo lo que promete el propósito: nada del alcance
      quedó sin requisito.
- [X] [2_spec.md](2_spec.md) tiene su **NO incluye** explícito.
- [X] Cada entrada de [6_contracts.md](6_contracts.md) documenta sus
      desenlaces de **ERROR**, no solo el camino feliz.
- [X] [6_contracts.md](6_contracts.md) dice el nombre EXACTO de cada campo
      **en el JSON**, no solo el de la columna. Se parecen, pero no siempre
      son iguales — y quien reconstruye el sistema copia del contrato.
- [X] Cada rechazo de integridad que la especificación promete tiene su
      **mensaje en español** escrito, no solo su código.
- [X] [5_data_model.md](5_data_model.md) trae los **datos exactos** de los
      que dependen los comandos del smoke test.
- [X] **La versión tiene su front especificado**, no solo su API: hay
      requisitos de lo que la persona ve y hace, y [6_contracts.md](6_contracts.md)
      dice qué pantalla existe y qué le pide a la API (Artículo 1.1 de la
      [constitución](../../1_constitution.md)).
- [X] Está dicho **qué hace la pantalla cuando la API no responde** — no
      basta con el camino feliz.
- [X] Está dicho **qué hace la pantalla cuando no hay filas**, y que eso no
      es un error.
- [X] Cada decisión de [4_research.md](4_research.md) tiene al menos una
      alternativa descartada con su razón.

## D. Coherencia — ¿los documentos dicen lo mismo?

- [X] Todo RF de [2_spec.md](2_spec.md) aparece en
      [6_contracts.md](6_contracts.md).
- [X] Todo lo que promete [6_contracts.md](6_contracts.md) tiene una tarea
      que lo construye en [8_tasks.md](8_tasks.md).
- [X] Todo criterio de aceptación tiene su comando en
      [7_quickstart.md](7_quickstart.md), **con el mismo número**.
- [X] Cada pantalla de [6_contracts.md](6_contracts.md) §9 usa un endpoint
      que existe en las secciones de arriba del mismo documento — y ninguna
      inventa uno.
- [X] Los ejemplos de [6_contracts.md](6_contracts.md) usan datos que
      existen en [5_data_model.md](5_data_model.md).
- [X] [3_plan.md](3_plan.md) no nombra ningún archivo que ninguna tarea
      construya, y ninguna tarea construye un archivo que el plan no liste.
- [X] El **Chequeo de constitución** de [3_plan.md](3_plan.md) está
      completo: artículo por artículo, sin saltarse ninguno.


## E. Alcance — ¿no se está anticipando?

- [X] Ningún documento nombra entidades, motores o pantallas fuera del
      alcance declarado en [2_spec.md](2_spec.md).
- [X] **Ninguna ruta es genérica**: no aparece `/api/{tabla}` ni nada que
      reciba el nombre del recurso como parámetro, ni en la API ni en el
      cliente del front (Artículo 10 de la
      [constitución](../../1_constitution.md)).
- [X] **Ningún recurso tiene operaciones que no le corresponden.** Si la
      especificación le da a un recurso un verbo que su negocio no admite
      —un PATCH de factura, por ejemplo— se está describiendo la comodidad
      de quien programa, no lo que la cosa hace.
- [X] **El dialecto del motor no aparece fuera de la capa de datos.** Ni un
      `SELECT`, ni un nombre de procedimiento, ni un número de error del
      motor en un servicio, un controlador o una vista (RNF5). Es lo que la
      v3 va a poner a prueba: si algo se filtró, se descubre allá y ya es
      tarde.
- [X] **Está dicho quién escribe cada columna.** Para cada campo: lo escribe
      el cliente, lo genera la base, o lo calcula un trigger. Un campo cuyo
      dueño no está declarado acaba escrito por dos.
- [X] Ningún documento anticipa una versión futura (Artículo 1 de la
      [constitución](../../1_constitution.md): lo que no pide esta versión, no se
      escribe).
- [X] Las dependencias que nombra [3_plan.md](3_plan.md) son exactamente
      las que permite la constitución.

---

## Resultado

| | |
|---|---|
| **Revisada por** | Sara Pabón González Y Miguel álngel Hincapie |
| **Fecha** | 30 de octubre del 2026 |
| **Casillas en rojo** | 0 |
| **Veredicto** | ⬜ En verde: puede empezar el código · ⬜ En rojo: vuelve a la spec |

> Si el veredicto es rojo, anote aquí qué documento hay que corregir y por
> qué. Esa nota es la que evita repetir el mismo error en la versión
> siguiente.
