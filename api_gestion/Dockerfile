# api_gestion/Dockerfile — Versión 3
#
# UNA sola imagen para los dos motores (RNF4). Trae los dos drivers de PDO
# (pdo_mysql y pdo_pgsql) y el ensamblador escoge al arrancar según DB_MOTOR.
# Dos imágenes distintas habrían escondido que es el mismo programa.

FROM php:8.3-cli

# Compilamos los dos drivers en un solo RUN: una capa, y libpq-dev solo
# queda en la imagen final lo justo (libpq5 es la librería de ejecución).
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_mysql pdo_pgsql \
 && apt-get purge -y --auto-remove libpq-dev \
 && apt-get install -y --no-install-recommends libpq5 \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiamos el código de la API.
COPY . .

# Puerto interno de la API. En el host se publica como 8186:8086.
EXPOSE 8086

# PHP puro: el servidor embebido sirve la API.
CMD ["php", "-S", "0.0.0.0:8086", "-t", "/var/www/html"]