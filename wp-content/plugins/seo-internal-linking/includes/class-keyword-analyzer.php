<?php
/**
 * Classe d'analyse des mots-clés
 *
 * Extrait et analyse les mots-clés importants des articles
 * pour identifier les opportunités de maillage interne.
 */

if (!defined('ABSPATH')) {
    exit;
}

class SIL_Keyword_Analyzer {

    /**
     * Options du plugin
     */
    private $options;

    /**
     * Mots à exclure (stop words)
     */
    private $stop_words = array();

    /**
     * Constructeur
     */
    public function __construct($options) {
        $this->options = $options;
        $this->init_stop_words();
    }

    /**
     * Initialiser les mots à exclure
     */
    private function init_stop_words() {
        $excluded = isset($this->options['excluded_words']) ? $this->options['excluded_words'] : '';
        $this->stop_words = array_map('trim', explode(',', strtolower($excluded)));

        // Ajouter des stop words français par défaut
        $default_stop_words = array(
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'd',
            'et', 'ou', 'mais', 'donc', 'car', 'ni', 'or',
            'que', 'qui', 'quoi', 'dont', 'où', 'quand', 'comment', 'pourquoi',
            'ce', 'cet', 'cette', 'ces', 'mon', 'ton', 'son', 'ma', 'ta', 'sa',
            'mes', 'tes', 'ses', 'notre', 'votre', 'leur', 'nos', 'vos', 'leurs',
            'je', 'tu', 'il', 'elle', 'on', 'nous', 'vous', 'ils', 'elles',
            'me', 'te', 'se', 'lui', 'y', 'en',
            'pour', 'par', 'sur', 'sous', 'avec', 'sans', 'dans', 'en', 'à', 'au', 'aux',
            'chez', 'vers', 'entre', 'parmi', 'contre', 'après', 'avant', 'depuis',
            'être', 'avoir', 'faire', 'dire', 'aller', 'voir', 'venir', 'pouvoir', 'vouloir',
            'est', 'sont', 'était', 'été', 'sera', 'serait', 'ont', 'avait', 'fait',
            'plus', 'moins', 'très', 'bien', 'aussi', 'encore', 'toujours', 'jamais',
            'tout', 'tous', 'toute', 'toutes', 'quel', 'quelle', 'quels', 'quelles',
            'autre', 'autres', 'même', 'mêmes', 'tel', 'telle', 'tels', 'telles',
            'si', 'ne', 'pas', 'non', 'oui', 'peut',
            'ici', 'là', 'alors', 'ainsi', 'comme', 'car', 'donc',
            'cela', 'celui', 'celle', 'ceux', 'celles',
            'http', 'https', 'www', 'com', 'fr', 'org', 'net'
        );

        $this->stop_words = array_unique(array_merge($this->stop_words, $default_stop_words));
    }

