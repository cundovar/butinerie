#!/usr/bin/env bash
set -e

CONTAINER=wordpress_wordpress_1

echo "🔄 Envoi vers le conteneur..."

docker cp ./wp-content/plugins/. "$CONTAINER":/var/www/html/wp-content/plugins/
docker cp ./wp-content/themes/.  "$CONTAINER":/var/www/html/wp-content/themes/
docker cp ./wp-content/uploads/. "$CONTAINER":/var/www/html/wp-content/uploads/

docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content

echo "✅ Plugins, thèmes et images envoyés"
