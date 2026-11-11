# Plan técnico — Versión 3: la fábrica y lo que cambia por motor (Gestión Profesoral)

> **Versión 3** · CÓMO construir lo especificado en [2_spec.md](2_spec.md).
> El porqué de cada decisión: [4_research.md](4_research.md) · contratos:
> [6_contracts.md](6_contracts.md) · orden: [8_tasks.md](8_tasks.md).

---

## 1. Stack

Ni una dependencia nueva de PHP. Lo que se agrega es un motor y un driver.

| Pieza | v2 | v3 |
|---|---|---|
| Lenguaje | PHP 8.3, `declare(strict_types=1)` | igual |
| Acceso a datos | PDO con `pdo_mysql` | PDO con **`pdo_mysql` y `pdo_pgsql`** |
| Motores | MariaDB 11 | **+ PostgreSQL 16** |
| Imagen de la API | `php:8.3-cli` + `pdo_mysql` | **una sola** imagen con los dos drivers |
| Front | sin cambios | + la etiqueta del motor en el pie |

**Una sola imagen para los dos motores**, y no dos. Es deliberado: el punto de
la versión es que **es el mismo programa**. Dos imágenes lo habrían escondido.

## 2. Estructura de carpetas

Solo se muestra lo que cambió respecto de la v2. La API vive en `api_gestion/`
y el front en `front_php/`.

```
├── .env.example                      ← los dos juegos de variables de conexión
│
├── db/
│   ├── mariadb/                      ← la carpeta que tenía la base, ahora con apellido
│   │   └── init.sql                  (bdgestion_mariadb_local)
│   └── postgres/
│       └── init.sql                  ← LA MISMA base, en dialecto de PostgreSQL
│                                       (bdgestion_postgres_local)
│
├── api_gestion/
│   ├── config/                       ← lee los dos juegos de variables de entorno
│   ├── repositorios/
│   │   ├── IRepositorio*.php               ← NO CAMBIARON (RNF1) — las 16
│   │   ├── Repositorio*MariaDB.php         (16, de la v1 y la v2)
│   │   ├── Repositorio*Postgres.php        ← LOS DIECISÉIS NUEVOS
│   │   ├── conflictos.php                  ← NUEVO: las frases, una sola vez
│   │   ├── errores_de_integridad_mariadb.php    ← el archivo de la v2, partido
│   │   └── errores_de_integridad_postgres.php   ← en dos
│   └── servicios/
│       └── ensamblador.php           ← LA FÁBRICA. El archivo de esta versión
│
└── pruebas_humo/
    ├── humo_front.py                 ← el mismo, más la comprobación del motor
    └── humo_los_dos_motores.py       ← NUEVO: lo corre contra los dos
```

`controladores/`, `servicios/Servicio*.php`, `modelos/`, `excepciones/` y
`front_php/vistas/` **no aparecen porque no cambian**. Que no estén en este
árbol es el criterio 14 de la spec visto desde el plan.

**Mover `db/init.sql` a `db/mariadb/init.sql` no es cosmética.** Mientras hubo
un motor, «la base» no necesitaba apellido. Ahora hay dos, y una carpeta
llamada `db/` con un solo `init.sql` adentro habría dejado la duda de a cuál
pertenece.

## 3. Arquitectura: la flecha que se bifurca

```
NAVEGADOR
     → front_php/index.php      (ruta → pantalla)
     → cliente_api.php
     ↓  HTTP + JSON
     → api_gestion/index.php    (método + ruta → controlador)
     → Controlador{Recurso}     ─┐
     → IServicio{Recurso}        │  NADA DE ESTO SE ENTERA
     → IRepositorio{Recurso}    ─┘
                    ↓
            ensamblador.php     ← AQUÍ, y solo aquí, se decide
             ┌──────┴──────┐
             ▼             ▼
   Repositorio*MariaDB   Repositorio*Postgres
             ▼             ▼
         MariaDB       PostgreSQL
```

La bifurcación está **abajo del todo**, y ésa es toda la arquitectura de la
versión. Todo lo que está por encima de `ensamblador.php` recibe una interfaz
y no pregunta qué hay detrás.

## 4. Decisiones de diseño clave

### 4.1 `ensamblador.php`: de armador a fábrica

En la v2 cada función armaba la única combinación que existía. Ahora escoge:

```php
function crearServicioDocente(): IServicioDocente
{
    [$dsn, $usuario, $clave] = datosDeConexion();
    return new ServicioDocente(
        motorActivo() === 'postgres'
            ? new RepositorioDocentePostgres($dsn, $usuario, $clave)
            : new RepositorioDocenteMariaDB($dsn, $usuario, $clave)
    );
}
```

Y `motorActivo()` es tres líneas:

```php
function motorActivo(): string
{
    return strtolower(trim((string) getenv('MOTOR'))) === 'postgres'
        ? 'postgres' : 'mariadb';
}
```

