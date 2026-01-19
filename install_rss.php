<?php
/**
 * Script d'installation pour l'application RSS Reader
 * À exécuter une seule fois pour créer la base de données
 */

$error = '';
$success = '';
$step = 1;

// Configuration de la base de données
$db_config = [
    'host' => 'localhost',
    'name' => 'rss_reader',
    'user' => 'root',
    'pass' => ''
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == '1') {
        // Étape 1 : Configuration de la base de données
        $db_config['host'] = $_POST['db_host'] ?? 'localhost';
        $db_config['name'] = $_POST['db_name'] ?? 'rss_reader';
        $db_config['user'] = $_POST['db_user'] ?? 'root';
        $db_config['pass'] = $_POST['db_pass'] ?? '';

        // Tester la connexion
        try {
            $pdo = new PDO(
                "mysql:host={$db_config['host']};charset=utf8mb4",
                $db_config['user'],
                $db_config['pass']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Créer la base de données si elle n'existe pas
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$db_config['name']}`");

            // Lire et exécuter le script SQL
            $sql = file_get_contents(__DIR__ . '/rss_setup.sql');
            $pdo->exec($sql);

            // Sauvegarder la configuration
            $config_content = "<?php
/**
 * Configuration pour l'application RSS Reader
 */

// Configuration de la base de données
define('RSS_DB_HOST', '{$db_config['host']}');
define('RSS_DB_NAME', '{$db_config['name']}');
define('RSS_DB_USER', '{$db_config['user']}');
define('RSS_DB_PASS', '{$db_config['pass']}');

// Configuration de l'application
define('RSS_APP_NAME', 'RSS Reader');
define('RSS_ITEMS_PER_PAGE', 20);
define('RSS_CACHE_TIME', 3600);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
function getRSSDB() {
    static \$pdo = null;

    if (\$pdo === null) {
        try {
            \$pdo = new PDO(
                \"mysql:host=\" . RSS_DB_HOST . \";dbname=\" . RSS_DB_NAME . \";charset=utf8mb4\",
                RSS_DB_USER,
                RSS_DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException \$e) {
            die(\"Erreur de connexion à la base de données : \" . \$e->getMessage());
        }
    }

    return \$pdo;
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset(\$_SESSION['rss_user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset(\$_SESSION['rss_user_role']) && \$_SESSION['rss_user_role'] === 'admin';
}

// Rediriger vers la page de login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: rss_login.php');
        exit;
    }
}

// Rediriger vers la page de login (admin)
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: rss_index.php');
        exit;
    }
}

// Nettoyer les données
function clean(\$data) {
    return htmlspecialchars(\$data, ENT_QUOTES, 'UTF-8');
}
?>";

            file_put_contents(__DIR__ . '/config/rss_config.php', $config_content);

            $success = "Installation terminée avec succès !";
            $step = 2;

        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - RSS Reader</title>
    <link rel="stylesheet" href="assets/css/rss_style.css">
</head>
<body class="login-page">
    <div class="login-container" style="max-width: 600px;">
        <div class="login-box">
            <div class="login-header">
                <h1>📰 Installation RSS Reader</h1>
                <p>Configuration de votre application de lecture RSS</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <form method="POST">
                    <input type="hidden" name="step" value="1">

                    <h3 style="margin-bottom: 1.5rem;">Configuration de la base de données</h3>

                    <div class="form-group">
                        <label for="db_host">Hôte MySQL</label>
                        <input
                            type="text"
                            id="db_host"
                            name="db_host"
                            value="<?= htmlspecialchars($db_config['host']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="db_name">Nom de la base de données</label>
                        <input
                            type="text"
                            id="db_name"
                            name="db_name"
                            value="<?= htmlspecialchars($db_config['name']) ?>"
                            required
                        >
                        <small style="color: #7f8c8d;">La base sera créée si elle n'existe pas</small>
                    </div>

                    <div class="form-group">
                        <label for="db_user">Utilisateur MySQL</label>
                        <input
                            type="text"
                            id="db_user"
                            name="db_user"
                            value="<?= htmlspecialchars($db_config['user']) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="db_pass">Mot de passe MySQL</label>
                        <input
                            type="password"
                            id="db_pass"
                            name="db_pass"
                            value="<?= htmlspecialchars($db_config['pass']) ?>"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Installer
                    </button>
                </form>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #dfe6e9;">
                    <h4 style="margin-bottom: 1rem;">À propos de l'installation</h4>
                    <ul style="color: #7f8c8d; font-size: 0.9rem;">
                        <li>Une base de données sera créée automatiquement</li>
                        <li>Un compte admin sera créé (admin / admin123)</li>
                        <li>Quelques flux RSS d'exemple seront ajoutés</li>
                        <li>Vous pourrez tout personnaliser ensuite</li>
                    </ul>
                </div>

            <?php else: ?>
                <div style="text-align: center; padding: 2rem 0;">
                    <h3 style="color: #27ae60; margin-bottom: 1rem;">✅ Installation réussie !</h3>
                    <p style="margin-bottom: 2rem;">Votre application RSS Reader est prête à l'emploi.</p>

                    <div style="background: #f5f6fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                        <h4 style="margin-bottom: 1rem;">Informations de connexion</h4>
                        <p><strong>Nom d'utilisateur :</strong> admin</p>
                        <p><strong>Mot de passe :</strong> admin123</p>
                        <p style="color: #e74c3c; margin-top: 1rem; font-size: 0.9rem;">
                            ⚠️ Pensez à changer ce mot de passe dans l'administration !
                        </p>
                    </div>

                    <a href="rss_login.php" class="btn btn-primary btn-block">
                        Accéder à l'application
                    </a>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #dfe6e9;">
                        <h4 style="margin-bottom: 1rem;">Prochaines étapes</h4>
                        <ol style="text-align: left; color: #7f8c8d; font-size: 0.9rem;">
                            <li>Connectez-vous avec les identifiants ci-dessus</li>
                            <li>Accédez à la section Administration</li>
                            <li>Ajoutez vos flux RSS préférés</li>
                            <li>Créez des dossiers pour organiser vos flux</li>
                            <li>Ajoutez des utilisateurs si nécessaire</li>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
