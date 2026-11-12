# Quickstart — Versión 3

> **Versión 3** · Validación rápida de la v3 ya construida. Si aún no hay
> nada, empiece por [8_tasks.md](8_tasks.md).

---

## 1. Arrancar TODO (un solo comando)

```powershell
# desde la raíz del proyecto (terminal integrada de VS Code):
docker compose up -d --build
```

| | Dónde | Qué es |
|---|---|---|
| **La pantalla** | <http://localhost:8120> | Por donde se usa el sistema |
| La API | <http://localhost:8122> | JSON; el front habla con ella |
| MariaDB | `localhost:13426` | `paradigmas` / `paradigmas123` |
| **PostgreSQL** | `localhost:15552` | `paradigmas` / `paradigmas123` (con DBeaver o SQLTools) |

Arranca en **MariaDB**, que es el motor por defecto.

## 2. Cambiar de motor (lo que esta versión viene a enseñar)

```powershell
$env:DB_MOTOR = "postgres"
docker compose up -d --no-deps --force-recreate api-gestion
```

Y para volver:

```powershell
$env:DB_MOTOR = "mariadb"
docker compose up -d --no-deps --force-recreate api-gestion
```

`--no-deps` es para no reiniciar las bases: solo se recrea la API.

**Compruébelo, que es la mitad de la versión:**

```powershell
curl.exe http://localhost:8122/
# → "motor": "postgres"
```

Y refresque http://localhost:8120: la etiqueta del pie cambió. Y nada
más. Las mismas 16 secciones, los mismos datos, los mismos botones.

Lo que NO hubo que hacer para cambiar de motor: editar un archivo,
reconstruir una imagen, tocar el front. Una variable y un reinicio.

## 3. Smoke test de la API (criterios 1 a 9)
Corra esto dos veces, una con cada motor, y compare. La gracia es que las
respuestas sean idénticas.

```powershell
# 1 y 2. El diagnóstico dice el motor
curl.exe http://localhost:8122/

# 3. Los 16 recursos, con los mismos totales en los dos motores
curl.exe http://localhost:8122/api/area_conocimiento?limite=2
curl.exe http://localhost:8122/api/programa?limite=2
curl.exe http://localhost:8122/api/docente?limite=2
curl.exe http://localhost:8122/api/estudios_realizados?limite=2

# 4. La llave la genera la base — en los dos, con la misma respuesta
curl.exe -X POST http://localhost:8122/api/estudios_realizados `
     -H "Content-Type: application/json" `
     -d '{\"titulo\":\"Prueba\",\"universidad\":\"UIS\",\"tipo\":\"Pregrado\",\"cedula\":\"123\"}'
#    → { "estado":200, "mensaje":"Estudio creado exitosamente.", "id": N }

# 5. LOS TRES CONFLICTOS — el MISMO texto con los dos motores
curl.exe -i -X POST http://localhost:8122/api/docente `
     -H "Content-Type: application/json" `
     -d '{\"cedula\":\"123\",\"nombre_completo\":\"Otro\"}'
#    → 409 "Ya existe un docente con esa llave."

curl.exe -i -X POST http://localhost:8122/api/estudios_realizados `
     -H "Content-Type: application/json" `
     -d '{\"titulo\":\"Prueba\",\"universidad\":\"UIS\",\"tipo\":\"Pregrado\",\"cedula\":\"999999\"}'
#    → 409 "Alguno de los códigos a los que apunta el estudio no existe."

curl.exe -i -X DELETE http://localhost:8122/api/docente/123
#    → 409 "No se puede eliminar el docente porque hay otras fichas que dependen de ella."

# ↑ Compárelos PALABRA POR PALABRA entre los dos motores. Que digan lo mismo
#   no es casualidad: MariaDB reporta esos tres casos con tres códigos
#   numéricos y PostgreSQL con dos SQLSTATE, uno de los cuales sirve para
#   dos casos opuestos. Emparejarlos es trabajo de los dos traductores.

# 6. El docente: maestro-detalle, igual con los dos
curl.exe -X POST http://localhost:8122/api/docente `
     -H "Content-Type: application/json" `
     -d '{
       \"cedula\":\"999\",
       \"nombre_completo\":\"Docente Prueba\",
       \"linea_investigacion_principal\": 1,
       \"estudios\": [
         {\"titulo\":\"Ingeniería\",\"universidad\":\"UIS\",\"tipo\":\"Pregrado\"},
         {\"titulo\":\"Maestría\",\"universidad\":\"UNAL\",\"tipo\":\"Maestría\"}
       ]
     }'

# 7. El docente NO tiene PATCH
curl.exe -i -X PATCH http://localhost:8122/api/docente/999 `
     -H "Content-Type: application/json" `
     -d '{\"nombre_completo\":\"Nuevo Nombre\"}'
#    → 405
```

