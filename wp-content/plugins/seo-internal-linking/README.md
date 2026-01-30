# SEO Internal Linking - Plugin WordPress de Maillage Interne

Plugin WordPress pour améliorer le référencement (SEO) de votre site grâce au maillage interne automatique entre vos articles.

## Fonctionnalités

### Analyse automatique des mots-clés
- Extraction intelligente des mots-clés de vos articles
- Détection des expressions multi-mots (bi-grams, tri-grams)
- Pondération des mots-clés selon leur importance
- Exclusion des mots vides (stop words) en français

### Suggestions de liens
- Génération automatique de suggestions de liens pertinents
- Score de pertinence basé sur les mots-clés communs
- Prise en compte des catégories et tags communs
- Interface pour valider ou rejeter les suggestions

### Insertion de liens
- Mode automatique : insertion des liens lors de l'affichage
- Mode manuel : validation et insertion depuis l'admin
- Contrôle du nombre maximum de liens par article
- Évitement des doublons de liens

### Détection des articles orphelins
- Identification des articles sans liens entrants
- Liste des articles avec peu de maillage
- Recommandations pour améliorer le maillage

### Statistiques et rapports
- Tableau de bord avec vue d'ensemble
- Graphiques de distribution des liens
- Historique des liens créés
- Export CSV des statistiques

## Installation

1. Téléchargez le dossier `seo-internal-linking` dans `/wp-content/plugins/`
2. Activez le plugin dans le menu "Extensions" de WordPress
3. Accédez à "Maillage SEO" dans le menu admin

## Configuration

### Paramètres généraux
- **Liens automatiques** : Activer/désactiver l'insertion automatique
- **Nombre max de liens** : Limite de liens auto-insérés par article (défaut: 5)
- **Longueur min des mots-clés** : Mots-clés de moins de X caractères ignorés
- **Types de contenu** : Sélectionner les types de posts à analyser
- **Mots exclus** : Liste personnalisée de mots à ignorer

## Utilisation

### Tableau de bord
Le tableau de bord affiche :
- Nombre d'articles analysés
- Total des liens internes
- Articles orphelins à corriger
- Suggestions en attente
- Score SEO moyen
- Mots-clés les plus fréquents

### Analyser les articles
1. Cliquez sur "Analyser tous les articles" pour une analyse complète
2. Ou analysez article par article depuis la meta box de l'éditeur

### Appliquer les suggestions
1. Allez dans "Suggestions"
2. Validez les liens pertinents en cliquant "Insérer"
3. Ignorez les suggestions non pertinentes

### Gérer les orphelins
1. Consultez la page "Orphelins"
2. Cliquez sur "Voir suggestions" pour chaque article
3. Ajoutez des liens vers ces articles depuis d'autres contenus

## Hooks disponibles

### Filtres
```php
// Modifier les mots exclus
add_filter('sil_stop_words', function($words) {
    $words[] = 'monmot';
    return $words;
});

// Modifier le score de pertinence
add_filter('sil_relevance_score', function($score, $keyword, $match) {
    return $score;
}, 10, 3);
```

### Actions
```php
// Après l'analyse d'un post
add_action('sil_post_analyzed', function($post_id, $keywords) {
    // Votre code
}, 10, 2);

// Après l'insertion d'un lien
add_action('sil_link_inserted', function($source_id, $target_id, $anchor) {
    // Votre code
}, 10, 3);
```

## Bonnes pratiques SEO

1. **Ne pas sur-optimiser** : Gardez un nombre raisonnable de liens (3-7 par article)
2. **Variez les ancres** : Utilisez différents textes d'ancrage
3. **Priorisez la pertinence** : Les liens doivent être utiles aux lecteurs
4. **Équilibrez le maillage** : Évitez les pages orphelines
5. **Maillage bidirectionnel** : Créez des liens dans les deux sens

## Support

Pour toute question ou problème, ouvrez une issue sur le dépôt GitHub.

## Licence

GPL v2 ou ultérieure
