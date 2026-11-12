# Especificación — Versión 3: el mismo sistema contra dos motores (Gestión Profesoral)

> **Versión 3** del desarrollo incremental ([mapa de versiones](../0_mapa_versiones.md)).
> Rige la constitución del proyecto: [../../1_constitution.md](../../1_constitution.md).
> Parte del estado que dejó la v2: **dieciséis recursos** (5 sin FK + 11 con FK)
> del módulo de gestión profesoral funcionando contra MariaDB.
>
> | Documento de esta versión | Contenido |
> |---|---|
> | **2_spec.md** (este) | QUÉ construir en v3 y sus criterios de aceptación |
> | [3_plan.md](3_plan.md) | CÓMO: la fábrica y lo que cambia por motor |
> | [4_research.md](4_research.md) | Decisiones y alternativas *(lectura opcional)* |
> | [5_data_model.md](5_data_model.md) | La misma base `gestion_profesoral`, en dos dialectos |
> | [6_contracts.md](6_contracts.md) | Los contratos — que NO cambian, y por qué eso es el punto |
> | [7_quickstart.md](7_quickstart.md) | Arranque, cambio de motor y smoke test |
> | [8_tasks.md](8_tasks.md) | Orden de construcción por fases verificables |

---

## 1. Propósito de la v3

**Esta versión no agrega ni una funcionalidad.** No hay recursos nuevos, ni
pantallas nuevas, ni un solo endpoint más. Lo que agrega es un segundo motor
de base de datos —PostgreSQL— y con él, la única forma honesta de comprobar si
las dos versiones anteriores quedaron bien cortadas.

Es distinto de todo lo anterior, y conviene entender por qué:

- La v1 y la v2 **prometían** que el motor estaba encerrado en la capa de
  datos. Era una promesa razonable, escrita en documentos, imposible de
  verificar: con un solo motor, cualquier arquitectura parece independiente
  del motor.
- La v3 **cobra la promesa**. Si al agregar PostgreSQL hay que tocar un
  controlador, un servicio o una pantalla, es que el motor se había filtrado
  hacia arriba y nadie se había dado cuenta.

Y en este módulo la promesa se cobra sobre terreno difícil: no son
catálogos sueltos, sino un **docente maestro-detalle**, **tablas puente con
llaves compuestas**, **llaves de texto** (`cedula`, `termino`), **ids
autoincrementales** y una **línea de investigación principal que calcula la
base**. Si la separación de capas aguanta eso, aguanta lo demás.

Por eso el criterio de aceptación más importante de esta versión no dice qué
hay que construir: dice **qué NO se puede haber tocado**.

> **El nombre técnico de esto es el principio abierto/cerrado** (la O de
> SOLID): un sistema debe estar **abierto** a que se le agreguen cosas y
> **cerrado** a que agregárselas obligue a reescribir lo que ya funcionaba.
> Hasta hoy era una frase de un documento. Hoy se ejecuta.

## 2. Alcance

**Incluye:**

- **PostgreSQL 16** como segundo motor, con la misma base en su dialecto:
  `bdgestion_postgres_local` (`db/postgres/init.sql`), con las **16 tablas**
  de la v1 y la v2, los mismos datos de ejemplo (incluidos los 218 registros
  de `area_conocimiento` y los 191 de `programa`), y los **triggers y
  procedimientos reescritos en el dialecto de PostgreSQL**. Se levanta con el
  mismo compose. La de MariaDB sigue siendo `bdgestion_mariadb_local`.
- **Dieciséis repositorios nuevos** (`Repositorio*Postgres`, uno por recurso),
  que cumplen las **mismas interfaces** que los de MariaDB.
- **Un traductor de errores de integridad por motor**, y las frases del
  usuario compartidas entre los dos.
- **La fábrica**: `ensamblador.php` deja de armar una sola combinación y pasa
  a escoger según la variable de entorno `MOTOR`.
- **El motor activo publicado** en `GET /` y mostrado en el pie de las
  pantallas — no para el usuario, sino para poder comprobar la promesa.
- **La prueba de los dos motores**: el mismo guion de humo, corrido dos veces.

**No incluye (y es deliberado):**

