<?php
// ============================================
// api_verifier_telephone.php
// ============================================
require_once 'db.php';

// On utilise la fonction getJsonBody() que nous avons définie dans db.php
$data      = getJsonBody();
$telephone = trim($data['telephone'] ?? '');

if (empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro manquant.']);
    exit;
}

try {
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
} catch (Exception $e) {
    // En cas d'erreur de base de données (ex: table 'client' manquante)
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}