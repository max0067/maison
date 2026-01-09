<?php
/**
 * Script d'installation du système de gestion de gîte
 * Exécutez ce fichier une seule fois pour initialiser la base de données
 * Supprimez ce fichier après l'installation pour des raisons de sécurité
 */

require_once 'config/database.php';

$errors = [];
$success = [];
$tablesExist = false;

// Vérifier si les tables existent déjà
try {
    $db = getDB();
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() > 0) {
        $tablesExist = true;
    }
} catch (PDOException $e) {
    $errors[] = "Erreur de connexion à la base de données : " . $e->getMessage();
}

// Exécuter l'installation si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    try {
        $db = getDB();
        $reinstall = isset($_POST['reinstall']) && $_POST['reinstall'] === 'yes';

        // Si réinstallation, supprimer les tables existantes
        if ($reinstall && $tablesExist) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("DROP TABLE IF EXISTS reservations");
            $db->exec("DROP TABLE IF EXISTS chambres");
            $db->exec("DROP TABLE IF EXISTS contenus");
            $db->exec("DROP TABLE IF EXISTS admin_users");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            $success[] = "Tables existantes supprimées.";
        }

        // Lire le fichier SQL
        $sql = file_get_contents(__DIR__ . '/setup.sql');

        // Diviser en requêtes individuelles (en ignorant les commentaires)
        $lines = explode("\n", $sql);
        $query = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorer les commentaires et les lignes vides
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }

            $query .= $line . "\n";

            // Si la ligne se termine par un point-virgule, exécuter la requête
            if (substr(trim($line), -1) === ';') {
                try {
                    $db->exec($query);
                } catch (PDOException $e) {
                    // Ignorer les erreurs de doublon si pas de réinstallation
                    if (!$reinstall && strpos($e->getMessage(), 'Duplicate') === false) {
                        throw $e;
                    }
                }
                $query = '';
            }
        }

        $success[] = "Installation réussie !";
        $success[] = "Vous pouvez maintenant vous connecter au backoffice avec :";
        $success[] = "Nom d'utilisateur : admin";
        $success[] = "Mot de passe : admin123";
        $success[] = "<strong style='color: red;'>IMPORTANT : Supprimez ce fichier install.php pour des raisons de sécurité !</strong>";

    } catch (PDOException $e) {
        $errors[] = "Erreur lors de l'installation : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Système de gestion de gîte</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .install-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c62828;
        }

        .success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #2e7d32;
        }

        .success p {
            margin-bottom: 10px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        ul {
            margin-left: 20px;
            margin-bottom: 20px;
        }

        li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <h1>🏡 Installation du système de gestion de gîte</h1>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success">
                <?php foreach ($success as $msg): ?>
                    <p><?php echo $msg; ?></p>
                <?php endforeach; ?>
            </div>
            <a href="admin/" class="link">Accéder au backoffice →</a>
        <?php else: ?>
            <?php if ($tablesExist): ?>
                <div class="error">
                    <p><strong>⚠️ Les tables existent déjà dans la base de données.</strong></p>
                    <p>Vous avez deux options :</p>
                </div>
                <div class="info">
                    <p><strong>Option 1 : Réinitialiser le mot de passe admin</strong></p>
                    <p>Si vous voulez juste réinitialiser votre mot de passe, utilisez le script <a href="reset_admin.php" style="color: #2196F3;">reset_admin.php</a></p>
                </div>
                <div class="info">
                    <p><strong>Option 2 : Réinstallation complète (ATTENTION : Toutes les données seront perdues !)</strong></p>
                    <p>Cela va supprimer toutes les tables existantes et recommencer l'installation.</p>
                    <form method="POST" onsubmit="return confirm('⚠️ ATTENTION : Cette action va supprimer TOUTES vos données (réservations, chambres, contenus, admin).\n\nÊtes-vous absolument sûr de vouloir continuer ?');">
                        <input type="hidden" name="reinstall" value="yes">
                        <button type="submit" class="btn" style="background: #dc3545;">Réinstaller (Supprimer toutes les données)</button>
                    </form>
                </div>
                <a href="diagnostic.php" class="link">Voir le diagnostic de la base de données →</a>
            <?php else: ?>
                <div class="info">
                    <p><strong>Ce script va installer :</strong></p>
                    <ul>
                        <li>Les tables nécessaires à la base de données</li>
                        <li>Un compte administrateur par défaut</li>
                        <li>Des chambres d'exemple</li>
                        <li>Les contenus par défaut du site</li>
                    </ul>
                    <p><strong>Configuration actuelle :</strong></p>
                    <ul>
                        <li>Base de données : <?php echo DB_NAME; ?></li>
                        <li>Serveur : <?php echo DB_HOST; ?></li>
                    </ul>
                </div>

                <?php if (empty($errors)): ?>
                    <form method="POST">
                        <button type="submit" class="btn">Installer maintenant</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
