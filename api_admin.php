<?php
// api_admin.php
require_once 'db.php';

// Récupération des données dynamiques
$data = json_decode(file_get_contents('php://input'), true);
$nom  = strtoupper(trim($data['nom_boutique'] ?? ''));
$pin  = trim($data['pin'] ?? '');

if (empty($nom) || strlen($pin) !== 4) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

try {
    $db = getDB();

    // 1. Vérifier si le nom existe déjà
    $check = $db->prepare("SELECT id_marchand FROM marchand WHERE nom_boutique = ?");
    $check->execute([$nom]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cette boutique existe déjà.']);
        exit;
    }

    // 2. Génération DYNAMIQUE du code (évite le "0")
    $code_genere = 'MCH-' . strtoupper(substr(uniqid(), -6));

    // 3. Insertion
    $stmt = $db->prepare("INSERT INTO marchand (code_marchand, nom_boutique, pin_marchand) VALUES (?, ?, ?)");
    $stmt->execute([$code_genere, $nom, $pin]);

    echo json_encode([
        'success' => true, 
        'code'    => $code_genere,
        'message' => 'Marchand ajouté avec succès.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()]);
}