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
        $db = getDB();

        switch ($_POST['action']) {
            case 'create':
                // Vérifier la disponibilité
                if (!verifierDisponibilite($_POST['chambre_id'], $_POST['date_arrivee'], $_POST['date_depart'])) {
                    $_SESSION['error'] = 'Cette chambre n\'est pas disponible pour ces dates.';
                    header('Location: ?action=new');
                    exit;
                } else {
                    $prix_total = calculerPrixTotal($_POST['chambre_id'], $_POST['date_arrivee'], $_POST['date_depart']);

                    $stmt = $db->prepare("
                        INSERT INTO reservations (
                            nom, email, telephone, chambre_id, date_arrivee, date_depart,
                            nombre_personnes, prix_total, commentaire, statut_paiement,
                            montant_acompte, montant_paye, mode_paiement
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    if ($stmt->execute([
                        $_POST['nom'],
                        $_POST['email'],
                        $_POST['telephone'],
                        $_POST['chambre_id'],
                        $_POST['date_arrivee'],
                        $_POST['date_depart'],
                        $_POST['nombre_personnes'],
                        $prix_total,
                        $_POST['commentaire'],
                        $_POST['statut_paiement'] ?? 'non_paye',
                        $_POST['montant_acompte'] ?? 0,
                        $_POST['montant_paye'] ?? 0,
                        $_POST['mode_paiement'] ?? null
                    ])) {
                        $_SESSION['message'] = 'Réservation créée avec succès !';
                        header('Location: ?');
                        exit;
                    } else {
                        $_SESSION['error'] = 'Erreur lors de la création de la réservation.';
                        header('Location: ?action=new');
                        exit;
                    }
                }
                break;

            case 'update':
                $stmt = $db->prepare("
                    UPDATE reservations SET
                        nom = ?, email = ?, telephone = ?, chambre_id = ?,
                        date_arrivee = ?, date_depart = ?, nombre_personnes = ?,
                        commentaire = ?, statut_paiement = ?, montant_acompte = ?,
                        montant_paye = ?, mode_paiement = ?, notes_paiement = ?
                    WHERE id = ?
                ");
                if ($stmt->execute([
                    $_POST['nom'],
                    $_POST['email'],
                    $_POST['telephone'],
                    $_POST['chambre_id'],
                    $_POST['date_arrivee'],
                    $_POST['date_depart'],
                    $_POST['nombre_personnes'],
                    $_POST['commentaire'],
                    $_POST['statut_paiement'],
                    $_POST['montant_acompte'],
                    $_POST['montant_paye'],
                    $_POST['mode_paiement'],
                    $_POST['notes_paiement'],
                    $_POST['id']
                ])) {
                    $_SESSION['message'] = 'Réservation modifiée avec succès !';
                    header('Location: ?');
                    exit;
                } else {
                    $_SESSION['error'] = 'Erreur lors de la modification.';
                    header('Location: ?action=edit&id=' . $_POST['id']);
                    exit;
                }
                break;

            case 'update_status':
                $stmt = $db->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
                if ($stmt->execute([$_POST['statut'], $_POST['id']])) {
                    $_SESSION['message'] = 'Statut mis à jour avec succès !';
                } else {
                    $_SESSION['error'] = 'Erreur lors de la mise à jour du statut.';
                }
                header('Location: ?');
                exit;
                break;

            case 'update_paiement':
                $stmt = $db->prepare("
                    UPDATE reservations SET
                        statut_paiement = ?, montant_acompte = ?, montant_paye = ?,
                        mode_paiement = ?, date_paiement = ?, notes_paiement = ?
                    WHERE id = ?
                ");
                if ($stmt->execute([
                    $_POST['statut_paiement'],
                    $_POST['montant_acompte'],
                    $_POST['montant_paye'],
                    $_POST['mode_paiement'],
                    $_POST['date_paiement'] ?: null,
                    $_POST['notes_paiement'],
                    $_POST['id']
                ])) {
                    $_SESSION['message'] = 'Paiement mis à jour avec succès !';
                    header('Location: ?action=paiement&id=' . $_POST['id']);
                    exit;
                } else {
                    $_SESSION['error'] = 'Erreur lors de la mise à jour du paiement.';
                    header('Location: ?action=paiement&id=' . $_POST['id']);
                    exit;
                }
                break;

            case 'delete':
                $stmt = $db->prepare("DELETE FROM reservations WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    $_SESSION['message'] = 'Réservation supprimée avec succès !';
                } else {
                    $_SESSION['error'] = 'Erreur lors de la suppression.';
                }
                header('Location: ?');
                exit;
                break;
        }
    }
}

// Récupérer les messages de la session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Récupérer les données selon l'action
$reservations = getAllReservations();
$chambres = getAllChambres();
$reservation = null;

if (($action === 'edit' || $action === 'paiement') && $reservation_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, c.nom as chambre_nom, c.prix as prix_nuit
        FROM reservations r
        LEFT JOIN chambres c ON r.chambre_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch();
}

// Statistiques pour le dashboard
$stats = [];
if ($action === 'list') {
    $stats['total'] = count($reservations);
    $stats['en_attente'] = count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente'));
    $stats['confirmees'] = count(array_filter($reservations, fn($r) => $r['statut'] === 'confirmee'));
    $stats['non_payes'] = count(array_filter($reservations, fn($r) => ($r['statut_paiement'] ?? 'non_paye') === 'non_paye'));
    $stats['paye_total'] = array_sum(array_map(fn($r) => $r['montant_paye'] ?? 0, $reservations));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservations - Administration</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .reservation-detail {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-200);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .detail-item {
            padding: 16px;
            background: var(--gray-50);
            border-radius: var(--radius);
            border-left: 4px solid var(--primary);
        }

        .detail-label {
            font-size: 0.813rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .detail-value {
            font-size: 1.125rem;
            color: var(--gray-900);
            font-weight: 500;
        }

        .paiement-section {
            background: linear-gradient(135deg, rgba(139, 115, 85, 0.05), rgba(201, 184, 150, 0.05));
            padding: 24px;
            border-radius: var(--radius-md);
            border: 2px solid var(--primary);
            margin-top: 24px;
        }

        .paiement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .paiement-header h3 {
            color: var(--primary);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .montant-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
            padding: 20px;
            background: var(--white);
            border-radius: var(--radius);
            margin: 20px 0;
        }

        .progress-bar {
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin: 16px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transition: width 0.3s ease;
        }

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-mini {
            background: var(--white);
            padding: 16px;
            border-radius: var(--radius);
            text-align: center;
            border: 1px solid var(--gray-200);
        }

        .stat-mini-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-mini-label {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        .badge-paiement {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.813rem;
            font-weight: 600;
        }

        .badge-paiement-non_paye {
            background: rgba(200, 90, 84, 0.15);
            color: #8b3a36;
        }

        .badge-paiement-acompte {
            background: rgba(217, 168, 78, 0.15);
            color: #8b6e32;
        }

        .badge-paiement-paye {
            background: rgba(92, 138, 79, 0.15);
            color: #3d5c33;
        }

        .modal-paiement {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-paiement.active {
            display: flex;
        }

        .modal-content {
            background: var(--white);
            padding: 32px;
            border-radius: var(--radius-lg);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Gestion des réservations</h1>
            <?php if ($action === 'list'): ?>
                <a href="?action=new" class="btn btn-primary">➕ Nouvelle réservation</a>
            <?php else: ?>
                <a href="?" class="btn btn-secondary">← Retour à la liste</a>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo e($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Statistiques rapides -->
            <div class="stats-mini">
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-mini-label">Total</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $stats['en_attente']; ?></div>
                    <div class="stat-mini-label">En attente</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $stats['confirmees']; ?></div>
                    <div class="stat-mini-label">Confirmées</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $stats['non_payes']; ?></div>
                    <div class="stat-mini-label">Non payés</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo number_format($stats['paye_total'], 0); ?> €</div>
                    <div class="stat-mini-label">Encaissé</div>
                </div>
            </div>

            <!-- Liste des réservations -->
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Chambre</th>
                            <th>Dates</th>
                            <th>Prix</th>
                            <th>Paiement</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res):
                            $statut_paiement = $res['statut_paiement'] ?? 'non_paye';
                            $montant_paye = $res['montant_paye'] ?? 0;
                            $pourcentage_paye = $res['prix_total'] > 0 ? ($montant_paye / $res['prix_total']) * 100 : 0;
                        ?>
                            <tr>
                                <td><strong>#<?php echo $res['id']; ?></strong></td>
                                <td>
                                    <div><strong><?php echo e($res['nom']); ?></strong></div>
                                    <div style="font-size: 0.813rem; color: var(--gray-600);"><?php echo e($res['email']); ?></div>
                                </td>
                                <td><?php echo e($res['chambre_nom']); ?></td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($res['date_arrivee'])); ?></div>
                                    <div style="font-size: 0.813rem; color: var(--gray-600);">→ <?php echo date('d/m/Y', strtotime($res['date_depart'])); ?></div>
                                </td>
                                <td>
                                    <div><strong><?php echo number_format($res['prix_total'], 2); ?> €</strong></div>
                                    <div style="font-size: 0.813rem; color: var(--success);">
                                        Payé: <?php echo number_format($montant_paye, 2); ?> €
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-paiement badge-paiement-<?php echo $statut_paiement; ?>">
                                        <?php
                                        echo $statut_paiement === 'non_paye' ? '❌ Non payé' :
                                             ($statut_paiement === 'acompte' ? '⏳ Acompte' : '✅ Payé');
                                        ?>
                                    </span>
                                    <div class="progress-bar" style="margin-top: 8px;">
                                        <div class="progress-fill" style="width: <?php echo $pourcentage_paye; ?>%"></div>
                                    </div>
                                </td>
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
                                        <a href="?action=paiement&id=<?php echo $res['id']; ?>" class="btn-icon" title="Gérer le paiement">💰</a>
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

        <?php elseif ($action === 'paiement' && $reservation): ?>
            <!-- Gestion du paiement -->
            <div class="reservation-detail">
                <h2>💰 Gestion du paiement - Réservation #<?php echo $reservation['id']; ?></h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Client</div>
                        <div class="detail-value"><?php echo e($reservation['nom']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Chambre</div>
                        <div class="detail-value"><?php echo e($reservation['chambre_nom']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Dates</div>
                        <div class="detail-value">
                            <?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?> →
                            <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?>
                        </div>
                    </div>
                </div>

                <div class="paiement-section">
                    <div class="paiement-header">
                        <h3>📊 Détails du paiement</h3>
                        <span class="badge-paiement badge-paiement-<?php echo $reservation['statut_paiement'] ?? 'non_paye'; ?>">
                            <?php
                            $sp = $reservation['statut_paiement'] ?? 'non_paye';
                            echo $sp === 'non_paye' ? '❌ Non payé' : ($sp === 'acompte' ? '⏳ Acompte versé' : '✅ Payé');
                            ?>
                        </span>
                    </div>

                    <?php
                    $montant_paye = $reservation['montant_paye'] ?? 0;
                    $montant_restant = $reservation['prix_total'] - $montant_paye;
                    $pourcentage = $reservation['prix_total'] > 0 ? ($montant_paye / $reservation['prix_total']) * 100 : 0;
                    ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                        <div class="stat-mini">
                            <div class="stat-mini-label">Prix total</div>
                            <div class="stat-mini-number"><?php echo number_format($reservation['prix_total'], 2); ?> €</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-label">Payé</div>
                            <div class="stat-mini-number" style="color: var(--success);"><?php echo number_format($montant_paye, 2); ?> €</div>
                        </div>
                        <div class="stat-mini">
                            <div class="stat-mini-label">Restant</div>
                            <div class="stat-mini-number" style="color: var(--danger);"><?php echo number_format($montant_restant, 2); ?> €</div>
                        </div>
                    </div>

                    <div class="progress-bar" style="height: 12px;">
                        <div class="progress-fill" style="width: <?php echo $pourcentage; ?>%"></div>
                    </div>
                    <div style="text-align: center; margin-top: 8px; font-size: 0.875rem; color: var(--gray-600);">
                        <?php echo round($pourcentage); ?>% payé
                    </div>

                    <form method="POST" class="form" style="margin-top: 32px;">
                        <input type="hidden" name="action" value="update_paiement">
                        <input type="hidden" name="id" value="<?php echo $reservation['id']; ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="statut_paiement">Statut du paiement *</label>
                                <select id="statut_paiement" name="statut_paiement" required>
                                    <option value="non_paye" <?php echo ($reservation['statut_paiement'] ?? 'non_paye') === 'non_paye' ? 'selected' : ''; ?>>Non payé</option>
                                    <option value="acompte" <?php echo ($reservation['statut_paiement'] ?? '') === 'acompte' ? 'selected' : ''; ?>>Acompte versé</option>
                                    <option value="paye" <?php echo ($reservation['statut_paiement'] ?? '') === 'paye' ? 'selected' : ''; ?>>Payé intégralement</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="mode_paiement">Mode de paiement</label>
                                <select id="mode_paiement" name="mode_paiement">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="especes" <?php echo ($reservation['mode_paiement'] ?? '') === 'especes' ? 'selected' : ''; ?>>Espèces</option>
                                    <option value="carte" <?php echo ($reservation['mode_paiement'] ?? '') === 'carte' ? 'selected' : ''; ?>>Carte bancaire</option>
                                    <option value="virement" <?php echo ($reservation['mode_paiement'] ?? '') === 'virement' ? 'selected' : ''; ?>>Virement</option>
                                    <option value="cheque" <?php echo ($reservation['mode_paiement'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Chèque</option>
                                    <option value="paypal" <?php echo ($reservation['mode_paiement'] ?? '') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="montant_acompte">Montant de l'acompte (€)</label>
                                <input type="number" id="montant_acompte" name="montant_acompte" step="0.01" min="0"
                                    value="<?php echo $reservation['montant_acompte'] ?? 0; ?>">
                            </div>

                            <div class="form-group">
                                <label for="montant_paye">Montant total payé (€) *</label>
                                <input type="number" id="montant_paye" name="montant_paye" step="0.01" min="0"
                                    value="<?php echo $montant_paye; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="date_paiement">Date du paiement</label>
                                <input type="date" id="date_paiement" name="date_paiement"
                                    value="<?php echo $reservation['date_paiement'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes_paiement">Notes sur le paiement</label>
                            <textarea id="notes_paiement" name="notes_paiement" rows="3"><?php echo e($reservation['notes_paiement'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Enregistrer le paiement</button>
                            <a href="?" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <!-- Formulaire de création/modification -->
            <div class="card">
                <h2><?php echo $action === 'new' ? '➕ Nouvelle réservation' : '✏️ Modifier la réservation'; ?></h2>
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

                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" value="<?php echo $reservation ? e($reservation['telephone']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
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

                    <?php if ($action === 'new'): ?>
                    <div class="paiement-section">
                        <h3 style="margin-bottom: 20px;">💰 Informations de paiement</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="statut_paiement">Statut du paiement</label>
                                <select id="statut_paiement" name="statut_paiement">
                                    <option value="non_paye">Non payé</option>
                                    <option value="acompte">Acompte versé</option>
                                    <option value="paye">Payé intégralement</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="montant_acompte">Montant de l'acompte (€)</label>
                                <input type="number" id="montant_acompte" name="montant_acompte" step="0.01" min="0" value="0">
                            </div>

                            <div class="form-group">
                                <label for="montant_paye">Montant payé (€)</label>
                                <input type="number" id="montant_paye" name="montant_paye" step="0.01" min="0" value="0">
                            </div>

                            <div class="form-group">
                                <label for="mode_paiement">Mode de paiement</label>
                                <select id="mode_paiement" name="mode_paiement">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="especes">Espèces</option>
                                    <option value="carte">Carte bancaire</option>
                                    <option value="virement">Virement</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="paypal">PayPal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="paiement-section">
                        <h3 style="margin-bottom: 20px;">💰 Informations de paiement</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="statut_paiement">Statut du paiement</label>
                                <select id="statut_paiement" name="statut_paiement">
                                    <option value="non_paye" <?php echo ($reservation['statut_paiement'] ?? 'non_paye') === 'non_paye' ? 'selected' : ''; ?>>Non payé</option>
                                    <option value="acompte" <?php echo ($reservation['statut_paiement'] ?? '') === 'acompte' ? 'selected' : ''; ?>>Acompte versé</option>
                                    <option value="paye" <?php echo ($reservation['statut_paiement'] ?? '') === 'paye' ? 'selected' : ''; ?>>Payé intégralement</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="montant_acompte">Montant de l'acompte (€)</label>
                                <input type="number" id="montant_acompte" name="montant_acompte" step="0.01" min="0"
                                    value="<?php echo $reservation['montant_acompte'] ?? 0; ?>">
                            </div>

                            <div class="form-group">
                                <label for="montant_paye">Montant payé (€)</label>
                                <input type="number" id="montant_paye" name="montant_paye" step="0.01" min="0"
                                    value="<?php echo $reservation['montant_paye'] ?? 0; ?>">
                            </div>

                            <div class="form-group">
                                <label for="mode_paiement">Mode de paiement</label>
                                <select id="mode_paiement" name="mode_paiement">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="especes" <?php echo ($reservation['mode_paiement'] ?? '') === 'especes' ? 'selected' : ''; ?>>Espèces</option>
                                    <option value="carte" <?php echo ($reservation['mode_paiement'] ?? '') === 'carte' ? 'selected' : ''; ?>>Carte bancaire</option>
                                    <option value="virement" <?php echo ($reservation['mode_paiement'] ?? '') === 'virement' ? 'selected' : ''; ?>>Virement</option>
                                    <option value="cheque" <?php echo ($reservation['mode_paiement'] ?? '') === 'cheque' ? 'selected' : ''; ?>>Chèque</option>
                                    <option value="paypal" <?php echo ($reservation['mode_paiement'] ?? '') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes_paiement">Notes sur le paiement</label>
                            <textarea id="notes_paiement" name="notes_paiement" rows="2"><?php echo e($reservation['notes_paiement'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="commentaire">Commentaire</label>
                        <textarea id="commentaire" name="commentaire" rows="3"><?php echo $reservation ? e($reservation['commentaire']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'new' ? '➕ Créer la réservation' : '💾 Enregistrer les modifications'; ?>
                        </button>
                        <a href="?" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
