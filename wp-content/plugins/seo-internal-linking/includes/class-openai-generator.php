<?php
/**
 * Classe de génération de contenu avec OpenAI
 *
 * Génère des articles, meta descriptions et images à la une
 * en utilisant l'API OpenAI (GPT-4 et DALL-E)
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_OpenAI_Generator {

    /**
     * Clé API OpenAI
     */
    private $api_key;

    /**
     * Options du plugin
     */
    private $options;

    /**
     * URL de l'API OpenAI
     */
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $image_api_url = 'https://api.openai.com/v1/images/generations';

    /**
     * Constructeur
     */
    public function __construct($options) {
        $this->options = $options;
        $this->api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
    }

    /**
     * Vérifier si l'API est configurée
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Générer un article complet
     */
    public function generate_article($keyword, $custom_prompt = '') {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => __('Clé API OpenAI non configurée.', 'seo-internal-linking')
            );
        }

        // Prompt par défaut ou personnalisé
        $prompt = !empty($custom_prompt) ? $custom_prompt : $this->get_default_prompt();

        // Remplacer le mot-clé dans le prompt
        $prompt = str_replace('{keyword}', $keyword, $prompt);

        // Appeler l'API OpenAI pour le contenu
        $content_response = $this->call_openai_api($prompt);

        if (!$content_response['success']) {
            return $content_response;
        }

        // Parser la réponse
        $parsed = $this->parse_article_response($content_response['content']);

        return array(
            'success' => true,
            'data' => $parsed,
            'keyword' => $keyword
        );
    }

    /**
     * Générer une image avec DALL-E
     */
    public function generate_image($keyword, $title = '') {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => __('Clé API OpenAI non configurée.', 'seo-internal-linking')
            );
        }

        // Créer un prompt pour l'image
        $image_prompt = $this->get_image_prompt($keyword, $title);

        $response = wp_remote_post($this->image_api_url, array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'dall-e-3',
                'prompt' => $image_prompt,
                'n' => 1,
                'size' => '1792x1024',
                'quality' => 'standard'
            ))
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return array(
                'success' => false,
                'message' => $body['error']['message']
            );
        }

        if (isset($body['data'][0]['url'])) {
            return array(
                'success' => true,
                'image_url' => $body['data'][0]['url'],
                'revised_prompt' => isset($body['data'][0]['revised_prompt']) ? $body['data'][0]['revised_prompt'] : ''
            );
        }

        return array(
            'success' => false,
            'message' => __('Erreur lors de la génération de l\'image.', 'seo-internal-linking')
        );
    }

    /**
     * Télécharger et attacher une image à un post
     */
    public function download_and_attach_image($image_url, $post_id, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Télécharger l'image
        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return array(
                'success' => false,
                'message' => $tmp->get_error_message()
            );
        }

        // Préparer le fichier
        $file_array = array(
            'name' => sanitize_file_name($title) . '.png',
            'tmp_name' => $tmp
        );

        // Sideload l'image
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return array(
                'success' => false,
                'message' => $attachment_id->get_error_message()
            );
        }

        // Définir comme image à la une
        set_post_thumbnail($post_id, $attachment_id);

        // Ajouter le texte alternatif
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);

        return array(
            'success' => true,
            'attachment_id' => $attachment_id
        );
    }

    /**
     * Créer l'article WordPress
     */
    public function create_post($data, $status = 'draft') {
        $post_data = array(
            'post_title'   => $data['title'],
            'post_content' => $data['content'],
            'post_status'  => $status,
            'post_type'    => 'post',
            'post_author'  => get_current_user_id()
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'message' => $post_id->get_error_message()
            );
        }

        // Ajouter la meta description (compatible avec Yoast, RankMath, etc.)
        if (!empty($data['meta_description'])) {
            // Meta générique
            update_post_meta($post_id, '_sil_meta_description', $data['meta_description']);

            // Yoast SEO
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['meta_description']);

            // RankMath
            update_post_meta($post_id, 'rank_math_description', $data['meta_description']);

            // All in One SEO
            update_post_meta($post_id, '_aioseo_description', $data['meta_description']);
        }

        // Ajouter le mot-clé focus
        if (!empty($data['keyword'])) {
            update_post_meta($post_id, '_sil_focus_keyword', $data['keyword']);
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $data['keyword']);
            update_post_meta($post_id, 'rank_math_focus_keyword', $data['keyword']);
        }

        return array(
            'success' => true,
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'preview_url' => get_preview_post_link($post_id)
        );
    }

    /**
     * Appeler l'API OpenAI
     */
    private function call_openai_api($prompt) {
        $model = isset($this->options['openai_model']) ? $this->options['openai_model'] : 'gpt-4o-mini';

        $response = wp_remote_post($this->api_url, array(
            'timeout' => 120,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'Tu es un expert en rédaction SEO. Tu écris des articles optimisés pour le référencement naturel en français.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => 4000
            ))
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return array(
                'success' => false,
                'message' => $body['error']['message']
            );
        }

        if (isset($body['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $body['choices'][0]['message']['content']
            );
        }

        return array(
            'success' => false,
            'message' => __('Réponse inattendue de l\'API.', 'seo-internal-linking')
        );
    }

    /**
     * Prompt par défaut pour la génération d'articles
     */
    private function get_default_prompt() {
        return <<<PROMPT
Écris un article de blog complet et optimisé SEO sur le sujet suivant : "{keyword}"

L'article doit respecter ce format EXACT (utilise ces balises pour structurer ta réponse) :

[TITLE]
Un titre H1 accrocheur et optimisé SEO (60-70 caractères max)
[/TITLE]

[META]
Une meta description de 150-154 caractères exactement, engageante et contenant le mot-clé
[/META]

[CONTENT]
Le contenu de l'article en HTML avec :
- Une introduction engageante (2-3 paragraphes)
- Des sous-titres H2 et H3 pertinents
- Des paragraphes bien structurés
- Des listes à puces quand c'est pertinent
- Une conclusion avec un appel à l'action
- Minimum 800 mots
- Utilise des balises <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>
[/CONTENT]

Important :
- Le contenu doit être unique et de haute qualité
- Intègre naturellement le mot-clé "{keyword}" plusieurs fois
- Utilise des synonymes et variations du mot-clé
- Le ton doit être professionnel mais accessible
PROMPT;
    }

    /**
     * Prompt pour la génération d'images
     */
    private function get_image_prompt($keyword, $title) {
        $base_prompt = isset($this->options['openai_image_prompt']) ? $this->options['openai_image_prompt'] : '';

        if (empty($base_prompt)) {
            $base_prompt = "Une image professionnelle et moderne pour illustrer un article de blog sur : {keyword}. Style : photographie réaliste de haute qualité, lumière naturelle, composition professionnelle. Pas de texte sur l'image.";
        }

        $prompt = str_replace('{keyword}', $keyword, $base_prompt);
        $prompt = str_replace('{title}', $title, $prompt);

        return $prompt;
    }

    /**
     * Parser la réponse de l'API
     */
    private function parse_article_response($content) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => ''
        );

        // Extraire le titre
        if (preg_match('/\[TITLE\](.*?)\[\/TITLE\]/s', $content, $matches)) {
            $result['title'] = trim($matches[1]);
        }

        // Extraire la meta description
        if (preg_match('/\[META\](.*?)\[\/META\]/s', $content, $matches)) {
            $result['meta_description'] = trim($matches[1]);
            // S'assurer qu'elle fait max 154 caractères
            if (mb_strlen($result['meta_description']) > 154) {
                $result['meta_description'] = mb_substr($result['meta_description'], 0, 151) . '...';
            }
        }

        // Extraire le contenu
        if (preg_match('/\[CONTENT\](.*?)\[\/CONTENT\]/s', $content, $matches)) {
            $result['content'] = trim($matches[1]);
        }

        // Si le parsing échoue, essayer une approche alternative
        if (empty($result['title']) || empty($result['content'])) {
            $result = $this->parse_article_fallback($content);
        }

        return $result;
    }

    /**
     * Parser alternatif si le format n'est pas respecté
     */
    private function parse_article_fallback($content) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => ''
        );

        // Chercher un H1 ou titre au début
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            $result['title'] = strip_tags($matches[1]);
            $content = preg_replace('/<h1[^>]*>.*?<\/h1>/i', '', $content, 1);
        } elseif (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            $result['title'] = trim($matches[1]);
            $content = preg_replace('/^#\s+.+$/m', '', $content, 1);
        } else {
            // Prendre la première ligne comme titre
            $lines = explode("\n", $content);
            $result['title'] = trim(strip_tags($lines[0]));
            array_shift($lines);
            $content = implode("\n", $lines);
        }

        // Générer une meta description à partir du contenu
        $plain_text = wp_strip_all_tags($content);
        $result['meta_description'] = mb_substr($plain_text, 0, 151) . '...';

        // Le reste est le contenu
        $result['content'] = trim($content);

        return $result;
    }

    /**
     * Estimer le coût de génération
     */
    public function estimate_cost($include_image = true) {
        $text_cost = 0.01; // Estimation pour GPT-4o-mini
        $image_cost = $include_image ? 0.04 : 0; // DALL-E 3 standard

        return array(
            'text' => $text_cost,
            'image' => $image_cost,
            'total' => $text_cost + $image_cost,
            'currency' => 'USD'
        );
    }
}
