<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$chambre_id = $_GET['id'] ?? null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $db = getDB();

        switch ($_POST['action']) {
            case 'create':
                $photo = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo = uploadImage($_FILES['photo']);
                }

                $stmt = $db->prepare("INSERT INTO chambres (nom, description, prix, capacite, photo, ordre, actif) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['prix'],
                    $_POST['capacite'],
                    $photo,
                    $_POST['ordre'] ?? 0,
                    isset($_POST['actif']) ? 1 : 0
                ])) {
                    $message = 'Chambre créée avec succès !';
                    $action = 'list';
                } else {
                    $error = 'Erreur lors de la création de la chambre.';
                }
                break;

            case 'update':
                $chambre = getChambreById($_POST['id']);
                $photo = $chambre['photo'];

                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $newPhoto = uploadImage($_FILES['photo']);
                    if ($newPhoto) {
                        // Supprimer l'ancienne photo
                        if ($photo && file_exists(__DIR__ . '/../uploads/chambres/' . $photo)) {
                            unlink(__DIR__ . '/../uploads/chambres/' . $photo);
                        }
                        $photo = $newPhoto;
                    }
                }

                $stmt = $db->prepare("UPDATE chambres SET nom = ?, description = ?, prix = ?, capacite = ?, photo = ?, ordre = ?, actif = ? WHERE id = ?");
                if ($stmt->execute([
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['prix'],
                    $_POST['capacite'],
                    $photo,
                    $_POST['ordre'] ?? 0,
                    isset($_POST['actif']) ? 1 : 0,
                    $_POST['id']
                ])) {
                    $message = 'Chambre modifiée avec succès !';
                    $action = 'list';
                } else {
                    $error = 'Erreur lors de la modification.';
                }
                break;

            case 'delete':
                $chambre = getChambreById($_POST['id']);
                $stmt = $db->prepare("DELETE FROM chambres WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    // Supprimer la photo
                    if ($chambre['photo'] && file_exists(__DIR__ . '/../uploads/chambres/' . $chambre['photo'])) {
                        unlink(__DIR__ . '/../uploads/chambres/' . $chambre['photo']);
                    }
                    $message = 'Chambre supprimée avec succès !';
                } else {
                    $error = 'Erreur lors de la suppression.';
                }
                break;
        }
    }
}

$chambres = getAllChambres();
$chambre = null;

if ($action === 'edit' && $chambre_id) {
    $chambre = getChambreById($chambre_id);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chambres - Administration</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Gestion des chambres</h1>
            <?php if ($action === 'list'): ?>
                <a href="?action=new" class="btn btn-primary">Ajouter une chambre</a>
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
            <!-- Liste des chambres -->
            <div class="chambres-grid">
                <?php foreach ($chambres as $c): ?>
                    <div class="chambre-card <?php echo $c['actif'] ? '' : 'inactive'; ?>">
                        <?php if ($c['photo']): ?>
                            <img src="../uploads/chambres/<?php echo e($c['photo']); ?>" alt="<?php echo e($c['nom']); ?>">
                        <?php else: ?>
                            <div class="no-image">Pas d'image</div>
                        <?php endif; ?>
                        <div class="chambre-info">
                            <h3><?php echo e($c['nom']); ?></h3>
                            <p><?php echo e($c['description']); ?></p>
                            <div class="chambre-details">
                                <span class="prix"><?php echo number_format($c['prix'], 2); ?> €/nuit</span>
                                <span class="capacite">👥 <?php echo $c['capacite']; ?> pers.</span>
                                <span class="ordre">Ordre: <?php echo $c['ordre']; ?></span>
                            </div>
                            <?php if (!$c['actif']): ?>
                                <span class="badge badge-inactive">Désactivée</span>
                            <?php endif; ?>
                            <div class="chambre-actions">
                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-small">Modifier</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette chambre ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($action === 'new' || $action === 'edit'): ?>
            <!-- Formulaire de création/modification -->
            <div class="card">
                <h2><?php echo $action === 'new' ? 'Nouvelle chambre' : 'Modifier la chambre'; ?></h2>
                <form method="POST" enctype="multipart/form-data" class="form">
                    <input type="hidden" name="action" value="<?php echo $action === 'new' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $chambre['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="nom">Nom de la chambre *</label>
                        <input type="text" id="nom" name="nom" required value="<?php echo $chambre ? e($chambre['nom']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?php echo $chambre ? e($chambre['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="prix">Prix par nuit (€) *</label>
                            <input type="number" id="prix" name="prix" step="0.01" required
                                value="<?php echo $chambre ? $chambre['prix'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="capacite">Capacité (personnes) *</label>
                            <input type="number" id="capacite" name="capacite" min="1" required
                                value="<?php echo $chambre ? $chambre['capacite'] : '2'; ?>">
                        </div>

                        <div class="form-group">
                            <label for="ordre">Ordre d'affichage</label>
                            <input type="number" id="ordre" name="ordre" min="0"
                                value="<?php echo $chambre ? $chambre['ordre'] : '0'; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="photo">Photo de la chambre</label>
                        <?php if ($chambre && $chambre['photo']): ?>
                            <div class="current-photo">
                                <img src="../uploads/chambres/<?php echo e($chambre['photo']); ?>" alt="Photo actuelle" style="max-width: 300px;">
                                <p>Photo actuelle (laissez vide pour la conserver)</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="photo" name="photo" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="actif" <?php echo ($chambre && $chambre['actif']) || !$chambre ? 'checked' : ''; ?>>
                            Chambre active (visible sur le site)
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'new' ? 'Créer la chambre' : 'Enregistrer les modifications'; ?>
                        </button>
                        <a href="?" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
