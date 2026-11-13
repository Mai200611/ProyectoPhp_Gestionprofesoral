# api_gestion/Dockerfile — Versión 3: UNA sola imagen para los DOS motores.
#
# En la v2 esta imagen solo traía pdo_mysql. En la v3 trae pdo_mysql Y
# pdo_pgsql, y el ensamblador escoge cuál usar al arrancar leyendo DB_MOTOR.
# Es deliberado que sea UNA imagen, no dos: el punto de la versión es que
# es el mismo programa contra los dos motores (RNF4, 4_research D1).
#
# Los dos drivers se compilan en el MISMO RUN, no en dos, porque compilar
# extensiones de PDO es caro y no queremos dos capas que hagan lo mismo.
#
# Puertos:
#   - EXPOSE 8022: el puerto donde escucha el servidor embebido de PHP
#     ADENTRO del contenedor. El compose lo publica al host como 8122.
#   - El +100 es del host: adentro todo es igual que en la constitución.

FROM php:8.3-cli

# --- Dependencias del sistema --------------------------------------------------
# libpq-dev : cabeceras de PostgreSQL, para compilar pdo_pgsql.
# libmariadb-dev : cabeceras de MariaDB, para compilar pdo_mysql.
# (Se usa libmariadb-dev y no libmysqlclient-dev porque la imagen oficial de
#  PHP ya trae pdo_mysql compilable contra MariaDB, que es el cliente que
#  MariaDB habla nativamente.)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libmariadb-dev \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# --- Los DOS drivers de PDO, en el mismo RUN --------------------------------
# Orden: primero el de MariaDB (que ya venía en la v2) y luego el de
# PostgreSQL (el nuevo). El `docker-php-ext-install` compila los dos y deja
# los .so en la misma extensión activa.
RUN docker-php-ext-install pdo_mysql pdo_pgsql

# --- Directorio de trabajo ---------------------------------------------------
WORKDIR /var/www/api_gestion

# --- Copiamos el código ------------------------------------------------------
# En desarrollo el compose MONTA ./api_gestion encima de este directorio, así
# que este COPY es solo para que la imagen sea autocontenida si alguien la
# corre sin el compose (útil para CI o para inspeccionarla). La recarga
# natural (Art. 7) sigue funcionando gracias al bind mount.
COPY . /var/www/api_gestion

# --- Puerto que escucha el servidor embebido DENTRO del contenedor -----------
# El compose lo publica al host como 8122. Adentro es 8022, igual que en la
# constitución.
EXPOSE 8022

# --- Comando de arranque -----------------------------------------------------
# El servidor embebido de PHP escucha en 0.0.0.0:8022 (no en localhost, o no
# sería alcanzable desde otro contenedor). El front controller es index.php.
#
# NO usamos `php -S` para producción: esto es didáctico y el servidor
# embebido alcanza de sobra. Lo que importa es que el enrutamiento vive en
# index.php (Art. 2), no en el servidor.
CMD ["php", "-S", "0.0.0.0:8022", "-t", "/var/www/api_gestion", "/var/www/api_gestion/index.php"]