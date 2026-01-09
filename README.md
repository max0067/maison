# Système de Gestion de Gîte - La Maison du Soleil

Un système complet de gestion de réservations pour maison d'hôtes / gîte avec backoffice d'administration.

## Fonctionnalités

### Site Public
- Présentation de la maison d'hôtes
- Affichage des chambres disponibles avec photos et prix
- Formulaire de réservation en ligne avec calcul automatique du prix
- Design responsive et élégant

### Backoffice Admin
- **Dashboard** : Vue d'ensemble avec statistiques
- **Gestion des réservations** :
  - Créer, modifier, supprimer des réservations
  - Changer le statut (en attente, confirmée, annulée)
  - Créer des réservations depuis le backoffice
  - Vérification automatique de disponibilité
- **Gestion des chambres** :
  - Ajouter, modifier, supprimer des chambres
  - Upload de photos
  - Activer/désactiver des chambres
  - Gérer l'ordre d'affichage
- **Gestion des contenus** :
  - Modifier tous les textes du site
  - Personnalisation complète sans coder

## Installation

### 1. Configuration de la base de données

Vous avez déjà vos identifiants MySQL :
- **Base de données** : u482325361_gite1
- **Utilisateur** : u482325361_gite1
- **Mot de passe** : Gite1***

Importez le fichier `setup.sql` dans votre base de données :

```bash
mysql -u u482325361_gite1 -p u482325361_gite1 < setup.sql
```

Ou via phpMyAdmin :
1. Connectez-vous à phpMyAdmin
2. Sélectionnez la base de données `u482325361_gite1`
3. Cliquez sur "Importer"
4. Sélectionnez le fichier `setup.sql`
5. Cliquez sur "Exécuter"

### 2. Configuration des permissions

Assurez-vous que le dossier `uploads/` est accessible en écriture :

```bash
chmod 755 uploads/
chmod 755 uploads/chambres/
```

### 3. Connexion au backoffice

Accédez à : `http://votre-domaine.com/admin/`

**Identifiants par défaut** :
- Nom d'utilisateur : `admin`
- Mot de passe : `admin123`

⚠️ **IMPORTANT** : Changez ces identifiants après la première connexion !

## Structure du projet

```
/
├── admin/                  # Backoffice d'administration
│   ├── index.php          # Page de connexion
│   ├── dashboard.php      # Tableau de bord
│   ├── reservations.php   # Gestion des réservations
│   ├── chambres.php       # Gestion des chambres
│   ├── contenus.php       # Gestion des contenus
│   └── logout.php         # Déconnexion
├── assets/
│   ├── css/
│   │   ├── style.css      # Styles du site public
│   │   └── admin.css      # Styles du backoffice
│   └── js/
├── config/
│   └── database.php       # Configuration de la base de données
├── includes/
│   ├── auth.php           # Système d'authentification
│   └── functions.php      # Fonctions utilitaires
├── uploads/
│   └── chambres/          # Photos des chambres
├── index.php              # Page d'accueil du site
├── reserver.php           # Traitement des réservations
└── setup.sql              # Script de création des tables
```

## Utilisation

### Modifier les contenus du site

1. Connectez-vous au backoffice
2. Allez dans "Contenus"
3. Modifiez les textes souhaités
4. Cliquez sur "Enregistrer les modifications"

### Ajouter une chambre

1. Allez dans "Chambres"
2. Cliquez sur "Ajouter une chambre"
3. Remplissez les informations
4. Uploadez une photo
5. Cliquez sur "Créer la chambre"

### Gérer les réservations

1. Allez dans "Réservations"
2. Vous pouvez :
   - Voir toutes les réservations
   - Changer le statut d'une réservation
   - Créer une réservation manuelle
   - Modifier ou supprimer une réservation

### Créer une réservation depuis le backoffice

1. Allez dans "Réservations"
2. Cliquez sur "Nouvelle réservation"
3. Remplissez le formulaire
4. Le système vérifie automatiquement la disponibilité
5. Cliquez sur "Créer la réservation"

## Sécurité

- Les mots de passe sont hachés avec bcrypt
- Protection contre les injections SQL via PDO
- Validation des données côté serveur
- Sessions sécurisées pour l'administration

## Personnalisation

### Changer le mot de passe admin

Connectez-vous à votre base de données et exécutez :

```sql
UPDATE admin_users
SET password = '$2y$10$VotreNouveauHashIci'
WHERE username = 'admin';
```

Pour générer un hash, utilisez ce code PHP :
```php
<?php echo password_hash('VotreNouveauMotDePasse', PASSWORD_DEFAULT); ?>
```

### Ajouter des photos par défaut

Placez vos photos dans le dossier `uploads/chambres/` et mettez à jour les chambres via le backoffice.

## Support

Pour toute question ou problème, contactez le développeur.

## Technologies utilisées

- PHP 7.4+
- MySQL 5.7+
- HTML5 / CSS3
- JavaScript (Vanilla)

## Licence

Propriétaire - Tous droits réservés
