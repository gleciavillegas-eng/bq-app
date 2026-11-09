FROM php:8.3-apache

RUN apt-get update && apt-get install -y git unzip libzip-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip intl opcache \
    && a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN { echo "upload_max_filesize=6M"; echo "post_max_size=8M"; } > /usr/local/etc/php/conf.d/uploads.ini

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html