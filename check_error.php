<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic des erreurs</h1>";

// Test 1: Vérifier la connexion à la base de données
echo "<h2>1. Test de connexion à la base de données</h2>";
try {
    require_once 'config/database.php';
    $db = getDB();
    echo "✅ Connexion à la base de données OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Vérifier si la table reservations existe
echo "<h2>2. Vérification de la table reservations</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'reservations'");
    $table = $stmt->fetch();
    if ($table) {
        echo "✅ Table 'reservations' existe<br>";
    } else {
        echo "❌ Table 'reservations' n'existe pas<br>";
        die();
    }
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Vérifier les colonnes de paiement
echo "<h2>3. Vérification des colonnes de paiement</h2>";
try {
    $stmt = $db->query("DESCRIBE reservations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $required_columns = [
        'statut_paiement',
        'montant_acompte',
        'montant_paye',
        'mode_paiement',
        'date_paiement',
        'notes_paiement'
    ];

    echo "Colonnes existantes : " . implode(', ', $columns) . "<br><br>";

    $missing_columns = [];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Colonne '$col' existe<br>";
        } else {
            echo "❌ Colonne '$col' MANQUANTE<br>";
            $missing_columns[] = $col;
        }
    }

    if (!empty($missing_columns)) {
        echo "<br><strong style='color: red;'>⚠️ ATTENTION: Des colonnes manquent dans la base de données!</strong><br>";
        echo "Vous devez exécuter la migration en accédant à <a href='migrate_paiement.php'>migrate_paiement.php</a><br>";
    }

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "<br>";
}

// Test 4: Tester une requête sur une réservation
echo "<h2>4. Test de récupération d'une réservation</h2>";
try {
    $stmt = $db->prepare("
        SELECT r.*, c.nom as chambre_nom, c.prix as prix_nuit
        FROM reservations r
        LEFT JOIN chambres c ON r.chambre_id = c.id
        WHERE r.id = 1
    ");
    $stmt->execute();
    $reservation = $stmt->fetch();

    if ($reservation) {
        echo "✅ Réservation #1 récupérée avec succès<br>";
        echo "<pre>";
        print_r($reservation);
        echo "</pre>";
    } else {
        echo "⚠️ Aucune réservation avec l'ID 1<br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "<br>";
}

echo "<br><h2>Conclusion</h2>";
echo "<p>Si toutes les vérifications sont OK, le problème vient d'ailleurs.</p>";
echo "<p>Si des colonnes manquent, exécutez <a href='migrate_paiement.php'><strong>migrate_paiement.php</strong></a></p>";
?>
