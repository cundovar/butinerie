#!/bin/bash

echo "🚀 Configuration environnement développement Butinerie"
echo "=================================================="

# Vérifier que Docker est lancé
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker n'est pas lancé. Veuillez démarrer Docker."
    exit 1
fi

# Lancer les conteneurs
echo "📦 Démarrage des conteneurs..."
docker-compose up -d

# Attendre que WordPress soit prêt
echo "⏳ Attente du démarrage de WordPress..."
sleep 15

# SOLUTION: Supprimer les plugins par défaut et copier les nôtres
echo "🔧 Synchronisation forcée des plugins..."
docker exec wordpress_wordpress_1 rm -rf /var/www/html/wp-content/plugins/*
docker exec wordpress_wordpress_1 rm -rf /var/www/html/wp-content/themes/twenty*

# Copier TOUS nos contenus
echo "📦 Copie complète des plugins et thèmes..."
docker cp ./wp-content/plugins/. wordpress_wordpress_1:/var/www/html/wp-content/plugins/
docker cp ./wp-content/themes/. wordpress_wordpress_1:/var/www/html/wp-content/themes/
docker cp ./wp-content/mu-plugins/. wordpress_wordpress_1:/var/www/html/wp-content/mu-plugins/ 2>/dev/null || true

# Copier les images du projet principal (une seule fois)
echo "📸 Copie des images initiales..."
if [ -d "./wp-content/uploads" ]; then
    docker cp ./wp-content/uploads/. wordpress_wordpress_1:/var/www/html/wp-content/uploads/ 2>/dev/null || true
    echo "✅ Images copiées avec succès"
else
    echo "⚠️  Dossier uploads non trouvé - normal au premier démarrage"
fi

# Fixer les permissions
echo "🔐 Correction des permissions..."
docker exec wordpress_wordpress_1 chown -R www-data:www-data /var/www/html/wp-content/

echo ""
echo "✅ Environnement prêt !"
echo "🌐 WordPress: http://localhost:8080"
echo "📁 Vous pouvez maintenant:"
echo "   - Ajouter/modifier/supprimer des images"
echo "   - Installer/modifier des plugins"
echo "   - Modifier les thèmes (gardez Enfold !)"
echo ""
echo "🔄 Pour redémarrer: docker-compose restart"
echo "🛑 Pour arrêter: docker-compose down"