# 🚀 Guide de Démarrage Rapide - RSS Reader

## Installation en 3 étapes

### 1️⃣ Configurer la base de données

Accédez à l'installateur :
```
http://votre-domaine/install_rss.php
```

Remplissez les informations :
- **Hôte MySQL** : `localhost` (dans la plupart des cas)
- **Nom de la base** : `rss_reader` (ou votre choix)
- **Utilisateur MySQL** : Votre utilisateur MySQL
- **Mot de passe MySQL** : Votre mot de passe MySQL

Cliquez sur "Installer" et attendez la confirmation.

### 2️⃣ Se connecter

Utilisez les identifiants par défaut :
- **Utilisateur** : `admin`
- **Mot de passe** : `admin123`

### 3️⃣ Configuration initiale

1. **Changez votre mot de passe** (recommandé !)
   - Allez dans l'administration
   - Créez un nouveau compte admin avec un mot de passe sécurisé

2. **Ajoutez vos flux RSS**
   - Cliquez sur "Admin" dans le menu
   - Onglet "Flux RSS"
   - Ajoutez vos flux préférés

3. **Créez vos dossiers**
   - Onglet "Dossiers"
   - Créez des dossiers pour organiser vos flux
   - Personnalisez l'icône et la couleur

4. **Assignez les flux aux dossiers**
   - Utilisez le formulaire en bas de l'onglet "Dossiers"
   - Sélectionnez le dossier et le flux
   - Cliquez sur "Assigner"

## 📱 Utilisation quotidienne

### Consulter vos articles
- Allez sur la page d'accueil
- Parcourez les derniers articles
- Cliquez pour ouvrir dans un nouvel onglet

### Rechercher un article
- Cliquez sur "Recherche" dans le menu
- Tapez vos mots-clés
- Consultez les résultats

### Rafraîchir les flux
- Cliquez sur le bouton 🔄 en haut à droite
- Les nouveaux articles seront récupérés

### Marquer comme favori
- Cliquez sur l'étoile ☆ sur un article
- Elle deviendra ⭐ pour indiquer qu'il est en favori

## 🎯 Astuces

### Flux RSS populaires à ajouter

**Actualités**
- Le Monde : `https://www.lemonde.fr/rss/une.xml`
- BBC News : `https://feeds.bbci.co.uk/news/rss.xml`

**Technologie**
- TechCrunch : `https://techcrunch.com/feed/`
- Numerama : `https://www.numerama.com/feed/`

**Blogs et médias**
- Medium : `https://medium.com/feed/[tag]`
- WordPress : Ajoutez `/feed` à l'URL du site

### Organisation par dossiers

Créez des dossiers thématiques :
- 📰 **Actualités** (flux d'actualités générales)
- 💻 **Tech** (flux technologiques)
- 🎮 **Loisirs** (jeux, cinéma, etc.)
- 📚 **Blogs** (blogs personnels)
- ⭐ **Favoris** (vos flux préférés)

### Automatisation

Pour rafraîchir automatiquement les flux, ajoutez à votre crontab :
```bash
# Rafraîchir les flux toutes les heures
0 * * * * php /chemin/vers/rss_cron.php
```

## ❓ Problèmes fréquents

### Pas d'articles affichés
➡️ Cliquez sur le bouton 🔄 pour rafraîchir les flux

### Flux non ajouté
➡️ Vérifiez que l'URL est correcte et accessible

### Erreur de connexion
➡️ Vérifiez vos identifiants MySQL dans `config/rss_config.php`

### Design cassé
➡️ Vérifiez que le fichier `assets/css/rss_style.css` est accessible

## 🔒 Sécurité

### Avant la mise en production

1. **Changez tous les mots de passe par défaut**
2. **Activez HTTPS** (certificat SSL/TLS)
3. **Limitez l'accès** à `install_rss.php` (supprimez-le après installation)
4. **Sauvegardez régulièrement** votre base de données
5. **Mettez à jour PHP** et MySQL

### Permissions recommandées

```bash
chmod 644 *.php
chmod 755 assets/
chmod 644 assets/css/*.css
chmod 600 config/rss_config.php
```

## 📚 Ressources

- **README complet** : `RSS_README.md`
- **Configuration** : `config/rss_config.php`
- **Styles** : `assets/css/rss_style.css`

## 🎉 C'est tout !

Votre application RSS Reader est maintenant configurée et prête à l'emploi.

Profitez de vos lectures ! 📰

---

**Besoin d'aide ?** Consultez le `RSS_README.md` pour plus de détails.
