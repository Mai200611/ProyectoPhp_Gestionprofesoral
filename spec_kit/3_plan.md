# Plan técnico — Versión 1: cinco recursos de punta a punta (front + API + MariaDB)

> **Versión 1** · CÓMO construir lo especificado en la especificación funcional.

> El porqué de cada decisión: documentación de arquitectura · contratos exactos: contratos de API · orden: tareas de implementación.

---

## 1. Stack

| Pieza                    | Elección                                                        | Por qué                                                                               |
| ------------------------ | --------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| Lenguaje                 | **PHP 8.3** (`declare(strict_types=1)`)                         | Es el lenguaje del curso y permite trabajar tipos escalares y de retorno.             |
| Framework                | **NINGUNO** (PHP puro)                                          | Se mantiene el enfoque didáctico y se evita ocultar la lógica detrás de un framework. |
| Gestor de paquetes       | **NINGUNO** (sin Composer, sin `vendor/`)                       | Se reducen dependencias externas.                                                     |
| Enrutamiento             | Front controller `index.php`, con comparaciones simples de ruta | El enrutamiento permanece visible y comprensible.                                     |
| Acceso a datos           | **PDO** con prepared statements                                 | El SQL permanece visible y parametrizado.                                             |
| Servidor API             | **PHP built-in server** (`php -S 0.0.0.0:8022 index.php`)       | No requiere Apache ni nginx para desarrollo.                                          |
| Contenedor API           | `php:8.3-cli` + extensión PDO para MariaDB/MySQL                | Permite que la API se conecte a MariaDB.                                              |
| Front                    | **PHP puro**, con su propio front controller y vistas `.php`    | El mismo lenguaje se utiliza en toda la aplicación.                                   |
| Comunicación front → API | **cURL**                                                        | La comunicación HTTP queda explícita y separada del código de las vistas.             |
| Contenedor front         | `php:8.3-cli` **sin extensión de base de datos**                | El front no puede conectarse directamente a MariaDB.                                  |
| Estilos                  | **Bootstrap 5.3 local** + estilos propios                       | Las pantallas funcionan sin depender de un CDN externo.                               |

---

# 2. Estructura de carpetas

```text
(raíz del proyecto)

├── docker-compose.yml
│
├── db/
│   └── init.sql
│
├── front_php/
│   ├── Dockerfile
│   ├── index.php
│   ├── cliente_api.php
│   │
│   ├── vistas/
│   │   ├── plantilla.php
│   │   ├── inicio.php
│   │   ├── area_conocimiento_lista.php
│   │   ├── area_conocimiento_formulario.php
│   │   ├── termino_clave_lista.php
│   │   ├── termino_clave_formulario.php
│   │   ├── linea_investigacion_lista.php
│   │   ├── linea_investigacion_formulario.php
│   │   ├── programa_lista.php
│   │   ├── programa_formulario.php
│   │   ├── red_lista.php
│   │   ├── red_formulario.php
│   │   └── no_encontrada.php
│   │
│   └── publico/
│       ├── bootstrap.min.css
│       ├── bootstrap.bundle.min.js
│       └── estilos.css
│
└── api_facturas/
    ├── Dockerfile
    ├── index.php
    │
    ├── modelos/
    │   ├── AreaConocimiento.php
    │   ├── TerminoClave.php
    │   ├── LineaInvestigacion.php
    │   ├── Programa.php
    │   └── Red.php
    │
    ├── controladores/
    │   ├── ControladorAreaConocimiento.php
    │   ├── ControladorTerminoClave.php
    │   ├── ControladorLineaInvestigacion.php
    │   ├── ControladorPrograma.php
    │   └── ControladorRed.php
    │
    ├── servicios/
    │   ├── IServicioAreaConocimiento.php
    │   ├── ServicioAreaConocimiento.php
    │   ├── IServicioTerminoClave.php
    │   ├── ServicioTerminoClave.php
    │   ├── IServicioLineaInvestigacion.php
    │   ├── ServicioLineaInvestigacion.php
    │   ├── IServicioPrograma.php
    │   ├── ServicioPrograma.php
    │   ├── IServicioRed.php
    │   ├── ServicioRed.php
    │   └── ensamblador.php
    │
    ├── repositorios/
    │   ├── IRepositorioAreaConocimiento.php
    │   ├── RepositorioAreaConocimientoMariaDB.php
    │   ├── IRepositorioTerminoClave.php
    │   ├── RepositorioTerminoClaveMariaDB.php
    │   ├── IRepositorioLineaInvestigacion.php
    │   ├── RepositorioLineaInvestigacionMariaDB.php
    │   ├── IRepositorioPrograma.php
    │   ├── RepositorioProgramaMariaDB.php
    │   ├── IRepositorioRed.php
    │   └── RepositorioRedMariaDB.php
    │
    ├── excepciones/
    │   └── NoEncontradoExcepcion.php
    │
    └── pruebas/
        └── prueba_capas.php
       
└── pruebas_humo/
    └── humo_front.py
```

