# Tareas — Versión 2: tablas con FK, integridad y maestro-detalle

> **Versión 2** · El orden de construcción, partiendo del estado que dejó la v1.
> Cada fase termina en algo **verificable**.
> Requisitos: [2_spec.md](2_spec.md) · técnica: [3_plan.md](3_plan.md) ·
> contratos: [6_contracts.md](6_contracts.md) · validación final:
> [7_quickstart.md](7_quickstart.md).

---

## Fase 1 — La nueva excepción y el traductor de errores
**Por qué:** antes de empezar a crear fichas, necesitamos que la base sepa decir «no» y que nuestra API traduzca ese «no» a un 409 en español. Si la integridad se rompe a la mitad de la construcción, ya tenemos la herramienta lista.

- [ ] Crear `api_gestion/excepciones/ConflictoDeIntegridadExcepcion.php` (hereda de Exception, representa el 409).
- [ ] Crear `api_gestion/repositorios/errores_de_integridad.php` con la función `traducirErrorDeIntegridad()`. Debe atrapar `PDOException` y revisar `$e->errorInfo[1]`:
  - Código `1062`: Llave duplicada.
  - Código `1452`: FK que apunta a algo inexistente.
  - Código `1451`: Fila con dependencias.
  - Cualquier otro: relanzar el error para que el controlador lo convierta en un 500.

**Verificar:** `php -l` pasa en ambos archivos. Si tienes un script de prueba, forzar un `PDOException` con código 1062 y comprobar que lanza la nueva excepción.

---

## Fase 2 — Los modelos de las 11 tablas nuevas
**Por qué:** necesitamos las clases que representan los datos. Como vimos en el plan, 6 tablas tienen llave autoincremental (su `id` es `?int` porque al crear aún no existe) y 5 tienen llaves naturales o compuestas.

- [ ] `modelos/Docente.php` (llave `string $cedula`).
- [ ] `modelos/EstudioRealizado.php` (llave `?int $id`).
- [ ] `modelos/DocenteDepartamento.php` (llave compuesta).
- [ ] `modelos/InteresFuturo.php` (llave compuesta).
- [ ] `modelos/EvaluacionDocente.php` (llave `?int $id`).
- [ ] `modelos/Reconocimiento.php` (llave `?int $id`).
- [ ] `modelos/Experiencia.php` (la tabla se llama `experiecia`, llave `?int $id`).
- [ ] `modelos/RedDocente.php` (llave compuesta).
- [ ] `modelos/EstudioAc.php` (llave compuesta).
- [ ] `modelos/ApoyoProfesoral.php` (llave compuesta).
- [ ] `modelos/Beca.php` (llave compuesta).

**Verificar:** instanciar un par de ellos en un script suelto y hacer `json_encode($obj->toArray())`. El JSON debe salir como en los contratos, con los `id` generados como `null` por defecto.

---

## Fase 3 — Los contratos (interfaces) de los 11 recursos
**Por qué:** la inversión de dependencias exige que los servicios hablen con interfaces, no con repositorios concretos. 

- [ ] En `repositorios/`: las 11 interfaces `IRepositorio*` (docente, estudios_realizados, etc.).
- [ ] En `servicios/`: las 11 interfaces `IServicio*`.
- [ ] **Atención a las llaves:** los métodos `crear()` y `actualizar()` de los repositorios con `id` autoincremental deben devolver `int` (el id generado), y los de llave natural deben devolver `bool` o `int` (filas afectadas).
- [ ] **En Docente:** la interfaz debe tener un método especial `obtenerCompleto(string $cedula): array` y `crearCompleto(array $datos): array` (o similar) para manejar el maestro-detalle.

**Verificar:** `php -l` pasa en todos los archivos de interfaces.

---

## Fase 4 — Los repositorios concretos MariaDB
**Por qué:** es la capa que realmente toca la base. Aquí es donde el 409 y las transacciones se vuelven reales.

- [ ] Implementar los 10 repositorios de fichas (`RepositorioEstudiosRealizadosMariaDB`, etc.).
- [ ] Envolver las operaciones `crear`, `actualizar` y `eliminar` en un `try...catch (PDOException $e)`.
- [ ] En el `catch`, llamar a `traducirErrorDeIntegridad($e, 'nombre de la ficha')` (el archivo de la Fase 1).
- [ ] **Para `docente`:** crear `RepositorioDocenteMariaDB.php`. **No escribas SQL directo** para el maestro-detalle. Llama a los procedimientos almacenados (`CALL sp_crear_docente(...)`). Recuerda el `closeCursor()` y leer el `@resultado` con un `SELECT`.
- [ ] En `crear` de las tablas con ID autoincremental, obtener el ID con `$pdo->lastInsertId()` y devolverlo.

**Verificar:** hacer un `POST` con un ID duplicado o una FK falsa desde un script PHP y comprobar que lanza `ConflictoDeIntegridadExcepcion` en vez de un error de PDO genérico.

---

## Fase 5 — Los servicios (reglas de negocio)
**Por qué:** el servicio valida que los datos tengan sentido antes de ir a la BD, y maneja la lógica del docente completo.

