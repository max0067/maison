# 🔍 Comment accéder à l'application RSS Reader

## 📍 Localisation des fichiers

Les fichiers sont actuellement dans : **`/home/user/maison`**

Sur la branche : **`claude/rss-reader-app-sJ5Nm`**

## ✅ Les fichiers sont bien présents

Tous les fichiers RSS ont été créés et committés :

```
✓ rss_index.php              (11 Ko)
✓ rss_login.php              (3 Ko)
✓ rss_search.php             (8 Ko)
✓ rss_admin.php              (21 Ko)
✓ rss_logout.php             (0.1 Ko)
✓ rss_setup.sql              (4 Ko)
✓ install_rss.php            (créé dans install_rss.php)
✓ config/rss_config.php      (créé)
✓ includes/rss_functions.php (créé)
✓ assets/css/rss_style.css   (créé)
✓ RSS_README.md              (7 Ko)
✓ RSS_GUIDE_RAPIDE.md        (4 Ko)
✓ RSS_APPLICATION_COMPLETE.md (9 Ko)
✓ rss_cron.php               (1 Ko)
✓ rss_htaccess.txt           (1 Ko)
```

## 🌐 Accès via GitHub

### Option 1 : Via l'interface GitHub

1. **Accéder au dépôt** : https://github.com/max0067/maison

2. **Changer de branche** :
   - Cliquer sur le menu déroulant des branches (en haut à gauche)
   - Rechercher : `claude/rss-reader-app-sJ5Nm`
   - Cliquer dessus

3. **Voir les fichiers** :
   - Les fichiers RSS seront visibles à la racine
   - Préfixés par `rss_` et `RSS_`

### Option 2 : Lien direct vers la branche

```
https://github.com/max0067/maison/tree/claude/rss-reader-app-sJ5Nm
```

### Option 3 : Voir le dernier commit

```
https://github.com/max0067/maison/commit/801ef2e
```

## 💻 Accès local (si tu as cloné le repo)

```bash
# Récupérer la branche
git fetch origin

# Changer vers la branche
git checkout claude/rss-reader-app-sJ5Nm

# Lister les fichiers RSS
ls -l rss_* RSS_*
```

## 🚀 Démarrer l'application

### Sur un serveur local (XAMPP, WAMP, MAMP, etc.)

1. **Copier les fichiers** dans le dossier de ton serveur web :
   ```bash
   # Par exemple pour XAMPP
   cp -r /home/user/maison/rss_* /opt/lampp/htdocs/rss-reader/
   cp -r /home/user/maison/RSS_* /opt/lampp/htdocs/rss-reader/
   cp -r /home/user/maison/assets /opt/lampp/htdocs/rss-reader/
   cp -r /home/user/maison/config /opt/lampp/htdocs/rss-reader/
   cp -r /home/user/maison/includes /opt/lampp/htdocs/rss-reader/
   cp /home/user/maison/install_rss.php /opt/lampp/htdocs/rss-reader/
   ```

2. **Démarrer MySQL** et ton serveur web

3. **Ouvrir dans le navigateur** :
   ```
   http://localhost/rss-reader/install_rss.php
   ```

### Avec le serveur PHP intégré

```bash
# Se placer dans le dossier
cd /home/user/maison

# Démarrer le serveur
php -S localhost:8000

# Ouvrir dans le navigateur
http://localhost:8000/install_rss.php
```

## 📦 Télécharger tous les fichiers

### Via GitHub (ZIP)

1. Aller sur : https://github.com/max0067/maison/tree/claude/rss-reader-app-sJ5Nm
2. Cliquer sur le bouton vert "Code"
3. Choisir "Download ZIP"
4. Extraire le ZIP
5. Les fichiers RSS sont à la racine

### Via Git

```bash
# Cloner le dépôt
git clone https://github.com/max0067/maison.git

# Changer vers la branche
cd maison
git checkout claude/rss-reader-app-sJ5Nm

# Les fichiers RSS sont maintenant disponibles
ls rss_*
```

## 🎯 Fichiers essentiels pour faire fonctionner l'app

Voici le minimum nécessaire :

```
install_rss.php              ← Commencer par ici !
rss_login.php
rss_index.php
rss_search.php
rss_admin.php
rss_logout.php
rss_setup.sql
config/rss_config.php
includes/rss_functions.php
assets/css/rss_style.css
```

## ❓ Tu ne vois toujours pas la branche ?

### Vérifications possibles :

1. **Le push a-t-il réussi ?**
   ```bash
   git log --oneline -1
   # Devrait afficher : 801ef2e Feature: Application RSS Reader complète et moderne
   ```

2. **La branche existe-t-elle à distance ?**
   ```bash
   git branch -r | grep rss-reader
   # Devrait afficher : origin/claude/rss-reader-app-sJ5Nm
   ```

3. **Rafraîchir GitHub**
   - Parfois il faut attendre quelques secondes
   - Essaie de rafraîchir la page GitHub (F5)

## 🆘 Besoin d'aide ?

Si tu ne trouves toujours pas :

1. **Dis-moi où tu cherches exactement**
   - Sur GitHub web ?
   - En local sur ta machine ?
   - Sur un serveur ?

2. **Quel message d'erreur vois-tu ?**

3. **As-tu accès au dépôt** https://github.com/max0067/maison ?

## 📁 Structure complète de l'application

```
maison/
├── assets/
│   └── css/
│       └── rss_style.css
├── config/
│   └── rss_config.php
├── includes/
│   └── rss_functions.php
├── rss_index.php
├── rss_login.php
├── rss_logout.php
├── rss_search.php
├── rss_admin.php
├── rss_setup.sql
├── rss_cron.php
├── rss_htaccess.txt
├── install_rss.php
├── RSS_README.md
├── RSS_GUIDE_RAPIDE.md
└── RSS_APPLICATION_COMPLETE.md
```

---

**Tous les fichiers sont présents et committés !** 🎉

Dis-moi exactement où tu cherches et je t'aiderai à y accéder.
