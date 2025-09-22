# butinerie

# 🏪 Butinerie - Environnement de Développement WordPress

> Configuration Docker pour le développement collaboratif du site Butinerie

## 🚀 Démarrage Rapide

### Prérequis
- [Docker](https://docs.docker.com/get-docker/) installé
- [Docker Compose](https://docs.docker.com/compose/install/) installé
- Accès au réseau Docker `sql_docker_mysql_network` (base de données existante)

### Installation

1. **Cloner le projet**
   ```bash
   git clone <url-du-repo>
   cd wordpress
   ```

2. **Lancer l'environnement de développement**
   ```bash
   ./setup-dev.sh
   ```

3. **Accéder au site**
   - WordPress : [http://localhost:8080](http://localhost:8080)
   - L'interface d'administration sera accessible avec vos identifiants habituels

## 📦 Architecture

### Configuration Docker
- **Base de données** : Utilise la base MySQL existante (`mysql_db` sur le réseau `sql_docker_mysql_network`)
- **WordPress** : Version 6.8.2 avec Apache
- **Uploads** : Volume local pour chaque développeur (pas de synchronisation)
- **Plugins/Thèmes** : Synchronisés entre tous les développeurs

### Structure du projet
```
.
├── docker-compose.yml          # Configuration Docker
├── setup-dev.sh               # Script de démarrage automatique
├── wp-content/
│   ├── plugins/               # 🔄 Synchronisé (20+ plugins)
│   ├── themes/                # 🔄 Synchronisé (Enfold inclus)
│   ├── mu-plugins/            # 🔄 Synchronisé
│   └── uploads/               # 📱 Local (volume Docker)
└── README.md                  # Ce fichier
```

## 🛠️ Développement

### Ce que vous pouvez faire
- ✅ **Installer/modifier des plugins** - Synchronisé avec l'équipe
- ✅ **Modifier les thèmes** - ⚠️ Attention : gardez le thème Enfold !
- ✅ **Ajouter/supprimer des images** - Local à votre environnement
- ✅ **Développer en PHP/CSS/JS** - Modifications synchronisées
- ✅ **Accéder à la base de données** - Partagée avec l'équipe

### Ce qui est synchronisé
- 🔄 Code PHP, CSS, JavaScript
- 🔄 Plugins et leur configuration
- 🔄 Thèmes et personnalisations
- 🔄 Fichiers de configuration

### Ce qui est local
- 📱 Images uploadées (`wp-content/uploads/`)
- 📱 Caches et fichiers temporaires
- 📱 Logs de développement

## 📋 Commandes Utiles

### Gestion des conteneurs
```bash
# Démarrer l'environnement
./setup-dev.sh

# Redémarrer WordPress
docker-compose restart

# Arrêter l'environnement
docker-compose down

# Voir les logs
docker-compose logs -f wordpress
```

### Accès aux fichiers
```bash
# Accéder au conteneur WordPress
docker exec -it wordpress_wordpress_1 bash

# Copier des fichiers vers le conteneur
docker cp ./fichier.php wordpress_wordpress_1:/var/www/html/

# Copier des fichiers depuis le conteneur
docker cp wordpress_wordpress_1:/var/www/html/wp-config.php ./
```

## 🔧 Configuration

### Base de données
- **Host** : `mysql_db:3306`
- **Database** : `backup_db`
- **User** : `root`
- **Password** : `rootpassword`

### URLs
- **Site** : http://localhost:8080
- **Admin** : http://localhost:8080/wp-admin

## 🚨 Problèmes Courants

### WordPress ne démarre pas
```bash
# Vérifier que la base de données est accessible
docker network ls | grep sql_docker_mysql_network

# Redémarrer complètement
docker-compose down
./setup-dev.sh
```

### Plugins manquants
```bash
# Le script setup-dev.sh copie automatiquement tous les plugins
# Si problème, relancer :
./setup-dev.sh
```

### Permissions
```bash
# Fixer les permissions si nécessaire
docker exec wordpress_wordpress_1 chown -R www-data:www-data /var/www/html/wp-content/
```

### Uploads non visibles
Les uploads sont stockés dans un volume Docker local. Pour partager des images spécifiques :
1. Copier manuellement : `docker cp image.jpg wordpress_wordpress_1:/var/www/html/wp-content/uploads/`
2. Ou les ajouter via l'interface WordPress admin

## 👥 Workflow Collaboratif

### Développeur qui rejoint l'équipe
1. Clone le repo
2. Lance `./setup-dev.sh`
3. Commence à développer immédiatement

### Ajouter un nouveau plugin
1. Installer via l'interface WordPress OU copier dans `wp-content/plugins/`
2. Commit et push les modifications
3. Les autres devs récupèrent avec `git pull`

### Modifier un thème
1. Éditer les fichiers dans `wp-content/themes/`
2. ⚠️ **IMPORTANT** : Ne pas supprimer ou casser le thème Enfold
3. Commit et push les modifications

## 📝 Notes Importantes

- **Thème principal** : Enfold (ne pas supprimer)
- **Base de données** : Partagée - attention aux modifications de structure
- **Uploads** : Chaque dev a ses propres images de test
- **Configuration** : Pas de variables d'environnement sensibles dans le repo

## 🆘 Support

En cas de problème :
1. Vérifier que Docker fonctionne : `docker --version`
2. Vérifier que la base MySQL externe est accessible
3. Consulter les logs : `docker-compose logs wordpress`
4. Redémarrer proprement : `docker-compose down && ./setup-dev.sh`

---

**Happy coding! 🚀**