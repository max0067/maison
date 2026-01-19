<?php
require_once 'config/rss_config.php';
require_once 'includes/rss_functions.php';

requireAdmin();

$user_id = $_SESSION['rss_user_id'];
$username = $_SESSION['rss_username'];
$db = getRSSDB();

$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_user':
            $new_username = $_POST['username'] ?? '';
            $new_password = $_POST['password'] ?? '';
            $new_email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? 'user';

            if (!empty($new_username) && !empty($new_password) && !empty($new_email)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                try {
                    $stmt = $db->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$new_username, $hashed_password, $new_email, $role]);
                    $success = "Utilisateur créé avec succès";
                } catch (PDOException $e) {
                    $error = "Erreur: " . $e->getMessage();
                }
            } else {
                $error = "Tous les champs sont requis";
            }
            break;

        case 'toggle_user':
            $toggle_user_id = (int)$_POST['user_id'];
            $stmt = $db->prepare("UPDATE users SET active = NOT active WHERE id = ?");
            $stmt->execute([$toggle_user_id]);
            $success = "Statut utilisateur modifié";
            break;

        case 'delete_user':
            $delete_user_id = (int)$_POST['user_id'];
            if ($delete_user_id != $user_id) {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$delete_user_id]);
                $success = "Utilisateur supprimé";
            } else {
                $error = "Vous ne pouvez pas supprimer votre propre compte";
            }
            break;

        case 'add_feed':
            $feed_title = $_POST['feed_title'] ?? '';
            $feed_url = $_POST['feed_url'] ?? '';
            $feed_description = $_POST['feed_description'] ?? '';

            if (!empty($feed_title) && !empty($feed_url)) {
                try {
                    $stmt = $db->prepare("INSERT INTO rss_feeds (title, url, description) VALUES (?, ?, ?)");
                    $stmt->execute([$feed_title, $feed_url, $feed_description]);
                    $success = "Flux RSS ajouté avec succès";

                    // Essayer de rafraîchir immédiatement
                    $feed_id = $db->lastInsertId();
                    $result = fetchRSSFeed($feed_url);
                    if (isset($result['success']) && $result['success']) {
                        saveFeedItems($feed_id, $result['items']);
                    }
                } catch (PDOException $e) {
                    $error = "Erreur: " . $e->getMessage();
                }
            } else {
                $error = "Le titre et l'URL sont requis";
            }
            break;

        case 'delete_feed':
            $delete_feed_id = (int)$_POST['feed_id'];
            $stmt = $db->prepare("DELETE FROM rss_feeds WHERE id = ?");
            $stmt->execute([$delete_feed_id]);
            $success = "Flux RSS supprimé";
            break;

        case 'add_folder':
            $folder_name = $_POST['folder_name'] ?? '';
            $folder_icon = $_POST['folder_icon'] ?? '📁';
            $folder_color = $_POST['folder_color'] ?? '#3498db';
            $folder_user_id = (int)$_POST['folder_user_id'];

            if (!empty($folder_name)) {
                $stmt = $db->prepare("INSERT INTO folders (user_id, name, icon, color) VALUES (?, ?, ?, ?)");
                $stmt->execute([$folder_user_id, $folder_name, $folder_icon, $folder_color]);
                $success = "Dossier créé avec succès";
            } else {
                $error = "Le nom du dossier est requis";
            }
            break;

        case 'delete_folder':
            $delete_folder_id = (int)$_POST['folder_id'];
            $stmt = $db->prepare("DELETE FROM folders WHERE id = ?");
            $stmt->execute([$delete_folder_id]);
            $success = "Dossier supprimé";
            break;

        case 'assign_feed':
            $assign_folder_id = (int)$_POST['assign_folder_id'];
            $assign_feed_id = (int)$_POST['assign_feed_id'];

            try {
                $stmt = $db->prepare("INSERT IGNORE INTO folder_feeds (folder_id, feed_id) VALUES (?, ?)");
                $stmt->execute([$assign_folder_id, $assign_feed_id]);
                $success = "Flux assigné au dossier";
            } catch (PDOException $e) {
                $error = "Erreur: " . $e->getMessage();
            }
            break;
    }
}