- Ningún recurso, endpoint ni pantalla nueva.
- SQL Server, que llega en una versión posterior.
- Las tablas de gestión de usuarios (`rol`, `usuario`, `rol_usuario`), el
  login y el JWT: pertenecen a otra versión del módulo y no se mezclan con
  esta, cuyo único objetivo es el motor.
- Migrar datos de un motor a otro, o mantenerlos sincronizados. **Son dos
  bases independientes**, cada una con los mismos datos de ejemplo; lo que se
  escriba en una no aparece en la otra. Esta versión demuestra que el
  *programa* funciona contra las dos, no que las dos sean la misma.
- Cambiar de motor sin reiniciar la API. La variable se lee al arrancar.

## 3. Requisitos funcionales

### RF1 — El motor se escoge por configuración, nunca por código
La variable de entorno `MOTOR` decide: `mariadb` (por defecto) o `postgres`.
Ningún archivo del sistema se edita para cambiar de motor.

Un valor desconocido (`MOTOR=oracle`) **no falla**: arranca en MariaDB. El
motor por defecto es una decisión de configuración, no una trampa para el que
se equivoque escribiendo.

### RF2 — Los dieciséis recursos se comportan igual contra los dos motores
Todo lo que la v1 y la v2 prometían —los listados, las seis operaciones, el
204, el 404, el 405 del `PATCH` del docente, el maestro-detalle— responde lo
mismo con `MOTOR=mariadb` y con `MOTOR=postgres`. Los dieciséis, no una
muestra: un recurso que se quedara solo en MariaDB escondería justo la fuga
que esta versión busca.

### RF3 — Los tres conflictos de integridad dicen el MISMO texto
Un 409 por llave duplicada, por referencia rota o por dependencias debe
entregar **la misma frase en español** con los dos motores.

Esto no sale gratis: los dos motores reportan esos problemas de forma
distinta —MariaDB con tres códigos numéricos (1062, 1452, 1451), PostgreSQL
con dos SQLSTATE (`23505` y `23503`), y uno de ellos (`23503`) sirve para dos
casos opuestos: insertar un hijo cuyo padre no existe, y borrar un padre que
aún tiene hijos—. Que el usuario lea lo mismo es un requisito, no una
casualidad.

Aplica también a las llaves compuestas de las tablas puente
(`docente_departamento`, `intereses_futuros`, `red_docente`, `estudio_ac`,
`apoyo_profesoral`, `beca`).

### RF4 — Lo que la base calcula, los dos motores lo calculan igual
La línea de investigación principal del docente la calcula la base a partir
de sus estudios, y la API **no repite ese cálculo** (Artículo 11 de la
constitución). Al portar el trigger y el procedimiento de alta a PostgreSQL,
el resultado debe ser el mismo que en MariaDB para los mismos datos.

El maestro-detalle sigue siendo **una unidad**: crear un docente con sus
estudios, intereses y redes **o se guarda completo, o no se guarda nada**, con
los dos motores. (Los dos motores se comportan distinto ante un error a mitad
de una transacción; que el resultado para el usuario sea el mismo es trabajo
de sus repositorios.)

### RF5 — Los datos viajan igual
Un mismo dato llega al JSON con la misma forma sin importar el motor: los
números como números (`calificacion`, `monto`, `graduados`, `cohortes`), los
campos de sí/no igual que los entrega hoy la v2, y las fechas con el mismo
formato. Los listados salen en el **mismo orden** y con los **mismos
totales**, aunque los motores comparen mayúsculas y acentos de forma distinta
en las llaves de texto (`termino`, `cedula`, `idr`).

El contrato HTTP no se mueve: los repositorios de PostgreSQL se ajustan a lo
que ya devuelve MariaDB, no al revés.

### RF6 — El diagnóstico dice qué motor está activo
`GET /` devuelve, además de lo de la v2, un campo `motor` con `"mariadb"` o
`"postgres"`.

**Es el único sitio de la API donde se menciona el motor.** Ninguna otra
respuesta lo nombra ni cambia de forma según cuál sea.

### RF7 — La pantalla lo muestra, en el pie y en letra pequeña
El front muestra el motor activo en el pie de página, y **no hace nada más
con ese dato**: no cambia una consulta, no oculta un botón, no toma ninguna
decisión.

El sitio es la mitad del mensaje: al usuario no le importa, y puede usar el
sistema entero sin mirarlo nunca. Está para que se pueda comprobar la promesa
de la versión sin ir a leer el compose.

