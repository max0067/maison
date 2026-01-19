# 📰 RSS Reader - Application de Lecture de Flux RSS

Application simple et moderne pour lire et organiser vos flux RSS préférés.

## ✨ Fonctionnalités

### Pour tous les utilisateurs
- 🏠 **Accueil** : Visualisation des derniers articles de tous vos flux
- 🔍 **Recherche** : Recherche dans les articles (titre, description, contenu)
- 📁 **Dossiers** : Organisation personnalisée de vos flux par dossiers
- ⭐ **Favoris** : Marquez vos articles préférés
- ✅ **Lectures** : Marquez les articles comme lus automatiquement
- 🔄 **Rafraîchissement** : Mise à jour automatique des flux

### Pour les administrateurs
- 👥 **Gestion des utilisateurs** : Créer, activer/désactiver, supprimer
- 📡 **Gestion des flux RSS** : Ajouter et gérer les flux
- 📁 **Gestion des dossiers** : Créer et organiser les dossiers par utilisateur
- 🔗 **Attribution** : Assigner des flux aux dossiers

## 🚀 Installation

### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx, etc.)
- Extension PHP : PDO, PDO_MySQL, SimpleXML

### Étapes d'installation

1. **Télécharger les fichiers**
   ```bash
   git clone [votre-repo]
   cd rss-reader
   ```

2. **Configurer le serveur web**
   - Pointez le répertoire racine vers le dossier de l'application
   - Assurez-vous que PHP est configuré correctement

3. **Lancer l'installation**
   - Ouvrez votre navigateur et accédez à : `http://votre-domaine/install_rss.php`
   - Suivez les instructions à l'écran
   - Configurez les paramètres de la base de données

4. **Se connecter**
   - Utilisateur par défaut : `admin`
   - Mot de passe par défaut : `admin123`
   - **Important** : Changez ce mot de passe immédiatement !

## 📂 Structure des fichiers

```
rss-reader/
├── assets/
│   └── css/
│       └── rss_style.css          # Styles modernes
├── config/
│   └── rss_config.php             # Configuration (généré à l'installation)
├── includes/
│   └── rss_functions.php          # Fonctions communes
├── rss_index.php                  # Page d'accueil
├── rss_login.php                  # Page de connexion
├── rss_logout.php                 # Déconnexion
├── rss_search.php                 # Page de recherche
├── rss_admin.php                  # Administration
├── rss_setup.sql                  # Script SQL
├── install_rss.php                # Installation
└── RSS_README.md                  # Ce fichier
```

## 🎯 Utilisation

### Page d'accueil
- Consultez les derniers articles de tous vos flux
- Cliquez sur un article pour l'ouvrir dans un nouvel onglet
- Les articles sont automatiquement marqués comme lus
- Utilisez le bouton ⭐ pour ajouter aux favoris
- Rafraîchissez les flux avec le bouton 🔄

### Recherche
- Tapez des mots-clés dans la barre de recherche
- La recherche s'effectue dans le titre, la description et le contenu
- Les résultats sont triés par date de publication

### Administration (Réservé aux administrateurs)

#### Gestion des utilisateurs
1. Accédez à l'onglet "Utilisateurs"
2. Remplissez le formulaire pour ajouter un utilisateur
3. Choisissez le rôle : `user` (utilisateur) ou `admin` (administrateur)
4. Activez/désactivez ou supprimez les utilisateurs existants

#### Gestion des flux RSS
1. Accédez à l'onglet "Flux RSS"
2. Ajoutez un nouveau flux avec son URL
3. Le flux sera automatiquement rafraîchi après ajout
4. Supprimez les flux non désirés

#### Gestion des dossiers
1. Accédez à l'onglet "Dossiers"
2. Créez un dossier pour un utilisateur
3. Personnalisez l'icône (emoji) et la couleur
4. Assignez des flux RSS aux dossiers

## 🛠️ Configuration avancée

