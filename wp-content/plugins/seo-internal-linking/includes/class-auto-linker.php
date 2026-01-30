<?php
/**
 * Classe d'insertion automatique de liens
 *
 * Gère l'insertion automatique et manuelle des liens internes
 * dans le contenu des articles.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_Auto_Linker {

    /**
     * Options du plugin
     */
    private $options;

    /**
     * Liens déjà insérés dans le contenu actuel
     */
    private $inserted_links = array();

    /**
     * Constructeur
     */
    public function __construct($options) {
        $this->options = $options;
    }

    /**
     * Traiter le contenu pour ajouter des liens automatiques
     */
    public function process_content($content, $post_id) {
        global $wpdb;

        // Réinitialiser les liens insérés
        $this->inserted_links = array();

        // Récupérer les liens existants dans le contenu
        $existing_links = $this->get_existing_links($content);

        // Récupérer les mots-clés à lier
        $keywords_to_link = $this->get_keywords_to_link($post_id);

        if (empty($keywords_to_link)) {
            return $content;
        }

        $max_links = isset($this->options['max_links_per_post']) ? intval($this->options['max_links_per_post']) : 5;
        $max_same_link = isset($this->options['max_same_link_occurrences']) ? intval($this->options['max_same_link_occurrences']) : 1;
        $links_added = 0;

        foreach ($keywords_to_link as $kw) {
            if ($links_added >= $max_links) {
                break;
            }

            // Vérifier si un lien vers cette cible existe déjà
            $target_url = get_permalink($kw->target_post_id);

            if (in_array($target_url, $existing_links)) {
                continue;
            }

            // Compter combien de fois ce lien a été ajouté
            $same_link_count = isset($this->inserted_links[$kw->target_post_id]) ? $this->inserted_links[$kw->target_post_id] : 0;

            if ($same_link_count >= $max_same_link) {
                continue;
            }

            // Essayer d'insérer le lien
            $new_content = $this->insert_link_in_content($content, $kw->anchor_text, $kw->target_post_id);

            if ($new_content !== $content) {
                $content = $new_content;
                $links_added++;

                if (!isset($this->inserted_links[$kw->target_post_id])) {
                    $this->inserted_links[$kw->target_post_id] = 0;
                }
                $this->inserted_links[$kw->target_post_id]++;

                // Enregistrer le lien
                $this->record_link($post_id, $kw->target_post_id, $kw->anchor_text, true);
            }
        }

        return $content;
    }

    /**
     * Récupérer les liens existants dans le contenu
     */
    private function get_existing_links($content) {
        $links = array();

        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

        if (!empty($matches[1])) {
            $links = $matches[1];
        }

        return $links;
    }

    /**
     * Récupérer les mots-clés à lier
     */
    private function get_keywords_to_link($post_id) {
        global $wpdb;

        $suggestions_table = $wpdb->prefix . 'sil_suggestions';
        $keywords_table = $wpdb->prefix . 'sil_keywords';

        // D'abord, essayer les suggestions validées
        $suggestions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.target_post_id, s.anchor_text, s.relevance_score
            FROM $suggestions_table s
            WHERE s.source_post_id = %d
            AND s.status IN ('pending', 'approved')
            ORDER BY s.relevance_score DESC
            LIMIT 20",
            $post_id
        ));

        if (!empty($suggestions)) {
            return $suggestions;
        }

        // Sinon, générer dynamiquement des liens basés sur les mots-clés
        $keywords = $wpdb->get_results($wpdb->prepare(
            "SELECT k1.keyword as anchor_text, k2.post_id as target_post_id,
                    (k1.weight * k2.weight) as relevance_score
            FROM $keywords_table k1
            INNER JOIN $keywords_table k2 ON k1.keyword = k2.keyword
            WHERE k1.post_id = %d
            AND k2.post_id != %d
            ORDER BY relevance_score DESC
            LIMIT 20",
            $post_id,
            $post_id
        ));

        return $keywords;
    }

    /**
     * Insérer un lien dans le contenu
     */
    private function insert_link_in_content($content, $anchor, $target_post_id) {
        // Nettoyer l'ancre
        $anchor = trim($anchor);

        if (empty($anchor)) {
            return $content;
        }

        // Échapper les caractères spéciaux pour le regex
        $escaped_anchor = preg_quote($anchor, '/');

        // Créer le pattern pour trouver le mot-clé
        // Ne pas matcher à l'intérieur des balises HTML ou des liens existants
        $pattern = '/(?<![<\w])(' . $escaped_anchor . ')(?![^<]*>)(?![^<]*<\/a>)/iu';

        // Vérifier si le pattern existe dans le contenu
        if (!preg_match($pattern, $content)) {
            return $content;
        }

        // Créer le lien
        $url = get_permalink($target_post_id);
        $title = esc_attr(get_the_title($target_post_id));

        $link_attributes = 'href="' . esc_url($url) . '"';
        $link_attributes .= ' title="' . $title . '"';

        if (isset($this->options['open_in_new_tab']) && $this->options['open_in_new_tab']) {
            $link_attributes .= ' target="_blank" rel="noopener"';
        }

        $replacement = '<a ' . $link_attributes . ' class="sil-auto-link">$1</a>';

        // Remplacer seulement la première occurrence
        $new_content = preg_replace($pattern, $replacement, $content, 1);

        return $new_content;
    }

    /**
     * Insérer manuellement un lien
     */
    public function insert_link_manually($source_id, $target_id, $anchor, $suggestion_id = 0) {
        $post = get_post($source_id);

        if (!$post) {
            return false;
        }

        $content = $post->post_content;

        // Créer le lien HTML
        $url = get_permalink($target_id);
        $title = esc_attr(get_the_title($target_id));

        $link_attributes = 'href="' . esc_url($url) . '"';
        $link_attributes .= ' title="' . $title . '"';

        if (isset($this->options['open_in_new_tab']) && $this->options['open_in_new_tab']) {
            $link_attributes .= ' target="_blank" rel="noopener"';
        }

        $link_html = '<a ' . $link_attributes . ' class="sil-manual-link">' . esc_html($anchor) . '</a>';

        // Échapper les caractères spéciaux pour le regex
        $escaped_anchor = preg_quote($anchor, '/');

        // Pattern pour trouver le mot-clé (pas dans un lien existant)
        $pattern = '/(?<![<\w])(' . $escaped_anchor . ')(?![^<]*>)(?![^<]*<\/a>)/iu';

        // Vérifier si le pattern existe
        if (!preg_match($pattern, $content)) {
            // Si le texte exact n'est pas trouvé, ajouter le lien à la fin d'un paragraphe pertinent
            $content = $this->append_link_to_content($content, $anchor, $link_html);
        } else {
            // Remplacer la première occurrence
            $content = preg_replace($pattern, $link_html, $content, 1);
        }

        // Mettre à jour le post
        $updated = wp_update_post(array(
            'ID' => $source_id,
            'post_content' => $content
        ));

        if ($updated && !is_wp_error($updated)) {
            // Enregistrer le lien
            $this->record_link($source_id, $target_id, $anchor, false);

            // Mettre à jour la suggestion si elle existe
            if ($suggestion_id > 0) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'sil_suggestions',
                    array(
                        'status' => 'applied',
                        'applied_at' => current_time('mysql')
                    ),
                    array('id' => $suggestion_id),
                    array('%s', '%s'),
                    array('%d')
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Ajouter un lien à la fin du contenu
     */
    private function append_link_to_content($content, $anchor, $link_html) {
        // Trouver le dernier paragraphe
        $last_p_pos = strrpos($content, '</p>');

        if ($last_p_pos !== false) {
            // Insérer avant le dernier </p>
            $insert_text = ' Voir aussi : ' . $link_html;
            $content = substr_replace($content, $insert_text, $last_p_pos, 0);
        } else {
            // Ajouter à la fin
            $content .= '<p>Voir aussi : ' . $link_html . '</p>';
        }

        return $content;
    }

    /**
     * Enregistrer un lien dans la base de données
     */
    private function record_link($source_id, $target_id, $anchor, $auto_inserted = false) {
        global $wpdb;

        $table = $wpdb->prefix . 'sil_links';

        // Vérifier si le lien existe déjà
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE source_post_id = %d AND target_post_id = %d",
            $source_id,
            $target_id
        ));

        if ($exists) {
            return;
        }

        $wpdb->insert(
            $table,
            array(
                'source_post_id' => $source_id,
                'target_post_id' => $target_id,
                'anchor_text' => $anchor,
                'auto_inserted' => $auto_inserted ? 1 : 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );

        // Mettre à jour les statistiques
        $this->update_link_statistics($source_id);
        $this->update_link_statistics($target_id);
    }

    /**
     * Mettre à jour les statistiques de liens
     */
    private function update_link_statistics($post_id) {
        global $wpdb;

        $links_table = $wpdb->prefix . 'sil_links';
        $stats_table = $wpdb->prefix . 'sil_statistics';

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

        // Mettre à jour ou insérer
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

    /**
     * Analyser les liens existants dans un post
     */
    public function scan_existing_links($post_id) {
        global $wpdb;

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $content = $post->post_content;
        $links_found = array();

        // Trouver tous les liens dans le contenu
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $url = $match[1];
            $anchor = $match[2];

            // Vérifier si c'est un lien interne
            $site_url = home_url();

            if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                // C'est un lien interne
                $target_id = url_to_postid($url);

                if ($target_id > 0 && $target_id !== $post_id) {
                    $links_found[] = array(
                        'target_id' => $target_id,
                        'anchor' => $anchor,
                        'url' => $url
                    );

                    // Enregistrer le lien
                    $this->record_link($post_id, $target_id, $anchor, false);
                }
            }
        }

        return $links_found;
    }

    /**
     * Scanner tous les posts pour indexer les liens existants
     */
    public function scan_all_existing_links($limit = 50, $offset = 0) {
        $post_types = isset($this->options['post_types']) ? $this->options['post_types'] : array('post', 'page');

        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $scanned = 0;
        $total_links = 0;

        foreach ($posts as $post) {
            $links = $this->scan_existing_links($post->ID);
            $total_links += count($links);
            $scanned++;
        }

        return array(
            'scanned' => $scanned,
            'links_found' => $total_links,
            'offset' => $offset + $limit
        );
    }

    /**
     * Supprimer un lien enregistré
     */
    public function remove_link($source_id, $target_id) {
        global $wpdb;

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'sil_links',
            array(
                'source_post_id' => $source_id,
                'target_post_id' => $target_id
            ),
            array('%d', '%d')
        );

        if ($deleted) {
            $this->update_link_statistics($source_id);
            $this->update_link_statistics($target_id);
        }

        return $deleted;
    }
}
