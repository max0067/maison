<?php
/**
 * Classe d'administration du plugin
 *
 * Gère l'interface d'administration WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_Admin {

    /**
     * Instance du plugin principal
     */
    private $plugin;

    /**
     * Options du plugin
     */
    private $options;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->plugin = SEO_Internal_Linking::get_instance();
        $this->options = get_option('sil_options', array());

        $this->init_hooks();
    }

    /**
     * Initialiser les hooks admin
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Meta box dans l'éditeur
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // Colonne personnalisée dans la liste des posts
        add_filter('manage_posts_columns', array($this, 'add_link_column'));
        add_filter('manage_pages_columns', array($this, 'add_link_column'));
        add_action('manage_posts_custom_column', array($this, 'render_link_column'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'render_link_column'), 10, 2);

        // Actions AJAX supplémentaires
        add_action('wp_ajax_sil_dismiss_suggestion', array($this, 'ajax_dismiss_suggestion'));
        add_action('wp_ajax_sil_export_csv', array($this, 'ajax_export_csv'));
        add_action('wp_ajax_sil_scan_links', array($this, 'ajax_scan_links'));
    }

    /**
     * Ajouter le menu admin
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Maillage Interne', 'seo-internal-linking'),
            __('Maillage SEO', 'seo-internal-linking'),
            'manage_options',
            'seo-internal-linking',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-links',
            30
        );

        // Sous-menu Dashboard
        add_submenu_page(
            'seo-internal-linking',
            __('Tableau de bord', 'seo-internal-linking'),
            __('Tableau de bord', 'seo-internal-linking'),
            'manage_options',
            'seo-internal-linking',
            array($this, 'render_dashboard_page')
        );

        // Sous-menu Suggestions
        add_submenu_page(
            'seo-internal-linking',
            __('Suggestions de liens', 'seo-internal-linking'),
            __('Suggestions', 'seo-internal-linking'),
            'edit_posts',
            'sil-suggestions',
            array($this, 'render_suggestions_page')
        );

        // Sous-menu Articles orphelins
        add_submenu_page(
            'seo-internal-linking',
            __('Articles orphelins', 'seo-internal-linking'),
            __('Orphelins', 'seo-internal-linking'),
            'edit_posts',
            'sil-orphans',
            array($this, 'render_orphans_page')
        );

        // Sous-menu Statistiques
        add_submenu_page(
            'seo-internal-linking',
            __('Statistiques', 'seo-internal-linking'),
            __('Statistiques', 'seo-internal-linking'),
            'manage_options',
            'sil-statistics',
            array($this, 'render_statistics_page')
        );

        // Sous-menu Générateur d'articles
        add_submenu_page(
            'seo-internal-linking',
            __('Générateur IA', 'seo-internal-linking'),
            __('Générateur IA', 'seo-internal-linking'),
            'edit_posts',
            'sil-generator',
            array($this, 'render_generator_page')
        );

        // Sous-menu Réglages
        add_submenu_page(
            'seo-internal-linking',
            __('Réglages', 'seo-internal-linking'),
            __('Réglages', 'seo-internal-linking'),
            'manage_options',
            'sil-settings',
            array($this, 'render_settings_page')
        );

        // Sous-menu Diagnostic
        add_submenu_page(
            'seo-internal-linking',
            __('Diagnostic', 'seo-internal-linking'),
            __('Diagnostic', 'seo-internal-linking'),
            'manage_options',
            'sil-diagnostic',
            array($this, 'render_diagnostic_page')
        );
    }

    /**
     * Enregistrer les réglages
     */
    public function register_settings() {
        register_setting('sil_options_group', 'sil_options', array($this, 'sanitize_options'));

        // Section générale
        add_settings_section(
            'sil_general_section',
            __('Paramètres généraux', 'seo-internal-linking'),
            array($this, 'render_general_section'),
            'sil-settings'
        );

        // Champs de paramètres
        add_settings_field(
            'auto_link_enabled',
            __('Liens automatiques', 'seo-internal-linking'),
            array($this, 'render_checkbox_field'),
            'sil-settings',
            'sil_general_section',
            array(
                'id' => 'auto_link_enabled',
                'description' => __('Insérer automatiquement des liens internes dans le contenu', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'max_links_per_post',
            __('Nombre max de liens', 'seo-internal-linking'),
            array($this, 'render_number_field'),
            'sil-settings',
            'sil_general_section',
            array(
                'id' => 'max_links_per_post',
                'min' => 1,
                'max' => 20,
                'description' => __('Nombre maximum de liens auto-insérés par article', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'min_keyword_length',
            __('Longueur min des mots-clés', 'seo-internal-linking'),
            array($this, 'render_number_field'),
            'sil-settings',
            'sil_general_section',
            array(
                'id' => 'min_keyword_length',
                'min' => 2,
                'max' => 10,
                'description' => __('Longueur minimale des mots-clés à analyser', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'post_types',
            __('Types de contenu', 'seo-internal-linking'),
            array($this, 'render_post_types_field'),
            'sil-settings',
            'sil_general_section'
        );

        add_settings_field(
            'excluded_words',
            __('Mots exclus', 'seo-internal-linking'),
            array($this, 'render_textarea_field'),
            'sil-settings',
            'sil_general_section',
            array(
                'id' => 'excluded_words',
                'description' => __('Mots à ignorer lors de l\'analyse (séparés par des virgules)', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'open_in_new_tab',
            __('Ouvrir dans nouvel onglet', 'seo-internal-linking'),
            array($this, 'render_checkbox_field'),
            'sil-settings',
            'sil_general_section',
            array(
                'id' => 'open_in_new_tab',
                'description' => __('Ouvrir les liens dans un nouvel onglet', 'seo-internal-linking')
            )
        );

        // Section OpenAI
        add_settings_section(
            'sil_openai_section',
            __('Paramètres OpenAI (Générateur IA)', 'seo-internal-linking'),
            array($this, 'render_openai_section'),
            'sil-settings'
        );

        add_settings_field(
            'openai_api_key',
            __('Clé API OpenAI', 'seo-internal-linking'),
            array($this, 'render_password_field'),
            'sil-settings',
            'sil_openai_section',
            array(
                'id' => 'openai_api_key',
                'description' => __('Votre clé API OpenAI (commence par sk-...)', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'openai_model',
            __('Modèle GPT', 'seo-internal-linking'),
            array($this, 'render_select_field'),
            'sil-settings',
            'sil_openai_section',
            array(
                'id' => 'openai_model',
                'options' => array(
                    'gpt-4o-mini' => 'GPT-4o Mini (rapide, économique)',
                    'gpt-4o' => 'GPT-4o (meilleure qualité)',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo (le plus économique)'
                ),
                'description' => __('Modèle à utiliser pour la génération de contenu', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'openai_custom_prompt',
            __('Prompt personnalisé', 'seo-internal-linking'),
            array($this, 'render_textarea_field'),
            'sil-settings',
            'sil_openai_section',
            array(
                'id' => 'openai_custom_prompt',
                'rows' => 10,
                'description' => __('Prompt personnalisé pour la génération d\'articles. Utilisez {keyword} pour insérer le mot-clé. Laissez vide pour utiliser le prompt par défaut.', 'seo-internal-linking')
            )
        );

        add_settings_field(
            'openai_image_prompt',
            __('Prompt pour les images', 'seo-internal-linking'),
            array($this, 'render_textarea_field'),
            'sil-settings',
            'sil_openai_section',
            array(
                'id' => 'openai_image_prompt',
                'rows' => 3,
                'description' => __('Prompt pour DALL-E. Utilisez {keyword} et {title}. Laissez vide pour le prompt par défaut.', 'seo-internal-linking')
            )
        );
    }

    /**
     * Charger les assets admin
     */
    public function enqueue_admin_assets($hook) {
        // Charger sur les pages du plugin uniquement
        $is_plugin_page = (
            strpos($hook, 'seo-internal-linking') !== false ||
            strpos($hook, 'sil-') !== false ||
            strpos($hook, '_page_sil') !== false ||
            $hook === 'post.php' ||
            $hook === 'post-new.php'
        );

        if (!$is_plugin_page) {
            return;
        }

        wp_enqueue_style(
            'sil-admin',
            SIL_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SIL_VERSION
        );

        wp_enqueue_script(
            'sil-admin',
            SIL_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            SIL_VERSION,
            true
        );

        wp_localize_script('sil-admin', 'silAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sil_nonce'),
            'strings' => array(
                'analyzing' => __('Analyse en cours...', 'seo-internal-linking'),
                'analyzed' => __('Analyse terminée !', 'seo-internal-linking'),
                'error' => __('Une erreur est survenue.', 'seo-internal-linking'),
                'confirm_insert' => __('Voulez-vous insérer ce lien ?', 'seo-internal-linking'),
                'link_inserted' => __('Lien inséré avec succès !', 'seo-internal-linking'),
                'scanning' => __('Scan des liens en cours...', 'seo-internal-linking')
            )
        ));

        // Chart.js pour les graphiques
        if (strpos($hook, 'sil-statistics') !== false) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '4.4.0',
                true
            );
        }
    }

    /**
     * Afficher la page tableau de bord
     */
    public function render_dashboard_page() {
        $stats = new SIL_Statistics($this->options);
        $global_stats = $stats->get_global_stats();
        $top_linked = $stats->get_top_linked_posts(5);
        $top_keywords = $stats->get_top_keywords(10);

        $suggester = new SIL_Link_Suggester($this->options);
        $orphans = $suggester->get_orphan_posts(5);
        ?>
        <div class="wrap sil-admin">
            <h1><?php _e('Maillage Interne SEO', 'seo-internal-linking'); ?></h1>

            <div class="sil-dashboard">
                <!-- Statistiques principales -->
                <div class="sil-stats-grid">
                    <div class="sil-stat-card">
                        <div class="sil-stat-number"><?php echo $global_stats['total_analyzed']; ?></div>
                        <div class="sil-stat-label"><?php _e('Articles analysés', 'seo-internal-linking'); ?></div>
                    </div>
                    <div class="sil-stat-card">
                        <div class="sil-stat-number"><?php echo $global_stats['total_links']; ?></div>
                        <div class="sil-stat-label"><?php _e('Liens internes', 'seo-internal-linking'); ?></div>
                    </div>
                    <div class="sil-stat-card sil-stat-warning">
                        <div class="sil-stat-number"><?php echo $global_stats['orphan_posts']; ?></div>
                        <div class="sil-stat-label"><?php _e('Articles orphelins', 'seo-internal-linking'); ?></div>
                    </div>
                    <div class="sil-stat-card">
                        <div class="sil-stat-number"><?php echo $global_stats['pending_suggestions']; ?></div>
                        <div class="sil-stat-label"><?php _e('Suggestions en attente', 'seo-internal-linking'); ?></div>
                    </div>
                    <div class="sil-stat-card">
                        <div class="sil-stat-number"><?php echo $global_stats['avg_seo_score']; ?>%</div>
                        <div class="sil-stat-label"><?php _e('Score SEO moyen', 'seo-internal-linking'); ?></div>
                    </div>
                    <div class="sil-stat-card">
                        <div class="sil-stat-number"><?php echo $global_stats['total_keywords']; ?></div>
                        <div class="sil-stat-label"><?php _e('Mots-clés indexés', 'seo-internal-linking'); ?></div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="sil-actions-bar">
                    <h2><?php _e('Actions rapides', 'seo-internal-linking'); ?></h2>
                    <button type="button" class="button button-primary" id="sil-bulk-analyze">
                        <?php _e('Analyser tous les articles', 'seo-internal-linking'); ?>
                    </button>
                    <button type="button" class="button" id="sil-scan-links">
                        <?php _e('Scanner les liens existants', 'seo-internal-linking'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=sil-suggestions'); ?>" class="button">
                        <?php _e('Voir les suggestions', 'seo-internal-linking'); ?>
                    </a>
                    <div id="sil-progress-bar" style="display:none;">
                        <div class="sil-progress">
                            <div class="sil-progress-inner" style="width: 0%"></div>
                        </div>
                        <span class="sil-progress-text"></span>
                    </div>
                </div>

                <div class="sil-dashboard-row">
                    <!-- Articles les mieux liés -->
                    <div class="sil-dashboard-widget">
                        <h3><?php _e('Articles les mieux liés', 'seo-internal-linking'); ?></h3>
                        <?php if (!empty($top_linked)) : ?>
                            <table class="sil-mini-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Article', 'seo-internal-linking'); ?></th>
                                        <th><?php _e('Liens entrants', 'seo-internal-linking'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_linked as $post) : ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo get_edit_post_link($post->post_id); ?>">
                                                    <?php echo esc_html(wp_trim_words($post->post_title, 6)); ?>
                                                </a>
                                            </td>
                                            <td><strong><?php echo $post->incoming_links_count; ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p><?php _e('Aucune donnée disponible.', 'seo-internal-linking'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Articles orphelins -->
                    <div class="sil-dashboard-widget">
                        <h3><?php _e('Articles orphelins récents', 'seo-internal-linking'); ?></h3>
                        <?php if (!empty($orphans)) : ?>
                            <table class="sil-mini-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Article', 'seo-internal-linking'); ?></th>
                                        <th><?php _e('Action', 'seo-internal-linking'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orphans as $post) : ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                                    <?php echo esc_html(wp_trim_words($post->post_title, 6)); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=sil-suggestions&post_id=' . $post->ID); ?>" class="button button-small">
                                                    <?php _e('Voir suggestions', 'seo-internal-linking'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><a href="<?php echo admin_url('admin.php?page=sil-orphans'); ?>"><?php _e('Voir tous les orphelins', 'seo-internal-linking'); ?> &rarr;</a></p>
                        <?php else : ?>
                            <p class="sil-success"><?php _e('Aucun article orphelin !', 'seo-internal-linking'); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Mots-clés populaires -->
                    <div class="sil-dashboard-widget">
                        <h3><?php _e('Mots-clés populaires', 'seo-internal-linking'); ?></h3>
                        <?php if (!empty($top_keywords)) : ?>
                            <div class="sil-keywords-cloud">
                                <?php foreach ($top_keywords as $kw) :
                                    $size = min(20, max(12, 12 + ($kw->post_count * 2)));
                                ?>
                                    <span class="sil-keyword" style="font-size: <?php echo $size; ?>px;">
                                        <?php echo esc_html($kw->keyword); ?>
                                        <small>(<?php echo $kw->post_count; ?>)</small>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p><?php _e('Aucun mot-clé indexé.', 'seo-internal-linking'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Afficher la page des suggestions
     */
    public function render_suggestions_page() {
        $suggester = new SIL_Link_Suggester($this->options);

        // Filtrer par post si demandé
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

        if ($post_id) {
            $suggestions = $suggester->get_suggestions($post_id, 'all');
            $post = get_post($post_id);
        } else {
            $suggestions = $suggester->get_all_pending_suggestions(100);
            $post = null;
        }

        // Compter les suggestions en attente
        $pending_count = 0;
        foreach ($suggestions as $s) {
            if ($s->status === 'pending') $pending_count++;
        }
        ?>
        <div class="wrap sil-admin">
            <h1>
                <?php _e('Suggestions de liens', 'seo-internal-linking'); ?>
                <?php if ($post) : ?>
                    - <?php echo esc_html($post->post_title); ?>
                <?php endif; ?>
            </h1>

            <?php if ($post_id) : ?>
                <p><a href="<?php echo admin_url('admin.php?page=sil-suggestions'); ?>">&larr; <?php _e('Voir toutes les suggestions', 'seo-internal-linking'); ?></a></p>
            <?php endif; ?>

            <?php if (empty($suggestions)) : ?>
                <div class="notice notice-info">
                    <p><?php _e('Aucune suggestion disponible. Lancez une analyse pour générer des suggestions.', 'seo-internal-linking'); ?></p>
                </div>
            <?php else : ?>
                <!-- Filtres -->
                <div class="sil-filters-bar">
                    <label>
                        <?php _e('Statut:', 'seo-internal-linking'); ?>
                        <select id="sil-filter-status">
                            <option value=""><?php _e('Tous', 'seo-internal-linking'); ?></option>
                            <option value="pending"><?php _e('En attente', 'seo-internal-linking'); ?></option>
                            <option value="applied"><?php _e('Appliqué', 'seo-internal-linking'); ?></option>
                            <option value="rejected"><?php _e('Ignoré', 'seo-internal-linking'); ?></option>
                        </select>
                    </label>
                    <label>
                        <?php _e('Score:', 'seo-internal-linking'); ?>
                        <select id="sil-filter-score">
                            <option value=""><?php _e('Tous', 'seo-internal-linking'); ?></option>
                            <option value="high"><?php _e('Élevé (≥70%)', 'seo-internal-linking'); ?></option>
                            <option value="medium"><?php _e('Moyen (40-69%)', 'seo-internal-linking'); ?></option>
                            <option value="low"><?php _e('Faible (<40%)', 'seo-internal-linking'); ?></option>
                        </select>
                    </label>
                    <label>
                        <?php _e('Recherche:', 'seo-internal-linking'); ?>
                        <input type="text" id="sil-filter-search" placeholder="<?php _e('Article, ancre...', 'seo-internal-linking'); ?>">
                    </label>
                    <span id="sil-filtered-info" style="display: none;"></span>
                </div>

                <!-- Barre d'actions en masse -->
                <div class="sil-bulk-actions">
                    <label>
                        <input type="checkbox" id="sil-select-all">
                        <?php _e('Tout sélectionner', 'seo-internal-linking'); ?>
                    </label>
                    <span class="sil-selected-count">(<span id="sil-selected-num">0</span> <?php _e('sélectionné(s)', 'seo-internal-linking'); ?>)</span>

                    <button type="button" class="button button-primary" id="sil-bulk-insert" disabled>
                        <?php _e('Insérer la sélection', 'seo-internal-linking'); ?>
                    </button>
                    <button type="button" class="button" id="sil-bulk-dismiss" disabled>
                        <?php _e('Ignorer la sélection', 'seo-internal-linking'); ?>
                    </button>

                    <span id="sil-bulk-status"></span>
                </div>

                <table class="wp-list-table widefat fixed striped" id="sil-suggestions-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;"><input type="checkbox" id="sil-select-all-header"></th>
                            <th><?php _e('Article source', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Article cible', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Texte d\'ancrage', 'seo-internal-linking'); ?></th>
                            <th style="width: 80px;"><?php _e('Score', 'seo-internal-linking'); ?></th>
                            <th style="width: 100px;"><?php _e('Statut', 'seo-internal-linking'); ?></th>
                            <th style="width: 180px;"><?php _e('Actions', 'seo-internal-linking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suggestions as $suggestion) : ?>
                            <tr data-suggestion-id="<?php echo $suggestion->id; ?>"
                                data-source="<?php echo $suggestion->source_post_id; ?>"
                                data-target="<?php echo $suggestion->target_post_id; ?>"
                                data-anchor="<?php echo esc_attr($suggestion->anchor_text); ?>"
                                data-status="<?php echo $suggestion->status; ?>">
                                <td>
                                    <?php if ($suggestion->status === 'pending') : ?>
                                        <input type="checkbox" class="sil-select-item">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($suggestion->source_post_id); ?>" target="_blank">
                                        <?php echo esc_html($suggestion->source_title ?? get_the_title($suggestion->source_post_id)); ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo get_permalink($suggestion->target_post_id); ?>" target="_blank">
                                        <?php echo esc_html($suggestion->target_title); ?>
                                    </a>
                                </td>
                                <td class="sil-anchor-cell">
                                    <code><?php echo esc_html($suggestion->anchor_text); ?></code>
                                    <?php if ($suggestion->status === 'pending') : ?>
                                        <button type="button" class="button button-small sil-edit-anchor" title="<?php _e('Modifier', 'seo-internal-linking'); ?>">&#9998;</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="sil-score sil-score-<?php echo $this->get_score_class($suggestion->relevance_score); ?>">
                                        <?php echo round($suggestion->relevance_score * 100); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="sil-status sil-status-<?php echo $suggestion->status; ?>">
                                        <?php echo $this->get_status_label($suggestion->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($suggestion->status === 'pending') : ?>
                                        <button type="button" class="button button-small sil-insert-link"
                                                data-source="<?php echo $suggestion->source_post_id; ?>"
                                                data-target="<?php echo $suggestion->target_post_id; ?>"
                                                data-anchor="<?php echo esc_attr($suggestion->anchor_text); ?>"
                                                data-suggestion="<?php echo $suggestion->id; ?>">
                                            <?php _e('Insérer', 'seo-internal-linking'); ?>
                                        </button>
                                        <button type="button" class="button button-small sil-dismiss-suggestion"
                                                data-suggestion="<?php echo $suggestion->id; ?>">
                                            <?php _e('Ignorer', 'seo-internal-linking'); ?>
                                        </button>
                                    <?php elseif ($suggestion->status === 'applied') : ?>
                                        <span class="dashicons dashicons-yes-alt" style="color: green;" title="<?php _e('Appliqué', 'seo-internal-linking'); ?>"></span>
                                    <?php elseif ($suggestion->status === 'rejected') : ?>
                                        <span class="dashicons dashicons-dismiss" style="color: #999;" title="<?php _e('Ignoré', 'seo-internal-linking'); ?>"></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="description">
                    <?php printf(__('Total : %d suggestions (%d en attente)', 'seo-internal-linking'), count($suggestions), $pending_count); ?>
                </p>

                <style>
                .sil-filters-bar {
                    background: #f9f9f9;
                    padding: 12px 15px;
                    margin-bottom: 10px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    gap: 20px;
                    flex-wrap: wrap;
                }
                .sil-filters-bar label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 500;
                }
                .sil-filters-bar select,
                .sil-filters-bar input[type="text"] {
                    padding: 5px 10px;
                }
                .sil-filters-bar input[type="text"] {
                    width: 200px;
                }
                #sil-filtered-info {
                    color: #666;
                    font-style: italic;
                    margin-left: auto;
                }
                .sil-bulk-actions {
                    background: #fff;
                    padding: 15px;
                    margin-bottom: 15px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    flex-wrap: wrap;
                }
                .sil-selected-count {
                    color: #666;
                    margin-right: 10px;
                }
                #sil-bulk-status {
                    margin-left: auto;
                }
                .sil-select-item {
                    width: 18px;
                    height: 18px;
                }
                tr.sil-processing {
                    opacity: 0.5;
                }
                .sil-anchor-cell {
                    position: relative;
                }
                .sil-anchor-cell .sil-edit-anchor {
                    margin-left: 5px;
                    padding: 2px 6px;
                    font-size: 14px;
                }
                .sil-anchor-cell .sil-anchor-input {
                    margin-bottom: 5px;
                }
                .sil-anchor-cell .sil-save-anchor,
                .sil-anchor-cell .sil-cancel-anchor {
                    margin-left: 3px;
                    padding: 2px 6px;
                }
                tr.sil-row-success {
                    background-color: #d4edda !important;
                }
                tr.sil-row-dismissed {
                    background-color: #f8f9fa !important;
                }
                </style>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Afficher la page des articles orphelins
     */
    public function render_orphans_page() {
        $suggester = new SIL_Link_Suggester($this->options);
        $orphans = $suggester->get_orphan_posts(100);
        $low_link_posts = $suggester->get_low_link_posts(2, 50);
        ?>
        <div class="wrap sil-admin">
            <h1><?php _e('Articles orphelins', 'seo-internal-linking'); ?></h1>

            <p class="description">
                <?php _e('Les articles orphelins sont des articles qui n\'ont aucun lien entrant depuis d\'autres articles de votre site.', 'seo-internal-linking'); ?>
            </p>

            <h2><?php _e('Articles sans liens entrants', 'seo-internal-linking'); ?></h2>

            <?php if (empty($orphans)) : ?>
                <div class="notice notice-success">
                    <p><?php _e('Excellent ! Aucun article orphelin trouvé.', 'seo-internal-linking'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Article', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Type', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Date', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Liens sortants', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Actions', 'seo-internal-linking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orphans as $post) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo $post->post_type; ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($post->post_date)); ?></td>
                                <td><?php echo $post->outgoing_links; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sil-suggestions&post_id=' . $post->ID); ?>" class="button button-small">
                                        <?php _e('Voir suggestions', 'seo-internal-linking'); ?>
                                    </a>
                                    <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank">
                                        <?php _e('Voir', 'seo-internal-linking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <h2 style="margin-top: 30px;"><?php _e('Articles avec peu de liens', 'seo-internal-linking'); ?></h2>

            <?php if (empty($low_link_posts)) : ?>
                <p><?php _e('Tous vos articles ont un bon maillage interne.', 'seo-internal-linking'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Article', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Liens sortants', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Liens entrants', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Score SEO', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Actions', 'seo-internal-linking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_link_posts as $post) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo $post->outgoing_links; ?></td>
                                <td><?php echo $post->incoming_links; ?></td>
                                <td>
                                    <span class="sil-score sil-score-<?php echo $this->get_score_class($post->seo_score / 100); ?>">
                                        <?php echo $post->seo_score; ?>%
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sil-suggestions&post_id=' . $post->ID); ?>" class="button button-small">
                                        <?php _e('Améliorer', 'seo-internal-linking'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Afficher la page des statistiques
     */
    public function render_statistics_page() {
        $stats = new SIL_Statistics($this->options);
        $global_stats = $stats->get_global_stats();
        $distribution = $stats->get_links_distribution();
        $history = $stats->get_links_history(30);
        ?>
        <div class="wrap sil-admin">
            <h1><?php _e('Statistiques du maillage interne', 'seo-internal-linking'); ?></h1>

            <div class="sil-stats-overview">
                <div class="sil-stat-box">
                    <h3><?php _e('Vue d\'ensemble', 'seo-internal-linking'); ?></h3>
                    <ul>
                        <li><strong><?php echo $global_stats['total_links']; ?></strong> <?php _e('liens internes total', 'seo-internal-linking'); ?></li>
                        <li><strong><?php echo $global_stats['auto_links']; ?></strong> <?php _e('liens auto-insérés', 'seo-internal-linking'); ?></li>
                        <li><strong><?php echo $global_stats['manual_links']; ?></strong> <?php _e('liens manuels', 'seo-internal-linking'); ?></li>
                        <li><strong><?php echo $global_stats['avg_outgoing_links']; ?></strong> <?php _e('liens sortants en moyenne', 'seo-internal-linking'); ?></li>
                        <li><strong><?php echo $global_stats['avg_incoming_links']; ?></strong> <?php _e('liens entrants en moyenne', 'seo-internal-linking'); ?></li>
                    </ul>
                </div>

                <div class="sil-stat-box">
                    <h3><?php _e('Suggestions', 'seo-internal-linking'); ?></h3>
                    <ul>
                        <li><strong><?php echo $global_stats['pending_suggestions']; ?></strong> <?php _e('en attente', 'seo-internal-linking'); ?></li>
                        <li><strong><?php echo $global_stats['applied_suggestions']; ?></strong> <?php _e('appliquées', 'seo-internal-linking'); ?></li>
                    </ul>
                </div>
            </div>

            <div class="sil-charts-row">
                <div class="sil-chart-container">
                    <h3><?php _e('Distribution des liens sortants', 'seo-internal-linking'); ?></h3>
                    <canvas id="sil-outgoing-chart"></canvas>
                </div>

                <div class="sil-chart-container">
                    <h3><?php _e('Distribution des liens entrants', 'seo-internal-linking'); ?></h3>
                    <canvas id="sil-incoming-chart"></canvas>
                </div>
            </div>

            <div class="sil-chart-container sil-chart-wide">
                <h3><?php _e('Liens créés (30 derniers jours)', 'seo-internal-linking'); ?></h3>
                <canvas id="sil-history-chart"></canvas>
            </div>

            <p>
                <button type="button" class="button" id="sil-export-csv">
                    <?php _e('Exporter en CSV', 'seo-internal-linking'); ?>
                </button>
            </p>

            <script>
            jQuery(document).ready(function($) {
                // Distribution des liens sortants
                if (document.getElementById('sil-outgoing-chart')) {
                    new Chart(document.getElementById('sil-outgoing-chart'), {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo json_encode(array_column($distribution['outgoing'], 'range_label')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($distribution['outgoing'], 'count')); ?>,
                                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#28a745', '#6f42c1']
                            }]
                        }
                    });
                }

                // Distribution des liens entrants
                if (document.getElementById('sil-incoming-chart')) {
                    new Chart(document.getElementById('sil-incoming-chart'), {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo json_encode(array_column($distribution['incoming'], 'range_label')); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_column($distribution['incoming'], 'count')); ?>,
                                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#28a745', '#6f42c1']
                            }]
                        }
                    });
                }

                // Historique
                if (document.getElementById('sil-history-chart')) {
                    new Chart(document.getElementById('sil-history-chart'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_column($history, 'date')); ?>,
                            datasets: [{
                                label: '<?php _e('Liens créés', 'seo-internal-linking'); ?>',
                                data: <?php echo json_encode(array_column($history, 'count')); ?>,
                                borderColor: '#0073aa',
                                tension: 0.3,
                                fill: false
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            });
            </script>
        </div>
        <?php
    }

    /**
     * Afficher la page des réglages
     */
    public function render_settings_page() {
        ?>
        <div class="wrap sil-admin">
            <h1><?php _e('Réglages du maillage interne', 'seo-internal-linking'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('sil_options_group');
                do_settings_sections('sil-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render de la section générale
     */
    public function render_general_section() {
        echo '<p>' . __('Configurez le comportement du plugin de maillage interne.', 'seo-internal-linking') . '</p>';
    }

    /**
     * Render champ checkbox
     */
    public function render_checkbox_field($args) {
        $value = isset($this->options[$args['id']]) ? $this->options[$args['id']] : false;
        ?>
        <label>
            <input type="checkbox" name="sil_options[<?php echo $args['id']; ?>]" value="1" <?php checked($value, true); ?>>
            <?php echo $args['description']; ?>
        </label>
        <?php
    }

    /**
     * Render champ nombre
     */
    public function render_number_field($args) {
        $value = isset($this->options[$args['id']]) ? $this->options[$args['id']] : 5;
        ?>
        <input type="number"
               name="sil_options[<?php echo $args['id']; ?>]"
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo $args['min']; ?>"
               max="<?php echo $args['max']; ?>"
               class="small-text">
        <p class="description"><?php echo $args['description']; ?></p>
        <?php
    }

    /**
     * Render champ textarea
     */
    public function render_textarea_field($args) {
        $value = isset($this->options[$args['id']]) ? $this->options[$args['id']] : '';
        $rows = isset($args['rows']) ? $args['rows'] : 3;
        ?>
        <textarea name="sil_options[<?php echo $args['id']; ?>]"
                  rows="<?php echo $rows; ?>"
                  cols="50"
                  class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php echo $args['description']; ?></p>
        <?php
    }

    /**
     * Render champ password
     */
    public function render_password_field($args) {
        $value = isset($this->options[$args['id']]) ? $this->options[$args['id']] : '';
        $masked = !empty($value) ? str_repeat('*', 20) . substr($value, -4) : '';
        ?>
        <input type="password"
               name="sil_options[<?php echo $args['id']; ?>]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               autocomplete="new-password">
        <?php if (!empty($value)) : ?>
            <span class="description" style="color: green;">&#10004; <?php _e('Configurée', 'seo-internal-linking'); ?></span>
        <?php endif; ?>
        <p class="description"><?php echo $args['description']; ?></p>
        <?php
    }

    /**
     * Render champ select
     */
    public function render_select_field($args) {
        $value = isset($this->options[$args['id']]) ? $this->options[$args['id']] : '';
        ?>
        <select name="sil_options[<?php echo $args['id']; ?>]">
            <?php foreach ($args['options'] as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo $args['description']; ?></p>
        <?php
    }

    /**
     * Render de la section OpenAI
     */
    public function render_openai_section() {
        echo '<p>' . __('Configurez OpenAI pour générer automatiquement des articles avec DALL-E pour les images.', 'seo-internal-linking') . '</p>';
        echo '<p><a href="https://platform.openai.com/api-keys" target="_blank">' . __('Obtenir une clé API OpenAI', 'seo-internal-linking') . ' &rarr;</a></p>';
    }

    /**
     * Render champ types de post
     */
    public function render_post_types_field() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $selected = isset($this->options['post_types']) ? $this->options['post_types'] : array('post', 'page');

        foreach ($post_types as $pt) {
            if ($pt->name === 'attachment') continue;
            ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox"
                       name="sil_options[post_types][]"
                       value="<?php echo $pt->name; ?>"
                       <?php checked(in_array($pt->name, $selected)); ?>>
                <?php echo $pt->label; ?>
            </label>
            <?php
        }
    }

    /**
     * Sanitize les options
     */
    public function sanitize_options($input) {
        $sanitized = array();

        $sanitized['auto_link_enabled'] = !empty($input['auto_link_enabled']);
        $sanitized['max_links_per_post'] = absint($input['max_links_per_post']);
        $sanitized['min_keyword_length'] = absint($input['min_keyword_length']);
        $sanitized['excluded_words'] = sanitize_textarea_field($input['excluded_words']);
        $sanitized['open_in_new_tab'] = !empty($input['open_in_new_tab']);
        $sanitized['post_types'] = isset($input['post_types']) ? array_map('sanitize_key', $input['post_types']) : array('post', 'page');

        // OpenAI settings
        $sanitized['openai_api_key'] = isset($input['openai_api_key']) ? sanitize_text_field($input['openai_api_key']) : '';
        $sanitized['openai_model'] = isset($input['openai_model']) ? sanitize_key($input['openai_model']) : 'gpt-4o-mini';
        $sanitized['openai_custom_prompt'] = isset($input['openai_custom_prompt']) ? sanitize_textarea_field($input['openai_custom_prompt']) : '';
        $sanitized['openai_image_prompt'] = isset($input['openai_image_prompt']) ? sanitize_textarea_field($input['openai_image_prompt']) : '';

        return $sanitized;
    }

    /**
     * Ajouter les meta boxes
     */
    public function add_meta_boxes() {
        $post_types = isset($this->options['post_types']) ? $this->options['post_types'] : array('post', 'page');

        foreach ($post_types as $post_type) {
            add_meta_box(
                'sil_link_suggestions',
                __('Maillage Interne SEO', 'seo-internal-linking'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render la meta box
     */
    public function render_meta_box($post) {
        $stats = new SIL_Statistics($this->options);
        $post_stats = $stats->get_post_stats($post->ID);
        ?>
        <div class="sil-metabox">
            <div class="sil-metabox-stats">
                <p>
                    <strong><?php _e('Liens sortants:', 'seo-internal-linking'); ?></strong>
                    <?php echo $post_stats['internal_links_count']; ?>
                </p>
                <p>
                    <strong><?php _e('Liens entrants:', 'seo-internal-linking'); ?></strong>
                    <?php echo $post_stats['incoming_links_count']; ?>
                </p>
                <p>
                    <strong><?php _e('Score SEO:', 'seo-internal-linking'); ?></strong>
                    <span class="sil-score sil-score-<?php echo $this->get_score_class($post_stats['seo_score'] / 100); ?>">
                        <?php echo $post_stats['seo_score']; ?>%
                    </span>
                </p>
            </div>

            <button type="button" class="button sil-analyze-post" data-post-id="<?php echo $post->ID; ?>">
                <?php _e('Analyser cet article', 'seo-internal-linking'); ?>
            </button>

            <a href="<?php echo admin_url('admin.php?page=sil-suggestions&post_id=' . $post->ID); ?>" class="button" style="margin-top: 5px; display: block; text-align: center;">
                <?php _e('Voir les suggestions', 'seo-internal-linking'); ?>
            </a>

            <div class="sil-metabox-result" style="display: none; margin-top: 10px;"></div>
        </div>
        <?php
    }

    /**
     * Ajouter colonne dans la liste des posts
     */
    public function add_link_column($columns) {
        $columns['sil_links'] = __('Maillage', 'seo-internal-linking');
        return $columns;
    }

    /**
     * Render la colonne de liens
     */
    public function render_link_column($column, $post_id) {
        if ($column !== 'sil_links') {
            return;
        }

        global $wpdb;
        $stats_table = $wpdb->prefix . 'sil_statistics';

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT internal_links_count, incoming_links_count, seo_score FROM $stats_table WHERE post_id = %d",
            $post_id
        ));

        if ($stats) {
            echo '<span title="' . __('Sortants / Entrants', 'seo-internal-linking') . '">';
            echo $stats->internal_links_count . ' / ' . $stats->incoming_links_count;
            echo '</span>';
        } else {
            echo '-';
        }
    }

    /**
     * AJAX: Ignorer une suggestion
     */
    public function ajax_dismiss_suggestion() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $suggestion_id = isset($_POST['suggestion_id']) ? intval($_POST['suggestion_id']) : 0;

        if (!$suggestion_id) {
            wp_send_json_error(__('ID invalide.', 'seo-internal-linking'));
        }

        $suggester = new SIL_Link_Suggester($this->options);
        $suggester->reject_suggestion($suggestion_id);

        wp_send_json_success(__('Suggestion ignorée.', 'seo-internal-linking'));
    }

    /**
     * AJAX: Exporter CSV
     */
    public function ajax_export_csv() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $stats = new SIL_Statistics($this->options);
        $csv = $stats->export_csv();

        wp_send_json_success(array('csv' => $csv));
    }

    /**
     * AJAX: Scanner les liens existants
     */
    public function ajax_scan_links() {
        check_ajax_referer('sil_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission refusée.', 'seo-internal-linking'));
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $auto_linker = new SIL_Auto_Linker($this->options);
        $result = $auto_linker->scan_all_existing_links(20, $offset);

        $total = wp_count_posts('post')->publish + wp_count_posts('page')->publish;
        $progress = min(100, round($result['offset'] / $total * 100));

        if ($result['scanned'] == 0) {
            wp_send_json_success(array(
                'completed' => true,
                'message' => __('Scan terminé !', 'seo-internal-linking')
            ));
        }

        wp_send_json_success(array(
            'completed' => false,
            'offset' => $result['offset'],
            'links_found' => $result['links_found'],
            'progress' => $progress,
            'message' => sprintf(__('%d articles scannés, %d liens trouvés...', 'seo-internal-linking'), $result['offset'], $result['links_found'])
        ));
    }

    /**
     * Obtenir la classe CSS du score
     */
    private function get_score_class($score) {
        if ($score >= 0.7) return 'high';
        if ($score >= 0.4) return 'medium';
        return 'low';
    }

    /**
     * Obtenir le label du statut
     */
    private function get_status_label($status) {
        $labels = array(
            'pending' => __('En attente', 'seo-internal-linking'),
            'applied' => __('Appliqué', 'seo-internal-linking'),
            'rejected' => __('Ignoré', 'seo-internal-linking')
        );

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Afficher la page de diagnostic
     */
    public function render_diagnostic_page() {
        $diagnostic = SIL_Diagnostic::run_all();
        ?>
        <div class="wrap sil-admin">
            <h1><?php _e('Diagnostic du plugin', 'seo-internal-linking'); ?></h1>

            <div class="notice notice-info">
                <p><?php _e('Cette page vous permet de vérifier que le plugin fonctionne correctement et de réparer les problèmes éventuels.', 'seo-internal-linking'); ?></p>
            </div>

            <!-- Boutons d'action -->
            <div class="sil-actions-bar">
                <button type="button" class="button button-primary" id="sil-fix-tables">
                    <?php _e('Réparer les tables et options', 'seo-internal-linking'); ?>
                </button>
                <button type="button" class="button" id="sil-refresh-diagnostic">
                    <?php _e('Actualiser le diagnostic', 'seo-internal-linking'); ?>
                </button>
                <span id="sil-diagnostic-status"></span>
            </div>

            <!-- État des tables -->
            <div class="sil-diagnostic-section">
                <h2><?php _e('État des tables de la base de données', 'seo-internal-linking'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Table', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Existe', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Nombre d\'entrées', 'seo-internal-linking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnostic['tables'] as $table => $info) : ?>
                            <tr>
                                <td><code><?php echo esc_html($table); ?></code></td>
                                <td>
                                    <?php if ($info['exists']) : ?>
                                        <span style="color: green;">&#10004; Oui</span>
                                    <?php else : ?>
                                        <span style="color: red;">&#10008; Non</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $info['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Articles disponibles -->
            <div class="sil-diagnostic-section">
                <h2><?php _e('Articles disponibles', 'seo-internal-linking'); ?></h2>

                <h3><?php _e('Types de posts configurés', 'seo-internal-linking'); ?></h3>
                <p>
                    <?php if (!empty($diagnostic['posts']['configured_types'])) : ?>
                        <strong><?php echo implode(', ', $diagnostic['posts']['configured_types']); ?></strong>
                    <?php else : ?>
                        <span style="color: red;"><?php _e('Aucun type configuré ! Cliquez sur "Réparer" ci-dessus.', 'seo-internal-linking'); ?></span>
                    <?php endif; ?>
                </p>

                <h3><?php _e('Nombre d\'articles par type', 'seo-internal-linking'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Type', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Nombre d\'articles publiés', 'seo-internal-linking'); ?></th>
                            <th><?php _e('Configuré', 'seo-internal-linking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diagnostic['posts']['available_types'] as $type => $info) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($info['label']); ?></strong> (<?php echo $type; ?>)</td>
                                <td><?php echo $info['count']; ?></td>
                                <td>
                                    <?php if (in_array($type, $diagnostic['posts']['configured_types'])) : ?>
                                        <span style="color: green;">&#10004; Oui</span>
                                    <?php else : ?>
                                        <span style="color: orange;">&#10008; Non</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p><strong><?php _e('Total d\'articles à analyser:', 'seo-internal-linking'); ?></strong> <?php echo $diagnostic['posts']['total']; ?></p>

                <?php if (!empty($diagnostic['posts']['sample_posts'])) : ?>
                    <h3><?php _e('Exemples d\'articles trouvés', 'seo-internal-linking'); ?></h3>
                    <ul>
                        <?php foreach ($diagnostic['posts']['sample_posts'] as $post) : ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($post['ID']); ?>">
                                    <?php echo esc_html($post['title']); ?>
                                </a>
                                (ID: <?php echo $post['ID']; ?>, Type: <?php echo $post['type']; ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="notice notice-warning">
                        <p><?php _e('Aucun article trouvé ! Vérifiez que vous avez des articles publiés.', 'seo-internal-linking'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Options -->
            <div class="sil-diagnostic-section">
                <h2><?php _e('Options du plugin', 'seo-internal-linking'); ?></h2>
                <pre style="background: #f5f5f5; padding: 15px; overflow: auto;"><?php print_r($diagnostic['options']['merged']); ?></pre>
            </div>

            <!-- Permissions -->
            <div class="sil-diagnostic-section">
                <h2><?php _e('Permissions', 'seo-internal-linking'); ?></h2>
                <ul>
                    <li>
                        <?php _e('Peut gérer les options:', 'seo-internal-linking'); ?>
                        <?php echo $diagnostic['permissions']['can_manage_options'] ? '<span style="color:green;">Oui</span>' : '<span style="color:red;">Non</span>'; ?>
                    </li>
                    <li>
                        <?php _e('Peut éditer les posts:', 'seo-internal-linking'); ?>
                        <?php echo $diagnostic['permissions']['can_edit_posts'] ? '<span style="color:green;">Oui</span>' : '<span style="color:red;">Non</span>'; ?>
                    </li>
                </ul>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#sil-fix-tables').on('click', function() {
                    var $btn = $(this);
                    var $status = $('#sil-diagnostic-status');

                    $btn.prop('disabled', true);
                    $status.text('<?php _e('Réparation en cours...', 'seo-internal-linking'); ?>');

                    $.ajax({
                        url: silAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'sil_fix_tables',
                            nonce: silAdmin.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $status.html('<span style="color:green;">' + response.data.message + '</span>');
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                $status.html('<span style="color:red;">' + response.data + '</span>');
                            }
                            $btn.prop('disabled', false);
                        },
                        error: function() {
                            $status.html('<span style="color:red;"><?php _e('Erreur lors de la réparation.', 'seo-internal-linking'); ?></span>');
                            $btn.prop('disabled', false);
                        }
                    });
                });

                $('#sil-refresh-diagnostic').on('click', function() {
                    location.reload();
                });
            });
            </script>

            <style>
            .sil-diagnostic-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
            }
            .sil-diagnostic-section h2 {
                margin-top: 0;
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
            }
            .sil-diagnostic-section h3 {
                margin-top: 20px;
            }
            </style>
        </div>
        <?php
    }

    /**
     * Afficher la page du générateur d'articles
     */
    public function render_generator_page() {
        $generator = new SIL_OpenAI_Generator($this->options);
        $is_configured = $generator->is_configured();
        ?>
        <div class="wrap sil-admin">
            <h1><?php _e('Générateur d\'articles IA', 'seo-internal-linking'); ?></h1>

            <?php if (!$is_configured) : ?>
                <div class="notice notice-warning">
                    <p>
                        <strong><?php _e('Configuration requise !', 'seo-internal-linking'); ?></strong>
                        <?php _e('Vous devez configurer votre clé API OpenAI dans les', 'seo-internal-linking'); ?>
                        <a href="<?php echo admin_url('admin.php?page=sil-settings'); ?>"><?php _e('Réglages', 'seo-internal-linking'); ?></a>.
                    </p>
                </div>
            <?php endif; ?>

            <div class="sil-generator-container">
                <!-- Étape 1: Mot-clé -->
                <div class="sil-generator-step" id="sil-step-keyword">
                    <h2><span class="step-number">1</span> <?php _e('Entrez votre mot-clé', 'seo-internal-linking'); ?></h2>
                    <div class="sil-form-group">
                        <label for="sil-keyword"><?php _e('Mot-clé principal', 'seo-internal-linking'); ?></label>
                        <input type="text" id="sil-keyword" class="regular-text" placeholder="<?php _e('Ex: recette gâteau chocolat', 'seo-internal-linking'); ?>" <?php echo !$is_configured ? 'disabled' : ''; ?>>
                        <p class="description"><?php _e('Le sujet principal de votre article', 'seo-internal-linking'); ?></p>
                    </div>
                    <button type="button" class="button button-primary button-hero" id="sil-generate-article" <?php echo !$is_configured ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-edit" style="margin-top: 4px;"></span>
                        <?php _e('Générer l\'article', 'seo-internal-linking'); ?>
                    </button>
                    <span id="sil-generate-status"></span>
                </div>

                <!-- Étape 2: Prévisualisation -->
                <div class="sil-generator-step" id="sil-step-preview" style="display: none;">
                    <h2><span class="step-number">2</span> <?php _e('Prévisualisation et modifications', 'seo-internal-linking'); ?></h2>

                    <div class="sil-preview-section">
                        <div class="sil-form-group">
                            <label for="sil-preview-title"><?php _e('Titre (H1)', 'seo-internal-linking'); ?></label>
                            <input type="text" id="sil-preview-title" class="large-text">
                            <span class="sil-char-count" id="sil-title-count">0/70</span>
                        </div>

                        <div class="sil-form-group">
                            <label for="sil-preview-meta"><?php _e('Meta Description', 'seo-internal-linking'); ?></label>
                            <textarea id="sil-preview-meta" rows="2" class="large-text"></textarea>
                            <span class="sil-char-count" id="sil-meta-count">0/154</span>
                        </div>

                        <div class="sil-form-group">
                            <label for="sil-preview-content"><?php _e('Contenu', 'seo-internal-linking'); ?></label>
                            <div id="sil-preview-content-wrapper">
                                <textarea id="sil-preview-content" rows="15" class="large-text"></textarea>
                            </div>
                            <p class="description" id="sil-word-count"><?php _e('Nombre de mots:', 'seo-internal-linking'); ?> <span>0</span></p>
                        </div>
                    </div>

                    <div class="sil-preview-actions">
                        <button type="button" class="button" id="sil-regenerate">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php _e('Régénérer', 'seo-internal-linking'); ?>
                        </button>
                        <button type="button" class="button button-primary" id="sil-generate-image">
                            <span class="dashicons dashicons-format-image" style="margin-top: 4px;"></span>
                            <?php _e('Générer l\'image', 'seo-internal-linking'); ?>
                        </button>
                        <span id="sil-image-status"></span>
                    </div>
                </div>

                <!-- Étape 3: Image -->
                <div class="sil-generator-step" id="sil-step-image" style="display: none;">
                    <h2><span class="step-number">3</span> <?php _e('Image à la une', 'seo-internal-linking'); ?></h2>

                    <div class="sil-image-preview" id="sil-image-preview">
                        <p class="description"><?php _e('L\'image générée apparaîtra ici', 'seo-internal-linking'); ?></p>
                    </div>

                    <div class="sil-image-actions">
                        <button type="button" class="button" id="sil-regenerate-image">
                            <span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
                            <?php _e('Nouvelle image', 'seo-internal-linking'); ?>
                        </button>
                        <button type="button" class="button" id="sil-skip-image">
                            <?php _e('Passer (sans image)', 'seo-internal-linking'); ?>
                        </button>
                    </div>
                </div>

                <!-- Étape 4: Publication -->
                <div class="sil-generator-step" id="sil-step-publish" style="display: none;">
                    <h2><span class="step-number">4</span> <?php _e('Créer l\'article', 'seo-internal-linking'); ?></h2>

                    <div class="sil-publish-options">
                        <label>
                            <input type="radio" name="sil-publish-status" value="draft" checked>
                            <?php _e('Brouillon (recommandé)', 'seo-internal-linking'); ?>
                        </label>
                        <label>
                            <input type="radio" name="sil-publish-status" value="publish">
                            <?php _e('Publier immédiatement', 'seo-internal-linking'); ?>
                        </label>
                    </div>

                    <button type="button" class="button button-primary button-hero" id="sil-create-post">
                        <span class="dashicons dashicons-yes" style="margin-top: 4px;"></span>
                        <?php _e('Créer l\'article', 'seo-internal-linking'); ?>
                    </button>
                    <span id="sil-publish-status"></span>
                </div>

                <!-- Résultat final -->
                <div class="sil-generator-step sil-success-step" id="sil-step-success" style="display: none;">
                    <div class="sil-success-message">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h2><?php _e('Article créé avec succès !', 'seo-internal-linking'); ?></h2>
                    </div>
                    <div class="sil-success-actions">
                        <a href="#" class="button button-primary" id="sil-edit-post-link" target="_blank">
                            <?php _e('Modifier l\'article', 'seo-internal-linking'); ?>
                        </a>
                        <a href="#" class="button" id="sil-preview-post-link" target="_blank">
                            <?php _e('Prévisualiser', 'seo-internal-linking'); ?>
                        </a>
                        <button type="button" class="button" id="sil-new-article">
                            <?php _e('Créer un autre article', 'seo-internal-linking'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <style>
            .sil-generator-container {
                max-width: 900px;
            }
            .sil-generator-step {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 25px;
                margin-bottom: 20px;
            }
            .sil-generator-step h2 {
                margin-top: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .step-number {
                background: #2271b1;
                color: #fff;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }
            .sil-form-group {
                margin-bottom: 20px;
            }
            .sil-form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }
            .sil-form-group input[type="text"],
            .sil-form-group textarea {
                width: 100%;
            }
            .sil-char-count {
                display: block;
                text-align: right;
                font-size: 12px;
                color: #666;
            }
            .sil-char-count.warning {
                color: #dba617;
            }
            .sil-char-count.error {
                color: #d63638;
            }
            .sil-preview-actions,
            .sil-image-actions {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            .sil-image-preview {
                background: #f5f5f5;
                border: 2px dashed #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                min-height: 200px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .sil-image-preview img {
                max-width: 100%;
                height: auto;
                border-radius: 4px;
            }
            .sil-publish-options {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }
            .sil-publish-options label {
                display: flex;
                align-items: center;
                gap: 5px;
                cursor: pointer;
            }
            .sil-success-step {
                text-align: center;
                background: #d4edda;
                border-color: #28a745;
            }
            .sil-success-message {
                margin-bottom: 20px;
            }
            .sil-success-message .dashicons {
                font-size: 60px;
                width: 60px;
                height: 60px;
                color: #28a745;
            }
            .sil-success-actions {
                display: flex;
                gap: 10px;
                justify-content: center;
            }
            #sil-generate-status,
            #sil-image-status,
            #sil-publish-status {
                margin-left: 10px;
            }
            .spinner.is-active {
                float: none;
                margin: 0;
            }
            </style>
        </div>
        <?php
    }
}

// Initialiser la classe admin
new SIL_Admin();
