# front_php/Dockerfile — Versión 3
#
# El front no necesita drivers de base de datos: no habla con la base, solo
# con la API por HTTP (RNF6). Por eso su imagen es distinta y más simple
# que la de la API.

FROM php:8.3-cli

WORKDIR /var/www/html

COPY . .

# Puerto interno del front. En el host se publica como 8184:8084.
EXPOSE 8084

CMD ["php", "-S", "0.0.0.0:8084", "-t", "/var/www/html"]