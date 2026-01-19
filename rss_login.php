<?php
require_once 'config/rss_config.php';

$error = '';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    header('Location: rss_index.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $db = getRSSDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['rss_user_id'] = $user['id'];
            $_SESSION['rss_username'] = $user['username'];
            $_SESSION['rss_user_role'] = $user['role'];

            header('Location: rss_index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= RSS_APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/rss_style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>📰 <?= RSS_APP_NAME ?></h1>
                <p>Connectez-vous pour accéder à vos flux</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= clean($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autofocus
                        value="<?= clean($_POST['username'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Se connecter
                </button>
            </form>

            <div class="login-footer">
                <p><small>Compte par défaut : admin / admin123</small></p>
            </div>
        </div>
    </div>
</body>
</html>
