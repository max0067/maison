<?php
require_once __DIR__ . '/../config/database.php';

// Récupérer un contenu par sa clé
function getContenu($cle) {
    $db = getDB();
    $stmt = $db->prepare("SELECT valeur FROM contenus WHERE cle = ?");
    $stmt->execute([$cle]);
    $result = $stmt->fetch();
    return $result ? $result['valeur'] : '';
}

// Mettre à jour un contenu
function updateContenu($cle, $valeur) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE contenus SET valeur = ? WHERE cle = ?");
    return $stmt->execute([$valeur, $cle]);
}

// Récupérer toutes les chambres actives
function getChambresActives() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM chambres WHERE actif = 1 ORDER BY ordre ASC");
    return $stmt->fetchAll();
}

// Récupérer toutes les chambres (pour admin)
function getAllChambres() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM chambres ORDER BY ordre ASC");
    return $stmt->fetchAll();
}

// Récupérer une chambre par ID
function getChambreById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM chambres WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Créer une réservation
function createReservation($data) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO reservations (nom, email, telephone, chambre_id, date_arrivee, date_depart, nombre_personnes, prix_total, commentaire)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    return $stmt->execute([
        $data['nom'],
        $data['email'],
        $data['telephone'] ?? null,
        $data['chambre_id'],
        $data['date_arrivee'],
        $data['date_depart'],
        $data['nombre_personnes'],
        $data['prix_total'],
        $data['commentaire'] ?? null
    ]);
}

// Récupérer toutes les réservations
function getAllReservations() {
    $db = getDB();
    $stmt = $db->query("
        SELECT r.*, c.nom as chambre_nom
        FROM reservations r
        LEFT JOIN chambres c ON r.chambre_id = c.id
        ORDER BY r.date_arrivee DESC, r.created_at DESC
    ");
    return $stmt->fetchAll();
}

// Calculer le prix total d'une réservation
function calculerPrixTotal($chambre_id, $date_arrivee, $date_depart) {
    $chambre = getChambreById($chambre_id);
    if (!$chambre) {
        return 0;
    }

    $debut = new DateTime($date_arrivee);
    $fin = new DateTime($date_depart);
    $diff = $debut->diff($fin);
    $nbNuits = $diff->days;

    return $nbNuits * $chambre['prix'];
}

// Vérifier la disponibilité d'une chambre
function verifierDisponibilite($chambre_id, $date_arrivee, $date_depart, $reservation_id = null) {
    $db = getDB();

    $sql = "SELECT COUNT(*) as count FROM reservations
            WHERE chambre_id = ?
            AND statut != 'annulee'
            AND (
                (date_arrivee BETWEEN ? AND ?) OR
                (date_depart BETWEEN ? AND ?) OR
                (date_arrivee <= ? AND date_depart >= ?)
            )";

    if ($reservation_id) {
        $sql .= " AND id != ?";
    }

    $stmt = $db->prepare($sql);

    if ($reservation_id) {
        $stmt->execute([$chambre_id, $date_arrivee, $date_depart, $date_arrivee, $date_depart, $date_arrivee, $date_depart, $reservation_id]);
    } else {
        $stmt->execute([$chambre_id, $date_arrivee, $date_depart, $date_arrivee, $date_depart, $date_arrivee, $date_depart]);
    }

    $result = $stmt->fetch();
    return $result['count'] == 0;
}

// Upload d'image
function uploadImage($file, $directory = 'uploads/chambres/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = __DIR__ . '/../' . $directory . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return false;
}

// Formater la date en français
function formatDateFr($date) {
    $mois = [
        'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
        'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
        'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
        'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
    ];

    $dateObj = new DateTime($date);
    $formatted = $dateObj->format('d F Y');

    return str_replace(array_keys($mois), array_values($mois), $formatted);
}

// Escape HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