- [ ] Implementar los 10 servicios de fichas. Igual que en la v1: validan que el `limite > 0` y traducen la ausencia de registros a `NoEncontradoExcepcion`.
- [ ] **El servicio de Docente:**
  - `obtenerPorCedula()` lanza `NoEncontradoExcepcion` si no existe.
  - `crearCompleto()` recibe el array con las listas, valida que cada estudio/interés/red tenga los campos correctos y llama al repositorio.
  - **No tiene `actualizarParcial()`** (la interfaz no lo declara).
- [ ] Actualizar `servicios/ensamblador.php` con 11 funciones nuevas (`crearServicioDocente()`, `crearServicioEstudiosRealizados()`, etc.).

**Verificar:** la prueba de capas. Crear `pruebas/prueba_capas_v2.php` con un repositorio falso en memoria para el docente y comprobar que se puede crear un docente con dos estudios sin MariaDB corriendo.

---

## Fase 6 — Los controladores y el enrutador
**Por qué:** la frontera HTTP. Aquí se valida la forma de los datos y se decide el código de respuesta.

- [ ] Crear los 11 controladores (`ControladorDocente.php`, etc.).
- [ ] En los controladores de fichas con id autoincremental, al crear, **no validar el `id`**, y en la respuesta incluir el `id` devuelto por el servicio.
- [ ] En el controlador de Docente:
  - `POST` valida el cuerpo y las listas anidadas (si el estudio 2 falla, responder 422 diciendo "estudio 2: falta X").
  - **No crear el método `actualizarParcial()`**.
- [ ] Actualizar `api_gestion/index.php` con las 11 rutas nuevas.
- [ ] En el enrutador de Docente, **NO** agregar la rama `PATCH` (así, si llega un PATCH, cae en el `405` por defecto).
- [ ] Actualizar el `GET /` de diagnóstico para que devuelva `"version": "v2"` y los 16 recursos.

**Verificar:** levantar el servidor local (`php -S localhost:8022 index.php`) y probar un `GET /` (debe listar los 16 recursos), un `POST /api/docente` con detalle, y un `PATCH /api/docente/{cedula}` (debe dar 405).

---

## Fase 7 — El front: cliente_api, enrutador y vistas base
**Por qué:** el front tiene que aprender a hablar con los 11 recursos nuevos y a manejar los desplegables de FK.

- [ ] Actualizar `front_php/cliente_api.php`: agregar las funciones de los 11 recursos (`listar_docentes()`, `crear_docente()`, etc.) y una función `obtener_docente_completo($cedula)`.
- [ ] En `cliente_api.php`, agregar el manejo del **409** en `mensajes_de_error()` para que junte `mensaje` y `detalle` en un solo string.
- [ ] Actualizar `front_php/index.php` con las rutas de los 11 recursos (`/docentes`, `/estudios`, etc.).
- [ ] Para los formularios con FK (estudios, intereses, etc.), agregar la carga de los desplegables llamando a las funciones de la v1 (`listar_docentes()`, `listar_lineas()`, etc.) antes de pintar.

**Verificar:** abrir el menú en el navegador y comprobar que aparecen los 11 enlaces. Entrar a `/docentes` (debe cargar la tabla vacía o con datos).

---

## Fase 8 — Las vistas (el maestro-detalle y los formularios)
**Por qué:** es la cara visible del sistema. La joya de la corona es la pantalla de ver el docente completo.

- [ ] Crear las 20 vistas de las 10 fichas nuevas (listas y formularios), siguiendo el patrón de la v1.
- [ ] **Docente (pantallas especiales):**
  - `docente_lista.php`: tabla con cédula, nombre y acciones (solo muestra «Eliminar» si no hay dependencias).
  - `docente_formulario.php`: el formulario de creación/edición. Incluye 5 renglones fijos para estudios, y arreglos para intereses y redes. Al editar, el PUT reemplaza todo.
  - `docente_detalle.php`: la pantalla de ver el docente completo. Muestra sus estudios (con sus apoyos y becas anidados), intereses, redes y evaluaciones.
- [ ] Asegurar que los desplegables (selects) usan `htmlspecialchars()` y muestran los nombres, no las llaves foráneas crudas.

**Verificar:** desde el navegador, crear un docente con dos estudios (deben verse en el detalle). Intentar borrar un docente con estudios: la API devuelve 409 y la pantalla debe mostrar el texto en español sin números.

---

## Fase 9 — La prueba de los dos procesos y cierre de versión
**Por qué:** verificar que la integridad se mantuvo, que el front y la API siguen separados y que la versión está lista.

- [ ] Actualizar `pruebas_humo/humo_front.py` para que recorra los 11 recursos y valide el maestro-detalle del docente y los rechazos 409.
- [ ] Correr el smoke test manual de `7_quickstart.md` (los 14 criterios).
- [ ] **Verificación final de la v1:** asegurarse de que las 5 tablas originales siguen respondiendo igual (no romper la compatibilidad).
- [ ] Commit y tag `v2`.

**La v2 está TERMINADA.** Solo ahora se escribe la spec de la v3.
