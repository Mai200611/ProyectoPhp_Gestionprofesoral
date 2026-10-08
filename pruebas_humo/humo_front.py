#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
pruebas_humo/humo_front.py
-------------------------------------------------------------------------------
¿Qué es este archivo?
    Es la PRUEBA DE HUMO del front: un script de Python que recorre las
    pantallas como lo haría un humano, pero sin manos, y verifica que
    todo funcione de punta a punta.

¿Qué papel cumple en la arquitectura?
    Es el último eslabón de la v1: la prueba objetiva del Artículo 3 de la
    constitución (front y API son dos procesos separados). Recorre el
    flujo completo (crear → guardar de dos maneras → eliminar) y, sobre
    todo, apaga la API con la base encendida para comprobar que la
    pantalla sigue en pie, sin filas.

¿Cómo se corre?
    Desde la raíz del proyecto, con los contenedores arriba
    (`docker compose up -d --build`):

        python pruebas_humo/humo_front.py

    No necesita dependencias externas: solo la biblioteca estándar de
    Python (urllib, http.client, re, sys).

¿Qué verifica?
    1. Que cada pantalla del front responda con HTTP 200.
    2. Que el HTML visible no hable en jerga técnica.
    3. Que los archivos estáticos se sirvan con su Content-Type correcto.
    4. El ciclo CRUD completo: crear, editar con PUT, editar con PATCH,
       eliminar — todo vía POST del navegador.
    5. La prueba de los dos procesos: con la API detenida, el front sigue
       respondiendo pero sin filas.

¿Qué NO hace?
    No abre un navegador real. Trabaja contra HTTP puro, igual que lo
    haría el navegador, pero sin pintar HTML ni ejecutar JavaScript.
    Eso es justamente lo que permite automatizarlo: como el front usa
    formularios corrientes, cada botón se puede enviar desde fuera.
