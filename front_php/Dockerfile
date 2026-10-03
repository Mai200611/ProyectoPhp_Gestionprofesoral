# front_php/Dockerfile
# ---------------------------------------------------------------------------
# ¿Qué es este archivo?
#   Es la receta para construir la IMAGEN Docker del servicio del front.
#
# ¿Por qué NO se instala pdo_mysql aquí?
#   A PROPÓSITO. El Artículo 3 de la constitución y el RNF6 exigen que el
#   front NO pueda llegar a la base de datos: es la única prueba de que
#   son dos procesos separados. Si mañana alguien intentara meter un
#   `new PDO` en el front, ni siquiera compilaría el driver. La ausencia
#   es la comprobación.
#
# ¿Qué hace este contenedor?
#   Servir el front con el servidor embebido de PHP en el puerto 8020
#   (adentro del contenedor). El host lo mapea al 8120 desde el
#   docker-compose.
# ---------------------------------------------------------------------------

FROM php:8.3-cli

# La extensión cURL la usa el cliente_api.php para hablar con la API. Viene
# activada por defecto en la imagen oficial de PHP, así que no hace falta
# instalar nada más. (La ausencia de pdo_mysql es deliberada.)

WORKDIR /app

COPY . /app

EXPOSE 8020

CMD ["php", "-S", "0.0.0.0:8020", "index.php"]