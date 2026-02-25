<?php
// api_inscription.php - Inscription d'un nouveau client
require_once 'db.php';

// Récupération sécurisée des données
$data      = json_decode(file_get_contents('php://input'), true); // Alternative à getJsonBody si non défini
$nom       = trim($data['nom'] ?? '');
$telephone = trim($data['telephone'] ?? '');
$pin       = trim($data['pin'] ?? '');

// 1. Validations de base
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

try {
    $db = getDB();

    // 2. Vérifier si le numéro existe déjà (Double sécurité avec le front)
    $stmt = $db->prepare("SELECT id_user FROM client WHERE telephone = ?");
    $stmt->execute([$telephone]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce numéro est déjà lié à un compte existant.']);
        exit;
    }

    // 3. Insertion du nouveau client
    // Note : On pourrait hacher le PIN ici avec password_hash($pin, PASSWORD_DEFAULT)
    $stmt = $db->prepare("INSERT INTO client (nom_user, telephone, pin_user, solde_user) VALUES (?, ?, ?, 5000.00)");
    $stmt->execute([$nom, $telephone, $pin]);
    $newId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Félicitations ! Votre compte EASY-PAY est activé.',
        'client'  => [
            'id_user'   => $newId,
            'nom_user'  => $nom,
            'solde_user' => 5000
        ]
    ]);

} catch (PDOException $e) {
    // Loguer l'erreur en production, ici on affiche un message générique
    echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la création du compte.']);
}