**Fíjese en que la comparación es contra un valor escrito aquí.** El texto de
la variable no se usa para armar nada — si alguien pone `MOTOR=oracle`, no
pasa nada raro: arranca en MariaDB.

**Y siguen siendo dieciséis funciones, no una con un `switch`.** La tentación
creció con el segundo motor: se podría escribir
`crearRepositorio($recurso, $motor)` y armar el nombre de la clase con texto.
Treinta y dos clases en cuatro líneas. Sigue estando mal por lo mismo que la
API no es genérica (Artículo 10):

- con las clases escritas, PHP verifica los tipos al llamar la función;
- con nombres armados en texto, el error sale **en producción**, el día que
  alguien pida el recurso que nadie probó **con el motor que nadie probó** —
  y ahora las combinaciones sin probar son el doble (treinta y dos);
- y quien lea el archivo ve, de un vistazo, qué treinta y dos clases existen.

### 4.2 Una clase por motor, y no un `if` adentro de una sola

La alternativa era un `RepositorioDocente` único con
`if ($motor === 'postgres')` en cada método. Se descartó, y §4.3 explica por
qué mirando lo que de verdad cambia: no es una línea por método, son
**muchas cosas distintas** repartidas.

### 4.3 Lo que cambia entre los dos motores (la tabla que justifica todo)

Ésta es la tabla que hay que mirar antes de opinar sobre si hacía falta
separar las clases:

| | MariaDB | PostgreSQL |
|---|---|---|
| Prefijo del DSN | `mysql:` | `pgsql:` |
| `rowCount()` de un UPDATE | cuenta filas **cambiadas** — hay que pedir `MYSQL_ATTR_FOUND_ROWS` para que cuente las encontradas | cuenta las encontradas, sin pedir nada |
| Leer una llave autogenerada | `lastInsertId()`, **en otra consulta** | `INSERT … RETURNING id`, en la misma |
| Dejar lista la llave autogenerada tras cargar datos con id explícito | el `AUTO_INCREMENT` sigue solo | la secuencia **no avanza**: hay que hacer `setval` al final del `init.sql`, o el primer `POST` choca con un id ya cargado |
| Llave duplicada | código `1062` en `errorInfo[1]` | SQLSTATE `23505` en `getCode()` |
| Referencia rota | `1452` | `23503` |
| Borrar algo con dependientes | `1451` | **`23503` — el mismo**; hay que mirar el texto |
| Parámetro de salida de un procedimiento | `OUT`; PDO no lo lee: hay que usar `@resultado` y **otra consulta** | `INOUT`; **llega como fila del propio `CALL`** |
| Después de un `CALL` | `closeCursor()` obligatorio | no hace falta |
| Rechazo de un procedimiento | `SIGNAL SQLSTATE '45000'` | `RAISE EXCEPTION` → `P0001` |
| El mensaje de ese rechazo | trae el número del motor **delante** | trae un bloque `CONTEXT:` **detrás** |
| Un error a mitad de una transacción | la transacción **sigue abierta**; las sentencias anteriores siguen vivas | la transacción queda **abortada**: nada más funciona hasta el `ROLLBACK` |
| Un trigger | cuerpo escrito directo en `CREATE TRIGGER` | una **función** de trigger aparte, más el `CREATE TRIGGER` que la llama |
| Comparar y ordenar texto (`termino`, `cedula`, `idr`) | por defecto **no distingue** mayúsculas ni acentos | por defecto **sí** distingue, y ordena según la configuración regional |
| Campo de sí/no | `TINYINT(1)`: llega `0`/`1` | `BOOLEAN`: llega `true`/`false` |
| `INT` al leerlo | llega como **texto** | llega como **entero** |
| `NUMERIC`/`DECIMAL` | texto | texto |
| Agregar el detalle en una lista (`/completo`) | `JSON_ARRAYAGG`, que devuelve texto | `json_agg`, que devuelve `NULL` si no hay filas |

Dieciocho diferencias, y ninguna en el `SELECT`. **Ése es el hallazgo que más
sorprende de esta versión:** las consultas resultaron idénticas —esta base usa
SQL estándar— y aun así hacían falta clases separadas, porque **todo lo de
alrededor cambia**.

Un solo `RepositorioDocente` con `if`s habría tenido que acumular esas
diferencias adentro, y habría hecho imposible el criterio 14.

### 4.4 Los errores de integridad: un archivo se partió en tres

La v2 tenía `errores_de_integridad.php`, que hacía dos cosas a la vez:
**reconocer** el problema (mirando los códigos de MariaDB) y **redactarlo** en
español. Mientras hubo un motor eso estaba bien; con dos se vio que eran dos
trabajos distintos, porque uno cambia con el motor y el otro no.

