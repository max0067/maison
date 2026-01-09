<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$reservation_id = $_GET['id'] ?? null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                // Vérifier la disponibilité
                if (!verifierDisponibilite($_POST['chambre_id'], $_POST['date_arrivee'], $_POST['date_depart'])) {
                    $error = 'Cette chambre n\'est pas disponible pour ces dates.';
                } else {
                    $prix_total = calculerPrixTotal($_POST['chambre_id'], $_POST['date_arrivee'], $_POST['date_depart']);
                    $data = [
                        'nom' => $_POST['nom'],
                        'email' => $_POST['email'],
                        'telephone' => $_POST['telephone'],
                        'chambre_id' => $_POST['chambre_id'],
                        'date_arrivee' => $_POST['date_arrivee'],
                        'date_depart' => $_POST['date_depart'],
                        'nombre_personnes' => $_POST['nombre_personnes'],
                        'prix_total' => $prix_total,
                        'commentaire' => $_POST['commentaire']
                    ];

                    if (createReservation($data)) {
                        $message = 'Réservation créée avec succès !';
                        $action = 'list';
                    } else {
                        $error = 'Erreur lors de la création de la réservation.';
                    }
                }
                break;

            case 'update':
                $db = getDB();
                $stmt = $db->prepare("UPDATE reservations SET nom = ?, email = ?, telephone = ?, chambre_id = ?, date_arrivee = ?, date_depart = ?, nombre_personnes = ?, commentaire = ? WHERE id = ?");
                if ($stmt->execute([
                    $_POST['nom'],
                    $_POST['email'],
                    $_POST['telephone'],
                    $_POST['chambre_id'],
                    $_POST['date_arrivee'],
                    $_POST['date_depart'],
                    $_POST['nombre_personnes'],
                    $_POST['commentaire'],
                    $_POST['id']
                ])) {
                    $message = 'Réservation modifiée avec succès !';
                    $action = 'list';
                } else {
                    $error = 'Erreur lors de la modification.';
                }
                break;

            case 'update_status':
                $db = getDB();
                $stmt = $db->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
                if ($stmt->execute([$_POST['statut'], $_POST['id']])) {
                    $message = 'Statut mis à jour avec succès !';
                } else {
                    $error = 'Erreur lors de la mise à jour du statut.';
                }
                break;

            case 'delete':
                $db = getDB();
                $stmt = $db->prepare("DELETE FROM reservations WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    $message = 'Réservation supprimée avec succès !';
                } else {
                    $error = 'Erreur lors de la suppression.';
                }
                break;
        }
    }
}

// Récupérer les données selon l'action
$reservations = getAllReservations();
$chambres = getAllChambres();
$reservation = null;

if ($action === 'edit' && $reservation_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations - Administration</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Gestion des réservations</h1>
            <?php if ($action === 'list'): ?>
                <a href="?action=new" class="btn btn-primary">Nouvelle réservation</a>
            <?php else: ?>
                <a href="?" class="btn btn-secondary">Retour à la liste</a>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Liste des réservations -->
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Chambre</th>
                            <th>Arrivée</th>
                            <th>Départ</th>
                            <th>Personnes</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td><?php echo $res['id']; ?></td>
                                <td><?php echo e($res['nom']); ?></td>
                                <td><?php echo e($res['email']); ?></td>
                                <td><?php echo e($res['telephone'] ?? '-'); ?></td>
                                <td><?php echo e($res['chambre_nom']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($res['date_arrivee'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($res['date_depart'])); ?></td>
                                <td><?php echo $res['nombre_personnes']; ?></td>
                                <td><?php echo number_format($res['prix_total'], 2); ?> €</td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo $res['id']; ?>">
                                        <select name="statut" onchange="this.form.submit()" class="status-select status-<?php echo $res['statut']; ?>">
                                            <option value="en_attente" <?php echo $res['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                            <option value="confirmee" <?php echo $res['statut'] === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                                            <option value="annulee" <?php echo $res['statut'] === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $res['id']; ?>" class="btn-icon" title="Modifier">✏️</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $res['id']; ?>">
                                            <button type="submit" class="btn-icon" title="Supprimer">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <!-- Formulaire de création/modification -->
            <div class="card">
                <h2><?php echo $action === 'new' ? 'Nouvelle réservation' : 'Modifier la réservation'; ?></h2>
                <form method="POST" class="form">
                    <input type="hidden" name="action" value="<?php echo $action === 'new' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $reservation['id']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom du client *</label>
                            <input type="text" id="nom" name="nom" required value="<?php echo $reservation ? e($reservation['nom']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required value="<?php echo $reservation ? e($reservation['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo $reservation ? e($reservation['telephone']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="chambre_id">Chambre *</label>
                            <select id="chambre_id" name="chambre_id" required>
                                <option value="">Sélectionner une chambre</option>
                                <?php foreach ($chambres as $chambre): ?>
                                    <option value="<?php echo $chambre['id']; ?>"
                                        <?php echo ($reservation && $reservation['chambre_id'] == $chambre['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($chambre['nom']) . ' - ' . number_format($chambre['prix'], 2) . ' €/nuit'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_arrivee">Date d'arrivée *</label>
                            <input type="date" id="date_arrivee" name="date_arrivee" required
                                value="<?php echo $reservation ? $reservation['date_arrivee'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_depart">Date de départ *</label>
                            <input type="date" id="date_depart" name="date_depart" required
                                value="<?php echo $reservation ? $reservation['date_depart'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="nombre_personnes">Nombre de personnes *</label>
                            <input type="number" id="nombre_personnes" name="nombre_personnes" min="1" required
                                value="<?php echo $reservation ? $reservation['nombre_personnes'] : '1'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="commentaire">Commentaire</label>
                        <textarea id="commentaire" name="commentaire" rows="3"><?php echo $reservation ? e($reservation['commentaire']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'new' ? 'Créer la réservation' : 'Enregistrer les modifications'; ?>
                        </button>
                        <a href="?" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
