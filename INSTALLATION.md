# Guide d'installation rapide

## Méthode 1 : Installation automatique (Recommandé)

1. **Accédez à l'installateur dans votre navigateur :**
   ```
   http://votre-domaine.com/install.php
   ```

2. **Cliquez sur "Installer maintenant"**
   - Cela va créer toutes les tables
   - Créer un compte admin
   - Insérer les données par défaut

3. **Connectez-vous au backoffice :**
   ```
   URL : http://votre-domaine.com/admin/
   Utilisateur : admin
   Mot de passe : admin123
   ```

4. **⚠️ Important : Supprimez les fichiers d'installation après utilisation**
   ```bash
   rm install.php reset_admin.php generate_password.php
   ```

## Méthode 2 : Installation manuelle via SQL

Si vous préférez installer manuellement :

1. **Importez le fichier SQL :**

   Via ligne de commande :
   ```bash
   mysql -u u482325361_gite1 -p u482325361_gite1 < setup.sql
   ```

   Ou via phpMyAdmin :
   - Connectez-vous à phpMyAdmin
   - Sélectionnez la base `u482325361_gite1`
   - Cliquez sur "Importer"
   - Sélectionnez `setup.sql`
   - Cliquez sur "Exécuter"

2. **Connectez-vous au backoffice**
   ```
   URL : http://votre-domaine.com/admin/
   Utilisateur : admin
   Mot de passe : admin123
   ```

## Problème de connexion ?

Si le mot de passe ne fonctionne pas :

1. **Accédez au script de réinitialisation :**
   ```
   http://votre-domaine.com/reset_admin.php
   ```

2. **Ce script va automatiquement :**
   - Créer ou mettre à jour le compte admin
   - Réinitialiser le mot de passe à `admin123`

3. **Connectez-vous ensuite normalement**

## Configuration des permissions

Assurez-vous que les dossiers sont accessibles en écriture :

```bash
chmod 755 uploads/
chmod 755 uploads/chambres/
```

## Configuration de la base de données

Si vous changez d'hébergement, modifiez le fichier `config/database.php` :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

## Après l'installation

1. ✅ Changez le mot de passe admin
2. ✅ Supprimez les fichiers d'installation
3. ✅ Ajoutez vos photos de chambres
4. ✅ Personnalisez les contenus du site
5. ✅ Configurez SSL/HTTPS pour la production

## Support

Consultez le fichier `README.md` pour la documentation complète.
