# 🚀 Synchronisation WordPress Docker ↔ IDE

Ces scripts permettent de synchroniser facilement les fichiers **WordPress** entre ton conteneur Docker (`wordpress_wordpress_1`) et ton projet local (IDE).  

---

## 📂 Scripts disponibles

### 🔽 1. `recup.sh`
Récupère les fichiers depuis le conteneur vers ton IDE.  
👉 Utile après avoir installé un **plugin/thème** depuis l’admin WordPress.  

```bash
./recup.sh
```

Cela va copier :  
- `wp-content/plugins`  
- `wp-content/themes`  
- `wp-content/uploads`  

---

### 🔼 2. `envoi.sh`
Envoie tes fichiers de l’IDE vers le conteneur.  
👉 Utile après avoir modifié un **plugin/thème** dans ton IDE.  

```bash
./envoi.sh
```

Cela va copier :  
- `wp-content/plugins`  
- `wp-content/themes`  
- `wp-content/uploads`  

Puis corriger les permissions pour WordPress (`www-data`).

---

## ⚙️ Installation

1. Placer `recup.sh` et `envoi.sh` à la racine de ton projet.  
2. Donner les droits d’exécution :  
   ```bash
   chmod +x recup.sh envoi.sh
   ```

---

## 🚦 Utilisation rapide

- **Récupérer depuis le conteneur** :  
  ```bash
  ./recup.sh
  ```

- **Envoyer vers le conteneur** :  
  ```bash
  ./envoi.sh
  ```

---

## ℹ️ Notes

- Le nom du conteneur utilisé est **`wordpress_wordpress_1`**.  
  Si ton conteneur a un autre nom, modifie la variable `CONTAINER=` dans les scripts.  
- Pas besoin de modifier ton `docker-compose.yml`.  
- Idéal si tu ne peux pas utiliser de **volumes montés** (`bind mounts`).  

---
