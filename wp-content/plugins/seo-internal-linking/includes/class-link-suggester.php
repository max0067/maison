<?php
/**
 * Classe de suggestion de liens internes
 *
 * Génère des suggestions de liens internes basées sur l'analyse des mots-clés
 * et la pertinence du contenu.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_Link_Suggester {

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
     * Générer les suggestions pour un article
     */
    public function generate_suggestions($post_id) {
        global $wpdb;

        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }

        // Récupérer les mots-clés de l'article
        $keywords_table = $wpdb->prefix . 'sil_keywords';
        $keywords = $wpdb->get_results($wpdb->prepare(
            "SELECT keyword, weight FROM $keywords_table WHERE post_id = %d ORDER BY weight DESC LIMIT 30",
            $post_id
        ));

        if (empty($keywords)) {
            return false;
        }

        // Supprimer les anciennes suggestions en attente
        $suggestions_table = $wpdb->prefix . 'sil_suggestions';
        $wpdb->delete(
            $suggestions_table,
            array(
                'source_post_id' => $post_id,
                'status' => 'pending'
            ),
            array('%d', '%s')
        );

        // Trouver les articles correspondants
        $post_types = isset($this->options['post_types']) ? $this->options['post_types'] : array('post', 'page');
        $suggestions = array();

        foreach ($keywords as $kw) {
            $matching_posts = $this->find_related_posts($kw->keyword, $post_id, $post_types);

            foreach ($matching_posts as $match) {
                $key = $post_id . '_' . $match->ID;

                if (!isset($suggestions[$key])) {
                    $suggestions[$key] = array(
                        'target_id' => $match->ID,
                        'anchor_text' => $kw->keyword,
                        'score' => 0,
                        'matches' => array()
                    );
                }

                // Calculer le score de pertinence
                $relevance = $this->calculate_relevance($kw, $match);
                $suggestions[$key]['score'] += $relevance;
                $suggestions[$key]['matches'][] = array(
                    'keyword' => $kw->keyword,
                    'weight' => $kw->weight,
                    'match_score' => $relevance
                );

                // Utiliser le meilleur ancre texte
                if ($relevance > $suggestions[$key]['score'] / 2) {
                    $suggestions[$key]['anchor_text'] = $this->get_best_anchor($kw->keyword, $match);
                }
            }
        }

        // Trier par score et sauvegarder
        uasort($suggestions, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limiter le nombre de suggestions
        $max_suggestions = isset($this->options['max_links_per_post']) ? intval($this->options['max_links_per_post']) * 2 : 10;
        $suggestions = array_slice($suggestions, 0, $max_suggestions, true);

        // Sauvegarder les suggestions
        foreach ($suggestions as $suggestion) {
            $wpdb->insert(
                $suggestions_table,
                array(
                    'source_post_id' => $post_id,
                    'target_post_id' => $suggestion['target_id'],
                    'anchor_text' => $suggestion['anchor_text'],
                    'relevance_score' => $suggestion['score'],
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%f', '%s', '%s')
            );
        }

        return count($suggestions);
    }

    /**
     * Trouver les articles liés par mot-clé
     */
    private function find_related_posts($keyword, $exclude_id, $post_types) {
        global $wpdb;

        $keywords_table = $wpdb->prefix . 'sil_keywords';
        $posts_table = $wpdb->posts;

        // Recherche exacte et partielle
        $like_keyword = '%' . $wpdb->esc_like($keyword) . '%';

        // Préparer les types de posts pour la requête
        $post_type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $query_args = array_merge(
            array($keyword, $like_keyword, $exclude_id),
            $post_types
        );

        $sql = $wpdb->prepare(
            "SELECT DISTINCT p.ID, p.post_title, p.post_type, k.weight as keyword_weight
            FROM $posts_table p
            INNER JOIN $keywords_table k ON p.ID = k.post_id
            WHERE (k.keyword = %s OR k.keyword LIKE %s)
            AND p.ID != %d
            AND p.post_status = 'publish'
            AND p.post_type IN ($post_type_placeholders)
            ORDER BY k.weight DESC
            LIMIT 20",
            ...$query_args
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Calculer le score de pertinence
     */
    private function calculate_relevance($keyword, $match) {
        $base_score = $keyword->weight * $match->keyword_weight;

        // Bonus pour le même type de post
        if (get_post_type() === $match->post_type) {
            $base_score *= 1.2;
        }

        // Bonus pour les catégories communes
        $current_cats = wp_get_post_categories(get_the_ID());
        $match_cats = wp_get_post_categories($match->ID);

        $common_cats = array_intersect($current_cats, $match_cats);
        if (!empty($common_cats)) {
            $base_score *= (1 + (count($common_cats) * 0.1));
        }

        // Bonus pour les tags communs
        $current_tags = wp_get_post_tags(get_the_ID(), array('fields' => 'ids'));
        $match_tags = wp_get_post_tags($match->ID, array('fields' => 'ids'));

        $common_tags = array_intersect($current_tags, $match_tags);
        if (!empty($common_tags)) {
            $base_score *= (1 + (count($common_tags) * 0.15));
        }

        return round($base_score, 3);
    }

    /**
     * Obtenir le meilleur texte d'ancrage
     */
    private function get_best_anchor($keyword, $match) {
        // Vérifier si le mot-clé apparaît dans le titre
        if (stripos($match->post_title, $keyword) !== false) {
            // Extraire la portion pertinente du titre
            return $this->extract_anchor_from_title($keyword, $match->post_title);
        }

        // Sinon, utiliser le mot-clé directement
        return ucfirst($keyword);
    }

    /**
     * Extraire l'ancre du titre
     */
    private function extract_anchor_from_title($keyword, $title) {
        // Si le titre est court, l'utiliser entièrement
        if (mb_strlen($title) <= 50) {
            return $title;
        }

        // Trouver la position du mot-clé
        $pos = mb_stripos($title, $keyword);

        if ($pos === false) {
            return ucfirst($keyword);
        }

        // Extraire une portion autour du mot-clé
        $start = max(0, $pos - 10);
        $length = min(50, mb_strlen($title) - $start);

        $anchor = mb_substr($title, $start, $length);

        // Nettoyer les bords
        if ($start > 0) {
            $anchor = preg_replace('/^\S+\s/', '', $anchor);
        }

        if ($start + $length < mb_strlen($title)) {
            $anchor = preg_replace('/\s\S+$/', '', $anchor);
        }

        return trim($anchor);
    }

    /**
     * Obtenir les suggestions pour un article
     */
    public function get_suggestions($post_id, $status = 'pending') {
        global $wpdb;

        $suggestions_table = $wpdb->prefix . 'sil_suggestions';
        $posts_table = $wpdb->posts;

        $where_status = '';
        if ($status !== 'all') {
            $where_status = $wpdb->prepare(" AND s.status = %s", $status);
        }

        $sql = $wpdb->prepare(
            "SELECT s.*, p.post_title as target_title, p.post_type as target_type
            FROM $suggestions_table s
            INNER JOIN $posts_table p ON s.target_post_id = p.ID
            WHERE s.source_post_id = %d
            $where_status
            AND p.post_status = 'publish'
            ORDER BY s.relevance_score DESC",
            $post_id
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Obtenir toutes les suggestions en attente
     */
    public function get_all_pending_suggestions($limit = 100, $offset = 0) {
        global $wpdb;

        $suggestions_table = $wpdb->prefix . 'sil_suggestions';
        $posts_table = $wpdb->posts;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*,
                    source.post_title as source_title,
                    target.post_title as target_title,
                    source.post_type as source_type,
                    target.post_type as target_type
            FROM $suggestions_table s
            INNER JOIN $posts_table source ON s.source_post_id = source.ID
            INNER JOIN $posts_table target ON s.target_post_id = target.ID
            WHERE s.status = 'pending'
            AND source.post_status = 'publish'
            AND target.post_status = 'publish'
            ORDER BY s.relevance_score DESC
            LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    /**
     * Mettre à jour le statut d'une suggestion
     */
    public function update_suggestion_status($suggestion_id, $status) {
        global $wpdb;

        $data = array('status' => $status);

        if ($status === 'applied') {
            $data['applied_at'] = current_time('mysql');
        }

        return $wpdb->update(
            $wpdb->prefix . 'sil_suggestions',
            $data,
            array('id' => $suggestion_id),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * Rejeter une suggestion
     */
    public function reject_suggestion($suggestion_id) {
        return $this->update_suggestion_status($suggestion_id, 'rejected');
    }

    /**
     * Obtenir les articles orphelins (sans liens entrants)
     */
    public function get_orphan_posts($limit = 50) {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';
        $posts_table = $wpdb->posts;

        $post_types = isset($this->options['post_types']) ? $this->options['post_types'] : array('post', 'page');
        $post_type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        $query_args = array_merge($post_types, array($limit));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type, p.post_date,
                    COALESCE(s.incoming_links_count, 0) as incoming_links,
                    COALESCE(s.internal_links_count, 0) as outgoing_links
            FROM $posts_table p
            LEFT JOIN $stats_table s ON p.ID = s.post_id
            WHERE p.post_status = 'publish'
            AND p.post_type IN ($post_type_placeholders)
            AND (s.incoming_links_count IS NULL OR s.incoming_links_count = 0)
            ORDER BY p.post_date DESC
            LIMIT %d",
            ...$query_args
        ));
    }

    /**
     * Obtenir les articles avec peu de liens
     */
    public function get_low_link_posts($min_links = 2, $limit = 50) {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';
        $posts_table = $wpdb->posts;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type, p.post_date,
                    COALESCE(s.incoming_links_count, 0) as incoming_links,
                    COALESCE(s.internal_links_count, 0) as outgoing_links,
                    COALESCE(s.seo_score, 0) as seo_score
            FROM $posts_table p
            LEFT JOIN $stats_table s ON p.ID = s.post_id
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND (s.internal_links_count IS NULL OR s.internal_links_count < %d)
            ORDER BY s.internal_links_count ASC, p.post_date DESC
            LIMIT %d",
            $min_links,
            $limit
        ));
    }
}
