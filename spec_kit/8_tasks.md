# Tareas — Versión 1: catálogos de punta a punta (front + API + MariaDB)

> **Versión 1** · El orden de construcción, partiendo de CERO. Cada fase termina
> en algo **verificable**. Requisitos: [2_spec.md](2_spec.md) · técnica:
> [3_plan.md](3_plan.md) · contratos: [6_contracts.md](6_contracts.md) ·
> validación final: [7_quickstart.md](7_quickstart.md).

---

## Fase 0 — Base de datos y esqueleto
- [ ] Copiar a `db/init.sql` el script **provisto** con esta versión (la BD `bdgestion` COMPLETA en dialecto MariaDB — no se escribe ni se genera con IA; ver [5_data_model.md](5_data_model.md) §1).
- [ ] Crear el `docker-compose.yml` con el servicio `mariadb` (imagen `mariadb:11`, volumen `mariadbdata`, `db/init.sql` montado en `/docker-entrypoint-initdb.d/`, puerto 13326, healthcheck — ver [3_plan.md](3_plan.md) §5) y levantarlo: `docker compose up -d`.
- [ ] Crear la carpeta `api_gestion/` con subcarpetas `modelos/`, `controladores/`, `servicios/`, `repositorios/` y `excepciones/`.
- [ ] Crear la carpeta `front_php/` con `vistas/` y `publico/`. **Ahora, no al final**: la versión incluye su pantalla (Artículo 1.1), y una carpeta vacía a la vista recuerda que falta la mitad del trabajo.

**Verificar:** un cliente SQL (HeidiSQL/DBeaver a `localhost:13326`) ve las **5 tablas** principales (`area_conocimiento`, `termino_clave`, `linea_investigacion`, `programa`, `red`) y `SELECT count(*) FROM programa` da **191**.

---

## Fase 1 — Los modelos del catálogo (clases entidad)
- [ ] `modelos/AreaConocimiento.php`: propiedades privadas (`id`, `gran_area`, `area`, `disciplina`), constructor, getters, setters para campos editables y `toArray()`.
- [ ] `modelos/TerminoClave.php`: propiedades privadas (`termino`, `termino_ingles`), constructor, getters, setters y `toArray()`.
- [ ] `modelos/LineaInvestigacion.php`: propiedades privadas (`id`, `nombre`, `descripcion`), constructor, getters, setters y `toArray()`.
- [ ] `modelos/Programa.php`: propiedades privadas (`id`, `nombre`, `tipo`, `nivel`, `fechas`, `cohortes`, `graduados`, `ciudad`, `facultad`), constructor, getters, setters y `toArray()`.
- [ ] `modelos/Red.php`: propiedades privadas (`idr`, `nombre`, `url`, `pais`), constructor, getters, setters y `toArray()`.

**Verificar:** en un script suelto (o `php -a`),
`$p = new Programa(1, 'Ingeniería de Sistemas', 'Pregrado', 'Universitario', null, null, 1200, 'Medellín', 'Ingeniería');` construye el objeto, `$p->getGraduados()` devuelve 1200, `$p->setGraduados(1250)` lo cambia, y `json_encode($p->toArray())` devuelve el JSON con todas las columnas.

---

## Fase 2 — Contratos (interfaces) y excepción de negocio
- [ ] `repositorios/`: crear las interfaces para las 5 entidades (`IRepositorioAreaConocimiento.php`, `IRepositorioTerminoClave.php`, `IRepositorioLineaInvestigacion.php`, `IRepositorioPrograma.php`, `IRepositorioRed.php`) con los 5 métodos (`obtenerTodos(int $limite)`, `obtenerPorPk`, `crear`, `actualizar` y `eliminar`).
- [ ] `servicios/`: crear las interfaces de servicio para las 5 entidades (`IServicioPrograma.php`, etc.).
- [ ] `excepciones/NoEncontradoExcepcion.php`: la excepción que los controladores traducirán a 404.

**Verificar:** `php -l` pasa en todos los archivos creados (sintaxis válida).

---

## Fase 3 — Repositorios MariaDB
- [ ] Implementar en `repositorios/` los repositorios concretos para MariaDB (`RepositorioProgramaMariaDB.php`, `RepositorioRedMariaDB.php`, etc.) usando PDO perezoso (`ERRMODE_EXCEPTION`, `ATTR_EMULATE_PREPARES => false`), prepared statements y casteo estricto de tipos de datos en la conversión de filas a objetos.

**Verificar:** un script suelto que instancie `RepositorioProgramaMariaDB` con el DSN de `localhost:13326` lista los registros de programas y trae el registro con `id = 1`.

---

## Fase 4 — Servicios (y la prueba de capas)
- [ ] Implementar los servicios para las 5 entidades (`ServicioPrograma.php`, `ServicioRed.php`, etc.): reciben su repositorio correspondiente por constructor, validan reglas de negocio (`limite > 0`, campos requeridos no vacíos, PATCH sin campos → `InvalidArgumentException`), y traducen la ausencia de registros a `NoEncontradoExcepcion`.
- [ ] `servicios/ensamblador.php`: funciones para instanciar y ensamblar cada servicio con su repositorio concreto.

