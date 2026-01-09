<?php
require_once 'includes/functions.php';

$chambres = getChambresActives();

// Contenus dynamiques
$site_titre = getContenu('site_titre');
$site_sous_titre = getContenu('site_sous_titre');
$maison_titre = getContenu('maison_titre');
$maison_description = getContenu('maison_description');
$chambres_titre = getContenu('chambres_titre');
$reservation_titre = getContenu('reservation_titre');
$footer_text = getContenu('footer_text');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($site_titre); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title"><?php echo e($site_titre); ?></h1>
            <p class="hero-subtitle"><?php echo e($site_sous_titre); ?></p>
        </div>
    </section>

    <!-- Maison Section -->
    <section class="maison-section">
        <div class="container">
            <h2 class="section-title"><?php echo e($maison_titre); ?></h2>
            <p class="maison-description"><?php echo nl2br(e($maison_description)); ?></p>
        </div>
    </section>

    <!-- Chambres Section -->
    <section class="chambres-section">
        <div class="container">
            <h2 class="section-title"><?php echo e($chambres_titre); ?></h2>

            <div class="chambres-grid">
                <?php foreach ($chambres as $chambre): ?>
                    <div class="chambre-card">
                        <?php if ($chambre['photo']): ?>
                            <img src="uploads/chambres/<?php echo e($chambre['photo']); ?>"
                                 alt="<?php echo e($chambre['nom']); ?>"
                                 class="chambre-image">
                        <?php else: ?>
                            <div class="chambre-image-placeholder"></div>
                        <?php endif; ?>

                        <div class="chambre-content">
                            <h3 class="chambre-nom"><?php echo e($chambre['nom']); ?></h3>
                            <p class="chambre-description"><?php echo e($chambre['description']); ?></p>
                            <p class="chambre-prix">
                                <strong><?php echo number_format($chambre['prix'], 0); ?> €</strong> / nuit
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Réservation Section -->
    <section class="reservation-section">
        <div class="container">
            <h2 class="section-title"><?php echo e($reservation_titre); ?></h2>

            <div class="reservation-form-container">
                <form id="reservationForm" method="POST" action="reserver.php" class="reservation-form">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>

                    <div class="form-group">
                        <label for="chambre_id">Chambre</label>
                        <select id="chambre_id" name="chambre_id" required>
                            <option value="">Sélectionner une chambre</option>
                            <?php foreach ($chambres as $chambre): ?>
                                <option value="<?php echo $chambre['id']; ?>"
                                        data-prix="<?php echo $chambre['prix']; ?>">
                                    <?php echo e($chambre['nom']) . ' – ' . number_format($chambre['prix'], 0) . ' €'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_arrivee">Date d'arrivée</label>
                            <input type="date" id="date_arrivee" name="date_arrivee" required>
                        </div>

                        <div class="form-group">
                            <label for="date_depart">Date de départ</label>
                            <input type="date" id="date_depart" name="date_depart" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nombre_personnes">Nombre de personnes</label>
                        <input type="number" id="nombre_personnes" name="nombre_personnes" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="commentaire">Commentaire (optionnel)</label>
                        <textarea id="commentaire" name="commentaire" rows="3"></textarea>
                    </div>

                    <div class="reservation-total">
                        <span class="total-label">Total :</span>
                        <span class="total-amount" id="total">0 €</span>
                    </div>

                    <button type="submit" class="btn-reserver">Réserver</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p><?php echo e($footer_text); ?></p>
        </div>
    </footer>

    <script>
        // Calcul automatique du prix total
        const chambreSelect = document.getElementById('chambre_id');
        const dateArrivee = document.getElementById('date_arrivee');
        const dateDepart = document.getElementById('date_depart');
        const totalElement = document.getElementById('total');

        function calculerTotal() {
            const chambreOption = chambreSelect.options[chambreSelect.selectedIndex];
            const prixNuit = parseFloat(chambreOption.dataset.prix || 0);

            if (dateArrivee.value && dateDepart.value && prixNuit > 0) {
                const debut = new Date(dateArrivee.value);
                const fin = new Date(dateDepart.value);
                const diffTime = fin - debut;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 0) {
                    const total = diffDays * prixNuit;
                    totalElement.textContent = total.toFixed(0) + ' €';
                } else {
                    totalElement.textContent = '0 €';
                }
            } else {
                totalElement.textContent = '0 €';
            }
        }

        chambreSelect.addEventListener('change', calculerTotal);
        dateArrivee.addEventListener('change', calculerTotal);
        dateDepart.addEventListener('change', calculerTotal);

        // Définir la date minimum à aujourd'hui
        const today = new Date().toISOString().split('T')[0];
        dateArrivee.setAttribute('min', today);
        dateDepart.setAttribute('min', today);

        // S'assurer que la date de départ est après la date d'arrivée
        dateArrivee.addEventListener('change', function() {
            dateDepart.setAttribute('min', this.value);
            if (dateDepart.value && dateDepart.value <= this.value) {
                dateDepart.value = '';
            }
        });
    </script>
</body>
</html>
