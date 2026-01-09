# 🚀 Démarrage Rapide - Résolution des problèmes

## Vous avez un problème de connexion ? Suivez ces étapes :

### Étape 1️⃣ : Diagnostic

Accédez à cette page pour voir l'état de votre base de données :
```
http://votre-domaine.com/diagnostic.php
```

Cette page vous montrera :
- ✓ Si la connexion à la base de données fonctionne
- ✓ Quelles tables existent
- ✓ La structure de chaque table
- ✓ Si un utilisateur admin existe

### Étape 2️⃣ : Choisir la solution

#### Solution A : Les tables n'existent pas encore
➡️ Allez sur `install.php` et cliquez sur "Installer maintenant"

#### Solution B : Les tables existent mais le mot de passe ne fonctionne pas
➡️ Allez sur `reset_admin.php` pour réinitialiser le mot de passe

#### Solution C : Les tables ont une structure incorrecte (colonne 'password' manquante)
➡️ Allez sur `install.php` et choisissez "Réinstaller (Supprimer toutes les données)"
⚠️ ATTENTION : Cela supprimera toutes vos données !

### Étape 3️⃣ : Connexion

Une fois l'installation terminée :

```
URL : http://votre-domaine.com/admin/
Utilisateur : admin
Mot de passe : admin123
```

### Étape 4️⃣ : Sécurité

Après la première connexion :

1. **Changez le mot de passe admin** dans le backoffice
2. **Supprimez les fichiers d'installation :**
   ```bash
   rm install.php reset_admin.php diagnostic.php generate_password.php
   ```

## 📋 Résumé des fichiers utiles

| Fichier | Usage |
|---------|-------|
| `diagnostic.php` | Voir l'état de la base de données |
| `install.php` | Installer ou réinstaller les tables |
| `reset_admin.php` | Réinitialiser le mot de passe admin |
| `admin/` | Backoffice d'administration |

## ❓ Questions fréquentes

### Q: J'ai "Column not found: 1054 Unknown column 'password'"
**R:** Vos tables ont une structure incorrecte. Utilisez `diagnostic.php` pour vérifier, puis `install.php` pour réinstaller.

### Q: J'ai "Table 'admin_users' doesn't exist"
**R:** Les tables n'ont pas été créées. Allez sur `install.php` pour installer.

### Q: Le mot de passe admin ne fonctionne pas
**R:** Utilisez `reset_admin.php` pour le réinitialiser à "admin123"

### Q: J'ai des données que je ne veux pas perdre
**R:** Faites une sauvegarde de votre base de données avant de réinstaller :
```bash
mysqldump -u u482325361_gite1 -p u482325361_gite1 > backup.sql
```

## 🆘 Support rapide

1. Vérifiez que `config/database.php` existe et contient les bons identifiants
2. Lancez `diagnostic.php` pour identifier le problème
3. Suivez les recommandations affichées

---

**Note :** Tous les fichiers nécessaires ont été mis à jour et sont disponibles dans votre dépôt Git.
