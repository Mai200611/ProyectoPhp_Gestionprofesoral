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

OK = 0
FALLOS = 0


# -----------------------------------------------------------------------------
# UTILIDADES DE HTTP Y DE SALIDA
# -----------------------------------------------------------------------------
def http_get(url, timeout=5):
    """Hace un GET y devuelve (codigo, cuerpo_texto, content_type)."""
    try:
        req = urllib.request.Request(url, method="GET")
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            cuerpo = resp.read().decode("utf-8", errors="replace")
            return resp.status, cuerpo, resp.headers.get("Content-Type", "")
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", errors="replace"), e.headers.get("Content-Type", "")
    except Exception as e:
        return 0, f"[ERROR DE CONEXIÓN: {e}]", ""


def http_post(url, datos, timeout=5):
    """
    Hace un POST con application/x-www-form-urlencoded.

    Desactiva el seguimiento automático de redirecciones para poder ver
    la URL final y el código intermedio (el POST que guarda debe
    responder 302 y redirigir al listado).
    """
    cuerpo = urllib.parse.urlencode(datos).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=cuerpo,
        method="POST",
        headers={"Content-Type": "application/x-www-form-urlencoded"},
    )
    # Para seguir el redirect manualmente necesitamos un opener que NO
    # siga redirecciones automáticamente:
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, req, fp, code, msg, headers, newurl):
            return None

    opener = urllib.request.build_opener(NoRedirect)
    try:
        with opener.open(req, timeout=timeout) as resp:
            return resp.status, resp.read().decode("utf-8", errors="replace"), url
    except urllib.error.HTTPError as e:
        # Un 302 llega como HTTPError cuando no se sigue el redirect.
        if e.code in (301, 302, 303, 307, 308):
            destino = e.headers.get("Location", "")
            return e.code, "", destino
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
    return re.sub(r"<[^>]+>", " ", texto)


def esperar_datos_en_listado(timeout=15):
    """Espera hasta `timeout` segundos a que /programas muestre datos reales."""
    inicio = time.time()
    while time.time() - inicio < timeout:
        _, cuerpo, _ = http_get(URL_FRONT + "/programas")
        if "Administraci" in cuerpo or "Maestr" in cuerpo:
            return True
        time.sleep(1)
    return False


# -----------------------------------------------------------------------------
# PRUEBA 1 — Cada pantalla responde 200
# -----------------------------------------------------------------------------
def prueba_pantallas_responde():
    titulo("Prueba 1: cada pantalla responde")

    rutas = [
        "/", "/areas", "/terminos", "/lineas", "/programas", "/redes",
        "/areas/nuevo", "/terminos/nuevo", "/lineas/nuevo",
        "/programas/nuevo", "/redes/nuevo",
    ]
    for ruta in rutas:
        codigo, _, _ = http_get(URL_FRONT + ruta)
        afirmar(codigo == 200, f"GET {ruta} responde 200 (recibido: {codigo})")


# -----------------------------------------------------------------------------
# PRUEBA 2 — Sin jerga técnica
# -----------------------------------------------------------------------------
def prueba_sin_jerga():
    titulo("Prueba 2: ninguna pantalla habla en jerga técnica")

    for ruta in ["/", "/areas", "/terminos", "/lineas", "/programas", "/redes"]:
        codigo, cuerpo, _ = http_get(URL_FRONT + ruta)
        if codigo != 200:
            afirmar(False, f"GET {ruta}: no respondió 200, no se puede revisar la jerga")
            continue
        visible = sin_html(cuerpo)
        encontrados = [p for p in JERGA_PROHIBIDA if p in visible]
        afirmar(not encontrados, f"GET {ruta}: sin jerga visible (encontrado: {encontrados})")


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
        afirmar(tipo_esperado in ct.lower(),
                f"GET {ruta}: Content-Type contiene '{tipo_esperado}' (recibido: '{ct}')")


