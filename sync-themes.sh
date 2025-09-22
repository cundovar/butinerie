#!/bin/bash

# Script pour synchroniser les thèmes Enfold et uploads avec le container
echo "🔄 Synchronisation des thèmes Enfold et uploads..."

# Vérifier si le container est en cours d'exécution
if ! docker ps | grep -q wordpress_wordpress_1; then
    echo "❌ Le container WordPress n'est pas en cours d'exécution"
    exit 1
fi

# Copier les thèmes Enfold
echo "📂 Copie du thème Enfold..."
docker cp wp-content/themes/enfold wordpress_wordpress_1:/var/www/html/wp-content/themes/

echo "📂 Copie du thème Enfold Child..."
docker cp wp-content/themes/enfold-child wordpress_wordpress_1:/var/www/html/wp-content/themes/

# Synchroniser les uploads si nécessaire
echo "📁 Synchronisation des uploads..."
if [ -d "wp-content/uploads" ]; then
    docker cp wp-content/uploads/. wordpress_wordpress_1:/var/www/html/wp-content/uploads/
fi

# Corriger les permissions
echo "🔧 Correction des permissions..."
docker exec wordpress_wordpress_1 chown -R www-data:www-data /var/www/html/wp-content/themes/enfold
docker exec wordpress_wordpress_1 chown -R www-data:www-data /var/www/html/wp-content/themes/enfold-child
docker exec wordpress_wordpress_1 chown -R www-data:www-data /var/www/html/wp-content/uploads

echo "✅ Synchronisation terminée!"
echo "🌐 Site accessible sur: http://localhost:8080"