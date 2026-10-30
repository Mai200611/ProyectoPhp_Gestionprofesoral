# Quickstart — Versión 2: tablas con FK y maestro-detalle

> **Versión 2** · Validación rápida de la v2 ya construida. Si aún no hay
> nada, empiece por [8_tasks.md](8_tasks.md).

---

## 1. Arrancar TODO (un solo comando)

```powershell
# desde la raíz del proyecto (terminal integrada de VS Code):
docker compose up -d --build
```

Eso deja corriendo tres cosas:

| | Dónde | Qué es |
|---|---|---|
| **La pantalla** | <http://localhost:8020> | Por donde se usa el sistema |
| La API | <http://localhost:8022> | JSON; el front habla con ella |
| MariaDB |localhost:13326 |	La base de datos (si se quiere mirar por dentro) |

No hay venv ni dependencias que instalar: **PHP no necesita nada más**.
Empiece por el 8020 — que es como lo va a ver alguien que no programó esto.

Alternativa para desarrollo fase a fase — la API local (requiere PHP 8.3 con
`pdo_mysql`) contra la BD del compose:

```powershell
docker compose up -d mariadb
$env:DB_DSN = "mysql:host=localhost;port=13326;dbname=bdgestion_mariadb_local"
$env:DB_USUARIO = "paradigmas"
$env:DB_CLAVE = "paradigmas123"
cd api_gestion
php -S localhost:8113 index.php
```

## 2. Smoke test de la API (criterios 1 a 8)
```
# 1. Diagnóstico y los 16 recursos
curl.exe http://localhost:8022/

# 2. La -V1 sigue funcionando
curl.exe http://localhost:8022/api/programa?limite=3

# 3. Los 11 recursos nuevos listan
curl.exe http://localhost:8022/api/docente?limite=2
curl.exe http://localhost:8022/api/estudios_realizados?limite=2
curl.exe http://localhost:8022/api/reconocimiento?limite=2

# 4. Llaves generadas por la base (estudios_realizados)
     # 1. Crear un estudio sin mandar 'id'
     curl.exe -X POST http://localhost:8022/api/estudios_realizados `
       -H "Content-Type: application/json" `
       -d '{\"titulo\":\"Prueba Quickstart\",\"universidad\":\"UIS\",\"tipo\":\"Pregrado\",\"cedula\":\"123\"}'
     
     # 2. La respuesta debe traer un "id" (ej. 100). Guárdelo.
     # 3. Consultar ese id:
     curl.exe http://localhost:8022/api/estudios_realizados/100
     
     # 4. Un id no numérico da 400:
     curl.exe -i http://localhost:8022/api/estudios_realizados/abc

#5. Los tres rechazos de integridad (409)
     # A. Llave duplicada (cédula existente)
     curl.exe -i -X POST http://localhost:8022/api/docente `
       -H "Content-Type: application/json" `
       -d '{\"cedula\":\"123\",\"nombre_completo\":\"Otro\"}'
     # Esperado: 409
     
     # B. FK que no existe (cédula inventada)
     curl.exe -i -X POST http://localhost:8022/api/estudios_realizados `
       -H "Content-Type: application/json" `
       -d '{\"titulo\":\"Prueba\",\"universidad\":\"UIS\",\"tipo\":\"Pregrado\",\"cedula\":\"999999\"}'
     # Esperado: 409
     
     # C. Borrar docente con dependencias
     curl.exe -i -X DELETE http://localhost:8022/api/docente/123
     # Esperado: 409

# 6. Crear docente con detalle (maestro-detalle)

     curl.exe -X POST http://localhost:8022/api/docente `
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