// Récupérer les données pour l'affichage
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$feeds = $db->query("SELECT * FROM rss_feeds ORDER BY title")->fetchAll();
$all_folders = $db->query("
    SELECT f.*, u.username
    FROM folders f
    JOIN users u ON f.user_id = u.id
    ORDER BY u.username, f.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?= RSS_APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/rss_style.css">
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <h1>📰 <?= RSS_APP_NAME ?></h1>
            </div>
            <div class="header-center">
                <nav class="main-nav">
                    <a href="rss_index.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        Accueil
                    </a>
                    <a href="rss_search.php" class="nav-link">
                        <span class="nav-icon">🔍</span>
                        Recherche
                    </a>
                    <a href="rss_admin.php" class="nav-link active">
                        <span class="nav-icon">⚙️</span>
                        Admin
                    </a>
                </nav>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <span class="user-name"><?= clean($username) ?></span>
                    <a href="rss_logout.php" class="btn btn-secondary">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container single-column">
        <main class="content content-wide">
            <h2>Administration</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= clean($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= clean($error) ?></div>
            <?php endif; ?>

            <!-- Onglets -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('users')">👥 Utilisateurs</button>
                <button class="tab-btn" onclick="showTab('feeds')">📡 Flux RSS</button>
                <button class="tab-btn" onclick="showTab('folders')">📁 Dossiers</button>
            </div>

            <!-- Gestion des utilisateurs -->
            <div id="users-tab" class="tab-content active">
                <div class="admin-section">
                    <h3>Ajouter un utilisateur</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="add_user">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom d'utilisateur</label>
                                <input type="text" name="username" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>Mot de passe</label>
                                <input type="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label>Rôle</label>
                                <select name="role">
                                    <option value="user">Utilisateur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>

                <div class="admin-section">
                    <h3>Liste des utilisateurs</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Actif</th>
                                <th>Créé le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td><?= clean($u['username']) ?></td>
                                    <td><?= clean($u['email']) ?></td>
                                    <td><span class="badge badge-<?= $u['role'] ?>"><?= clean($u['role']) ?></span></td>
                                    <td>
                                        <span class="status-badge <?= $u['active'] ? 'active' : 'inactive' ?>">
                                            <?= $u['active'] ? 'Oui' : 'Non' ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                                    <td class="actions">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-sm">
                                                <?= $u['active'] ? 'Désactiver' : 'Activer' ?>
                                            </button>
                                        </form>
                                        <?php if ($u['id'] != $user_id): ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gestion des flux RSS -->
            <div id="feeds-tab" class="tab-content">
                <div class="admin-section">
                    <h3>Ajouter un flux RSS</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="add_feed">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Titre du flux</label>
                                <input type="text" name="feed_title" required>
                            </div>
                            <div class="form-group">
                                <label>URL du flux RSS</label>
                                <input type="url" name="feed_url" required placeholder="https://...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="feed_description" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>

                <div class="admin-section">
                    <h3>Liste des flux RSS</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titre</th>
                                <th>URL</th>
                                <th>Dernière màj</th>
                                <th>Actif</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feeds as $feed): ?>
                                <tr>
                                    <td><?= $feed['id'] ?></td>
                                    <td><?= clean($feed['title']) ?></td>
                                    <td><small><?= clean(substr($feed['url'], 0, 50)) ?>...</small></td>
                                    <td><?= $feed['last_fetch'] ? timeAgo($feed['last_fetch']) : 'Jamais' ?></td>
                                    <td>
                                        <span class="status-badge <?= $feed['active'] ? 'active' : 'inactive' ?>">
                                            <?= $feed['active'] ? 'Oui' : 'Non' ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                                            <input type="hidden" name="action" value="delete_feed">
                                            <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gestion des dossiers -->
            <div id="folders-tab" class="tab-content">
                <div class="admin-section">
                    <h3>Créer un dossier</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="add_folder">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom du dossier</label>
                                <input type="text" name="folder_name" required>
                            </div>
                            <div class="form-group">
                                <label>Utilisateur</label>
                                <select name="folder_user_id" required>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= clean($u['username']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Icône</label>
                                <input type="text" name="folder_icon" value="📁" maxlength="2">
                            </div>
                            <div class="form-group">
                                <label>Couleur</label>
                                <input type="color" name="folder_color" value="#3498db">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Créer</button>
                    </form>
                </div>

                <div class="admin-section">
                    <h3>Liste des dossiers</h3>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Nom</th>
                                <th>Icône</th>
                                <th>Couleur</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_folders as $folder): ?>
                                <tr>
                                    <td><?= $folder['id'] ?></td>
                                    <td><?= clean($folder['username']) ?></td>
                                    <td><?= clean($folder['name']) ?></td>
                                    <td><?= clean($folder['icon']) ?></td>
                                    <td>
                                        <span class="color-preview" style="background: <?= clean($folder['color']) ?>"></span>
                                    </td>
                                    <td class="actions">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                                            <input type="hidden" name="action" value="delete_folder">
                                            <input type="hidden" name="folder_id" value="<?= $folder['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="admin-section">
                    <h3>Assigner un flux à un dossier</h3>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="assign_feed">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Dossier</label>
                                <select name="assign_folder_id" required>
                                    <?php foreach ($all_folders as $folder): ?>
                                        <option value="<?= $folder['id'] ?>">
                                            <?= clean($folder['username']) ?> - <?= clean($folder['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Flux RSS</label>
                                <select name="assign_feed_id" required>
                                    <?php foreach ($feeds as $feed): ?>
                                        <option value="<?= $feed['id'] ?>"><?= clean($feed['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Assigner</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showTab(tabName) {
            // Cacher tous les contenus
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Désactiver tous les boutons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Afficher le contenu sélectionné
            document.getElementById(tabName + '-tab').classList.add('active');

            // Activer le bouton sélectionné
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
