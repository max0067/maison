<?php
/**
 * Fonctions pour gérer les flux RSS
 */

require_once __DIR__ . '/../config/rss_config.php';

/**
 * Récupérer et parser un flux RSS
 */
function fetchRSSFeed($url) {
    try {
        // Utiliser libxml pour parser le XML
        libxml_use_internal_errors(true);

        // Options de contexte pour le chargement
        $opts = [
            'http' => [
                'user_agent' => 'RSS Reader/1.0',
                'timeout' => 10
            ]
        ];
        $context = stream_context_create($opts);

        // Charger le flux
        $xml = @file_get_contents($url, false, $context);

        if ($xml === false) {
            return ['error' => 'Impossible de charger le flux'];
        }

        // Parser le XML
        $feed = @simplexml_load_string($xml);

        if ($feed === false) {
            return ['error' => 'Impossible de parser le flux XML'];
        }

        // Détecter le type de flux (RSS ou Atom)
        $items = [];

        if (isset($feed->channel)) {
            // Format RSS 2.0
            $items = parseRSS20($feed);
        } elseif (isset($feed->entry)) {
            // Format Atom
            $items = parseAtom($feed);
        }

        return ['success' => true, 'items' => $items];

    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Parser un flux RSS 2.0
 */
function parseRSS20($feed) {
    $items = [];

    foreach ($feed->channel->item as $item) {
        $items[] = [
            'title' => (string)$item->title,
            'link' => (string)$item->link,
            'description' => (string)$item->description,
            'content' => isset($item->children('content', true)->encoded)
                ? (string)$item->children('content', true)->encoded
                : (string)$item->description,
            'author' => (string)($item->author ?? $item->children('dc', true)->creator ?? ''),
            'pub_date' => (string)($item->pubDate ?? ''),
            'guid' => (string)($item->guid ?? $item->link),
            'image' => extractImageFromItem($item)
        ];
    }

    return $items;
}

/**
 * Parser un flux Atom
 */
function parseAtom($feed) {
    $items = [];

    foreach ($feed->entry as $entry) {
        $link = '';
        if (isset($entry->link)) {
            $link = is_object($entry->link)
                ? (string)$entry->link['href']
                : (string)$entry->link;
        }

        $items[] = [
            'title' => (string)$entry->title,
            'link' => $link,
            'description' => (string)($entry->summary ?? ''),
            'content' => (string)($entry->content ?? $entry->summary ?? ''),
            'author' => (string)($entry->author->name ?? ''),
            'pub_date' => (string)($entry->updated ?? $entry->published ?? ''),
            'guid' => (string)($entry->id ?? $link),
            'image' => ''
        ];
    }

    return $items;
}

/**
 * Extraire l'image d'un article
 */
function extractImageFromItem($item) {
    // Chercher dans media:content
    if (isset($item->children('media', true)->content)) {
        return (string)$item->children('media', true)->content->attributes()->url;
    }

    // Chercher dans enclosure
    if (isset($item->enclosure) && strpos((string)$item->enclosure['type'], 'image') !== false) {
        return (string)$item->enclosure['url'];
    }

    return '';
}

/**
 * Sauvegarder les articles d'un flux
 */
function saveFeedItems($feed_id, $items) {
    $db = getRSSDB();

    $stmt = $db->prepare("
        INSERT INTO rss_items (feed_id, title, link, description, content, author, pub_date, guid, image_url)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            content = VALUES(content)
    ");

    $count = 0;
    foreach ($items as $item) {
        $pub_date = !empty($item['pub_date']) ? date('Y-m-d H:i:s', strtotime($item['pub_date'])) : null;

        $stmt->execute([
            $feed_id,
            $item['title'],
            $item['link'],
            $item['description'],
            $item['content'],
            $item['author'],
            $pub_date,
            $item['guid'],
            $item['image']
        ]);

        $count++;
    }

    // Mettre à jour la date de dernière récupération
    $stmt = $db->prepare("UPDATE rss_feeds SET last_fetch = NOW() WHERE id = ?");
    $stmt->execute([$feed_id]);

    return $count;
}

/**
 * Rafraîchir tous les flux actifs
 */
function refreshAllFeeds() {
    $db = getRSSDB();

    $stmt = $db->query("
        SELECT id, url
        FROM rss_feeds
        WHERE active = 1
        AND (last_fetch IS NULL OR last_fetch < DATE_SUB(NOW(), INTERVAL refresh_interval SECOND))
    ");

    $results = [];
    while ($feed = $stmt->fetch()) {
        $result = fetchRSSFeed($feed['url']);

        if (isset($result['success']) && $result['success']) {
            $count = saveFeedItems($feed['id'], $result['items']);
            $results[] = ['feed_id' => $feed['id'], 'count' => $count];
        } else {
            $results[] = ['feed_id' => $feed['id'], 'error' => $result['error']];
        }
    }

    return $results;
}

/**
 * Récupérer les articles récents
 */
function getRecentItems($user_id, $limit = 20, $offset = 0) {
    $db = getRSSDB();

    $stmt = $db->prepare("
        SELECT
            ri.*,
            rf.title as feed_title,
            rf.icon as feed_icon,
            ur.id as is_read,
            uf.id as is_favorite
        FROM rss_items ri
        JOIN rss_feeds rf ON ri.feed_id = rf.id
        LEFT JOIN user_reads ur ON ri.id = ur.item_id AND ur.user_id = ?
        LEFT JOIN user_favorites uf ON ri.id = uf.item_id AND uf.user_id = ?
        WHERE rf.active = 1
        ORDER BY ri.pub_date DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->execute([$user_id, $user_id, $limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Rechercher dans les articles
 */
function searchItems($user_id, $query, $limit = 20) {
    $db = getRSSDB();

    $search = "%$query%";
    $stmt = $db->prepare("
        SELECT
            ri.*,
            rf.title as feed_title,
            rf.icon as feed_icon,
            ur.id as is_read,
            uf.id as is_favorite
        FROM rss_items ri
        JOIN rss_feeds rf ON ri.feed_id = rf.id
        LEFT JOIN user_reads ur ON ri.id = ur.item_id AND ur.user_id = ?
        LEFT JOIN user_favorites uf ON ri.id = uf.item_id AND uf.user_id = ?
        WHERE rf.active = 1
        AND (ri.title LIKE ? OR ri.description LIKE ? OR ri.content LIKE ?)
        ORDER BY ri.pub_date DESC
        LIMIT ?
    ");

    $stmt->execute([$user_id, $user_id, $search, $search, $search, $limit]);
    return $stmt->fetchAll();
}

/**
 * Récupérer les dossiers d'un utilisateur
 */
function getUserFolders($user_id) {
    $db = getRSSDB();

    $stmt = $db->prepare("
        SELECT * FROM folders
        WHERE user_id = ?
        ORDER BY ordre, name
    ");

    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Récupérer les flux d'un dossier
 */
function getFolderFeeds($folder_id) {
    $db = getRSSDB();

    $stmt = $db->prepare("
        SELECT rf.*, ff.ordre
        FROM rss_feeds rf
        JOIN folder_feeds ff ON rf.id = ff.feed_id
        WHERE ff.folder_id = ?
        ORDER BY ff.ordre, rf.title
    ");

    $stmt->execute([$folder_id]);
    return $stmt->fetchAll();
}

/**
 * Marquer un article comme lu
 */
function markAsRead($user_id, $item_id) {
    $db = getRSSDB();

    $stmt = $db->prepare("
        INSERT IGNORE INTO user_reads (user_id, item_id)
        VALUES (?, ?)
    ");

    return $stmt->execute([$user_id, $item_id]);
}

/**
 * Ajouter/retirer un favori
 */
function toggleFavorite($user_id, $item_id) {
    $db = getRSSDB();

    // Vérifier si déjà en favori
    $stmt = $db->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$user_id, $item_id]);

    if ($stmt->fetch()) {
        // Retirer des favoris
        $stmt = $db->prepare("DELETE FROM user_favorites WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$user_id, $item_id]);
        return false;
    } else {
        // Ajouter aux favoris
        $stmt = $db->prepare("INSERT INTO user_favorites (user_id, item_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $item_id]);
        return true;
    }
}

/**
 * Formater une date de manière lisible
 */
function timeAgo($datetime) {
    if (empty($datetime)) return 'Date inconnue';

    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'À l\'instant';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' heure' . ($hours > 1 ? 's' : '');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' jour' . ($days > 1 ? 's' : '');
    } else {
        return date('d/m/Y', $time);
    }
}
?>