## 7. El docente no tiene PATCH, y POST inválido da 422
     # PATCH no permitido
     curl.exe -i -X PATCH http://localhost:8022/api/docente/999 `
       -H "Content-Type: application/json" `
       -d '{\"nombre_completo\":\"Nuevo Nombre\"}'
     # Esperado: 405
     
     # POST inválido (falta nombre)
     curl.exe -i -X POST http://localhost:8022/api/docente `
       -H "Content-Type: application/json" `
       -d '{\"cedula\":\"888\"}'
     # Esperado: 422
# 8.Borrar docente sin dependencias da 200
# Crear un docente sin estudios
curl.exe -X POST http://localhost:8022/api/docente `
  -H "Content-Type: application/json" `
  -d '{\"cedula\":\"777\",\"nombre_completo\":\"Sin Dependencias\"}'

# Borrarlo
curl.exe -i -X DELETE http://localhost:8022/api/docente/777
```
## 3. La pantalla (criterios 9 a 14)

### 3.1 Automático
```
python pruebas_humo/humo_front.py
```
### 3.2 A mano, que es lo que un guion no ve
Abra ```http://localhost:8020``` y compruebe:

1. Criterio 9: Los servicios se pueden probar con repositorios falsos.
docker compose exec api-gestion php pruebas/prueba_capas.php
Esperado: debe terminar en verde, sin base de datos.

2. Criterio 10: Todas las pantallas responden y se ven con estilos.

- Abra ```http://localhost:8020/docentes``` (y las demás).
- Verifique que las tablas tienen bordes, el menú es oscuro, y el CSS carga bien.
- Abra http://localhost:8020/publico/estilos.css: debe devolver CSS, no una página HTML.
Vaya a una ruta inventada ```(ej. http://localhost:8020/no-existe)```: debe mostrar «Esa página no existe» con el menú arriba.

3. Criterio 11: El recorrido de una ficha cualquiera (ej. estudios):

Agregar un estudio (el desplegable de docente debe mostrar cédulas y nombres).
Editar: «Guardar la ficha completa» y luego «Guardar solo lo que cambié».
Intentar eliminar un docente con estudios: debe salir un aviso en español («No se puede eliminar el docente porque hay otras fichas que dependen de ella»), sin números.

4. Criterio 12: Crear un docente con dos estudios desde la pantalla.

Vaya a /docentes/nuevo, llene los datos y los dos estudios.
Al guardar, debe ir a la pantalla de detalle (/docentes/{cedula}).
Verifique que el desplegable de línea de investigación principal muestra solo las líneas existentes.

5. Criterio 13: Ninguna pantalla habla en jerga.

Busque con Ctrl+F en cualquier pantalla: PUT, PATCH, 409, /api/, PDO, SELECT, MariaDB, cedula, idlinea. No deben aparecer en el texto visible.

6. Criterio 14: La prueba de los dos procesos.

```docker compose stop api-gestion```
Refresque /docentes. La pantalla sigue en pie, muestra el aviso de «servicio no disponible» y no muestra ninguna fila.
Luego: ```docker compose start api-gestion```

## 4. Si algo falla

| Síntoma | Causa probable |
|---|---|
| `500` en todos los endpoints | MariaDB apagada o el DSN mal apuntado en el compose. |
| Un `500` en vez de `409` | El archivo `errores_de_integridad.php` no está traduciendo el código de error del motor (`1062`, `1452`, `1451`). |
| El docente no devuelve `estudios` al crearlo | El repositorio no está llamando al procedimiento almacenado o no está leyendo el JSON de salida (`@resultado`). |
| El `PATCH` del docente da `200` en vez de `405` | El enrutador (`api_gestion/index.php`) tiene una rama `PATCH` para docente que no debería existir. |
| El desplegable de FK no carga | El front no está llamando a las funciones `listar_*` (ej. `listar_docentes()`) antes de pintar el formulario. |
| `204` donde esperaba registros | La tabla está vacía. Ejecute `docker compose down -v` y luego `up -d` para recargar `db/init.sql` (borra la base actual). |
| `Warning: require_once(...)` en la API | La ruta de un `require_once` está mal, o el archivo no se guardó. Verifique que todos los archivos de las interfaces y modelos existan en sus carpetas. |
