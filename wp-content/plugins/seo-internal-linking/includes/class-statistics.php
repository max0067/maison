<?php
/**
 * Classe de gestion des statistiques
 *
 * Collecte et affiche les statistiques du maillage interne
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_Statistics {

    /**
     * Options du plugin
     */
    private $options;

    /**
     * Constructeur
     */
    public function __construct($options) {
        $this->options = $options;
    }

    /**
     * Obtenir les statistiques globales
     */
    public function get_global_stats() {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';
        $links_table = $wpdb->prefix . 'sil_links';
        $suggestions_table = $wpdb->prefix . 'sil_suggestions';
        $keywords_table = $wpdb->prefix . 'sil_keywords';

        // Nombre total de posts analysés
        $total_analyzed = $wpdb->get_var("SELECT COUNT(*) FROM $stats_table");

        // Nombre total de liens internes
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $links_table");

        // Nombre de liens auto-insérés
        $auto_links = $wpdb->get_var("SELECT COUNT(*) FROM $links_table WHERE auto_inserted = 1");

        // Nombre de liens manuels
        $manual_links = $total_links - $auto_links;

        // Nombre d'articles orphelins
        $orphan_posts = $wpdb->get_var("SELECT COUNT(*) FROM $stats_table WHERE orphan_status = 1");

        // Suggestions en attente
        $pending_suggestions = $wpdb->get_var("SELECT COUNT(*) FROM $suggestions_table WHERE status = 'pending'");

        // Suggestions appliquées
        $applied_suggestions = $wpdb->get_var("SELECT COUNT(*) FROM $suggestions_table WHERE status = 'applied'");

        // Mots-clés indexés
        $total_keywords = $wpdb->get_var("SELECT COUNT(DISTINCT keyword) FROM $keywords_table");

        // Score SEO moyen
        $avg_seo_score = $wpdb->get_var("SELECT AVG(seo_score) FROM $stats_table");

        // Moyenne de liens sortants par article
        $avg_outgoing = $wpdb->get_var("SELECT AVG(internal_links_count) FROM $stats_table");

        // Moyenne de liens entrants par article
        $avg_incoming = $wpdb->get_var("SELECT AVG(incoming_links_count) FROM $stats_table");

        return array(
            'total_analyzed' => (int) $total_analyzed,
            'total_links' => (int) $total_links,
            'auto_links' => (int) $auto_links,
            'manual_links' => (int) $manual_links,
            'orphan_posts' => (int) $orphan_posts,
            'pending_suggestions' => (int) $pending_suggestions,
            'applied_suggestions' => (int) $applied_suggestions,
            'total_keywords' => (int) $total_keywords,
            'avg_seo_score' => round((float) $avg_seo_score, 1),
            'avg_outgoing_links' => round((float) $avg_outgoing, 1),
            'avg_incoming_links' => round((float) $avg_incoming, 1)
        );
    }

    /**
     * Obtenir les statistiques d'un article
     */
    public function get_post_stats($post_id) {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';
        $links_table = $wpdb->prefix . 'sil_links';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $stats_table WHERE post_id = %d",
            $post_id
        ));

        if (!$stats) {
            return array(
                'post_id' => $post_id,
                'internal_links_count' => 0,
                'incoming_links_count' => 0,
                'orphan_status' => true,
                'seo_score' => 0,
                'last_analyzed' => null,
                'outgoing_links' => array(),
                'incoming_links' => array()
            );
        }

        // Récupérer les détails des liens sortants
        $outgoing_links = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as target_title
            FROM $links_table l
            INNER JOIN {$wpdb->posts} p ON l.target_post_id = p.ID
            WHERE l.source_post_id = %d",
            $post_id
        ));

        // Récupérer les détails des liens entrants
        $incoming_links = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as source_title
            FROM $links_table l
            INNER JOIN {$wpdb->posts} p ON l.source_post_id = p.ID
            WHERE l.target_post_id = %d",
            $post_id
        ));

        return array(
            'post_id' => $stats->post_id,
            'internal_links_count' => (int) $stats->internal_links_count,
            'incoming_links_count' => (int) $stats->incoming_links_count,
            'orphan_status' => (bool) $stats->orphan_status,
            'seo_score' => (float) $stats->seo_score,
            'last_analyzed' => $stats->last_analyzed,
            'outgoing_links' => $outgoing_links,
            'incoming_links' => $incoming_links
        );
    }

    /**
     * Obtenir le top des articles les mieux liés
     */
    public function get_top_linked_posts($limit = 10) {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.post_title, p.post_type
            FROM $stats_table s
            INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
            WHERE p.post_status = 'publish'
            ORDER BY s.incoming_links_count DESC
            LIMIT %d",
            $limit
        ));
    }

    /**
     * Obtenir les articles avec le meilleur score SEO
     */
    public function get_top_seo_posts($limit = 10) {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, p.post_title, p.post_type
            FROM $stats_table s
            INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
            WHERE p.post_status = 'publish'
            ORDER BY s.seo_score DESC
            LIMIT %d",
            $limit
        ));
    }

    /**
     * Obtenir les mots-clés les plus fréquents
     */
    public function get_top_keywords($limit = 20) {
        global $wpdb;

        $keywords_table = $wpdb->prefix . 'sil_keywords';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT keyword, COUNT(*) as post_count, SUM(frequency) as total_frequency, AVG(weight) as avg_weight
            FROM $keywords_table
            GROUP BY keyword
            ORDER BY post_count DESC, total_frequency DESC
            LIMIT %d",
            $limit
        ));
    }

    /**
     * Mettre à jour toutes les statistiques
     */
    public function update_all_statistics() {
        global $wpdb;

        $post_types = isset($this->options['post_types']) ? $this->options['post_types'] : array('post', 'page');

        $post_type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $posts = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($post_type_placeholders)",
            ...$post_types
        ));

        $links_table = $wpdb->prefix . 'sil_links';
        $stats_table = $wpdb->prefix . 'sil_statistics';

        foreach ($posts as $post_id) {
            // Compter les liens sortants
            $outgoing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $links_table WHERE source_post_id = %d",
                $post_id
            ));

            // Compter les liens entrants
            $incoming = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $links_table WHERE target_post_id = %d",
                $post_id
            ));

            // Calculer le statut orphelin
            $orphan = ($incoming == 0) ? 1 : 0;

            // Calculer le score SEO
            $seo_score = min(100, ($outgoing * 10) + ($incoming * 5));

            // Vérifier si une entrée existe
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $stats_table WHERE post_id = %d",
                $post_id
            ));

            $data = array(
                'post_id' => $post_id,
                'internal_links_count' => $outgoing,
                'incoming_links_count' => $incoming,
                'orphan_status' => $orphan,
                'seo_score' => $seo_score,
                'last_analyzed' => current_time('mysql')
            );

            if ($existing) {
                $wpdb->update($stats_table, $data, array('id' => $existing));
            } else {
                $wpdb->insert($stats_table, $data);
            }
        }

        return count($posts);
    }

    /**
     * Obtenir l'historique des liens par période
     */
    public function get_links_history($days = 30) {
        global $wpdb;

        $links_table = $wpdb->prefix . 'sil_links';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
            FROM $links_table
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC",
            $days
        ));
    }

    /**
     * Obtenir la distribution des liens
     */
    public function get_links_distribution() {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';

        // Distribution des liens sortants
        $outgoing_dist = $wpdb->get_results(
            "SELECT
                CASE
                    WHEN internal_links_count = 0 THEN '0 liens'
                    WHEN internal_links_count BETWEEN 1 AND 2 THEN '1-2 liens'
                    WHEN internal_links_count BETWEEN 3 AND 5 THEN '3-5 liens'
                    WHEN internal_links_count BETWEEN 6 AND 10 THEN '6-10 liens'
                    ELSE '10+ liens'
                END as range_label,
                COUNT(*) as count
            FROM $stats_table
            GROUP BY range_label
            ORDER BY MIN(internal_links_count)"
        );

        // Distribution des liens entrants
        $incoming_dist = $wpdb->get_results(
            "SELECT
                CASE
                    WHEN incoming_links_count = 0 THEN '0 liens'
                    WHEN incoming_links_count BETWEEN 1 AND 2 THEN '1-2 liens'
                    WHEN incoming_links_count BETWEEN 3 AND 5 THEN '3-5 liens'
                    WHEN incoming_links_count BETWEEN 6 AND 10 THEN '6-10 liens'
                    ELSE '10+ liens'
                END as range_label,
                COUNT(*) as count
            FROM $stats_table
            GROUP BY range_label
            ORDER BY MIN(incoming_links_count)"
        );

        return array(
            'outgoing' => $outgoing_dist,
            'incoming' => $incoming_dist
        );
    }

    /**
     * Exporter les statistiques en CSV
     */
    public function export_csv() {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';

        $data = $wpdb->get_results(
            "SELECT s.post_id, p.post_title, p.post_type,
                    s.internal_links_count, s.incoming_links_count,
                    s.orphan_status, s.seo_score, s.last_analyzed
            FROM $stats_table s
            INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
            WHERE p.post_status = 'publish'
            ORDER BY s.seo_score DESC",
            ARRAY_A
        );

        $csv_content = "ID,Titre,Type,Liens sortants,Liens entrants,Orphelin,Score SEO,Dernière analyse\n";

        foreach ($data as $row) {
            $csv_content .= implode(',', array(
                $row['post_id'],
                '"' . str_replace('"', '""', $row['post_title']) . '"',
                $row['post_type'],
                $row['internal_links_count'],
                $row['incoming_links_count'],
                $row['orphan_status'] ? 'Oui' : 'Non',
                $row['seo_score'],
                $row['last_analyzed']
            )) . "\n";
        }

        return $csv_content;
    }
}
