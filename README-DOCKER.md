# Configuration Docker pour le Développement en Équipe

## Prérequis
- Docker et Docker Compose installés
- Git pour la synchronisation du code
- Base de données MySQL externe (configurée dans wp-config.php)

## Structure du Projet
```
wordpress/
├── docker-compose.yml        # Configuration WordPress
├── .dockerignore             # Fichiers exclus du build
├── wp-content/               # Thèmes et plugins (synchronisé)
├── wp-config.php            # Configuration WordPress (synchronisé)
├── .htaccess                # Règles Apache (synchronisé)
└── README-DOCKER.md         # Ce fichier
```

## Démarrage Rapide

### 1. Cloner le Projet
```bash
git clone <url-du-repo>
cd wordpress
```

### 2. Configurer la Base de Données
Assurez-vous que votre base de données externe est accessible et que wp-config.php contient les bonnes informations de connexion.

### 3. Lancer l'Application
```bash
# Démarrage simple
docker-compose up -d

# Synchroniser les thèmes Enfold (nécessaire au premier démarrage)
./sync-themes.sh
```

### 4. Accès
- **Site WordPress** : http://localhost:8080 (avec thème Enfold Child actif)

## Développement en Équipe

### Fichiers Synchronisés
Ces fichiers/dossiers sont montés en volumes et modifiables en temps réel :
- `wp-content/` - Thèmes et plugins
- `wp-config.php` - Configuration WordPress
- `.htaccess` - Règles de réécriture

### Workflow Recommandé
1. **Modifier le code** dans votre IDE local
2. **Les changements** sont instantanément visibles dans le container
3. **Committer** seulement les fichiers pertinents :
   ```bash
   git add wp-content/ wp-config.php .htaccess
   git commit -m "Description des modifications"
   git push
   ```

### Commandes Utiles
```bash
# Voir les logs
docker-compose logs -f wordpress

# Arrêter les services
docker-compose down

# Redémarrer après modification
docker-compose restart wordpress

# Synchroniser les thèmes Enfold (si nécessaire)
./sync-themes.sh

# Accéder au container WordPress
docker-compose exec wordpress bash
```

## Thèmes Enfold

### Configuration
Le site utilise le thème **Enfold** avec un **thème enfant (enfold-child)** actif pour la personnalisation.

### Synchronisation des Thèmes
Les thèmes Enfold ne sont pas automatiquement synchronisés par le volume mount. Utilisez le script fourni :

```bash
# Après démarrage du container ou modification des thèmes
./sync-themes.sh
```

### Développement du Thème
- Modifiez le **thème enfant** (`wp-content/themes/enfold-child/`)
- Le thème parent Enfold reste intact
- Utilisez `./sync-themes.sh` après modifications importantes

### Gestion des Uploads
Les médias WordPress (`wp-content/uploads/`) sont synchronisés :
- **Automatiquement** via le volume mount pour les nouveaux fichiers
- **Manuellement** via `./sync-themes.sh` pour les fichiers existants
- Incluez uploads/ dans vos commits Git si nécessaire pour l'équipe

## Configuration Base de Données

### Base de Données Externe
La base de données est gérée en externe. Utilisez vos outils habituels pour :
- Sauvegarder/restaurer
- Gérer les utilisateurs
- Optimiser les performances

## Partage avec l'Équipe

### Configuration Git
Créer un `.gitignore` approprié :
```gitignore
# Fichiers WordPress core (gérés par Docker)
/wp-admin/
/wp-includes/
/wp-*.php
/index.php
/license.txt
/readme.html
/xmlrpc.php

# Garder ces fichiers
!wp-config.php
!.htaccess

# Uploads et cache
/wp-content/uploads/
/wp-content/cache/

# Docker
.env
docker-compose.override.yml

# Sauvegardes
*.sql
*.backup
```

### Bonnes Pratiques
1. **Ne jamais committer** : uploads, cache, logs
2. **Toujours committer** : thèmes, plugins, wp-config.php, .htaccess
3. **Tester localement** avant de pusher
4. **Utiliser des branches** pour les nouvelles fonctionnalités

## Dépannage

### Container ne démarre pas
```bash
# Vérifier les logs
docker-compose logs

# Reconstruire complètement
docker-compose down -v
docker-compose up --build
```

### Problèmes de permissions
```bash
# Corriger les permissions dans le container
docker-compose exec wordpress chown -R www-data:www-data /var/www/html/wp-content
```

### Base de données corrompue
```bash
# Supprimer le volume de données
docker-compose down -v
docker volume rm wordpress_db_data
docker-compose up --build
```

## Environnements

### Variables d'Environnement
Créer un fichier `.env` pour personnaliser :
```env
WORDPRESS_PORT=8080
DB_PASSWORD=votre_mot_de_passe
DB_NAME=votre_base
```

### Production
Modifier `docker-compose.yml` pour la production :
- Changer les ports
- Utiliser des secrets pour les mots de passe
- Ajouter des volumes persistants pour les uploads