> **Esta es la única excepción** a la regla de la v1 que prohíbe mostrar el
> nombre del motor al usuario (RNF7 y criterio 15 de la v1). La excepción es
> el pie, y solo el pie.

### RF8 — Un solo comando sigue levantando todo
`docker compose up -d --build` deja funcionando **las dos bases** (MariaDB en
`mariadb:3306`, PostgreSQL en `postgres:5432`; hacia el host, 13326 y 15452),
la API (en MariaDB, puerto 8022) y el front (8020). Cambiar de motor es una
variable y un reinicio de la API, no un compose distinto.

`docker compose down -v` sigue devolviendo **las dos** bases a su estado
original.

## 4. Requisitos no funcionales

- **RNF1 — Las interfaces no cambian.** Las dieciséis interfaces de
  repositorio son las mismas de la v2. Si una interfaz tuvo que cambiar para
  que PostgreSQL entrara, estaba describiendo a MariaDB y no al negocio.
- **RNF2 — El dialecto vive SOLO en las clases con nombre de motor.** Ni un
  `SELECT`, ni un nombre de procedimiento, ni un código de error fuera de
  `Repositorio*MariaDB`, `Repositorio*Postgres` y sus dos traductores.
- **RNF3 — Las frases del usuario son las mismas.** Los mensajes de conflicto
  se escriben una vez, en un archivo compartido (`conflictos.php`); los
  traductores por motor solo deciden **cuál** de ellas corresponde.
- **RNF4 — Una sola imagen de la API.** El mismo contenedor sirve para los dos
  motores: trae los dos drivers de PDO y escoge al arrancar. Dos imágenes
  distintas habrían escondido que es el mismo programa.
- **RNF5 — Cero secretos en el código.** La cadena de conexión de cada motor
  y la variable `MOTOR` llegan por variables de entorno; el `.env` va en el
  `.gitignore` y el repositorio trae un `.env.example` con los dos motores.
  Lo único de la conexión que cambia entre motores es el DSN.
- **RNF6 — Todo lo demás de la v2 sigue vigente**: capas estrictas, PHP puro,
  prepared statements, el front sin acceso a la base ni dependencia de ella
  en el compose, rutas escritas a mano por recurso (nada de `/api/{tabla}`),
  y nada de jerga en la pantalla (salvo la excepción del RF7).

## 5. Criterios de aceptación

1. **`docker compose up -d --build` — un solo comando —** deja corriendo
   MariaDB, PostgreSQL, la API y el front. `GET /` responde `"motor":
   "mariadb"`.
2. Con `MOTOR=postgres` y un reinicio de la API, `GET /` responde
   `"motor": "postgres"` — **sin haber editado ningún archivo**. Con
   `MOTOR=oracle` arranca y responde `"mariadb"`.
3. Los dieciséis recursos listan sus datos de ejemplo con los dos motores,
   con la misma envoltura, los mismos totales (218 en `area_conocimiento`,
   191 en `programa`) y el mismo orden.
4. El ciclo completo de una ficha (crear, PUT, PATCH, DELETE) funciona igual
   con los dos motores, probado en tres tipos de llave: **id autoincremental**
   (p. ej. `linea_investigacion`), **llave de texto** (p. ej. `red` o
   `termino_clave`) y **llave compuesta** (p. ej. `docente_departamento`),
   incluido el `id` que devuelve el `POST`. En los recursos cuyo id genera la
   base, el primer registro nuevo posterior a la carga de los catálogos del
   Excel recibe un id libre y no choca con los ya cargados.
5. **Los tres conflictos de integridad responden 409 con el MISMO texto** con
   los dos motores: crear una `red` con un `idr` repetido, crear un
   `estudios_realizados` con una cédula que no existe, y borrar un `docente`
   que tiene estudios. Compare las respuestas palabra por palabra.
6. **Los datos viajan igual:** la respuesta de un mismo recurso, con los
   mismos datos, es idéntica con los dos motores (tipos incluidos). Compare
   el JSON, salvo el campo `motor` del diagnóstico.
7. El maestro-detalle funciona igual con los dos: crear un docente con
   estudios, intereses y redes, y leerlo con `GET /api/docente/{cedula}/completo`,
   devuelve la misma ficha, **con la misma línea de investigacion principal
   calculada por la base**; el `PATCH` sigue respondiendo 405.
