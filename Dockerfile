# Image de base PHP 8.2 + Apache
FROM php:8.2-apache

# Installer les dépendances système et extensions PHP nécessaires à Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP requises
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Activer mod_rewrite Apache (obligatoire pour Laravel)
RUN a2enmod rewrite

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . .

# Installer les dépendances Composer (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Définir les permissions Laravel (storage et bootstrap/cache)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Pointer Apache sur /public au lieu de la racine
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurer Apache pour autoriser .htaccess (mod_rewrite)
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Script de démarrage : migrations + démarrer Apache
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Render utilise PORT, Apache écoute par défaut sur 80
ENV PORT=80
EXPOSE 80

CMD ["/usr/local/bin/start.sh"]