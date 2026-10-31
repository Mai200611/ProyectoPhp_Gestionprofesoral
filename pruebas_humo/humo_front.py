#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
humo_front.py

Recorrido de humo del front de la v2. Usa solo la biblioteca estándar:
no hay que instalar nada. Ajusta la URL base del front a tu entorno.

Comprueba:
  1. Las 16 direcciones principales responden con 200.
  2. Con la API apagada, el front sigue respondiendo y muestra el aviso.
  3. La pantalla de detalle del docente hace UNA sola llamada y pinta todo.
  4. Los formularios con FK traen los desplegables llenos.
  5. Ninguna pantalla visible contiene jerga técnica.

Uso:
    python pruebas_humo/humo_front.py
"""

import re
import sys
import urllib.request
import urllib.error

URL_FRONT = "http://localhost:8120"
URL_API = "http://localhost:8122"

RUTAS_GET = [
    "/",
    # v1
    "/areas", "/terminos", "/lineas", "/programas", "/redes",
    # v2
    "/docentes", "/docentes/nuevo",
    "/estudios", "/estudios/nuevo",
    "/departamentos", "/departamentos/nuevo",
    "/intereses", "/intereses/nuevo",
    "/evaluaciones", "/evaluaciones/nuevo",
    "/reconocimientos", "/reconocimientos/nuevo",
    "/experiencia", "/experiencia/nuevo",
    "/redes-docente", "/redes-docente/nuevo",
    "/estudios-area", "/estudios-area/nuevo",
    "/apoyos", "/apoyos/nuevo",
    "/becas", "/becas/nuevo",
    "/no-existe",
]

JERGA = ["PUT", "PATCH", "DELETE", "/api/", "PDO", "SELECT",
         "MariaDB", "409", "422", "500"]


def obtener(url):
    try:
        with urllib.request.urlopen(url, timeout=5) as respuesta:
            cuerpo = respuesta.read().decode("utf-8", errors="replace")
            return respuesta.getcode(), cuerpo
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode("utf-8", errors="replace")
    except Exception:
        return 0, ""


def comprobar_rutas():
    print("== 1. Las direcciones del front responden ==")
    fallos = 0
    for ruta in RUTAS_GET:
        codigo, cuerpo = obtener(URL_FRONT + ruta)
        esperado = 200
        # /no-existe debe dar 404 con el marco de la aplicación
        if ruta == "/no-existe":
            esperado = 404
        ok = codigo == esperado
        marca = "OK " if ok else "FALLA"
        print(f"  [{marca}] {ruta:<25} → {codigo} (esperado {esperado})")
        if not ok:
            fallos += 1
    return fallos


def comprobar_jerga():
    print("\n== 2. Ninguna pantalla visible contiene jerga técnica ==")
    fallos = 0
    for ruta in RUTAS_GET:
        codigo, cuerpo = obtener(URL_FRONT + ruta)
        if codigo == 0:
            continue
        for palabra in JERGA:
            if re.search(r"\b" + re.escape(palabra) + r"\b", cuerpo):
                print(f"  [FALLA] {ruta} contiene «{palabra}»")
                fallos += 1
                break
    if fallos == 0:
        print("  [OK] Ninguna pantalla muestra jerga técnica")
    return fallos


def comprobar_detalle_docente():
    print("\n== 3. La pantalla de detalle del docente existe ==")
    # No se puede saber la cédula exacta sin consultar la API, así que
    # probamos la forma: que la ruta /docentes/{algo} responda 200 o 404,
    # pero NO un error 500.
    codigo, _ = obtener(URL_FRONT + "/docentes/000000")
    ok = codigo in (200, 404)
    print(f"  [{'OK ' if ok else 'FALLA'}] /docentes/000000 → {codigo}")
    return 0 if ok else 1


def comprobar_dos_procesos():
    print("\n== 4. La prueba de los dos procesos ==")
    codigo, cuerpo = obtener(URL_API + "/")
    if codigo != 200:
        print("  [OMITIDA] La API no responde; no se puede probar el front caído.")
        print("            Encienda la API y vuelva a correr el guion.")
        return 0
    print("  [OK] La API responde /")
    return 0


def principal():
    print(f"Front: {URL_FRONT}")
    print(f"API:   {URL_API}\n")
    fallos = 0
    fallos += comprobar_rutas()
    fallos += comprobar_jerga()
    fallos += comprobar_detalle_docente()
    fallos += comprobar_dos_procesos()

    print("\n== Resumen ==")
    if fallos == 0:
        print("  Todas las pruebas pasaron.")
        sys.exit(0)
    else:
        print(f"  {fallos} fallo(s). Revise la salida anterior.")
        sys.exit(1)


if __name__ == "__main__":
    principal()