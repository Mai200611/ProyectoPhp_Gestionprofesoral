# api_gestion/Dockerfile
# ---------------------------------------------------------------------------
# ¿Qué es este archivo?
#   Es la receta para construir la IMAGEN Docker del servicio de la API.
#   Docker lee este archivo con `docker compose build` y arma un contenedor
#   con PHP 8.3 y la extensión pdo_mysql lista para hablar con MariaDB.
#
# ¿Por qué php:8.3-cli y no php:8.3-apache?
#   Porque el servidor embebido de PHP (`php -S`) alcanza para desarrollo,
#   y además el 3_plan.md §1 y §19 lo fijan así: sin Apache ni nginx, sin
#   archivos de configuración de servidor que no son contenido del curso.
#
# ¿Por qué instalamos pdo_mysql?
#   La imagen oficial php:8.3-cli NO trae por defecto el driver PDO para
#   MySQL/MariaDB. La API lo necesita para conectarse. La extensión se
#   instala con `docker-php-ext-install pdo_mysql`, que es la forma
#   canónica en imágenes oficiales de PHP.
#
# ¿Qué NO hace este Dockerfile?
#   - NO copia Composer (no hay Composer en el proyecto).
#   - NO instala frameworks ni librerías.
#   - NO arranca Apache ni nginx: solo el servidor embebido `php -S`.
# ---------------------------------------------------------------------------

FROM php:8.3-cli

# Instalar la extensión pdo_mysql. Es lo único que el contenedor de la API
# necesita de más: el resto es PHP puro del lenguaje.
RUN docker-php-ext-install pdo_mysql

# Carpeta de trabajo adentro del contenedor. El código se monta encima
# desde el docker-compose (volumen), así que en desarrollo los archivos
# que ves en tu IDE son los que ejecuta el contenedor.
WORKDIR /app

# Copiamos el código como respaldo: si alguien corre la imagen SIN
# montar el volumen (por ejemplo `docker run` a mano), la API funcione
# igual. En el día a día del compose, el volumen sobrescribe esto y la
# edición es en vivo.
COPY . /app

# Puerto en el que adentro del contenedor escuchará el servidor embebido.
# No es el puerto del host; el host se mapea desde el docker-compose.
EXPOSE 8113

# Comando de arranque: el servidor embebido de PHP con index.php como
# router. Todas las peticiones pasan por el front controller.
CMD ["php", "-S", "0.0.0.0:8113", "index.php"]