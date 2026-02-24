<?php
// api_login_client.php - Connexion client via PIN
require_once 'db.php';

$data      = getJsonBody();
$telephone = trim($data['telephone'] ?? '');
$pin       = trim($data['pin'] ?? '');

if (empty($telephone) || strlen($pin) !== 4) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
    exit;
}

$db   = getDB();
// On cherche le client par téléphone ET pin (PIN stocké en clair comme demandé)
$stmt = $db->prepare("SELECT id_user, nom_user, solde_user FROM client WHERE telephone = ? AND pin_user = ?");
$stmt->execute([$telephone, $pin]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(['success' => false, 'message' => 'PIN incorrect ou compte introuvable.']);
    exit;
}

echo json_encode([
    'success' => true,
    'client'  => [
        'id_user'    => $client['id_user'],
        'nom_user'   => $client['nom_user'],
        'solde_user' => (float) $client['solde_user']
    ]
]);
