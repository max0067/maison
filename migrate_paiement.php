<?php
/**
 * Script de migration pour ajouter les champs de paiement
 * Exécutez ce fichier UNE SEULE FOIS pour ajouter les colonnes de paiement
 */

require_once 'config/database.php';

$message = '';
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();

        // Lire et exécuter le fichier SQL
        $sql = file_get_contents(__DIR__ . '/migration_paiement.sql');

        // Séparer les requêtes et les exécuter
        $queries = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($queries as $query) {
            if (!empty($query) && substr($query, 0, 2) !== '--') {
                try {
                    $db->exec($query);
                } catch (PDOException $e) {
                    // Si la colonne existe déjà, continuer
                    if (strpos($e->getMessage(), 'Duplicate column') === false) {
                        throw $e;
                    }
                }
            }
        }

        $success = true;
        $message = 'Migration réussie ! Les champs de paiement ont été ajoutés à la table reservations.';

    } catch (PDOException $e) {
        $error = 'Erreur lors de la migration : ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration - Ajout champs paiement</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #8b7355, #c9b896);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #3a3a3a;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #2e7d32;
        }

        .error {
            background: #ffebee;
            border-left: 4px solid #f44336;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c62828;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: #8b7355;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #6f5a42;
        }

        .link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #8b7355;
            text-decoration: none;
        }

        ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Migration - Système de paiement</h1>

        <?php if ($success): ?>
            <div class="success">
                <p><?php echo $message; ?></p>
            </div>

            <div class="info">
                <p><strong>Les champs suivants ont été ajoutés :</strong></p>
                <ul>
                    <li>statut_paiement (non payé, acompte, payé)</li>
                    <li>montant_acompte (montant de l'acompte versé)</li>
                    <li>montant_paye (montant total payé)</li>
                    <li>mode_paiement (espèces, carte, virement, etc.)</li>
                    <li>date_paiement (date du paiement)</li>
                    <li>notes_paiement (notes sur le paiement)</li>
                </ul>
            </div>

            <a href="admin/reservations.php" class="link">Aller aux réservations →</a>

        <?php elseif ($error): ?>
            <div class="error">
                <p><?php echo $error; ?></p>
            </div>

        <?php else: ?>
            <div class="info">
                <p><strong>Cette migration va ajouter :</strong></p>
                <ul>
                    <li>Statut de paiement (non payé, acompte versé, payé)</li>
                    <li>Montant de l'acompte</li>
                    <li>Montant total payé</li>
                    <li>Mode de paiement</li>
                    <li>Date de paiement</li>
                    <li>Notes sur le paiement</li>
                </ul>
                <p><strong>⚠️ Important :</strong> Cette migration modifie la table <code>reservations</code>.</p>
            </div>

            <form method="POST">
                <button type="submit" class="btn">Exécuter la migration</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
