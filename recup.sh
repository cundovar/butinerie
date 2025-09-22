#!/usr/bin/env bash
set -e

CONTAINER=wordpress_wordpress_1

echo "🔄 Récupération depuis le conteneur..."

mkdir -p ./wp-content/plugins ./wp-content/themes ./wp-content/uploads

docker cp "$CONTAINER":/var/www/html/wp-content/plugins/. ./wp-content/plugins/
docker cp "$CONTAINER":/var/www/html/wp-content/themes/.  ./wp-content/themes/
docker cp "$CONTAINER":/var/www/html/wp-content/uploads/. ./wp-content/uploads/

echo "✅ Plugins, thèmes et images récupérés"