    /**
     * Analyser un article
     */
    public function analyze_post($post_id) {
        $post = get_post($post_id);

        if (!$post || $post->post_status !== 'publish') {
            return false;
        }

        // Récupérer le contenu complet
        $content = $post->post_title . ' ' . $post->post_content;

        // Ajouter les catégories et tags
        $categories = wp_get_post_categories($post_id, array('fields' => 'names'));
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));

        $content .= ' ' . implode(' ', $categories);
        $content .= ' ' . implode(' ', $tags);

        // Extraire et analyser les mots-clés
        $keywords = $this->extract_keywords($content);

        // Sauvegarder en base de données
        $this->save_keywords($post_id, $keywords);

        // Mettre à jour les statistiques
        $this->update_post_stats($post_id);

        return array(
            'post_id' => $post_id,
            'keywords' => $keywords
        );
    }

    /**
     * Extraire les mots-clés d'un texte
     */
    public function extract_keywords($text) {
        // Nettoyer le HTML
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Nettoyer les caractères spéciaux tout en gardant les accents
        $text = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = mb_strtolower(trim($text), 'UTF-8');

        // Découper en mots
        $words = preg_split('/\s+/', $text);

        // Compter les occurrences
        $word_count = array();
        $min_length = isset($this->options['min_keyword_length']) ? intval($this->options['min_keyword_length']) : 3;

        foreach ($words as $word) {
            $word = trim($word, '-');

            // Ignorer les mots trop courts ou les stop words
            if (mb_strlen($word) < $min_length) {
                continue;
            }

            if (in_array($word, $this->stop_words)) {
                continue;
            }

            // Ignorer les nombres seuls
            if (is_numeric($word)) {
                continue;
            }

            if (!isset($word_count[$word])) {
                $word_count[$word] = 0;
            }
            $word_count[$word]++;
        }

        // Extraire les expressions de 2-3 mots (n-grams)
        $ngrams = $this->extract_ngrams($words, $min_length);

        // Fusionner avec les mots simples
        foreach ($ngrams as $ngram => $count) {
            if (!isset($word_count[$ngram])) {
                $word_count[$ngram] = $count;
            } else {
                $word_count[$ngram] += $count;
            }
        }

        // Trier par fréquence
        arsort($word_count);

        // Calculer les scores
        $keywords = array();
        $max_count = max($word_count) ?: 1;

        foreach ($word_count as $word => $count) {
            // Score basé sur la fréquence et la longueur
            $freq_score = $count / $max_count;
            $length_score = min(1, mb_strlen($word) / 20);
            $weight = ($freq_score * 0.7) + ($length_score * 0.3);

            // Bonus pour les expressions multi-mots
            if (str_word_count($word) > 1) {
                $weight *= 1.5;
            }

            $keywords[] = array(
                'keyword' => $word,
                'frequency' => $count,
                'weight' => round($weight, 3)
            );
        }

        // Limiter aux 50 meilleurs mots-clés
        return array_slice($keywords, 0, 50);
    }

    /**
     * Extraire les n-grams (expressions de 2-3 mots)
     */
    private function extract_ngrams($words, $min_length) {
        $ngrams = array();
        $word_count = count($words);

        // Bi-grams (2 mots)
        for ($i = 0; $i < $word_count - 1; $i++) {
            $word1 = trim($words[$i], '-');
            $word2 = trim($words[$i + 1], '-');

            if (mb_strlen($word1) >= $min_length && mb_strlen($word2) >= $min_length) {
                if (!in_array($word1, $this->stop_words) && !in_array($word2, $this->stop_words)) {
                    $ngram = $word1 . ' ' . $word2;
                    if (!isset($ngrams[$ngram])) {
                        $ngrams[$ngram] = 0;
                    }
                    $ngrams[$ngram]++;
                }
            }
        }

        // Tri-grams (3 mots)
        for ($i = 0; $i < $word_count - 2; $i++) {
            $word1 = trim($words[$i], '-');
            $word2 = trim($words[$i + 1], '-');
            $word3 = trim($words[$i + 2], '-');

            // Le mot du milieu peut être un stop word
            if (mb_strlen($word1) >= $min_length && mb_strlen($word3) >= $min_length) {
                if (!in_array($word1, $this->stop_words) && !in_array($word3, $this->stop_words)) {
                    $ngram = $word1 . ' ' . $word2 . ' ' . $word3;
                    if (!isset($ngrams[$ngram])) {
                        $ngrams[$ngram] = 0;
                    }
                    $ngrams[$ngram]++;
                }
            }
        }

        // Garder seulement les n-grams qui apparaissent plus d'une fois
        return array_filter($ngrams, function($count) {
            return $count >= 2;
        });
    }

    /**
     * Sauvegarder les mots-clés en base de données
     */
    private function save_keywords($post_id, $keywords) {
        global $wpdb;

        $table = $wpdb->prefix . 'sil_keywords';

        // Supprimer les anciens mots-clés
        $wpdb->delete($table, array('post_id' => $post_id), array('%d'));

        // Insérer les nouveaux
        foreach ($keywords as $kw) {
            $wpdb->insert(
                $table,
                array(
                    'post_id' => $post_id,
                    'keyword' => $kw['keyword'],
                    'frequency' => $kw['frequency'],
                    'weight' => $kw['weight'],
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%d', '%f', '%s')
            );
        }
    }

    /**
     * Mettre à jour les statistiques d'un article
     */
    private function update_post_stats($post_id) {
        global $wpdb;

        $stats_table = $wpdb->prefix . 'sil_statistics';
        $links_table = $wpdb->prefix . 'sil_links';

        // Compter les liens internes sortants
        $outgoing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $links_table WHERE source_post_id = %d",
            $post_id
        ));

        // Compter les liens entrants
        $incoming = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $links_table WHERE target_post_id = %d",
            $post_id
        ));

        // Vérifier si c'est un article orphelin (sans liens entrants)
        $orphan = ($incoming == 0) ? 1 : 0;

        // Calculer un score SEO simple
        $seo_score = $this->calculate_seo_score($outgoing, $incoming);

        // Insérer ou mettre à jour
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
     * Calculer un score SEO
     */
    private function calculate_seo_score($outgoing, $incoming) {
        // Score basique:
        // - Points pour les liens sortants (max 5)
        // - Points pour les liens entrants (bonus progressif)

        $out_score = min(5, $outgoing) * 10; // Max 50 points
        $in_score = min(10, $incoming) * 5;  // Max 50 points

        return min(100, $out_score + $in_score);
    }

    /**
     * Rechercher les mots-clés correspondants dans d'autres articles
     */
    public function find_matching_posts($keyword, $exclude_post_id = 0, $limit = 10) {
        global $wpdb;

        $keywords_table = $wpdb->prefix . 'sil_keywords';
        $posts_table = $wpdb->posts;

        $sql = $wpdb->prepare(
            "SELECT k.post_id, k.keyword, k.weight, p.post_title, p.post_type
            FROM $keywords_table k
            INNER JOIN $posts_table p ON k.post_id = p.ID
            WHERE k.keyword LIKE %s
            AND k.post_id != %d
            AND p.post_status = 'publish'
            ORDER BY k.weight DESC
            LIMIT %d",
            '%' . $wpdb->esc_like($keyword) . '%',
            $exclude_post_id,
            $limit
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Obtenir les mots-clés d'un article
     */
    public function get_post_keywords($post_id, $limit = 20) {
        global $wpdb;

        $table = $wpdb->prefix . 'sil_keywords';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT keyword, frequency, weight
            FROM $table
            WHERE post_id = %d
            ORDER BY weight DESC
            LIMIT %d",
            $post_id,
            $limit
        ));
    }
}
