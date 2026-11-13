# front_php/Dockerfile — Versión 3.
#
# El front es la mitad del trabajo de la versión (Art. 1.1), pero en la v3
# casi no cambia: lo único que se le agregó es la etiqueta del motor en el
# pie, que lee del GET / de la API (RF7, C4).
#
# Por eso este Dockerfile es casi idéntico al de la v2:
#   - sigue SIN drivers de base de datos (el front no abre conexiones, Art. 3);
#   - sigue SIN credenciales de base (las pone el compose, y el compose no
#     las pasa a este servicio a propósito);
#   - lo único que cambia es el EXPOSE y el CMD, para alinearse con los
#     puertos de ESTA versión.
#
# Puertos:
#   - EXPOSE 8020: el puerto donde escucha el servidor embebido de PHP
#     ADENTRO del contenedor. El compose lo publica al host como 8120.
#   - Adentro todo es igual que en la constitución; el +100 es del host.

FROM php:8.3-cli

# --- Directorio de trabajo ---------------------------------------------------
WORKDIR /var/www/front_php

# --- Copiamos el código ------------------------------------------------------
# En desarrollo el compose MONTA ./front_php encima de este directorio, así
# que este COPY es solo para que la imagen sea autocontenida si alguien la
# corre sin el compose. La recarga natural (Art. 7) sigue funcionando gracias
# al bind mount.
COPY . /var/www/front_php

# --- Puerto que escucha el servidor embebido DENTRO del contenedor -----------
# El compose lo publica al host como 8120. Adentro es 8020.
EXPOSE 8020

# --- Comando de arranque -----------------------------------------------------
# El front controller es index.php (Art. 2: una sola entrada). El servidor
# embebido escucha en 0.0.0.0:8020 para ser alcanzable desde el navegador
# del host a través del puerto publicado.
#
# SIN variables de base de datos: el front solo conoce URL_API (que le pasa
# el compose) y habla HTTP con la API. Nada más.
CMD ["php", "-S", "0.0.0.0:8020", "-t", "/var/www/front_php", "/var/www/front_php/index.php"]