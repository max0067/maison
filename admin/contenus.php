<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    foreach ($_POST as $cle => $valeur) {
        if ($cle !== 'action') {
            updateContenu($cle, $valeur);
        }
    }

    $message = 'Contenus mis à jour avec succès !';
}

// Récupérer tous les contenus
$db = getDB();
$stmt = $db->query("SELECT * FROM contenus ORDER BY cle ASC");
$contenus = $stmt->fetchAll();

// Organiser les contenus par catégorie
$categories = [
    'En-tête' => ['site_titre', 'site_sous_titre', 'hero_image'],
    'Maison d\'hôtes' => ['maison_titre', 'maison_description'],
    'Chambres' => ['chambres_titre'],
    'Réservation' => ['reservation_titre'],
    'Pied de page' => ['footer_text']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contenus - Administration</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Gestion des contenus du site</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" class="form">
                <?php foreach ($categories as $categorie => $cles): ?>
                    <div class="content-category">
                        <h2><?php echo $categorie; ?></h2>

                        <?php foreach ($contenus as $contenu):
                            if (in_array($contenu['cle'], $cles)): ?>
                                <div class="form-group">
                                    <label for="<?php echo $contenu['cle']; ?>">
                                        <?php
                                        $labels = [
                                            'site_titre' => 'Titre du site',
                                            'site_sous_titre' => 'Sous-titre',
                                            'hero_image' => 'Image d\'en-tête',
                                            'maison_titre' => 'Titre de la section',
                                            'maison_description' => 'Description de la maison',
                                            'chambres_titre' => 'Titre de la section',
                                            'reservation_titre' => 'Titre de la section',
                                            'footer_text' => 'Texte du pied de page'
                                        ];
                                        echo $labels[$contenu['cle']] ?? $contenu['cle'];
                                        ?>
                                    </label>

                                    <?php if ($contenu['type'] === 'textarea'): ?>
                                        <textarea id="<?php echo $contenu['cle']; ?>"
                                                  name="<?php echo $contenu['cle']; ?>"
                                                  rows="4"><?php echo e($contenu['valeur']); ?></textarea>
                                    <?php else: ?>
                                        <input type="text"
                                               id="<?php echo $contenu['cle']; ?>"
                                               name="<?php echo $contenu['cle']; ?>"
                                               value="<?php echo e($contenu['valeur']); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endif;
                        endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="../index.php" target="_blank" class="btn btn-secondary">Prévisualiser le site</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