**9. Prueba de capas** (sin ninguna base corriendo):

```powershell
docker compose exec api-gestion php pruebas/prueba_capas.php
```

Este archivo no depende del motor: los repositorios falsos están en
memoria. Que siga pasando sin cambios es parte del criterio 9.

## 4. La prueba de los dos motores (criterio 12)

```powershell
python pruebas_humo/humo_los_dos_motores.py
```

Reinicia la API contra cada motor y corre el guion de humo completo —el
mismo de la v2, sin una línea distinta— contra los dos.

Y ahí está la gracia: humo_front.py no sabe nada de motores. Llena
formularios, oprime botones y lee la pantalla. Que dé el mismo resultado dos
veces demuestra que el motor no se filtra hacia arriba.

## 5. EL CRITERIO 13: qué se tocó y qué no

Éste no se comprueba con `curl`, sino con un `diff`. Con las dos carpetas al
lado:

```powershell
diff -rq --exclude=.git --exclude=pruebas_humo ..\proyecto_php2 .
```

La lista de archivos que difieren no puede incluir ningún controlador ni
ninguna vista. Los cambios permitidos, con su motivo, están en
2_spec.md §5, criterio 13.b — incluida la excepción del pie de página, que
está escrita y no escondida.

Es el criterio más importante de la versión y el más raro: los demás
comprueban que el sistema funciona; éste comprueba que la arquitectura
servía para algo.

## 6. El recorrido a mano

1.Abra http://localhost:8120 y use el sistema un rato: cree un área, un
programa, un docente con estudios.
2.Mire el pie: dice MariaDB.
3.Cambie el motor (§2) y refresque.
4.Vuelva a hacer exactamente lo mismo. Todo debe sentirse igual.
5.Fíjese en lo único que sí cambió: los datos. El área que creó con MariaDB
no está aquí — son dos bases independientes, y está declarado en el
alcance

## 7. Volver al punto de partida

```powershell
docker compose down -v && docker compose up -d --build
```

`down -v` borra los **dos** volúmenes, así que los dos `init.sql` vuelven a
ejecutarse y las dos bases quedan como recién nacidas.

## 8. Si algo falla

| Síntoma | Causa probable |
|---|---|
| `could not find driver` al cambiar a postgres | Falta `pdo_pgsql` en la imagen de la API: hay que reconstruirla (`--build`), no solo recrearla |
| Cambié `DB_MOTOR` y el diagnóstico sigue diciendo el otro | Faltó `--force-recreate`: el contenedor viejo sigue vivo con la variable vieja |
| Todo funciona en un motor y falla en el otro | **Eso es el hallazgo**, no un accidente: algo del motor se filtró hacia arriba. Busque el `SELECT`, el nombre de procedimiento o el código de error que quedó fuera de una clase con apellido de motor |
| Un 409 dice cosas distintas según el motor | Los mensajes se están redactando en los traductores en vez de en `conflictos.php` |
| `PUT` a un docente inexistente da 409 en un motor y 404 en el otro | El `interpretarRechazo` de ese motor no está limpiando bien el mensaje del procedimiento |
| «Cannot execute queries while other unbuffered queries are active» | Falta el `closeCursor()` después del `CALL` — **es cosa de MariaDB**; en PostgreSQL no aparece |
| Puerto 8120, 8122, 13426 o 15552 ocupado | Otro proyecto del curso está corriendo — apáguelo (`docker compose down` en ESE proyecto) |
