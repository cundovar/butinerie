#!/usr/bin/env bash
set -e

CONTAINER=wordpress_wordpress_1

echo "🎨 Envoi themes et plugins vers le conteneur..."

# Vérifier que le conteneur existe
if ! docker ps | grep -q "$CONTAINER"; then
    echo "❌ Conteneur $CONTAINER non trouvé. Lancez d'abord: docker-compose up -d"
    exit 1
fi

# Nettoyer les plugins par défaut WordPress
echo "🧹 Suppression des plugins par défaut..."
docker exec "$CONTAINER" rm -rf /var/www/html/wp-content/plugins/* 2>/dev/null || true
docker exec "$CONTAINER" rm -rf /var/www/html/wp-content/themes/twenty* 2>/dev/null || true

# Envoi plugins
if [ -d "./wp-content/plugins" ]; then
    echo "🔌 Envoi des plugins..."
    docker cp ./wp-content/plugins/. "$CONTAINER":/var/www/html/wp-content/plugins/
    PLUGIN_COUNT=$(ls -1 ./wp-content/plugins/ | wc -l)
    echo "   ✅ $PLUGIN_COUNT plugins envoyés"
else
    echo "⚠️  Dossier plugins non trouvé"
fi

# Envoi themes
if [ -d "./wp-content/themes" ]; then
    echo "🎨 Envoi des thèmes..."
    docker cp ./wp-content/themes/. "$CONTAINER":/var/www/html/wp-content/themes/
    THEME_COUNT=$(ls -1 ./wp-content/themes/ | wc -l)
    echo "   ✅ $THEME_COUNT thèmes envoyés (Enfold inclus)"
else
    echo "⚠️  Dossier themes non trouvé"
fi

# Envoi mu-plugins si présent
if [ -d "./wp-content/mu-plugins" ]; then
    echo "⚙️  Envoi des mu-plugins..."
    docker cp ./wp-content/mu-plugins/. "$CONTAINER":/var/www/html/wp-content/mu-plugins/
fi

# Fixer les permissions
echo "🔐 Correction des permissions..."
docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/plugins/ /var/www/html/wp-content/themes/

echo "✅ Themes et plugins envoyés"
echo "🌐 Vérifiez: http://localhost:8080/wp-admin/plugins.php"