FROM php:8.0.15-cli
RUN apt-get update && \
  apt-get install -y libpq-dev && \
  docker-php-ext-install pdo pdo_pgsql
