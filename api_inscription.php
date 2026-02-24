<?php
// api_inscription.php - Inscription d'un nouveau client
require_once 'db.php';

$data      = getJsonBody();
$nom       = trim($data['nom'] ?? '');
$telephone = trim($data['telephone'] ?? '');
$pin       = trim($data['pin'] ?? '');

// Validations
if (strlen($nom) < 2) {
    echo json_encode(['success' => false, 'message' => 'Le prénom doit faire au moins 2 caractères.']);
    exit;
}
if (strlen($telephone) < 8) {
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide.']);
    exit;
}
if (strlen($pin) !== 4 || !ctype_digit($pin)) {
    echo json_encode(['success' => false, 'message' => 'Le PIN doit contenir exactement 4 chiffres.']);
    exit;
}

$db = getDB();

// Vérifier si le numéro existe déjà
$stmt = $db->prepare("SELECT id_user FROM client WHERE telephone = ?");
$stmt->execute([$telephone]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Ce numéro appartient déjà à un compte.']);
    exit;
}

// Insertion du nouveau client (solde de départ : 5000)
$stmt = $db->prepare("INSERT INTO client (nom_user, telephone, pin_user, solde_user) VALUES (?, ?, ?, 5000.00)");
$stmt->execute([$nom, $telephone, $pin]);
$newId = $db->lastInsertId();

echo json_encode([
    'success' => true,
    'message' => 'Compte créé avec succès !',
    'client'  => [
        'id_user'   => $newId,
        'nom_user'  => $nom,
        'solde_user' => 5000
    ]
]);
