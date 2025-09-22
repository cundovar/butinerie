#!/bin/bash
set -euo pipefail

echo "🚀 Initialisation WordPress collaborative..."

# Lancer WordPress normalement pour qu'il s'initialise
echo "📦 Initialisation WordPress de base..."
/usr/local/bin/docker-entrypoint.sh apache2-foreground &
WORDPRESS_PID=$!

# Attendre que WordPress soit prêt
echo "⏳ Attente de l'initialisation..."
sleep 30

echo "✅ WordPress initialisé, prêt pour la collaboration !"

# Garder le processus en vie
wait $WORDPRESS_PID