La separación conserva la idea fundamental del plan original: el **front no conoce MariaDB**, mientras que la API concentra el acceso a datos.

---

# 3. Arquitectura en capas

```text
NAVEGADOR

    ↓

front_php/index.php
(front controller del FRONT)

    ↓

cliente_api.php
(única pieza del front que habla HTTP)

    ↓

HTTP + JSON

    ↓

api_facturas/index.php
(front controller de la API)

    ↓

Controlador específico
(ControladorAreaConocimiento
 ControladorTerminoClave
 ControladorLineaInvestigacion
 ControladorPrograma
 ControladorRed)

    ↓

IServicio específico

    ↓

IRepositorio específico

    ↓

Repositorio específico MariaDB

    ↓

MariaDB
```

La regla de dependencias continúa siendo:

```text
controlador
    ↓
servicio
    ↓
interfaz de repositorio
    ↓
repositorio MariaDB
    ↓
base de datos
```

Solo `ensamblador.php` conoce las clases concretas.

La flecha que **no existe** es:

```text
FRONT ─────────X────────→ MariaDB
```

El front no llama directamente una función PHP de la API. Envía una **petición HTTP** y recibe **JSON**. Esta separación es precisamente uno de los principios centrales del plan original.

---

# 4. Decisiones de diseño clave

## 4.1 Interfaces nativas de PHP desde v1

Cada recurso tendrá su propio contrato de repositorio.

### Área de conocimiento

```php
interface IRepositorioAreaConocimiento
{
    public function obtenerTodos(int $limite): array;

    public function obtenerPorId(int $id): ?AreaConocimiento;

    public function crear(AreaConocimiento $area): bool;

    public function actualizar(int $id, array $datos): int;

    public function eliminar(int $id): int;
}
```

### Término clave

```php
interface IRepositorioTerminoClave
{
    public function obtenerTodos(int $limite): array;

    public function obtenerPorTermino(string $termino): ?TerminoClave;

    public function crear(TerminoClave $termino): bool;

    public function actualizar(string $termino, array $datos): int;

    public function eliminar(string $termino): int;
}
```

### Línea de investigación

```php
interface IRepositorioLineaInvestigacion
{
    public function obtenerTodos(int $limite): array;

    public function obtenerPorId(int $id): ?LineaInvestigacion;

    public function crear(LineaInvestigacion $linea): bool;

    public function actualizar(int $id, array $datos): int;

    public function eliminar(int $id): int;
}
```

### Programa

```php
interface IRepositorioPrograma
{
    public function obtenerTodos(int $limite): array;

    public function obtenerPorId(int $id): ?Programa;

    public function crear(Programa $programa): bool;

    public function actualizar(int $id, array $datos): int;

    public function eliminar(int $id): int;
}
```

### Red

```php
interface IRepositorioRed
{
    public function obtenerTodos(int $limite): array;

    public function obtenerPorIdr(int $idr): ?Red;

    public function crear(Red $red): bool;

    public function actualizar(int $idr, array $datos): int;

    public function eliminar(int $idr): int;
}
```

La decisión conserva el principio del documento original: las lecturas devuelven **objetos del modelo**, mientras que `actualizar()` recibe un `array` porque PATCH puede modificar solamente una parte de los atributos.

---

## 4.2 Modelos clásicos específicos por recurso

Cada tabla tendrá una entidad propia.

### `AreaConocimiento`

La entidad representa los atributos definidos para `area_conocimiento`:

```text
id
gran_area
area
disciplina
```

Su modelo tendrá propiedades privadas, constructor según corresponda, getters, setters y `toArray()`.

---

### `TerminoClave`

