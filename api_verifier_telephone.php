<?php
// api_verifier_telephone.php - Vérification numéro (inscription + récupération PIN)
require_once 'db.php';

$data      = getJsonBody();
$telephone = trim($data['telephone'] ?? '');

if (empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro manquant.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id_user FROM client WHERE telephone = ?");
$stmt->execute([$telephone]);

if ($stmt->fetch()) {
    // Numéro trouvé en BDD
    echo json_encode(['success' => true, 'message' => 'Numéro vérifié.']);
} else {
    // Numéro introuvable
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone inconnu.']);
}
