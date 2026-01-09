<?php
/**
 * Script pour générer un hash de mot de passe
 * Utilisez ce script pour créer un nouveau mot de passe admin
 */

// Générer le hash pour "admin123"
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Hash généré pour le mot de passe '$password' :\n\n";
echo $hash . "\n\n";

// Vérifier que le hash fonctionne
if (password_verify($password, $hash)) {
    echo "✓ Vérification réussie : le hash fonctionne correctement\n";
} else {
    echo "✗ Erreur : le hash ne fonctionne pas\n";
}
?>