8. **Todo o nada** con los dos motores: crear un docente con un detalle
   inválido (p. ej. un `termino` inexistente en sus intereses) responde 409
   y **no deja guardado nada** — ni el docente ni los renglones previos.
9. `PUT /api/docente/{cédula inexistente}` responde **404** con los dos
   motores. (Los dos procedimientos avisan de forma distinta; que el
   resultado sea el mismo es trabajo de sus repositorios.)
10. Prueba de capas: los servicios siguen funcionando con repositorios falsos,
    sin ninguna base corriendo — y **ningún repositorio falso tuvo que
    cambiar de forma**, porque las interfaces no se movieron.
11. Todas las pantallas de los dieciséis recursos responden y se ven con sus
    estilos, con los dos motores, y el pie muestra el nombre del que está
    activo.
12. El nombre del motor aparece **exactamente una vez** en cada pantalla, y
    es en el pie. En ninguna otra parte del texto que el usuario ve.
13. **La prueba de los dos motores termina en verde:**
    `python pruebas_humo/humo_los_dos_motores.py` corre el guion de humo
    completo contra cada uno y los dos dan el mismo resultado.
14. **EL CRITERIO DE LA VERSIÓN.** Compare esta versión con la v2 archivo
    por archivo:

    ```powershell
    # (con las dos carpetas al lado, fuera del contenedor)
    diff -rq --exclude=.git ..\proyecto_php2 .
    ```

    **14.a — Ningún controlador y ninguna vista cambiaron.** Ni uno. Si
    alguno cambió, el motor estaba filtrado hasta ahí y se corrigió en el
    sitio equivocado: la versión **no está terminada** aunque todo funcione.

    **14.b — Los únicos cambios permitidos, y su motivo:**

    | Archivo | Por qué cambió |
    |---|---|
    | `servicios/ensamblador.php` | Pasó a ser la fábrica. **Es el archivo de esta versión.** |
    | Los dieciséis `Repositorio*MariaDB` | Su traductor de errores cambió de nombre al partirse en dos (uno por motor) |
    | `api_facturas/index.php` | **Una línea**: publicar el motor en el diagnóstico |
    | El front y su prueba de humo | Mostrar el motor en el pie, y correr contra los dos |
    | Los dos `Dockerfile` y el `docker-compose.yml` | El driver y el servicio del motor nuevo |
    | `.env.example` | Las variables del segundo motor |

    **Si el diff muestra algo que no está en la tabla, no se agrega a la
    tabla para que dé verde.** Se explica por qué cambió, a la vista, o se
    corrige. Un segundo motor no solo prueba la arquitectura: también obliga
    a releer lo escrito, y ahí es donde aparecen las inconsistencias que un
    solo camino nunca revela (un método que se llama distinto en dos
    interfaces, por ejemplo). Si aparece una, se registra aquí con su motivo;
    nunca se esconde detrás de un criterio redactado para que dé verde. Y
    solo vale si es un cambio de nombre interno: **el contrato HTTP no se
    mueve**.

> El criterio 14 es raro y es el más importante. Los demás comprueban que el
> sistema funciona; ése comprueba que la arquitectura servía para algo. Y
> fíjese en que **se aprueba con un `diff`, no con una opinión**.

## 6. Glosario mínimo

| Término | Significado |
|---|---|
| **Fábrica** | La pieza que decide qué clase concreta construir. Aquí, `ensamblador.php` |
| **Abierto/cerrado** | Abierto a que le agreguen cosas, cerrado a que agregárselas obligue a reescribirlo |
| **Dialecto** | Las diferencias de SQL y de comportamiento entre motores. `RETURNING` existe en PostgreSQL y no en MariaDB; el autoincremental, los procedimientos y los triggers también se escriben distinto |
| **SQLSTATE** | El código de error estándar de SQL. PostgreSQL lo usa bien; MariaDB pone `23000` para todo y el número útil va aparte |
| **DSN** | La cadena que le dice a PDO a qué base conectarse. Es lo único de la conexión que cambia entre motores |
| **Maestro-detalle** | El docente con sus estudios, intereses y redes, tratado como una sola unidad: se guarda completo o no se guarda |
| **Llave compuesta** | Llave primaria formada por dos campos, como en las tablas puente |

