#!/usr/bin/env bash
set -e

CONTAINER=wordpress_wordpress_1

echo "📸 Envoi uploads vers le conteneur..."
echo "⚠️  Ceci peut prendre plusieurs minutes (images lourdes)"

# Vérifier que le conteneur existe
if ! docker ps | grep -q "$CONTAINER"; then
    echo "❌ Conteneur $CONTAINER non trouvé. Lancez d'abord: docker-compose up -d"
    exit 1
fi

# Afficher la taille avant envoi
echo "📊 Taille du dossier uploads:"
du -sh ./wp-content/uploads/ 2>/dev/null || echo "Dossier uploads non trouvé"

# Envoi avec barre de progression simulée
echo "🔄 Transfert en cours..."
docker cp ./wp-content/uploads/. "$CONTAINER":/var/www/html/wp-content/uploads/

# Fixer les permissions
echo "🔐 Correction des permissions..."
docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/uploads/

echo "✅ Uploads envoyés vers le conteneur"
echo "🌐 Testez: http://localhost:8080/wp-content/uploads/"