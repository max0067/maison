<?php
/**
 * Plugin Name: SEO Internal Linking
 * Plugin URI: https://github.com/max0067/maison
 * Description: Plugin de maillage interne automatique pour améliorer le référencement SEO. Analyse vos articles et suggère/insère automatiquement des liens internes pertinents.
 * Version: 1.0.0
 * Author: Maison
 * Author URI: https://github.com/max0067
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: seo-internal-linking
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('SIL_VERSION', '1.0.0');
define('SIL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin SEO Internal Linking
 */
class SEO_Internal_Linking {

    /**
     * Instance unique du plugin
     */
    private static $instance = null;

    /**
     * Options du plugin
     */
    private $options;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_options();
        $this->init_hooks();
    }

    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        require_once SIL_PLUGIN_DIR . 'includes/class-keyword-analyzer.php';
        require_once SIL_PLUGIN_DIR . 'includes/class-link-suggester.php';
        require_once SIL_PLUGIN_DIR . 'includes/class-auto-linker.php';
        require_once SIL_PLUGIN_DIR . 'includes/class-statistics.php';
        require_once SIL_PLUGIN_DIR . 'includes/class-diagnostic.php';

        if (is_admin()) {
            require_once SIL_PLUGIN_DIR . 'admin/class-admin.php';
        }
    }

    /**
     * Définir les options par défaut
     */
    private function set_options() {
        $defaults = array(
            'auto_link_enabled' => false,
            'max_links_per_post' => 5,
            'min_keyword_length' => 3,
            'excluded_words' => 'le,la,les,un,une,des,de,du,et,ou,mais,donc,car,ni,que,qui,quoi,dont,où,pour,par,sur,sous,avec,sans,dans,en,à,au,aux',
            'link_to_categories' => true,
            'link_to_tags' => true,
            'open_in_new_tab' => false,
            'nofollow_external' => true,
            'post_types' => array('post', 'page'),
            'min_post_age_days' => 0,
            'max_same_link_occurrences' => 1,
            'priority_order' => 'relevance', // relevance, date, random
        );

        $this->options = get_option('sil_options', $defaults);
        $this->options = wp_parse_args($this->options, $defaults);
    }

    /**
     * Obtenir une option
     */
    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Initialiser les hooks
     */
    private function init_hooks() {
        // Activation/Désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialisation
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Filtrer le contenu pour ajouter les liens automatiques
        if ($this->get_option('auto_link_enabled')) {
            add_filter('the_content', array($this, 'auto_insert_links'), 99);
        }

        // AJAX handlers
        add_action('wp_ajax_sil_get_suggestions', array($this, 'ajax_get_suggestions'));
        add_action('wp_ajax_sil_analyze_post', array($this, 'ajax_analyze_post'));
        add_action('wp_ajax_sil_insert_link', array($this, 'ajax_insert_link'));
        add_action('wp_ajax_sil_bulk_insert_links', array($this, 'ajax_bulk_insert_links'));
        add_action('wp_ajax_sil_bulk_analyze', array($this, 'ajax_bulk_analyze'));
        add_action('wp_ajax_sil_run_diagnostic', array($this, 'ajax_run_diagnostic'));
        add_action('wp_ajax_sil_fix_tables', array($this, 'ajax_fix_tables'));

        // Cron pour l'analyse automatique
        add_action('sil_daily_analysis', array($this, 'run_daily_analysis'));
    }

    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables personnalisées
        $this->create_tables();

        // Définir les options par défaut
        if (!get_option('sil_options')) {
            add_option('sil_options', array());
        }

        // Planifier le cron
        if (!wp_next_scheduled('sil_daily_analysis')) {
            wp_schedule_event(time(), 'daily', 'sil_daily_analysis');
        }

        // Flush des règles de réécriture
        flush_rewrite_rules();
    }

    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Supprimer le cron
        wp_clear_scheduled_hook('sil_daily_analysis');

        // Flush des règles de réécriture
        flush_rewrite_rules();
    }

    /**
     * Créer les tables de la base de données
     */
    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table des mots-clés extraits
        $table_keywords = $wpdb->prefix . 'sil_keywords';
        $sql_keywords = "CREATE TABLE IF NOT EXISTS $table_keywords (
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

        // Table des suggestions de liens
        $table_suggestions = $wpdb->prefix . 'sil_suggestions';
        $sql_suggestions = "CREATE TABLE IF NOT EXISTS $table_suggestions (
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

        // Table des liens insérés
        $table_links = $wpdb->prefix . 'sil_links';
        $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
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
        $sql_stats = "CREATE TABLE IF NOT EXISTS $table_stats (
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
        dbDelta($sql_keywords);
        dbDelta($sql_suggestions);
        dbDelta($sql_links);
        dbDelta($sql_stats);
    }

    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'seo-internal-linking',
            false,
            dirname(SIL_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Charger les assets frontend
     */
    public function enqueue_frontend_assets() {
        if (!is_singular()) {
            return;
        }

        wp_enqueue_style(
            'sil-frontend',
            SIL_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SIL_VERSION
        );
    }

    /**
     * Insérer automatiquement les liens dans le contenu
     */
    public function auto_insert_links($content) {
        if (!is_singular() || is_admin()) {
            return $content;
        }

        $post_id = get_the_ID();
        $post_types = $this->get_option('post_types', array('post', 'page'));

        if (!in_array(get_post_type($post_id), $post_types)) {
            return $content;
        }

        $auto_linker = new SIL_Auto_Linker($this->options);
        return $auto_linker->process_content($content, $post_id);
    }

    /**
     * AJAX: Obtenir les suggestions de liens
     */
    public function ajax_get_suggestions() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(__('ID de post invalide.', 'seo-internal-linking'));
        }

        $suggester = new SIL_Link_Suggester($this->options);
        $suggestions = $suggester->get_suggestions($post_id);

        wp_send_json_success($suggestions);
    }

    /**
     * AJAX: Analyser un post
     */
    public function ajax_analyze_post() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(__('ID de post invalide.', 'seo-internal-linking'));
        }

        $analyzer = new SIL_Keyword_Analyzer($this->options);
        $result = $analyzer->analyze_post($post_id);

        if ($result) {
            // Générer les suggestions
            $suggester = new SIL_Link_Suggester($this->options);
            $suggester->generate_suggestions($post_id);

            wp_send_json_success(array(
                'keywords' => $result['keywords'],
                'message' => __('Analyse terminée avec succès.', 'seo-internal-linking')
            ));
        } else {
            wp_send_json_error(__('Erreur lors de l\'analyse.', 'seo-internal-linking'));
        }
    }

    /**
     * AJAX: Insérer un lien
     */
    public function ajax_insert_link() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
        $anchor = isset($_POST['anchor']) ? sanitize_text_field($_POST['anchor']) : '';
        $suggestion_id = isset($_POST['suggestion_id']) ? intval($_POST['suggestion_id']) : 0;

        if (!$source_id || !$target_id || !$anchor) {
            wp_send_json_error(__('Paramètres manquants.', 'seo-internal-linking'));
        }

        $auto_linker = new SIL_Auto_Linker($this->options);
        $result = $auto_linker->insert_link_manually($source_id, $target_id, $anchor, $suggestion_id);

        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: Insérer plusieurs liens en masse
     */
    public function ajax_bulk_insert_links() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $links = isset($_POST['links']) ? $_POST['links'] : array();

        if (empty($links) || !is_array($links)) {
            wp_send_json_error(__('Aucun lien à insérer.', 'seo-internal-linking'));
        }

        // Sanitize les données
        $clean_links = array();
        foreach ($links as $link) {
            $clean_links[] = array(
                'source_id' => intval($link['source_id']),
                'target_id' => intval($link['target_id']),
                'anchor' => sanitize_text_field($link['anchor']),
                'suggestion_id' => isset($link['suggestion_id']) ? intval($link['suggestion_id']) : 0
            );
        }

        $auto_linker = new SIL_Auto_Linker($this->options);
        $results = $auto_linker->insert_links_bulk($clean_links);

        wp_send_json_success(array(
            'success_count' => $results['success'],
            'failed_count' => $results['failed'],
            'message' => sprintf(
                __('%d lien(s) inséré(s) avec succès, %d échec(s).', 'seo-internal-linking'),
                $results['success'],
                $results['failed']
            ),
            'details' => $results['details']
        ));
    }

    /**
     * AJAX: Analyse en masse
     */
    public function ajax_bulk_analyze() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = 10;

        $post_types = $this->get_option('post_types', array('post', 'page'));

        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (empty($posts)) {
            wp_send_json_success(array(
                'completed' => true,
                'message' => __('Analyse complète terminée.', 'seo-internal-linking')
            ));
        }

        $analyzer = new SIL_Keyword_Analyzer($this->options);
        $suggester = new SIL_Link_Suggester($this->options);
        $analyzed = 0;

        foreach ($posts as $post) {
            $analyzer->analyze_post($post->ID);
            $suggester->generate_suggestions($post->ID);
            $analyzed++;
        }

        $total = wp_count_posts('post')->publish + wp_count_posts('page')->publish;
        $progress = min(100, round(($offset + $analyzed) / $total * 100));

        wp_send_json_success(array(
            'completed' => false,
            'analyzed' => $analyzed,
            'offset' => $offset + $limit,
            'progress' => $progress,
            'message' => sprintf(__('%d articles analysés...', 'seo-internal-linking'), $offset + $analyzed)
        ));
    }

    /**
     * Exécuter l'analyse quotidienne
     */
    public function run_daily_analysis() {
        $analyzer = new SIL_Keyword_Analyzer($this->options);
        $suggester = new SIL_Link_Suggester($this->options);
        $stats = new SIL_Statistics($this->options);

        // Analyser les nouveaux posts
        $recent_posts = get_posts(array(
            'post_type' => $this->get_option('post_types', array('post', 'page')),
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'date_query' => array(
                array(
                    'after' => '1 day ago'
                )
            )
        ));

        foreach ($recent_posts as $post) {
            $analyzer->analyze_post($post->ID);
            $suggester->generate_suggestions($post->ID);
        }

        // Mettre à jour les statistiques
        $stats->update_all_statistics();
    }

    /**
     * AJAX: Exécuter le diagnostic
     */
    public function ajax_run_diagnostic() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $results = SIL_Diagnostic::run_all();
        wp_send_json_success($results);
    }

    /**
     * AJAX: Réparer les tables et options
     */
    public function ajax_fix_tables() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        // Recréer les tables
        $table_results = SIL_Diagnostic::recreate_tables();

        // Réparer les options
        $options = SIL_Diagnostic::fix_options();

        wp_send_json_success(array(
            'tables' => $table_results,
            'options' => $options,
            'message' => __('Réparation effectuée avec succès !', 'seo-internal-linking')
        ));
    }
}

/**
 * Initialiser le plugin
 */
function seo_internal_linking_init() {
    return SEO_Internal_Linking::get_instance();
}

// Démarrer le plugin
add_action('plugins_loaded', 'seo_internal_linking_init');