**Verificar (criterio 6 de la spec):** `pruebas/prueba_capas.php` instancia `ServicioPrograma` con un **repositorio falso en memoria** (una clase con `implements IRepositorioPrograma` sobre un array) y hace crear/listar/eliminar SIN MariaDB corriendo. Si esto funciona, las capas quedaron bien cortadas.

---

## Fase 5 — Controladores y front controller
- [ ] Crear los controladores específicos para cada recurso en `controladores/` (`ControladorPrograma.php`, `ControladorRed.php`, etc.): 6 métodos por controlador con validación (422), traducción de excepciones (400/404/500) y 204 para lista vacía.
- [ ] `index.php`: requires, header JSON, enrutador explícito con `if` por ruta específica (`/api/area_conocimiento`, `/api/termino_clave`, `/api/linea_investigacion`, `/api/programa`, `/api/red`) y verbo HTTP, endpoint `/` de diagnóstico y 404 por defecto.

**Verificar:** con la BD arriba y `php -S localhost:8022 index.php` (o el contenedor), probar: listar (200 con registros y `?limite=3` con 3), obtener un registro existente (200), inexistente (404), POST inválido (422 con `errores[]`), y el contraste PUT vs PATCH.

---

## Fase 6 — Docker: un solo comando
- [ ] `api_gestion/Dockerfile`: `php:8.3-cli`, `docker-php-ext-install pdo_mysql`, copiar el código, `CMD php -S 0.0.0.0:8022 index.php`.
- [ ] Agregar al `docker-compose.yml` el servicio `api-gestion`: `build:`, código montado como volumen, puerto 8022, variables `DB_DSN` / `DB_USUARIO` / `DB_CLAVE` con el host interno `mariadb:3306`, y `depends_on` con `condition: service_healthy`.

**Verificar:** `docker compose down` y luego `docker compose up -d --build` — UN comando deja BD y API funcionando; editar un `.php`, guardar y refrescar muestra el cambio sin reiniciar nada.

---

## Fase 7 — El front: las pantallas de los 5 catálogos

- [ ] `front_php/cliente_api.php`: `URL_API` desde el entorno, `llamar_api($metodo, $ruta, $cuerpo)` con cURL —que devuelve **`null` cuando no hubo respuesta**—, `mensajes_de_error()` y las funciones cliente para cada recurso (`listar_programas`, `crear_programa`, etc.). Todas devuelven `['ok', 'datos', 'errores']`, y el **204 es `ok = true` con la lista vacía**.
- [ ] `front_php/index.php`: el enrutador del front — `redirigir_con()`, `aviso_pendiente()`, `pintar()`, las rutas para los 5 catálogos (`/programas`, `/redes`, `/terminos`, `/lineas`, `/areas`), la lógica de guardado (PUT/PATCH) según `$_POST['verbo']`, y el **`return false` para los archivos estáticos** (`/publico/`).
- [ ] `front_php/vistas/`: `plantilla.php` (cabecera, menú con las 5 secciones, avisos, pie), `inicio.php`, vistas de listado y formularios para las entidades (sirven para agregar Y para editar: la PK va de solo lectura al editar, y aparecen los **dos** botones de guardado) y `no_encontrada.php`. Todo lo que se pinte pasa por `htmlspecialchars`.
- [ ] `front_php/publico/`: copiar `bootstrap.min.css` y `bootstrap.bundle.min.js` (archivos locales, no CDN) y el archivo `estilos.css`.
- [ ] `front_php/Dockerfile`: `php:8.3-cli`, copiar el código, `CMD php -S 0.0.0.0:8020 index.php`. **Sin `pdo_mysql`** — la ausencia es la comprobación.
- [ ] Agregar al `docker-compose.yml` el servicio `front-php`: puerto 8020, `URL_API=http://api-gestion:8022` y `depends_on: api-gestion`. **Sin `DB_DSN`, sin credenciales y sin nombrar a `mariadb`.**

**Verificar:** `http://localhost:8020/programas` muestra los registros del catálogo **y se ve con sus estilos aplicados**, y el recorrido de [7_quickstart.md](7_quickstart.md) §3.2 —agregar, los dos botones, eliminar— funciona desde el navegador.

---

## Fase 8 — La prueba de los dos procesos

- [ ] `pruebas_humo/humo_front.py`: el guion que recorre las pantallas de los 5 catálogos, hace el ciclo completo con los mismos POST que manda el navegador, revisa que ninguna pantalla hable en jerga y **apaga la API para comprobar que la pantalla sigue en pie sin datos**.

**Verificar:** `python pruebas_humo/humo_front.py` termina en verde.

> Si la pantalla siguiera mostrando datos con la API apagada, la versión estaría mal aunque todo lo demás funcione: querría decir que el front llegó a la base por su cuenta. Es la comprobación del Artículo 3, y no hay otra forma de hacerla.

---

## Fase 9 — Cierre de la versión
- [ ] Correr el smoke test completo de [7_quickstart.md](7_quickstart.md) — los **10** criterios de aceptación de [2_spec.md](2_spec.md) §5.
- [ ] `.gitignore` (`*.session.sql`, archivos de IDE).
- [ ] Commit y tag `v1`.

**La v1 está TERMINADA.** Solo ahora se escribe la spec de la v2 ([mapa de versiones](../0_mapa_versiones.md)).