# Imagem para rodar o cassino no seu computador (localhost).
# Use junto com o docker-compose.yml da raiz:  docker compose up -d
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev libonig-dev mariadb-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip intl gd bcmath exif opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && { echo 'upload_max_filesize=64M'; echo 'post_max_size=64M'; echo 'memory_limit=512M'; echo 'max_execution_time=300'; } > /usr/local/etc/php/conf.d/casino.ini

COPY docker/entrypoint.sh /usr/local/bin/casino-entrypoint
RUN chmod +x /usr/local/bin/casino-entrypoint

WORKDIR /var/www/html
ENTRYPOINT ["casino-entrypoint"]
CMD ["apache2-foreground"]
