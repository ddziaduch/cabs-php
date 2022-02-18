FROM php:8.0.15-cli
RUN apt-get update && \
  apt-get install -y libpq-dev && \
  docker-php-ext-install pdo pdo_pgsql
RUN pecl install xdebug
RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.iniOLD
RUN docker-php-ext-enable xdebug
