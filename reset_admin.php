<?php
/**
 * Script de réinitialisation du mot de passe admin
 * Si vous ne pouvez pas vous connecter, exécutez ce script
 * Il réinitialisera le mot de passe admin à "admin123"
 */

require_once 'config/database.php';

try {
    $db = getDB();

    // Vérifier si la table admin_users existe
    $stmt = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("La table 'admin_users' n'existe pas. Veuillez d'abord exécuter l'installation.");
    }

    // Vérifier la structure de la table
    $stmt = $db->query("DESCRIBE admin_users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('password', $columns)) {
        throw new Exception("La structure de la table 'admin_users' est incorrecte (colonne 'password' manquante). Veuillez réinstaller la base de données.");
    }

    // Nouveau mot de passe : admin123
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Vérifier si l'utilisateur admin existe
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        // Mettre à jour le mot de passe
        $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
        $stmt->execute([$hash]);
        $success = true;
        $message = "Mot de passe admin réinitialisé avec succès !";
    } else {
        // Créer l'utilisateur admin
        $stmt = $db->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'admin@maison-soleil.com']);
        $success = true;
        $message = "Utilisateur admin créé avec succès !";
    }

} catch (Exception $e) {
    $success = false;
    $message = "Erreur : " . $e->getMessage();

    if (strpos($e->getMessage(), "n'existe pas") !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
        $message .= "<br><br><strong>Solution :</strong> Veuillez d'abord exécuter <a href='install.php' style='color: #2196F3;'>install.php</a> pour créer les tables.";
    } elseif (strpos($e->getMessage(), "incorrecte") !== false) {
        $message .= "<br><br><strong>Solution :</strong> Allez sur <a href='install.php' style='color: #2196F3;'>install.php</a> et choisissez la réinstallation complète.";
    }
} catch (PDOException $e) {
    $success = false;
    $message = "Erreur de base de données : " . $e->getMessage();

    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $message .= "<br><br><strong>Solution :</strong> Veuillez d'abord exécuter <a href='install.php' style='color: #2196F3;'>install.php</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe admin</title>
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

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 20px;
        }

        .success {
            background: #28a745;
            color: white;
        }

        .error {
            background: #dc3545;
            color: white;
        }

        .message {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #f44336;
        }

        .credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .credentials p {
            margin: 10px 0;
            font-family: monospace;
            font-size: 14px;
        }

        .credentials strong {
            color: #667eea;
        }

        .link {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .link:hover {
            background: #5568d3;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="icon success">✓</div>
            <h1>Réinitialisation réussie</h1>
            <div class="message success-message">
                <?php echo $message; ?>
            </div>

            <div class="credentials">
                <h3 style="margin-bottom: 15px;">Identifiants de connexion :</h3>
                <p><strong>URL :</strong> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/admin/</p>
                <p><strong>Nom d'utilisateur :</strong> admin</p>
                <p><strong>Mot de passe :</strong> admin123</p>
            </div>

            <a href="admin/" class="link">Accéder au backoffice</a>

            <div class="warning">
                ⚠️ Pensez à changer votre mot de passe après la connexion !<br>
                Supprimez ce fichier <strong>reset_admin.php</strong> pour des raisons de sécurité.
            </div>
        <?php else: ?>
            <div class="icon error">✗</div>
            <h1>Erreur</h1>
            <div class="message error-message">
                <?php echo $message; ?>
            </div>
            <a href="install.php" class="link">Aller à l'installation</a>
        <?php endif; ?>
    </div>
</body>
</html>
