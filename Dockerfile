FROM php:8.4-apache

# Extensiones que php:8.4-apache no trae y Quetzal exige:
# pdo_mysql, gd (install.php:33-38) e intl (format_date).
# unzip lo usa composer para --prefer-dist.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev libicu-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd intl pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# rewrite: rutas amigables. headers: .htaccess:36 usa `Header set` sin
# <IfModule>, sin el módulo Apache tira 500. expires: bloque de caché.
RUN a2enmod rewrite headers expires

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY . /var/www/html

RUN composer install -d /var/www/html/app \
        --no-dev --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
