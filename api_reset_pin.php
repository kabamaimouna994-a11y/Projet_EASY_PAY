<?php
// api_reset_pin.php - Réinitialisation du PIN (PIN stocké en clair)
require_once 'db.php';

$data        = getJsonBody();
$telephone   = trim($data['telephone'] ?? '');
$nouveau_pin = trim($data['nouveau_pin'] ?? '');

if (empty($telephone) || strlen($nouveau_pin) !== 4 || !ctype_digit($nouveau_pin)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("UPDATE client SET pin_user = ? WHERE telephone = ?");
$stmt->execute([$nouveau_pin, $telephone]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'PIN réinitialisé avec succès !']);
} else {
    echo json_encode(['success' => false, 'message' => 'Compte introuvable.']);
}
