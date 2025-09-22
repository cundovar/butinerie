#!/bin/bash
set -e

echo "🚀 Démarrage WordPress sans écrasement..."

# Lancer Apache directement sans l'entrypoint WordPress
echo "📦 Démarrage Apache..."

# Fixer la config si besoin
if [ ! -f /var/www/html/wp-config.php ]; then
    echo "⚙️ Configuration WordPress..."
    # Utiliser les variables d'environnement pour générer wp-config.php
    cat > /var/www/html/wp-config.php << 'EOF'
<?php
define('DB_NAME', getenv('WORDPRESS_DB_NAME'));
define('DB_USER', getenv('WORDPRESS_DB_USER'));
define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD'));
define('DB_HOST', getenv('WORDPRESS_DB_HOST'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = 'wp_';

define('WP_DEBUG', false);

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
EOF
fi

echo "✅ Configuration prête, démarrage Apache..."
exec apache2-foreground