FROM php:8.3-apache

# Extensión de MongoDB (necesita OpenSSL para conectar con Atlas) + herramientas de Composer
RUN apt-get update \
 && apt-get install -y --no-install-recommends libssl-dev git unzip \
 && pecl install mongodb \
 && docker-php-ext-enable mongodb \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/rescat.ini
COPY docker/apache.conf /etc/apache2/conf-available/rescat.conf
RUN a2enconf rescat

WORKDIR /var/www/html

# Dependencias primero para aprovechar la caché de Docker
COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Código de la aplicación
COPY --chown=www-data:www-data . /var/www/html
RUN mkdir -p uploads/donaciones uploads/foro \
 && chown -R www-data:www-data uploads

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000
CMD ["entrypoint.sh"]
