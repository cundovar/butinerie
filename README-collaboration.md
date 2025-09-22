# 🤝 Guide de collaboration WordPress + Docker + Git

Ce projet utilise **WordPress dans Docker**.  
👉 Comme nous travaillons à plusieurs, voici les **règles d’or** pour éviter les erreurs.

---

## 🚀 Workflow Git (branches)

### Branches principales
- **main** → version stable (prod)
- **develop** → branche d’intégration (tout le monde fusionne ici)

### Branches de travail
- Chaque fonctionnalité ou correctif doit être faite dans une branche dédiée :
  - `feature/nom-fonctionnalite`
  - `fix/nom-correctif`

👉 Exemple :
```bash
git checkout develop
git pull origin develop
git checkout -b feature/ajout-elementor
```

---

## 🔄 Workflow de synchro avec Docker

⚠️ **Important** : WordPress installe les plugins/thèmes/uploads **dans le conteneur**.  
Nous devons les synchroniser avec l’IDE pour les versionner dans Git.

### 1. Récupérer depuis le conteneur
Après avoir installé un plugin ou un thème dans l’admin WP :  
```bash
./recup.sh
git add wp-content/
git commit -m "Ajout plugin XXX"
git push origin feature/ma-branche
```

### 2. Envoyer vers le conteneur
Après avoir fait un `git pull` pour récupérer le travail des collègues :  
```bash
git pull origin develop
./envoi.sh
```

👉 Cela mettra à jour ton WordPress local avec les plugins/thèmes installés par les autres.

---

## 📏 Règles d’or à respecter

❌ **Ne jamais travailler directement sur `main` ou `develop`**  
✅ Toujours créer une branche (`feature/...` ou `fix/...`).

❌ **Ne pas oublier `./recup.sh` avant un commit**  
✅ Sinon tes plugins/thèmes installés ne seront pas dans Git → tes collègues ne les auront pas.

❌ **Ne pas lancer `docker-compose down -v`**  
✅ Cela supprime les volumes et donc les données (DB, uploads).  
Utiliser simplement :
```bash
docker-compose down
```

❌ **Ne pas modifier les fichiers WordPress core** (ex: `wp-config.php`, `wp-includes/...`)  
✅ Limite-toi à `wp-content/plugins`, `wp-content/themes`, `wp-content/uploads`.

---

## 🚦 Exemple de cycle de travail

1. Je veux ajouter un plugin Elementor addon :  
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/elementor-addon
   ```

2. J’installe le plugin via l’admin WP.  
3. Je fais :
   ```bash
   ./recup.sh
   git add wp-content/
   git commit -m "Ajout plugin Elementor Addon"
   git push origin feature/elementor-addon
   ```

4. J’ouvre une **Merge Request / Pull Request** vers `develop`.  
5. Les collègues font :
   ```bash
   git pull origin develop
   ./envoi.sh
   ```

---

## ✅ Résumé
- **recup.sh** → copier du conteneur vers Git.  
- **envoi.sh** → mettre à jour ton conteneur avec Git.  
- **Toujours travailler dans une branche.**  
- **Jamais supprimer les volumes Docker.**

Bonne collaboration 🚀
