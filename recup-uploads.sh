#!/usr/bin/env bash
set -e

CONTAINER=wordpress_wordpress_1

echo "📸 Récupération uploads depuis le conteneur..."
echo "⚠️  Ceci peut prendre plusieurs minutes (images lourdes)"

# Vérifier que le conteneur existe
if ! docker ps | grep -q "$CONTAINER"; then
    echo "❌ Conteneur $CONTAINER non trouvé. Lancez d'abord: docker-compose up -d"
    exit 1
fi

# Créer le dossier uploads si nécessaire
mkdir -p ./wp-content/uploads

# Sauvegarde de l'ancien uploads si présent
if [ -d "./wp-content/uploads" ] && [ "$(ls -A ./wp-content/uploads 2>/dev/null)" ]; then
    BACKUP_DIR="./wp-content/uploads-backup-$(date +%Y%m%d-%H%M%S)"
    echo "💾 Sauvegarde uploads existants vers: $BACKUP_DIR"
    cp -r ./wp-content/uploads "$BACKUP_DIR"
fi

# Afficher la taille dans le conteneur
echo "📊 Vérification de la taille dans le conteneur..."
docker exec "$CONTAINER" du -sh /var/www/html/wp-content/uploads/ 2>/dev/null || echo "Pas d'uploads dans le conteneur"

# Récupération
echo "🔄 Transfert en cours..."
docker cp "$CONTAINER":/var/www/html/wp-content/uploads/. ./wp-content/uploads/

echo "✅ Uploads récupérés depuis le conteneur"
echo "📁 Vérifiez le dossier: ./wp-content/uploads/"