<?php
// api_verifier_telephone.php
require_once 'db.php';

// Optionnel : s'assurer que getJsonBody est défini (souvent dans db.php)
$data      = json_decode(file_get_contents('php://input'), true);
$telephone = trim($data['telephone'] ?? '');

if (empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro manquant.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id_user FROM client WHERE telephone = ?");
$stmt->execute([$telephone]);

if ($stmt->fetch()) {
    // Le numéro existe : on pourra se connecter
    echo json_encode(['success' => true, 'message' => 'Client existant.']);
} else {
    // Le numéro n'existe pas : on peut créer un compte
    echo json_encode(['success' => false, 'message' => 'Nouveau client.']);
}