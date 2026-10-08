# Modelo de datos — Versión 1: la BD completa, la API solo usa `producto`

> **Versión 1** · Decisión clave: **la base de datos `bdgestion` se crea
> COMPLETA desde el inicio** (las 19 tablas y sus datos de ejemplo). Usted ya vio bases de
> datos — la BD no es lo que se construye por versiones: **lo que crece
> versión a versión es la API**. La v1 solo usa las tablas `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red`; el resto
> ya está ahí, esperando a las versiones siguientes.

---

## 1. El script de la BD (artefacto de esta versión)

El script completo viene **provisto** en el repositorio: **`db/init.sql`**
(~1150 líneas: 19 tablas, restricciones, secuencias y datos de ejemplo en dialecto MySQL/MariaDB). **Se copia tal cual — no se escribe ni se
genera con IA.** Es infraestructura dada, igual que la imagen de MariaDB.

Vista rápida de lo que crea:

```
Independientes:  area_conocimiento · termino_clave · linea_investigacion · programa · red
                 rol · usuario
Con FK:          docente · estudios_realizados · docente_departamento · intereses_futuros · evaluacion_docente
                 reconocimiento · experiecia · red_docente · estudio_ac · apoyo_profesoral · beca · rol_usuario
```

## 2. Las tablas que la v1 SÍ usa: `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red`

**`area_conocimiento`**

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | VARCHAR(6) | **PK** | Identificador legible ('1A01'…) |
| `gran_area` | VARCHAR(60) | NOT NULL | Categoría gran área de conocimiento, hace parte del catálogo que es area_conocimiento |
| `area` | VARCHAR(60) | NOT NULL | Subcategoría de gran área de conocimiento |
| `disciplina` | VARCHAR(150) | NOT NULL | Subcategoría de área |

Datos de ejemplo: **8 area_conocimiento** (1A01, Ciencias Naturales, Matemáticas, Matemáticas puras … 1C03, Ciencias Naturales, Ciencias físicas, Física de partículas y campos).

**`termino_clave`**

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `termino` | VARCHAR(30) | **PK** | Palabra clave de busqueda |
| `termino_ingles` | VARCHAR(30) | NOT NULL | Palabra clave de busqueda en inglés |

**`linea_investigacion`**

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | INTEGER | **PK** | Identificador autoincremental |
| `nombre` | VARCHAR(45) | NOT NULL | Nombre de la linea de investigación |
| `descripción` | VARCHAR(256) | NOT NULL | Descripción de la linea de investigación |

**`programa`**

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `id` | INTEGER | **PK** | Identificador del programa académico |
| `nombre` | VARCHAR(150) | NOT NULL | Nombre del programa académico |
| `tipo` | VARCHAR(45) | NOT NULL | Tipo de programa académico |
| `nivel` | VARCHAR(45) | NOT NULL | Nivel del programa académico |
| `fecha_creación` | VARCHAR(45) | NOT NULL | Fecha de creación del programa académico |
| `fecha_cierre` | VARCHAR(45) | NOT NULL | Fecha de cierre del programa académico |
| `numero_cohortes` | VARCHAR(45) | NOT NULL | Número de cohortes del programa académico |
| `cant_graduados` | VARCHAR(45) | NOT NULL | Cantidad de graduados del programa académico |
| `fecha_actualización` | VARCHAR(45) | NOT NULL | Fecha de actualización del programa académico |
| `ciudad` | VARCHAR(45) | NOT NULL | Ciudad del programa académico |
| `facultad` | INTEGER | NOT NULL | Facultad del programa académico |

Datos de ejemplo: **8 programas** (10101, Administración de Empresas, Programa Académico, Pregrado, sin dato, NULL, sin dato, sin dato, sin dato, Bogotá, 101 … 0204, Maestría en Derecho y Administración de Justicia, Programa Académico, Maestría, sin dato, NULL, sin dato, sin dato, sin dato, Bogotá, 102).

**`red`**

| Columna | Tipo | Restricción | Descripción |
|---|---|---|---|
| `idr` | INTEGER | **PK** | Identificador de las redes académicas |
| `nombre` | VARCHAR(45) | NOT NULL | Nombre de las redes académicas |
| `url` | VARCHAR(45) | NOT NULL | URL de las redes académicas |
| `pais` | VARCHAR(45) | NOT NULL | Pais de las redes académicas |

**Notas para la API:**
- Los valores requeridos (como `id`, `nombre`, `tipo`, `nivel`, etc.) y las longitudes máximas de texto los garantiza la validación del controlador en la API (la frontera de entrada, con código HTTP 422).
- El driver mysql de PDO entrega valores numéricos como `string` o según el tipo de columna — el repositorio realiza los casts explícitos al construir los objetos del modelo (`id → int`, `facultad → int`).
- El borrado de programas en la base de datos es LÓGICO (`activo = FALSE`): las lecturas y actualizaciones en el repositorio filtran siempre por `activo = TRUE` para no listar programas retirados ni permitir operaciones sobre ellos.

## 3. Montar la BD para la v1

La BD es el servicio `mariadb` del `docker-compose.yml` del proyecto
(ver [3_plan.md](3_plan.md) §5). Para levantarla sola durante las primeras
fases de construcción:

```powershell
docker compose up -d mariadb      # desde la raíz del proyecto
```

Conexión PDO para la API (variables de entorno):

```
# API corriendo EN el compose (las inyecta docker-compose.yml, host interno):
DB_DSN     = mysql:host=mariadb;port=3306;dbname=bdgestion_mariadb_local
DB_USUARIO = paradigmas
DB_CLAVE   = paradigmas123

# API corriendo LOCAL (php -S), la BD publica el puerto 13326 al host:
DB_DSN     = mysql:host=localhost;port=13326;dbname=bdgestion_mariadb_local
```

Verificación: un cliente SQL debe ver **19 tablas** y `SELECT count(*) FROM
area_conocimiento` debe dar **218**.

## 4. Qué toca la API en cada versión (la BD no cambia)

| Versión | Tablas que usa el código (API **y** pantalla) |
|---|---|
| **v1** | `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red` — nada más |
| v2 | + docente, estudios_realizados, docente_departamento, intereses_futuros, evaluacion_docente, reconocimiento, experiecia, red_docente, estudio_ac, apoyo_profesoral, beca, rol_usuario |
| v3–v4 | las mismas, contra más motores |

La columna dice «API **y** pantalla» a propósito: cada versión trae el front
de las tablas que agrega (Artículo 1.1 de la [constitución](../../1_constitution.md)). No hay una versión final que «muestre todo lo demás».

**Regla de la v1:** el código —el de la API y el del front— solo puede
nombrar las tablas `area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa` y `red`. Que las otras 14 existan no es invitación a usarlas — eso es alcance de las versiones siguientes.
