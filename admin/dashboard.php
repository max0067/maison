<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$chambres = getAllChambres();
$reservations = getAllReservations();

// Statistiques
$totalReservations = count($reservations);
$reservationsEnAttente = count(array_filter($reservations, fn($r) => $r['statut'] === 'en_attente'));
$reservationsConfirmees = count(array_filter($reservations, fn($r) => $r['statut'] === 'confirmee'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administration</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <h1>Tableau de bord</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $totalReservations; ?></div>
                    <div class="stat-label">Réservations totales</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $reservationsEnAttente; ?></div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo $reservationsConfirmees; ?></div>
                    <div class="stat-label">Confirmées</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🏠</div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo count($chambres); ?></div>
                    <div class="stat-label">Chambres</div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-section">
                <h2>Dernières réservations</h2>
                <?php if (empty($reservations)): ?>
                    <p style="color: #666;">Aucune réservation pour le moment</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Chambre</th>
                                <th>Arrivée</th>
                                <th>Départ</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($reservations, 0, 5) as $reservation): ?>
                                <tr>
                                    <td><?php echo e($reservation['nom']); ?></td>
                                    <td><?php echo e($reservation['chambre_nom']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_arrivee'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $reservation['statut']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $reservation['statut'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="reservations.php" class="btn btn-secondary">Voir toutes les réservations</a>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <h2>Raccourcis</h2>
                <div class="quick-links">
                    <a href="reservations.php?action=new" class="quick-link">
                        <div class="quick-link-icon">➕</div>
                        <div class="quick-link-text">Nouvelle réservation</div>
                    </a>
                    <a href="chambres.php" class="quick-link">
                        <div class="quick-link-icon">🏠</div>
                        <div class="quick-link-text">Gérer les chambres</div>
                    </a>
                    <a href="contenus.php" class="quick-link">
                        <div class="quick-link-icon">✏️</div>
                        <div class="quick-link-text">Modifier le contenu</div>
                    </a>
                    <a href="../index.php" target="_blank" class="quick-link">
                        <div class="quick-link-icon">🌐</div>
                        <div class="quick-link-text">Voir le site</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