### Modifier les paramètres de l'application
Éditez le fichier `config/rss_config.php` :

```php
define('RSS_APP_NAME', 'Mon RSS Reader');      // Nom de l'application
define('RSS_ITEMS_PER_PAGE', 20);              // Articles par page
define('RSS_CACHE_TIME', 3600);                // Cache en secondes (1h)
```

### Personnaliser le design
Éditez le fichier `assets/css/rss_style.css` pour modifier :
- Les couleurs (variables CSS en haut du fichier)
- Les polices
- Les espacements
- Les animations

### Ajouter des flux RSS populaires

Voici quelques exemples de flux RSS :

**Actualités françaises**
- Le Monde : `https://www.lemonde.fr/rss/une.xml`
- Le Figaro : `https://www.lefigaro.fr/rss/figaro_actualites.xml`
- Libération : `https://www.liberation.fr/arc/outboundfeeds/rss/`

**Technologie**
- TechCrunch : `https://techcrunch.com/feed/`
- Numerama : `https://www.numerama.com/feed/`
- Journal du Geek : `https://www.journaldugeek.com/feed/`

**Actualités internationales**
- BBC News : `https://feeds.bbci.co.uk/news/rss.xml`
- Reuters : `https://www.reutersagency.com/feed/`

## 🔒 Sécurité

### Recommandations
1. **Changez le mot de passe admin** immédiatement après l'installation
2. **Utilisez HTTPS** en production
3. **Sauvegardez régulièrement** votre base de données
4. **Mettez à jour PHP** et MySQL régulièrement
5. **Limitez les permissions** sur les fichiers de configuration

### Gestion des mots de passe
Les mots de passe sont hashés avec `password_hash()` (bcrypt) pour une sécurité optimale.

## 📱 Responsive

L'application est entièrement responsive et s'adapte à tous les écrans :
- 💻 Desktop
- 📱 Tablettes
- 📱 Smartphones

## 🐛 Résolution des problèmes

### L'installation échoue
- Vérifiez les identifiants MySQL
- Assurez-vous que l'utilisateur MySQL a les droits CREATE DATABASE
- Vérifiez que PHP PDO est installé : `php -m | grep pdo`

### Les flux ne se rafraîchissent pas
- Vérifiez que `allow_url_fopen` est activé dans php.ini
- Vérifiez que l'URL du flux est correcte
- Certains flux peuvent être temporairement indisponibles

### Erreur 500
- Activez l'affichage des erreurs dans php.ini
- Vérifiez les logs Apache/Nginx
- Vérifiez les permissions sur les dossiers

### Les articles ne s'affichent pas
- Cliquez sur le bouton "Rafraîchir les flux"
- Vérifiez que les flux sont actifs dans l'administration
- Attendez quelques minutes pour le premier rafraîchissement

## 🎨 Personnalisation

### Changer les couleurs
Dans `assets/css/rss_style.css`, modifiez les variables CSS :

```css
:root {
    --primary-color: #2c3e50;      /* Couleur principale */
    --secondary-color: #3498db;    /* Couleur secondaire */
    --success-color: #27ae60;      /* Couleur de succès */
    --danger-color: #e74c3c;       /* Couleur de danger */
}
```

### Ajouter des fonctionnalités
L'application est conçue pour être simple mais extensible. Vous pouvez ajouter :
- Export des articles en PDF
- Partage sur les réseaux sociaux
- Notifications pour les nouveaux articles
- Filtres avancés

## 📞 Support

Pour toute question ou problème :
1. Consultez ce README
2. Vérifiez les logs d'erreur
3. Consultez la documentation PHP et MySQL

## 📄 Licence

Cette application est libre d'utilisation pour vos projets personnels et professionnels.

## 🙏 Remerciements

Merci d'utiliser RSS Reader ! N'hésitez pas à personnaliser l'application selon vos besoins.

---

**Version** : 1.0.0
**Dernière mise à jour** : 2026
