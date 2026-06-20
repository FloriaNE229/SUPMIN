#!/bin/bash
set -e

echo "🚀 Démarrage SUPMIN..."

# Vider et reconstruire les caches
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Optimisations production
php artisan config:cache || true
php artisan route:cache || true

# Lancer les migrations (1ère fois)
echo "📦 Migrations en cours..."
php artisan migrate --force || true

# Lien symbolique pour storage
php artisan storage:link || true

# Configurer le port (Render fournit $PORT dynamiquement)
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/*.conf

echo "✅ Démarrage Apache sur le port ${PORT}"
apache2-foreground