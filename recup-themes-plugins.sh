#!/usr/bin/env bash
set -e

CONTAINER=wordpress_wordpress_1

echo "🎨 Récupération themes et plugins depuis le conteneur..."

# Vérifier que le conteneur existe
if ! docker ps | grep -q "$CONTAINER"; then
    echo "❌ Conteneur $CONTAINER non trouvé. Lancez d'abord: docker-compose up -d"
    exit 1
fi

# Créer les dossiers si nécessaires
mkdir -p ./wp-content/plugins ./wp-content/themes ./wp-content/mu-plugins

# Sauvegarde si des thèmes/plugins existent déjà
if [ -d "./wp-content/plugins" ] && [ "$(ls -A ./wp-content/plugins 2>/dev/null)" ]; then
    BACKUP_DIR="./wp-content-backup-$(date +%Y%m%d-%H%M%S)"
    echo "💾 Sauvegarde vers: $BACKUP_DIR"
    mkdir -p "$BACKUP_DIR"
    cp -r ./wp-content/plugins "$BACKUP_DIR/" 2>/dev/null || true
    cp -r ./wp-content/themes "$BACKUP_DIR/" 2>/dev/null || true
fi

# Récupération plugins
echo "🔌 Récupération des plugins..."
docker cp "$CONTAINER":/var/www/html/wp-content/plugins/. ./wp-content/plugins/
PLUGIN_COUNT=$(ls -1 ./wp-content/plugins/ 2>/dev/null | wc -l)
echo "   ✅ $PLUGIN_COUNT plugins récupérés"

# Récupération themes
echo "🎨 Récupération des thèmes..."
docker cp "$CONTAINER":/var/www/html/wp-content/themes/. ./wp-content/themes/
THEME_COUNT=$(ls -1 ./wp-content/themes/ 2>/dev/null | wc -l)
echo "   ✅ $THEME_COUNT thèmes récupérés"

# Récupération mu-plugins si présent
echo "⚙️  Récupération des mu-plugins..."
docker cp "$CONTAINER":/var/www/html/wp-content/mu-plugins/. ./wp-content/mu-plugins/ 2>/dev/null || echo "   ℹ️  Pas de mu-plugins"

echo "✅ Themes et plugins récupérés"
echo "📁 Vérifiez les dossiers: ./wp-content/plugins/ et ./wp-content/themes/"