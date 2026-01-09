<?php
require_once 'includes/functions.php';

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des données
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $chambre_id = intval($_POST['chambre_id'] ?? 0);
    $date_arrivee = $_POST['date_arrivee'] ?? '';
    $date_depart = $_POST['date_depart'] ?? '';
    $nombre_personnes = intval($_POST['nombre_personnes'] ?? 1);
    $commentaire = trim($_POST['commentaire'] ?? '');

    // Vérifications
    if (empty($nom) || empty($email) || !$chambre_id || empty($date_arrivee) || empty($date_depart)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } elseif (strtotime($date_depart) <= strtotime($date_arrivee)) {
        $error = 'La date de départ doit être après la date d\'arrivée.';
    } elseif (strtotime($date_arrivee) < strtotime('today')) {
        $error = 'La date d\'arrivée ne peut pas être dans le passé.';
    } elseif (!verifierDisponibilite($chambre_id, $date_arrivee, $date_depart)) {
        $error = 'Cette chambre n\'est pas disponible pour ces dates. Veuillez choisir d\'autres dates.';
    } else {
        // Calculer le prix total
        $prix_total = calculerPrixTotal($chambre_id, $date_arrivee, $date_depart);

        // Créer la réservation
        $data = [
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone,
            'chambre_id' => $chambre_id,
            'date_arrivee' => $date_arrivee,
            'date_depart' => $date_depart,
            'nombre_personnes' => $nombre_personnes,
            'prix_total' => $prix_total,
            'commentaire' => $commentaire
        ];

        if (createReservation($data)) {
            $success = true;
            $chambre = getChambreById($chambre_id);
            $message = 'Votre réservation a été enregistrée avec succès !';
        } else {
            $error = 'Une erreur est survenue lors de la réservation. Veuillez réessayer.';
        }
    }
}

$site_titre = getContenu('site_titre');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - <?php echo e($site_titre); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="reservation-confirmation">
        <div class="confirmation-card">
            <?php if ($success): ?>
                <div class="success-icon">✓</div>
                <h1>Réservation confirmée</h1>
                <p class="success-message"><?php echo e($message); ?></p>

                <div class="reservation-details">
                    <h2>Détails de votre réservation</h2>
                    <div class="detail-row">
                        <span class="detail-label">Nom :</span>
                        <span class="detail-value"><?php echo e($nom); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email :</span>
                        <span class="detail-value"><?php echo e($email); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Chambre :</span>
                        <span class="detail-value"><?php echo e($chambre['nom']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Arrivée :</span>
                        <span class="detail-value"><?php echo formatDateFr($date_arrivee); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Départ :</span>
                        <span class="detail-value"><?php echo formatDateFr($date_depart); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Personnes :</span>
                        <span class="detail-value"><?php echo $nombre_personnes; ?></span>
                    </div>
                    <div class="detail-row total-row">
                        <span class="detail-label">Prix total :</span>
                        <span class="detail-value"><strong><?php echo number_format($prix_total, 2); ?> €</strong></span>
                    </div>
                </div>

                <p class="info-message">
                    Vous recevrez un email de confirmation à l'adresse <strong><?php echo e($email); ?></strong>.
                    Nous vous contacterons rapidement pour confirmer votre réservation.
                </p>

            <?php else: ?>
                <div class="error-icon">✗</div>
                <h1>Erreur</h1>
                <p class="error-message"><?php echo e($error); ?></p>
            <?php endif; ?>

            <a href="index.php" class="btn-retour">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
