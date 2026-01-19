<?php
require_once 'config/rss_config.php';
require_once 'includes/rss_functions.php';

requireLogin();

$user_id = $_SESSION['rss_user_id'];
$username = $_SESSION['rss_username'];

// Traitement des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    switch ($_POST['action']) {
        case 'mark_read':
            $item_id = (int)$_POST['item_id'];
            markAsRead($user_id, $item_id);
            echo json_encode(['success' => true]);
            exit;

        case 'toggle_favorite':
            $item_id = (int)$_POST['item_id'];
            $is_favorite = toggleFavorite($user_id, $item_id);
            echo json_encode(['success' => true, 'is_favorite' => $is_favorite]);
            exit;

        case 'refresh_feeds':
            $results = refreshAllFeeds();
            echo json_encode(['success' => true, 'results' => $results]);
            exit;
    }
}

// Récupérer les dossiers et flux de l'utilisateur
$folders = getUserFolders($user_id);

// Récupérer les articles récents
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * RSS_ITEMS_PER_PAGE;
$items = getRecentItems($user_id, RSS_ITEMS_PER_PAGE, $offset);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= RSS_APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/rss_style.css">
</head>
<body>
    <!-- En-tête -->
    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <h1>📰 <?= RSS_APP_NAME ?></h1>
            </div>
            <div class="header-center">
                <nav class="main-nav">
                    <a href="rss_index.php" class="nav-link active">
                        <span class="nav-icon">🏠</span>
                        Accueil
                    </a>
                    <a href="rss_search.php" class="nav-link">
                        <span class="nav-icon">🔍</span>
                        Recherche
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="rss_admin.php" class="nav-link">
                            <span class="nav-icon">⚙️</span>
                            Admin
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            <div class="header-right">
                <button onclick="refreshFeeds()" class="btn btn-icon" title="Rafraîchir les flux">
                    <span id="refresh-icon">🔄</span>
                </button>
                <div class="user-menu">
                    <span class="user-name"><?= clean($username) ?></span>
                    <a href="rss_logout.php" class="btn btn-secondary">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Sidebar avec les dossiers -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Mes Dossiers</h2>
            </div>

            <div class="folders-list">
                <?php if (empty($folders)): ?>
                    <div class="empty-state">
                        <p>Aucun dossier créé</p>
                        <?php if (isAdmin()): ?>
                            <a href="rss_admin.php" class="btn btn-sm">Créer un dossier</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($folders as $folder): ?>
                        <div class="folder-item" style="border-left: 3px solid <?= clean($folder['color']) ?>">
                            <div class="folder-header">
                                <span class="folder-icon"><?= clean($folder['icon']) ?></span>
                                <span class="folder-name"><?= clean($folder['name']) ?></span>
                            </div>
                            <div class="folder-feeds">
                                <?php
                                $feeds = getFolderFeeds($folder['id']);
                                if (!empty($feeds)):
                                    foreach ($feeds as $feed):
                                ?>
                                    <a href="rss_index.php?feed=<?= $feed['id'] ?>" class="feed-link">
                                        <?= clean($feed['title']) ?>
                                    </a>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="content">
            <div class="content-header">
                <h2>Derniers articles</h2>
                <p class="content-subtitle"><?= count($items) ?> articles</p>
            </div>

            <div class="items-list">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <p>Aucun article disponible</p>
                        <p><small>Les flux RSS seront automatiquement rafraîchis</small></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <article class="item-card <?= $item['is_read'] ? 'read' : '' ?>" data-item-id="<?= $item['id'] ?>">
                            <div class="item-header">
                                <div class="item-source">
                                    <span class="source-icon"><?= $item['feed_icon'] ?: '📄' ?></span>
                                    <span class="source-name"><?= clean($item['feed_title']) ?></span>
                                </div>
                                <div class="item-meta">
                                    <span class="item-date"><?= timeAgo($item['pub_date']) ?></span>
                                </div>
                            </div>

                            <div class="item-content">
                                <?php if ($item['image_url']): ?>
                                    <div class="item-image">
                                        <img src="<?= clean($item['image_url']) ?>" alt="" loading="lazy">
                                    </div>
                                <?php endif; ?>

                                <h3 class="item-title">
                                    <a href="<?= clean($item['link']) ?>" target="_blank" onclick="markAsRead(<?= $item['id'] ?>)">
                                        <?= clean($item['title']) ?>
                                    </a>
                                </h3>

                                <?php if ($item['description']): ?>
                                    <p class="item-description">
                                        <?= clean(substr(strip_tags($item['description']), 0, 200)) ?>...
                                    </p>
                                <?php endif; ?>

                                <?php if ($item['author']): ?>
                                    <p class="item-author">Par <?= clean($item['author']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="item-actions">
                                <button
                                    onclick="toggleFavorite(<?= $item['id'] ?>, this)"
                                    class="btn-action <?= $item['is_favorite'] ? 'active' : '' ?>"
                                    title="Favori"
                                >
                                    <span class="action-icon"><?= $item['is_favorite'] ? '⭐' : '☆' ?></span>
                                </button>
                                <a href="<?= clean($item['link']) ?>" target="_blank" class="btn-action" title="Ouvrir">
                                    <span class="action-icon">🔗</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if (count($items) >= RSS_ITEMS_PER_PAGE): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary">← Précédent</a>
                    <?php endif; ?>
                    <span class="page-info">Page <?= $page ?></span>
                    <?php if (count($items) === RSS_ITEMS_PER_PAGE): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary">Suivant →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Marquer un article comme lu
        function markAsRead(itemId) {
            fetch('rss_index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_read&item_id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('[data-item-id="' + itemId + '"]').classList.add('read');
                }
            });
        }

        // Toggle favori
        function toggleFavorite(itemId, button) {
            fetch('rss_index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=toggle_favorite&item_id=' + itemId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('active');
                    button.querySelector('.action-icon').textContent = data.is_favorite ? '⭐' : '☆';
                }
            });
        }

        // Rafraîchir les flux
        function refreshFeeds() {
            const icon = document.getElementById('refresh-icon');
            icon.style.animation = 'spin 1s linear infinite';

            fetch('rss_index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=refresh_feeds'
            })
            .then(response => response.json())
            .then(data => {
                icon.style.animation = '';
                if (data.success) {
                    alert('Flux rafraîchis avec succès !');
                    location.reload();
                }
            })
            .catch(() => {
                icon.style.animation = '';
                alert('Erreur lors du rafraîchissement');
            });
        }
    </script>
</body>
</html>
