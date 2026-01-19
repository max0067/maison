<?php
/**
 * Script CRON pour rafraîchir automatiquement les flux RSS
 *
 * À exécuter via crontab, par exemple toutes les heures :
 * 0 * * * * php /chemin/vers/rss_cron.php
 */

require_once __DIR__ . '/config/rss_config.php';
require_once __DIR__ . '/includes/rss_functions.php';

echo "Démarrage du rafraîchissement des flux RSS à " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n";

try {
    $results = refreshAllFeeds();

    $total_feeds = count($results);
    $success_count = 0;
    $error_count = 0;

    foreach ($results as $result) {
        if (isset($result['count'])) {
            echo "✓ Flux #{$result['feed_id']} : {$result['count']} articles récupérés\n";
            $success_count++;
        } else {
            echo "✗ Flux #{$result['feed_id']} : ERREUR - {$result['error']}\n";
            $error_count++;
        }
    }

    echo str_repeat('=', 60) . "\n";
    echo "Résumé :\n";
    echo "  - Total : $total_feeds flux\n";
    echo "  - Succès : $success_count\n";
    echo "  - Erreurs : $error_count\n";
    echo "\nTerminé à " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "ERREUR CRITIQUE : " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
?>
