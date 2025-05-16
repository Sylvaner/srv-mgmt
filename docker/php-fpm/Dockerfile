FROM php:8.3-fpm

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libldap2-dev \
    libonig-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP requises
RUN docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install ldap \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install zip \
    && docker-php-ext-install gd

# Configuration PHP
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Répertoire de travail
WORKDIR /var/www/html

CMD ["php-fpm"]
