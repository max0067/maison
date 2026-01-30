<?php
/**
 * Classe de diagnostic du plugin
 *
 * Vérifie que tout fonctionne correctement
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_Diagnostic {

    /**
     * Exécuter tous les diagnostics
     */
    public static function run_all() {
        $results = array();

        $results['tables'] = self::check_tables();
        $results['posts'] = self::check_posts();
        $results['options'] = self::check_options();
        $results['permissions'] = self::check_permissions();

        return $results;
    }

    /**
     * Vérifier les tables de la base de données
     */
    public static function check_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'sil_keywords',
            $wpdb->prefix . 'sil_suggestions',
            $wpdb->prefix . 'sil_links',
            $wpdb->prefix . 'sil_statistics'
        );

        $results = array();

        foreach ($tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $count = 0;

            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            }

            $results[$table] = array(
                'exists' => $exists,
                'count' => (int) $count
            );
        }

        return $results;
    }

    /**
     * Vérifier les posts disponibles
     */
    public static function check_posts() {
        $options = get_option('sil_options', array());
        $post_types = isset($options['post_types']) ? $options['post_types'] : array('post', 'page');

        // Si vide, utiliser les valeurs par défaut
        if (empty($post_types)) {
            $post_types = array('post', 'page');
        }

        $results = array(
            'configured_types' => $post_types,
            'counts' => array(),
            'total' => 0,
            'sample_posts' => array()
        );

        foreach ($post_types as $pt) {
            $count_obj = wp_count_posts($pt);
            $count = isset($count_obj->publish) ? (int) $count_obj->publish : 0;
            $results['counts'][$pt] = $count;
            $results['total'] += $count;
        }

        // Récupérer quelques posts en exemple
        $sample = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        foreach ($sample as $post) {
            $results['sample_posts'][] = array(
                'ID' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'date' => $post->post_date
            );
        }

        // Vérifier aussi tous les types de posts publics disponibles
        $all_public_types = get_post_types(array('public' => true), 'objects');
        $results['available_types'] = array();

        foreach ($all_public_types as $pt) {
            if ($pt->name === 'attachment') continue;

            $count_obj = wp_count_posts($pt->name);
            $count = isset($count_obj->publish) ? (int) $count_obj->publish : 0;

            $results['available_types'][$pt->name] = array(
                'label' => $pt->label,
                'count' => $count
            );
        }

        return $results;
    }

    /**
     * Vérifier les options du plugin
     */
    public static function check_options() {
        $options = get_option('sil_options', array());

        $defaults = array(
            'auto_link_enabled' => false,
            'max_links_per_post' => 5,
            'min_keyword_length' => 3,
            'post_types' => array('post', 'page'),
            'excluded_words' => ''
        );

        return array(
            'saved_options' => $options,
            'defaults' => $defaults,
            'merged' => wp_parse_args($options, $defaults)
        );
    }

    /**
     * Vérifier les permissions
     */
    public static function check_permissions() {
        return array(
            'can_manage_options' => current_user_can('manage_options'),
            'can_edit_posts' => current_user_can('edit_posts'),
            'is_admin' => is_admin()
        );
    }

    /**
     * Recréer les tables
     */
    public static function recreate_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table des mots-clés
        $table_keywords = $wpdb->prefix . 'sil_keywords';
        $sql_keywords = "CREATE TABLE $table_keywords (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            keyword varchar(255) NOT NULL,
            frequency int(11) DEFAULT 1,
            weight float DEFAULT 1.0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY keyword (keyword(191))
        ) $charset_collate;";

        // Table des suggestions
        $table_suggestions = $wpdb->prefix . 'sil_suggestions';
        $sql_suggestions = "CREATE TABLE $table_suggestions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) NOT NULL,
            target_post_id bigint(20) NOT NULL,
            anchor_text varchar(255) NOT NULL,
            relevance_score float DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            applied_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY source_post_id (source_post_id),
            KEY target_post_id (target_post_id),
            KEY status (status)
        ) $charset_collate;";

        // Table des liens
        $table_links = $wpdb->prefix . 'sil_links';
        $sql_links = "CREATE TABLE $table_links (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) NOT NULL,
            target_post_id bigint(20) NOT NULL,
            anchor_text varchar(255) NOT NULL,
            position int(11) DEFAULT 0,
            auto_inserted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_post_id (source_post_id),
            KEY target_post_id (target_post_id)
        ) $charset_collate;";

        // Table des statistiques
        $table_stats = $wpdb->prefix . 'sil_statistics';
        $sql_stats = "CREATE TABLE $table_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            internal_links_count int(11) DEFAULT 0,
            incoming_links_count int(11) DEFAULT 0,
            orphan_status tinyint(1) DEFAULT 0,
            last_analyzed datetime DEFAULT NULL,
            seo_score float DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $results = array();
        $results['keywords'] = dbDelta($sql_keywords);
        $results['suggestions'] = dbDelta($sql_suggestions);
        $results['links'] = dbDelta($sql_links);
        $results['statistics'] = dbDelta($sql_stats);

        return $results;
    }

    /**
     * Forcer la réinitialisation des options avec les bons types de posts
     */
    public static function fix_options() {
        $options = get_option('sil_options', array());

        // S'assurer que post_types contient au moins post et page
        if (empty($options['post_types'])) {
            $options['post_types'] = array('post', 'page');
        }

        // S'assurer que c'est un tableau
        if (!is_array($options['post_types'])) {
            $options['post_types'] = array('post', 'page');
        }

        update_option('sil_options', $options);

        return $options;
    }
}