# -----------------------------------------------------------------------------
# PRUEBA 4 — Ciclo CRUD completo de programa
# -----------------------------------------------------------------------------
def prueba_ciclo_programa():
    titulo("Prueba 4: ciclo CRUD completo de programa (vía POST del front)")

    id_prueba = 999999

    # Limpieza preventiva.
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
    codigo, _, destino = http_post(f"{URL_FRONT}/programas/nuevo", datos_alta)
    afirmar(codigo == 302, f"POST /programas/nuevo responde 302 (recibido: {codigo})")
    afirmar("/programas" in destino and "/nuevo" not in destino,
            f"POST /programas/nuevo redirige a /programas (recibido: {destino})")

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
    codigo, _, _ = http_post(f"{URL_FRONT}/programas/{id_prueba}/editar", datos_put)
    afirmar(codigo == 302, f"POST /programas/{id_prueba}/editar (PUT) responde 302")

    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Programa de Prueba Humo (PUT)" in listado, "El PUT actualizó el nombre")

    # --- 4.3 Editar con PATCH (solo ciudad) ---
    datos_patch = {"ciudad": "Bogota", "verbo": "PATCH"}
    codigo, _, _ = http_post(f"{URL_FRONT}/programas/{id_prueba}/editar", datos_patch)
    afirmar(codigo == 302, f"POST /programas/{id_prueba}/editar (PATCH) responde 302")

    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Bogota" in listado, "El PATCH cambió la ciudad")
    afirmar("Programa de Prueba Humo (PUT)" in listado, "El PATCH no borró el nombre")

    # --- 4.4 Eliminar ---
    codigo, _, _ = http_post(f"{URL_FRONT}/programas/{id_prueba}/eliminar", {})
    afirmar(codigo == 302, f"POST /programas/{id_prueba}/eliminar responde 302")

    _, listado, _ = http_get(f"{URL_FRONT}/programas")
    afirmar("Programa de Prueba Humo (PUT)" not in listado, "El programa eliminado ya no aparece")

    # --- 4.5 El 404 del front ---
    codigo, cuerpo, _ = http_get(f"{URL_FRONT}/ruta-inexistente")
    afirmar(codigo == 404, f"GET /ruta-inexistente responde 404 (recibido: {codigo})")
    afirmar("Esa página no existe" in cuerpo,
            "La pantalla 404 muestra el mensaje en español con el marco del front")


# -----------------------------------------------------------------------------
# PRUEBA 5 — La prueba de los dos procesos
# -----------------------------------------------------------------------------
def prueba_dos_procesos():
    titulo("Prueba 5: con la API detenida, la pantalla sigue en pie pero sin filas")

    # Asegurar API arriba.
    print("Levantando la API...")
    subprocess.run(["docker", "compose", "start", "api-gestion"],
                   capture_output=True, text=True)

    # Espera activa: no un sleep fijo, sino hasta que el listado traiga datos.
    afirmar(esperar_datos_en_listado(20),
            "Antes de apagar: el listado muestra datos reales")

    # Apagar SOLO la API.
    print("Apagando la API (MariaDB sigue encendida)...")
    subprocess.run(["docker", "compose", "stop", "api-gestion"],
                   capture_output=True, text=True)
    time.sleep(2)

    codigo, cuerpo, _ = http_get(f"{URL_FRONT}/programas")
    afirmar(codigo == 200,
            f"Con la API apagada, GET /programas sigue respondiendo 200 (recibido: {codigo})")
    afirmar("Gestión Profesoral" in cuerpo,
            "La pantalla sigue mostrando el marco del front (el título está)")
    afirmar("no está disponible" in cuerpo or "No está disponible" in cuerpo,
            "La pantalla muestra el aviso de servicio no disponible")
    afirmar("Administraci" not in cuerpo and "Maestr" not in cuerpo,
            "La pantalla NO muestra datos (el front no llegó a la base por su cuenta)")

    # Reencender.
    print("Volviendo a encender la API...")
    subprocess.run(["docker", "compose", "start", "api-gestion"],
                   capture_output=True, text=True)

    afirmar(esperar_datos_en_listado(20),
            "Al reencender la API, los datos vuelven sin tocar el front")


# -----------------------------------------------------------------------------
# MAIN
# -----------------------------------------------------------------------------
def main():
    print("Prueba de humo del front — v1 de Gestión Profesoral")
    print("URL del front: " + URL_FRONT)

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

    print()
    print("=====================================")
    print(f"  Resultado: {OK} OK, {FALLOS} fallos")
    print("=====================================")
    sys.exit(1 if FALLOS > 0 else 0)


if __name__ == "__main__":
    main()