Representa:

```text
termino
termino_ingles
```

El identificador propio del recurso es:

```text
termino
```

---

### `LineaInvestigacion`

Representa:

```text
id
nombre
descripcion
```

El identificador propio del recurso es:

```text
id
```

---

### `Programa`

Representa los atributos definidos para el recurso:

```text
id
nombre
tipo
nivel
fechas
cohortes
graduados
ciudad
facultad
```

Los nombres y tipos exactos de cualquier campo compuesto o de fechas deberán respetar el esquema real proporcionado en `init.sql`; este plan no agrega restricciones que no estén definidas allí.

---

### `Red`

Representa:

```text
idr
nombre
url
pais
```

El identificador propio del recurso es:

```text
idr
```

---

## 4.3 La validación vive en los controladores

El principio del documento original se conserva: la frontera HTTP es responsabilidad del controlador.

Cada controlador tendrá validaciones específicas para su recurso.

Por ejemplo:

```text
ControladorAreaConocimiento
    ↓
validar body
    ↓
ServicioAreaConocimiento
```

y:

```text
ControladorPrograma
    ↓
validar body
    ↓
ServicioPrograma
```

La validación deberá comprobar:

* existencia de los campos obligatorios;
* tipo de dato recibido;
* formato cuando el contrato lo establezca;
* campos permitidos;
* campos desconocidos;
* diferencias entre POST, PUT y PATCH.

No se deben inventar reglas como longitudes máximas, rangos numéricos o formatos especiales cuando esas restricciones no estén definidas por el contrato o por el esquema de la base de datos.

---

## 4.4 PUT y PATCH

Se conserva la distinción del plan original.

### PUT

Representa un reemplazo completo.

```text
PUT
 ↓
se esperan todos los campos obligatorios
 ↓
se reemplaza la información del recurso
```

### PATCH

Representa una actualización parcial.

```text
PATCH
 ↓
solo llegan los campos que se desean modificar
 ↓
solo esos campos se actualizan
```

Esto permite que el front tenga los dos comportamientos:

```text
Guardar la ficha completa
        ↓
       PUT

Guardar solo lo que cambié
        ↓
      PATCH
```

La razón conceptual es la misma que en el plan base: PUT y PATCH no son dos botones arbitrarios, sino dos formas distintas de expresar qué información se está enviando.

---

# 5. Recursos específicos de la API

La API no utilizará un endpoint genérico como:

```text
/api/{tabla}
```

Cada recurso tendrá su ruta específica.

## 5.1 Área de conocimiento

```text
/api/area_conocimiento
/api/area_conocimiento/{id}
```

Operaciones:

```text
GET
POST
PUT
PATCH
DELETE
```

---

## 5.2 Término clave

```text
/api/termino_clave
/api/termino_clave/{termino}
```

Operaciones:

```text
GET
POST
PUT
PATCH
DELETE
```

---

## 5.3 Línea de investigación

```text
/api/linea_investigacion
/api/linea_investigacion/{id}
```

Operaciones:

```text
GET
POST
PUT
PATCH
DELETE
```

---

## 5.4 Programa

```text
/api/programa
/api/programa/{id}
```

Operaciones:

```text
GET
POST
PUT
PATCH
DELETE
```

---

## 5.5 Red

```text
/api/red
/api/red/{idr}
```

Operaciones:

```text
GET
POST
PUT
PATCH
DELETE
```

La especificidad de las rutas también se aplica a las funciones del front: habrá una función para cada operación y recurso, en lugar de una función genérica que reciba el nombre de una tabla. Esto sigue directamente el principio del Artículo 10 documentado en el plan.

---

# 6. SQL de los repositorios

Cada repositorio tendrá SQL explícito y preparado mediante PDO.

## Área de conocimiento

```sql
SELECT id, gran_area, area, disciplina
FROM area_conocimiento
ORDER BY id
LIMIT :limite;
```

```sql
SELECT id, gran_area, area, disciplina
FROM area_conocimiento
WHERE id = :id;
```

```sql
INSERT INTO area_conocimiento (...)
VALUES (...);
```

```sql
UPDATE area_conocimiento
SET ...
WHERE id = :id;
```

```sql
DELETE FROM area_conocimiento
WHERE id = :id;
```

---

## Término clave