-------------------------------------------------------------------------------
"""

import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request


# -----------------------------------------------------------------------------
# CONFIGURACIÓN
# -----------------------------------------------------------------------------
URL_FRONT = "http://localhost:8120"

# Palabras que NUNCA deben aparecer en el HTML que ve el usuario.
# Si aparecen, es jerga técnica que se coló a la pantalla (RNF7).
JERGA_PROHIBIDA = [
    "PUT",
    "PATCH",
    "DELETE",
    "/api/",
    "PDO",
    "SELECT ",
    "MariaDB",
    "MySQL",
    "PDOException",
    "filasAfectadas",
    "filasEliminadas",
    "422",
    "404",
    "500",
    "status code",
]

# Contador de resultados.
OK = 0
FALLOS = 0


# -----------------------------------------------------------------------------
# UTILIDADES DE HTTP Y DE SALIDA
# -----------------------------------------------------------------------------
def http_get(url, timeout=5):
    """Hace un GET a la URL y devuelve (codigo, cuerpo_texto, content_type)."""
    try:
        req = urllib.request.Request(url, method="GET")
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            cuerpo = resp.read().decode("utf-8", errors="replace")
            ct = resp.headers.get("Content-Type", "")
            return resp.status, cuerpo, ct
    except urllib.error.HTTPError as e:
        cuerpo = e.read().decode("utf-8", errors="replace")
        ct = e.headers.get("Content-Type", "")
        return e.code, cuerpo, ct
    except Exception as e:
        return 0, f"[ERROR DE CONEXIÓN: {e}]", ""


def http_post(url, datos, timeout=5):
    """Hace un POST con application/x-www-form-urlencoded."""
    cuerpo = urllib.parse.urlencode(datos).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=cuerpo,
        method="POST",
        headers={"Content-Type": "application/x-www-form-urlencoded"},
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return resp.status, resp.read().decode("utf-8", errors="replace"), resp.url
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", errors="replace"), url
    except Exception as e:
        return 0, f"[ERROR DE CONEXIÓN: {e}]", url


def titulo(texto):
    print()
    print("=== " + texto + " ===")


def afirmar(cond, descripcion):
    global OK, FALLOS
    if cond:
        OK += 1
        print(f"[OK]    {descripcion}")
    else:
        FALLOS += 1
        print(f"[FALLO] {descripcion}")


def sin_html(texto):
    """Quita las etiquetas HTML para revisar solo lo que ve el usuario."""
    return re.sub(r"<[^>]+>", " ", texto)


# -----------------------------------------------------------------------------
# PRUEBA 1 — Cada pantalla responde 200
# -----------------------------------------------------------------------------
def prueba_pantallas_responde():
    titulo("Prueba 1: cada pantalla responde")

    rutas = [
        "/",
        "/areas",
        "/terminos",
        "/lineas",
        "/programas",
        "/redes",
        "/areas/nuevo",
        "/terminos/nuevo",
        "/lineas/nuevo",
        "/programas/nuevo",
        "/redes/nuevo",
    ]
    for ruta in rutas:
        codigo, _, _ = http_get(URL_FRONT + ruta)
        afirmar(codigo == 200, f"GET {ruta} responde 200 (recibido: {codigo})")


# -----------------------------------------------------------------------------
# PRUEBA 2 — Sin jerga técnica en las pantallas
# -----------------------------------------------------------------------------
def prueba_sin_jerga():
    titulo("Prueba 2: ninguna pantalla habla en jerga técnica")

    rutas = [
        "/",
        "/areas",
        "/terminos",
        "/lineas",
        "/programas",
        "/redes",
    ]
    for ruta in rutas:
        codigo, cuerpo, _ = http_get(URL_FRONT + ruta)
        if codigo != 200:
            afirmar(False, f"GET {ruta}: no respondió 200, no se puede revisar la jerga")
            continue

        # Solo revisamos el HTML VISIBLE (sin etiquetas), para no castigar
        # atributos internos como name="verbo" value="PUT".
        visible = sin_html(cuerpo)
        encontrados = [p for p in JERGA_PROHIBIDA if p in visible]
        afirmar(
            not encontrados,
            f"GET {ruta}: sin jerga visible (encontrado: {encontrados})",
        )


# -----------------------------------------------------------------------------
# PRUEBA 3 — Archivos estáticos
# -----------------------------------------------------------------------------
def prueba_estaticos():
    titulo("Prueba 3: archivos estáticos servidos correctamente")

    estaticos = [
        ("/publico/bootstrap.min.css", "text/css"),
        ("/publico/bootstrap.bundle.min.js", "javascript"),
        ("/publico/estilos.css", "text/css"),
    ]
    for ruta, tipo_esperado in estaticos:
        codigo, _, ct = http_get(URL_FRONT + ruta)
        afirmar(codigo == 200, f"GET {ruta}: responde 200 (recibido: {codigo})")
        afirmar(
            tipo_esperado in ct.lower(),
            f"GET {ruta}: Content-Type contiene '{tipo_esperado}' (recibido: '{ct}')",
        )


# -----------------------------------------------------------------------------
# PRUEBA 4 — Ciclo CRUD completo de programa
# -----------------------------------------------------------------------------
def prueba_ciclo_programa():
    titulo("Prueba 4: ciclo CRUD completo de programa (vía POST del front)")

    # Nota: usamos un id fuera del rango del catálogo para no pisar datos
    # reales. El id de programa es numérico y lo elige el cliente.
    id_prueba = 999999

    # Limpieza preventiva por si quedó de una corrida anterior.
    http_post(f"{URL_FRONT}/programas/{id_prueba}/eliminar", {})

    # --- 4.1 Crear ---
    datos_alta = {
        "id": id_prueba,
        "nombre": "Programa de Prueba Humo",
        "tipo": "Pregrado",
        "nivel": "Tecnologico",
        "fecha_creacion": "2026-01-01",
        "fecha_cierre": "",
        "numero_cohortes": "1",
        "cant_graduados": "0",
        "fecha_actualizacion": "2026-01-01",
        "ciudad": "Medellin",
        "facultad": "101",
    }
    codigo, cuerpo, url_final = http_post(f"{URL_FRONT}/programas/nuevo", datos_alta)
    afirmar(codigo == 200, f"POST /programas/nuevo responde 200 (recibido: {codigo})")
    # Si el guardado fue exitoso, el front redirige al listado.
    afirmar("/programas" in url_final, f"POST /programas/nuevo redirige a /programas (quedó en: {url_final})")

    # Verificar que se ve en el listado.
    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Programa de Prueba Humo" in listado, "El programa creado aparece en el listado")

    # --- 4.2 Editar con PUT (ficha completa) ---
    datos_put = {
        "nombre": "Programa de Prueba Humo (PUT)",
        "tipo": "Pregrado",
        "nivel": "Tecnologico",
        "fecha_creacion": "2026-01-01",
        "fecha_cierre": "",
        "numero_cohortes": "2",
        "cant_graduados": "0",
        "fecha_actualizacion": "2026-01-02",
        "ciudad": "Medellin",
        "facultad": "101",
        "verbo": "PUT",
    }
    codigo, _, url_final = http_post(f"{URL_FRONT}/programas/{id_prueba}/editar", datos_put)
    afirmar(codigo == 200, f"POST /programas/{id_prueba}/editar (PUT) responde 200")

    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Programa de Prueba Humo (PUT)" in listado, "El PUT actualizó el nombre")

    # --- 4.3 Editar con PATCH (solo ciudad) ---
    datos_patch = {
        "ciudad": "Bogota",
        "verbo": "PATCH",
    }
    codigo, _, _ = http_post(f"{URL_FRONT}/programas/{id_prueba}/editar", datos_patch)
    afirmar(codigo == 200, f"POST /programas/{id_prueba}/editar (PATCH) responde 200")

    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Bogota" in listado, "El PATCH cambió la ciudad")
    afirmar("Programa de Prueba Humo (PUT)" in listado, "El PATCH no borró el nombre")

    # --- 4.4 Eliminar ---
    codigo, _, url_final = http_post(f"{URL_FRONT}/programas/{id_prueba}/eliminar", {})
    afirmar(codigo == 200, f"POST /programas/{id_prueba}/eliminar responde 200")

    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Programa de Prueba Humo (PUT)" not in listado, "El programa eliminado ya no aparece")

    # --- 4.5 El 404 del front se pinta en pantalla ---
    codigo, cuerpo, _ = http_get(f"{URL_FRONT}/ruta-inexistente")
    afirmar(codigo == 404, f"GET /ruta-inexistente responde 404 (recibido: {codigo})")
    afirmar(
        "Esa página no existe" in cuerpo,
        "La pantalla 404 muestra el mensaje en español con el marco del front",
    )


# -----------------------------------------------------------------------------
# PRUEBA 5 — La prueba de los dos procesos
# -----------------------------------------------------------------------------
def prueba_dos_procesos():
    titulo("Prueba 5: con la API detenida, la pantalla sigue en pie pero sin filas")

    # Nos aseguramos de que la API esté arriba primero.
    print("Levantando la API...")
    subprocess.run(
        ["docker", "compose", "start", "api-gestion"],
        capture_output=True,
        text=True,
    )
    time.sleep(3)

    _, listado_antes, _ = http_get(f"{URL_FRONT}/programas")
    hay_datos_antes = "Administraci" in listado_antes or "Maestr" in listado_antes
    afirmar(hay_datos_antes, "Antes de apagar: el listado muestra datos reales")

    # Apagamos SOLO la API, dejando MariaDB encendida.
    print("Apagando la API (MariaDB sigue encendida)...")
    subprocess.run(
        ["docker", "compose", "stop", "api-gestion"],
        capture_output=True,
        text=True,
    )
    time.sleep(2)

    codigo, cuerpo, _ = http_get(f"{URL_FRONT}/programas")
    afirmar(codigo == 200, f"Con la API apagada, GET /programas sigue respondiendo 200 (recibido: {codigo})")
    afirmar(
        "Gestión Profesoral" in cuerpo,
        "La pantalla sigue mostrando el marco del front (el título está)",
    )
    afirmar(
        "no está disponible" in cuerpo or "No está disponible" in cuerpo,
        "La pantalla muestra el aviso de servicio no disponible",
    )
    afirmar(
        "Administraci" not in cuerpo and "Maestr" not in cuerpo,
        "La pantalla NO muestra datos (el front no llegó a la base por su cuenta)",
    )

    # Volvemos a encender la API.
    print("Volviendo a encender la API...")
    subprocess.run(
        ["docker", "compose", "start", "api-gestion"],
        capture_output=True,
        text=True,
    )
    time.sleep(5)

    _, listado_despues, _ = http_get(f"{URL_FRONT}/programas")
    afirmar(
        "Administraci" in listado_despues or "Maestr" in listado_despues,
        "Al reencender la API, los datos vuelven sin tocar el front",
    )


# -----------------------------------------------------------------------------
# MAIN
# -----------------------------------------------------------------------------
def main():
    print("Prueba de humo del front — v1 de Gestión Profesoral")
    print("URL del front: " + URL_FRONT)

    # Comprobación inicial: ¿está el front encendido?
    codigo, _, _ = http_get(URL_FRONT + "/")
    if codigo != 200:
        print()
        print("!! El front no responde en " + URL_FRONT)
        print("   Levante los contenedores con: docker compose up -d --build")
        sys.exit(2)

    prueba_pantallas_responde()
    prueba_sin_jerga()
    prueba_estaticos()
    prueba_ciclo_programa()
    prueba_dos_procesos()

    # Resumen final.
    print()
    print("=====================================")
    print(f"  Resultado: {OK} OK, {FALLOS} fallos")
    print("=====================================")
    if FALLOS > 0:
        sys.exit(1)
    sys.exit(0)


if __name__ == "__main__":
    main()
    