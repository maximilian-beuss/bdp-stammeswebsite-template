# Lokale Entwicklungsumgebung — Stamm der Vaganten
#
# Spiegelt die auf netcup dokumentierte Zielumgebung (DEPLOYMENT.md § 2):
# PHP 8.2 + genau die dort gelisteten Erweiterungen.
FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libxml2-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        pdo_mysql \
        mysqli \
        zip \
        mbstring \
        dom \
        simplexml \
        opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# php.ini-Werte gemäß DEPLOYMENT.md § 2.1
RUN { \
        echo 'memory_limit = 256M'; \
        echo 'max_execution_time = 300'; \
        echo 'upload_max_filesize = 128M'; \
        echo 'post_max_size = 128M'; \
        echo 'max_input_vars = 5000'; \
        echo 'opcache.enable = 1'; \
    } > /usr/local/etc/php/conf.d/vaganten.ini

# Concrete braucht AllowOverride All, damit die .htaccess im
# Dokumentwurzel greift (sprechende URLs, Zugriffsschutz).
RUN { \
        echo '<Directory /var/www/html>'; \
        echo '    AllowOverride All'; \
        echo '    Require all granted'; \
        echo '</Directory>'; \
    } > /etc/apache2/conf-available/vaganten.conf \
    && a2enconf vaganten