```sql
SELECT termino, termino_ingles
FROM termino_clave
ORDER BY termino
LIMIT :limite;
```

```sql
SELECT termino, termino_ingles
FROM termino_clave
WHERE termino = :termino;
```

```sql
INSERT INTO termino_clave (...)
VALUES (...);
```

```sql
UPDATE termino_clave
SET ...
WHERE termino = :termino;
```

```sql
DELETE FROM termino_clave
WHERE termino = :termino;
```

---

## Línea de investigación

```sql
SELECT id, nombre, descripcion
FROM linea_investigacion
ORDER BY id
LIMIT :limite;
```

```sql
SELECT id, nombre, descripcion
FROM linea_investigacion
WHERE id = :id;
```

```sql
INSERT INTO linea_investigacion (...)
VALUES (...);
```

```sql
UPDATE linea_investigacion
SET ...
WHERE id = :id;
```

```sql
DELETE FROM linea_investigacion
WHERE id = :id;
```

---

## Programa

```sql
SELECT
    id,
    nombre,
    tipo,
    nivel,
    fechas,
    cohortes,
    graduados,
    ciudad,
    facultad
FROM programa
ORDER BY id
LIMIT :limite;
```

Los demás `SELECT`, `INSERT`, `UPDATE` y `DELETE` deberán utilizar exactamente las columnas existentes en el esquema de `programa`.

---

## Red

```sql
SELECT idr, nombre, url, pais
FROM red
ORDER BY idr
LIMIT :limite;
```

```sql
SELECT idr, nombre, url, pais
FROM red
WHERE idr = :idr;
```

```sql
INSERT INTO red (...)
VALUES (...);
```

```sql
UPDATE red
SET ...
WHERE idr = :idr;
```

```sql
DELETE FROM red
WHERE idr = :idr;
```

Todos los valores provenientes del cliente deberán viajar mediante parámetros preparados.

---

# 7. Conexión PDO

La conexión se realizará de forma perezosa y reutilizable.

Se conservarán las decisiones del plan base:

```php
PDO::ERRMODE_EXCEPTION
```

y:

```php
PDO::ATTR_EMULATE_PREPARES => false
```

Además, para MariaDB se deberá enlazar `LIMIT` como entero:

```php
$sentencia->bindValue(
    ':limite',
    $limite,
    PDO::PARAM_INT
);
```

El plan original identifica expresamente esta consideración como necesaria para MariaDB.

---

# 8. Traducción de excepciones a HTTP

La API mantendrá un catálogo uniforme:

| Situación                                  | HTTP |
| ------------------------------------------ | ---: |
| Body con errores de forma                  |  422 |
| Regla de negocio inválida                  |  400 |
| Recurso inexistente                        |  404 |
| Error de BD u otra excepción no controlada |  500 |

Por ejemplo:

```text
NoEncontradoExcepcion
        ↓
       404
```

Mientras que un error de validación:

```text
datos inválidos
        ↓
       422
```

El sobre de respuesta seguirá la forma establecida por el contrato:

```json
{
    "estado": "...",
    "mensaje": "...",
    "detalle": "..."
}
```

Cuando corresponda a validación:

```json
{
    "estado": "...",
    "mensaje": "...",
    "errores": []
}
```

La traducción de excepciones permanece en el controlador, como en el diseño original.

---

# 9. Front controller de la API

`api_facturas/index.php` será el único punto de entrada de la API.

Su trabajo será:

1. Activar tipos estrictos.
2. Cargar las clases mediante `require`.
3. Establecer:

```php
header('Content-Type: application/json; charset=utf-8');
```

4. Leer:

```php
$_SERVER['REQUEST_METHOD']
```

5. Obtener la ruta desde:

```php
$_SERVER['REQUEST_URI']
```

6. Identificar el recurso.
7. Identificar el verbo HTTP.
8. Delegar al controlador correspondiente.
9. Devolver JSON.
10. Responder 404 cuando la ruta no exista.
11. Responder 405 cuando el verbo no corresponda.

La implementación seguirá siendo deliberadamente explícita mediante `if`/`elseif`, tal como establece el plan original.

---

# 10. Diseño del front

El front conservará tres responsabilidades principales:

| Archivo           | Su único trabajo                     | Lo que NO hace                  |
| ----------------- | ------------------------------------ | ------------------------------- |
| `cliente_api.php` | Hablar HTTP con la API               | No pinta HTML ni accede a BD    |
| `index.php`       | Interpretar ruta y seleccionar vista | No arma SQL                     |
| `vistas/*.php`    | Pintar HTML                          | No llaman directamente a la API |

`cliente_api.php` será el equivalente al repositorio desde la perspectiva del front.

Su responsabilidad será transformar la comunicación HTTP en una estructura sencilla para las vistas:

```php
[
    'ok' => bool,
    'datos' => mixed,
    'errores' => string[]
]
```

Esta decisión conserva exactamente la idea del documento original: las vistas no necesitan conocer si la API respondió 200, 204, 404 o 500; reciben una estructura que pueden interpretar.

---

# 11. Funciones específicas del cliente API

No se utilizará:

```php
listar($tabla)
```

ni:

```php
crear($tabla, $datos)
```

En cambio, existirán funciones explícitas.

### Área de conocimiento

```text
listar_areas_conocimiento()
obtener_area_conocimiento()
crear_area_conocimiento()
actualizar_area_conocimiento()
eliminar_area_conocimiento()
```

### Término clave

```text
listar_terminos_clave()
obtener_termino_clave()
crear_termino_clave()
actualizar_termino_clave()
eliminar_termino_clave()
```

### Línea de investigación

```text
listar_lineas_investigacion()
obtener_linea_investigacion()
crear_linea_investigacion()
actualizar_linea_investigacion()
eliminar_linea_investigacion()
```

### Programa

```text
listar_programas()
obtener_programa()
crear_programa()
actualizar_programa()
eliminar_programa()
```

### Red

```text
listar_redes()
obtener_red()
crear_red()
actualizar_red()
eliminar_red()
```

La repetición es intencional: hace visible qué recurso maneja cada función y evita ocultar el diseño detrás de una abstracción genérica.

---

# 12. El 204 no es un error

Cuando un listado no tenga registros:

```text
API
 ↓
204
 ↓
cliente_api.php
 ↓
ok = true
datos = []
 ↓
vista
 ↓
mensaje de que todavía no existen registros
```

Por tanto:

```text
204 ≠ error
```

El cliente API será responsable de traducir esta situación a una estructura que la vista pueda manejar sin conocer detalles HTTP.

Esta misma separación se utiliza en el plan original para distinguir «no existen registros» de «la API falló».

---

# 13. API caída

Debe mantenerse la distinción:

```text
API respondió con 404
```

no significa lo mismo que:

```text
API no respondió
```

Por eso:

```php
llamar_api()
```

podrá representar una ausencia de respuesta mediante:

```php
null
```

Mientras que una respuesta HTTP válida, incluso si es un error, representa que:

```text
la API sí está funcionando
```

El front utilizará esta diferencia para mostrar un mensaje apropiado cuando la API esté caída.

---

# 14. Vistas del front

Cada recurso tendrá su pantalla de listado y su formulario cuando corresponda.

## Área de conocimiento

```text
/area_conocimiento
/area_conocimiento/nuevo
/area_conocimiento/{id}/editar
```

La lista mostrará los atributos propios del recurso:

```text
ID
Gran área
Área
Disciplina
```

---

## Término clave

```text
/terminos_clave
/terminos_clave/nuevo
/terminos_clave/{termino}/editar
```

La lista mostrará:

```text
Término
Término en inglés
```

---

## Línea de investigación

```text
/lineas_investigacion
/lineas_investigacion/nuevo
/lineas_investigacion/{id}/editar
```

La lista mostrará:

```text
ID
Nombre
Descripción
```

---

## Programa

```text
/programas
/programas/nuevo
/programas/{id}/editar
```

La pantalla utilizará los atributos propios de `programa`:

```text
ID
Nombre
Tipo
Nivel
Fechas
Cohortes
Graduados
Ciudad
Facultad
```

---

## Red

```text
/redes
/redes/nuevo
/redes/{idr}/editar
```

La lista mostrará:

```text
IDR
Nombre
URL
País
```

Estas rutas son la propuesta de organización del front para los cinco recursos; deberán coincidir con las rutas definitivas establecidas en los contratos antes de implementar.

---

# 15. Formularios

Cada formulario permitirá:

```text
Crear
Editar
Guardar cambios
Eliminar
```

La edición conservará los dos comportamientos:

