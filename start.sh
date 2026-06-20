#!/bin/bash
set -e

echo "🚀 Démarrage SUPMIN..."

# Générer la clé d'application si pas encore définie
if [ -z "$APP_KEY" ]; then
    echo "⚠️  Génération automatique APP_KEY"
    php artisan key:generate --force
fi

# Vider et reconstruire les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimisations production
php artisan config:cache
php artisan route:cache

# Lancer les migrations + seeders (1ère fois uniquement)
echo "📦 Migrations en cours..."
php artisan migrate --force

# Lien symbolique pour storage
php artisan storage:link || true

# Configurer le port (Render fournit $PORT dynamiquement)
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/*.conf

echo "✅ Démarrage Apache sur le port ${PORT}"
apache2-foreground