## 7. Definición de TERMINADA

Los 14 criterios pasan → commit + tag `v3` → recién entonces se escribe la
spec de la versión siguiente ([mapa](../0_mapa_versiones.md)).

## 8. Clarificaciones

| # | La pregunta | La respuesta acordada, con su razón | Dónde quedó |
|---|---|---|---|
| C1 | ¿Dos servicios de API, uno por motor, o uno solo que escoge? | **Uno solo.** El punto de la versión es que **es el mismo programa**; dos servicios habrían escondido justamente eso, y además habrían duplicado los puertos y la configuración. | RNF4 · 4_research D1 |
| C2 | Si `MOTOR` trae un valor desconocido, ¿la API falla al arrancar? | **No: arranca en MariaDB.** El motor por defecto es configuración, no una trampa. Un sistema que no arranca porque alguien escribió «postgresql» con L de más es un sistema hostil. | RF1 |
| C3 | Los dos motores reportan las llaves foráneas distinto. ¿El usuario debería ver esa diferencia? | **No.** Los tres mensajes de conflicto se escriben una sola vez en `conflictos.php` y los dos traductores los usan. El contrato promete un texto, no un código; un contrato que cambia según lo que haya detrás no es un contrato. | RF3 · RNF3 |
| C4 | ¿El front necesita saber qué motor hay? | **No, y no lo usa para nada.** Lo muestra en el pie y ahí termina. El día que el front pregunte «¿estoy contra PostgreSQL?» para hacer algo distinto, la separación de capas se rompió. | RF7 |
| C5 | Las dos bases, ¿se sincronizan? | **No: son independientes.** Cada una arranca con los mismos datos de ejemplo, y lo que se escriba en una no aparece en la otra. Esta versión demuestra que el programa funciona contra las dos, no que las dos sean la misma. | Alcance |
| C6 | Los `SELECT` resultaron idénticos en los dos motores. ¿Hacía falta una clase por motor? | **Sí.** Lo que cambia no es el SQL de estas consultas —esta base usa SQL estándar— sino **todo lo de alrededor**: cómo se lee una llave generada, cómo se llama un procedimiento, cómo se reconoce un error, cómo se devuelven los tipos. Una sola clase con `if ($motor === …)` adentro habría acumulado esas diferencias hasta volverse ilegible, y habría hecho imposible el criterio 14. | 4_research D2 |
| C7 | ¿Se puede cambiar de motor sin reiniciar? | **No, y no se intentó.** Leer la variable en cada petición habría permitido que dos peticiones simultáneas usaran motores distintos, con la mitad de los datos en cada base. Se lee al arrancar. | Alcance |
| C8 | ¿Se portan los dieciséis recursos o basta con una muestra? | **Los dieciséis.** Los catálogos de la v1 son lo fácil; lo que de verdad pone a prueba la separación está en la v2 (el docente maestro-detalle, las llaves compuestas, los hijos de estudio). Probar solo lo fácil daría un verde que no vale. | RF2 |
| C9 | ¿Entran las tablas de usuarios (`rol`, `usuario`, `rol_usuario`) en esta versión? | **No.** Traen login, JWT y roles, que es otra clase de trabajo. Mezclarlas con el cambio de motor haría imposible saber, si algo falla, si la culpa es del motor o de la autenticación. | Alcance |
| C10 | La línea de investigacion principal la calcula la base. ¿Quién garantiza que los dos motores la calculen igual? | **El trigger y el procedimiento portados a PostgreSQL**, verificados con el mismo guion de humo. La API no recalcula nada para «arreglar» una diferencia: si los dos motores difieren, el error está en el dialecto portado, no en PHP. | RF4 · crit. 7 |
| C11 | Los motores comparan mayúsculas y acentos distinto en las llaves de texto. ¿Eso se le muestra al usuario? | **No.** El mismo dato debe producir el mismo orden y el mismo conflicto en los dos. Se resuelve en la base de PostgreSQL, no en PHP. | RF5 · crit. 3 y 6 |
| C12 | Un docente con un detalle inválido, ¿queda a medias? | **No, con ninguno de los dos.** PostgreSQL y MariaDB reaccionan distinto a un error a mitad de la transacción; que el usuario vea lo mismo (409 y nada guardado) es trabajo de los repositorios. | RF4 · crit. 8 |