```text
Guardar la ficha completa
        ↓
       PUT
```

y:

```text
Guardar solo lo que cambié
        ↓
      PATCH
```

El front no decidirá si un valor es válido desde el punto de vista del negocio.

Su responsabilidad será preparar correctamente los datos para enviarlos a la API.

---

# 16. Conversión de datos del formulario

Los formularios HTML producen valores como texto.

Por ejemplo:

```text
"191"
```

aunque conceptualmente represente un número.

Por ello el front podrá ajustar la forma de los datos antes de enviarlos.

La responsabilidad queda dividida así:

```text
FRONT
 ↓
forma del dato

API
 ↓
validez del dato
```

El documento original hace explícita esta diferencia: convertir `"12"` a `12` no significa validar que el valor sea aceptable para el negocio.

---

# 17. Avisos entre pantallas

Después de crear, actualizar o eliminar:

```text
POST/PUT/PATCH/DELETE
        ↓
redirigir
        ↓
listado
```

Se utilizará el patrón:

```text
Post/Redirect/Get
```

Los mensajes temporales podrán viajar mediante la sesión PHP:

```text
redirigir_con()
aviso_pendiente()
```

De esta forma, refrescar el listado no vuelve a ejecutar la operación anterior.

---

# 18. Archivos estáticos

El servidor embebido PHP utilizará el front controller.

Para no interpretar archivos como:

```text
/publico/estilos.css
/publico/bootstrap.min.css
/publico/bootstrap.bundle.min.js
```

como rutas de aplicación, el router deberá devolver:

```php
false
```

cuando el archivo solicitado exista físicamente.

La razón es exactamente la documentada en el plan original: cuando el router procesa todas las peticiones, un archivo CSS puede terminar recibiendo una respuesta HTML 404 en lugar del contenido CSS.

---

# 19. Docker

La Versión 1 continuará utilizando un solo comando:

```bash
docker compose up -d --build
```

Servicios:

```text
mariadb
api-facturas
front-php
```

## MariaDB

```text
mariadb:11
```

Utilizará:

```text
db/init.sql
```

y un volumen para conservar los datos.

---

## API

```text
api-facturas
```

Utilizará:

```text
php:8.3-cli
```

con la extensión necesaria para MariaDB.

Su servidor:

```bash
php -S 0.0.0.0:8022 index.php
```

---

## Front

```text
front-php
```

Utilizará:

```text
php:8.3-cli
```

sin `pdo_mysql`.

Su servidor:

```bash
php -S 0.0.0.0:8020 index.php
```

El front recibirá:

```text
URL_API=http://api-facturas:8022
```

y **no** recibirá:

```text
DB_DSN
DB_USUARIO
DB_CLAVE
```

Además, no tendrá dependencia directa de `mariadb`.

El principio se mantiene idéntico al del documento base: el aislamiento debe estar reforzado por la propia infraestructura y no solamente por una regla escrita.

---

# 20. Variables de entorno

La API utilizará variables de entorno para la conexión:

```text
DB_DSN
DB_USUARIO
DB_CLAVE
```

El ensamblador será el único lugar donde se construirá la implementación concreta:

```text
RepositorioAreaConocimientoMariaDB
RepositorioTerminoClaveMariaDB
RepositorioLineaInvestigacionMariaDB
RepositorioProgramaMariaDB
RepositorioRedMariaDB
```

La idea conserva la decisión del plan original: en v1 existe un solo motor y no se crea una fábrica innecesariamente compleja.

---

# 21. Ensamblador

El archivo:

```text
api_facturas/servicios/ensamblador.php
```

será responsable de conectar interfaces con implementaciones concretas.

Conceptualmente:

```text
IRepositorioAreaConocimiento
        ↓
RepositorioAreaConocimientoMariaDB
        ↓
ServicioAreaConocimiento
```

y lo mismo para los otros cuatro recursos.

Los controladores no deberán crear directamente repositorios concretos.

---

# 22. Pruebas de capas

Se mantendrá:

```text
api_facturas/pruebas/prueba_capas.php
```

La prueba podrá utilizar repositorios falsos.

Por ejemplo:

```text
Controlador
    ↓
Servicio
    ↓
Repositorio falso
    ↓
SIN MariaDB
```

Esto permite comprobar que el servicio realmente depende de una **interfaz** y no de una implementación concreta.

