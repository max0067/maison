<?php
require_once 'config/rss_config.php';
require_once 'includes/rss_functions.php';

requireLogin();

$user_id = $_SESSION['rss_user_id'];
$username = $_SESSION['rss_username'];

$query = $_GET['q'] ?? '';
$items = [];

if (!empty($query)) {
    $items = searchItems($user_id, $query);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche - <?= RSS_APP_NAME ?></title>
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
                    <a href="rss_index.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        Accueil
                    </a>
                    <a href="rss_search.php" class="nav-link active">
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
                <div class="user-menu">
                    <span class="user-name"><?= clean($username) ?></span>
                    <a href="rss_logout.php" class="btn btn-secondary">Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="main-container single-column">
        <main class="content content-wide">
            <div class="search-header">
                <h2>Recherche dans les articles</h2>
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <input
                            type="text"
                            name="q"
                            placeholder="Rechercher un article..."
                            value="<?= clean($query) ?>"
                            class="search-input"
                            autofocus
                        >
                        <button type="submit" class="btn btn-primary">
                            <span>🔍</span> Rechercher
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($query)): ?>
                <div class="search-results">
                    <h3>Résultats pour "<?= clean($query) ?>"</h3>
                    <p class="results-count"><?= count($items) ?> résultat(s) trouvé(s)</p>

                    <?php if (empty($items)): ?>
                        <div class="empty-state">
                            <p>Aucun article trouvé pour cette recherche</p>
                            <p><small>Essayez avec d'autres mots-clés</small></p>
                        </div>
                    <?php else: ?>
                        <div class="items-list">
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
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="search-tips">
                    <h3>Conseils de recherche</h3>
                    <ul>
                        <li>Utilisez des mots-clés spécifiques</li>
                        <li>La recherche s'effectue dans le titre, la description et le contenu des articles</li>
                        <li>Les résultats sont triés par date de publication</li>
                    </ul>
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
    </script>
</body>
</html>