| | ¿Cambia con el motor? | Dónde vive ahora |
|---|---|---|
| Reconocer que fue una llave duplicada | **Sí** (`1062` vs `23505`) | `errores_de_integridad_{motor}.php` |
| Explicárselo al usuario | **No** | `conflictos.php`, compartido |

Si cada motor redactara sus propios mensajes, la MISMA petición contra dos
motores le diría cosas distintas al usuario. El contrato promete un texto, no
un código.

Y en este módulo el reconocimiento tiene más trabajo que en uno de dos
tablas: las **llaves compuestas** de las tablas puente
(`docente_departamento`, `intereses_futuros`, `red_docente`, `estudio_ac`,
`apoyo_profesoral`, `beca`) también producen «llave duplicada», y los
**hijos** del docente y del estudio producen «referencia rota» al crearse y
«dependencias» al borrar al padre. Los dos traductores cubren esos casos con
las mismas tres frases.

**Ésta es la clase de costura que solo se ve cuando llega el segundo motor.**
No es que la v2 estuviera mal: es que con un solo motor no había forma de
saber dónde estaba la frontera.

### 4.5 El docente: lo que calcula la base y el todo-o-nada

El docente es lo más delicado de portar, por dos razones.

**La línea de investigación principal la calcula la base.** El trigger que la
actualiza a partir de los estudios, y el procedimiento de alta con su detalle,
se **reescriben en el dialecto de PostgreSQL** (§4.3: función de trigger
aparte, `RAISE EXCEPTION`, `INOUT`). La API no la recalcula en PHP, ni para
«emparejar» los motores (Artículo 11): si los dos resultados difieren, el
error está en el dialecto portado.

**El alta con detalle es una unidad.** Si un renglón del detalle falla (un
`termino` que no existe, una red inexistente), no queda nada guardado. Con
MariaDB basta con deshacer la transacción; con PostgreSQL, después del error
la transacción **ya está abortada**, y cualquier otra sentencia fallaría con
un mensaje distinto que taparía el original. Por eso `RepositorioDocentePostgres`
hace el `ROLLBACK` **antes** de traducir el error, y los dos terminan
entregando el mismo 409.

### 4.6 Uniformidades que hay que forzar

El contrato promete una forma; cada vez que un motor entrega algo con otra,
el trabajo de emparejarlo es **del repositorio** — nunca del servicio, y
mucho menos de la pantalla:

- **Enteros y decimales.** MariaDB entrega los `INT` como texto y PostgreSQL
  como entero; `calificacion` y `monto` llegan como texto en los dos. Cada
  repositorio convierte a lo que el contrato dice (`(int)`, `(float)`).
- **Campos de sí/no.** `true`/`false` de PostgreSQL y `0`/`1` de MariaDB se
  llevan a **la forma que ya entrega la v2** con MariaDB. El contrato no se
  mueve: se mueve PostgreSQL.
- **Listas vacías.** `json_agg` devuelve `NULL` si no hay filas; el `?? []`
  del repositorio está para que un docente sin estudios no tumbe la pantalla.
- **Orden.** Todo listado se pide con `ORDER BY` de su llave. Sin él, cada
  motor devuelve las filas en el orden que le queda cómodo.
- **Texto como llave.** MariaDB trata `Algoritmo` y `algoritmo` como la misma
  llave; PostgreSQL, por defecto, como dos. La diferencia se cierra **en la
  base**, no en PHP: el `init.sql` de PostgreSQL declara las columnas de llave
  de texto con una *collation* que las compare como MariaDB (la opción a
  evaluar es una collation ICU no determinista; queda registrada en
  4_research.md).
- **La secuencia tras la carga.** Los catálogos del Excel (218 áreas, 191
  programas) entran con su id; al final del `init.sql` de PostgreSQL, un
  `setval` deja la secuencia en el máximo, y el primer registro nuevo recibe
  un id libre.

### 4.7 El motor activo, publicado

`GET /` gana un campo `motor`. Es **el único sitio de la API** donde el motor
se menciona; ninguna otra respuesta lo nombra ni cambia de forma según cuál
sea.

Y el front lo muestra en el pie, en letra pequeña. El sitio es la mitad del
mensaje: al usuario no le importa. Está para poder comprobar la promesa de la
versión sin ir a leer el compose. Es la **única excepción** a la regla de la
v1 que prohíbe mostrar el nombre del motor.

**Lo que el front NO hace con ese dato es lo que importa: nada.** No cambia
una consulta, no oculta un botón, no toma ninguna decisión. El día que el
front pregunte «¿estoy contra PostgreSQL?» para hacer algo distinto, la
separación de capas se rompió.

### 4.8 Cuándo se lee la variable: al arrancar, no en cada petición

Leerla en cada petición habría permitido cambiar de motor sin reiniciar — y
también que dos peticiones simultáneas usaran motores distintos, dejando la
mitad de los datos en cada base. Se lee al arrancar, y cambiar de motor es
reiniciar la API.

