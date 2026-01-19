<?php
/**
 * Configuration pour l'application RSS Reader
 */

// Configuration de la base de données
define('RSS_DB_HOST', 'localhost');
define('RSS_DB_NAME', 'rss_reader');
define('RSS_DB_USER', 'root');
define('RSS_DB_PASS', '');

// Configuration de l'application
define('RSS_APP_NAME', 'RSS Reader');
define('RSS_ITEMS_PER_PAGE', 20);
define('RSS_CACHE_TIME', 3600); // 1 heure en secondes

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
function getRSSDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . RSS_DB_HOST . ";dbname=" . RSS_DB_NAME . ";charset=utf8mb4",
                RSS_DB_USER,
                RSS_DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage() . "<br>Veuillez exécuter install_rss.php pour configurer la base de données.");
        }
    }

    return $pdo;
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['rss_user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['rss_user_role']) && $_SESSION['rss_user_role'] === 'admin';
}

// Rediriger vers la page de login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Rediriger vers la page de login (admin)
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Nettoyer les données
function clean($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>
