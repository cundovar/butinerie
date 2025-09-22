#!/bin/bash

# Copier les fichiers de configuration depuis le volume partagé
echo "Synchronizing configuration files..."

# Copier wp-config.php si il existe
if [ -f /shared-config/wp-config.php ]; then
    cp /shared-config/wp-config.php /var/www/html/wp-config.php
    chown www-data:www-data /var/www/html/wp-config.php
    echo "wp-config.php synchronized"
fi

# Copier .htaccess si il existe, sinon créer un basique
if [ -f /shared-config/.htaccess ]; then
    cp /shared-config/.htaccess /var/www/html/.htaccess
    chown www-data:www-data /var/www/html/.htaccess
    echo ".htaccess synchronized"
else
    # Créer un .htaccess basique si absent
    cat > /var/www/html/.htaccess << 'EOF'
# BEGIN WordPress
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
# END WordPress
EOF
    chown www-data:www-data /var/www/html/.htaccess
    echo "Basic .htaccess created"
fi

# Démarrer Apache en arrière-plan
apache2-foreground &

# Surveiller les changements et resynchroniser
while true; do
    sleep 10

    # Vérifier si wp-config.php a changé
    if [ -f /shared-config/wp-config.php ] && ! cmp -s /shared-config/wp-config.php /var/www/html/wp-config.php; then
        echo "wp-config.php changed, updating..."
        cp /shared-config/wp-config.php /var/www/html/wp-config.php
        chown www-data:www-data /var/www/html/wp-config.php
    fi

    # Vérifier si .htaccess a changé
    if [ -f /shared-config/.htaccess ] && ! cmp -s /shared-config/.htaccess /var/www/html/.htaccess; then
        echo ".htaccess changed, updating..."
        cp /shared-config/.htaccess /var/www/html/.htaccess
        chown www-data:www-data /var/www/html/.htaccess
    fi
done