---

# 23. Pruebas de humo

Se conservará:

```text
pruebas_humo/
└── humo_front.py
```

Las pruebas deberán comprobar el recorrido desde la pantalla.

Se verificarán como mínimo:

```text
inicio
 ↓
listados
 ↓
crear
 ↓
editar
 ↓
PATCH
 ↓
eliminar
```

para los recursos definidos en la versión.

También se comprobarán los archivos estáticos y su tipo de contenido, siguiendo el aprendizaje obtenido en el plan original.

---

# 24. Convenciones

Se mantienen las convenciones de la constitución:

* Todo el código en español.
* Comentario de apertura con el rol arquitectónico en cada archivo.
* Clases en PascalCase.
* Interfaces con prefijo `I`.
* Métodos y variables en camelCase.
* Un archivo por clase.
* `declare(strict_types=1)` en los archivos PHP.
* SQL preparado mediante PDO.
* Sin framework.
* Sin Composer.
* Sin acceso directo del front a la base de datos.

El plan original fija expresamente estas convenciones.

---

# 25. Chequeo de constitución

| Artículo                                 | Cómo lo cumple esta versión                                                                                 |
| ---------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| **1 — Propósito didáctico**              | Código explícito, en español y organizado para que cada responsabilidad pueda estudiarse.                   |
| **1.1 — Una versión incluye su FRONT**   | La versión incluye el front de los cinco recursos.                                                          |
| **2 — PHP puro**                         | No se utiliza framework ni Composer.                                                                        |
| **3 — Arquitectura de 3 capas estricta** | Front, API y MariaDB están separados.                                                                       |
| **4 — Un solo comando**                  | `docker compose up -d --build`.                                                                             |
| **5 — Independencia del motor**          | Los servicios dependen de interfaces de repositorio; en esta versión la implementación concreta es MariaDB. |
| **6 — Persistencia**                     | MariaDB utiliza volumen.                                                                                    |
| **7 — Desarrollo con recarga natural**   | El código PHP se monta y PHP lo interpreta en cada petición.                                                |
| **8 — Convenciones fijas**               | Se mantienen puertos, rutas, respuestas y convenciones establecidas por el proyecto.                        |
| **9 — Seguridad académica**              | PDO preparado y salida HTML escapada.                                                                       |
| **10 — API específica**                  | Cada tabla posee sus propios endpoints, controladores, servicios, repositorios y funciones del cliente API. |

---

# 26. Complejidad justificada

La repetición entre recursos es **intencional**.

No se crea:

```php
RepositorioGenerico
```

ni:

```php
ControladorGenerico
```

ni:

```php
listar($tabla)
```

ni:

```php
crear($tabla, $datos)
```

porque el propósito de esta versión es que cada recurso pueda estudiarse de forma independiente.

La API específica es una decisión explícita del proyecto: el plan original señala que la API debe ser específica y que el front debe tener una función por operación, en lugar de convertir las tablas en parámetros de una implementación genérica.

La complejidad adicional de tener cinco conjuntos de clases se justifica porque permite observar directamente:

```text
AreaConocimiento
    ↓
ControladorAreaConocimiento
    ↓
ServicioAreaConocimiento
    ↓
IRepositorioAreaConocimiento
    ↓
RepositorioAreaConocimientoMariaDB
```

y repetir el mismo patrón para:

```text
TerminoClave
LineaInvestigacion
Programa
Red
```

sin esconder las diferencias propias de cada recurso.

---

# 27. Límite de esta versión

Esta versión implementa los cinco recursos de manera independiente.

No se deben agregar relaciones entre las tablas si estas no forman parte del contrato de esta versión.

Por tanto, no se incorporan automáticamente:

* búsquedas cruzadas;
* filtros avanzados;
* relaciones entre recursos;
* endpoints genéricos;
* autenticación;
* autorización;
* selección automática entre múltiples motores;
* funcionalidades que pertenezcan a versiones posteriores.

La versión debe permanecer enfocada en construir cada recurso **de punta a punta**:

```text
BD
 ↓
Repositorio
 ↓
Servicio
 ↓
Controlador
 ↓
API
 ↓
Cliente API
 ↓
Front controller
 ↓
Vista
 ↓
Navegador
```

y comprobar que todo el recorrido funciona sin romper la separación arquitectónica.