### 4.9 Variables de entorno, sin secretos en el código

La conexión de cada motor llega por variables, y el repositorio solo trae el
`.env.example`; el `.env` real va en el `.gitignore`.

```
MOTOR=mariadb
MARIADB_DSN=mysql:host=mariadb;port=3306;dbname=bdgestion_mariadb_local;charset=utf8mb4
MARIADB_USUARIO=...
MARIADB_CLAVE=...
POSTGRES_DSN=pgsql:host=postgres;port=5432;dbname=bdgestion_postgres_local
POSTGRES_USUARIO=...
POSTGRES_CLAVE=...
```

**Lo único de la conexión que cambia entre motores es el DSN** (y las
credenciales, que son otra variable). `datosDeConexion()` devuelve el juego
que corresponde a `motorActivo()`.

## 5. Docker: cuatro servicios y una variable

```yaml
services:
  mariadb:             # 13326 al host · db/mariadb/init.sql · healthcheck
  postgres:            # 15452 al host · db/postgres/init.sql · pg_isready
  api-gestion:         # 8022 · MOTOR: ${MOTOR:-mariadb}
                       #   y los DOS juegos de variables, que viajan siempre
    depends_on: los dos, con condition: service_healthy
  front-php:           # 8020 · URL_API → http://api-gestion:8022
                       #   sin credenciales de base y sin depends_on de ellas
volumes:
  mariadbdata:
  pgdata:
```

**`${MOTOR:-mariadb}`** significa: usa la variable `MOTOR` del entorno, y si no
está, usa `mariadb`. Así el comando de siempre sigue funcionando:

```powershell
docker compose up -d --build            # arranca en MariaDB
$env:MOTOR = "postgres"; docker compose up -d    # lo mismo, contra PostgreSQL
```

**Los dos juegos de credenciales viajan siempre**, y el ensamblador usa el que
corresponda. Se prefirió esto a tener dos servicios de API porque el punto de
la versión es que es el mismo programa.

## 6. Convenciones

Las de la constitución, más una que estrena esta versión: **el nombre del
motor es el apellido de la clase** (`RepositorioDocentePostgres`), y ninguna
clase sin ese apellido puede contener SQL de un motor concreto.

## 7. Chequeo de constitución

| Artículo | Cómo lo cumple esta versión |
|---|---|
| **1** — Propósito didáctico | Todo en español; y la versión entera existe para hacer *visible* un principio que hasta ahora era una frase. |
| **1.1** — Una versión incluye su front | Cumple, aunque el front casi no cambió: lo que se le agregó es la etiqueta del motor, que es lo que permite comprobar la versión desde la pantalla. |
| **2** — PHP puro | Sin framework ni Composer. Lo único que se agregó es una extensión de PDO, que es del lenguaje. |
| **3** — Tres capas estrictas | Cumple, y **ahora se puede demostrar**: el criterio 14 se aprueba con un `diff`. |
| **4** — Un solo comando | `docker compose up -d --build` deja los cuatro servicios funcionando. |
| **5** — Independencia del motor | **Es el artículo de esta versión.** Deja de ser meta y pasa a ser estado: el motor se escoge con configuración y el resto del sistema no se entera. Las dos bases se llaman `bdgestion_mariadb_local` y `bdgestion_postgres_local`. |
| **6** — Persistencia | Dos volúmenes, uno por motor; `down -v` devuelve las dos bases a su estado original. |
| **7** — Recarga natural | PHP reinterpreta cada petición. (Cambiar de motor sí exige reiniciar: es configuración de arranque, no código — §4.8.) |
| **8** — Convenciones fijas | Puertos de esta versión (8020, 8022, 13326, 15452), más la convención del apellido de motor (§6). |
| **9** — Seguridad académica | Conexión por variables de entorno, `.env` fuera del repositorio; `htmlspecialchars` en todo lo que se pinta. |
| **10** — API específica | Las rutas no cambiaron. Y la fábrica siguió la misma regla: treinta y dos clases escritas, ningún nombre armado con texto (§4.1). |
| **11** — La base también es parte del sistema | Se cumple **dos veces**: los mismos triggers y procedimientos existen en los dos motores, y la API los usa en los dos sin recalcular nada (§4.5). |

**Complejidad justificada:** la desviación consciente de esta versión es
depender del **texto** de un mensaje del motor en dos sitios: para saber en
qué sentido falló una llave foránea en PostgreSQL (`23503` sirve para dos
casos opuestos, §4.3), y para reconocer el rechazo de un procedimiento
(`SIGNAL` vs `P0001`) y entregar un 404 igual con los dos al reemplazar un
docente inexistente. Las dos están aisladas en un método por motor, con su
fragilidad escrita al lado.
