<?php
// api_paiement.php - Gestion des marchands et transactions
require_once 'db.php';

// Récupération des données JSON envoyées par le téléphone
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$db = getDB();

// --- PARTIE 1 : VÉRIFICATION DU MARCHAND (Utilisé lors de la saisie du code) ---
if ($action === 'check_merchant') {
    $code = strtoupper(trim($data['merchant_code'] ?? ''));
    
    if (empty($code)) {
        echo json_encode(['success' => false]);
        exit;
    }

    // On utilise 'nom_boutique' car c'est le nom réel en BDD
    $stmt = $db->prepare("SELECT nom_boutique FROM marchand WHERE merchant_fixed_code = ?");
    $stmt->execute([$code]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($m) {
        echo json_encode(['success' => true, 'nom_boutique' => $m['nom_boutique']]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// --- PARTIE 2 : TRAITEMENT DU PAIEMENT (Après saisie du PIN) ---
$id_user       = (int)($data['id_user'] ?? 0);
$pin           = trim($data['pin'] ?? '');
$merchant_code = strtoupper(trim($data['merchant_code'] ?? ''));
$merchant_name = trim($data['merchant_name'] ?? ''); 
$montant       = (float)($data['montant'] ?? 0);

if (!$id_user || strlen($pin) !== 4 || empty($merchant_code) || $montant <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données de paiement invalides.']);
    exit;
}

try {
    // 1. Vérifier le client et son PIN
    $stmt = $db->prepare("SELECT id_user, nom_user FROM client WHERE id_user = ? AND pin_user = ?");
    $stmt->execute([$id_user, $pin]);
    $client = $stmt->fetch();

    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'PIN incorrect.']);
        exit;
    }

    // 2. Récupérer l'ID du marchand pour la transaction
    $stmt = $db->prepare("SELECT id_marchand, nom_boutique FROM marchand WHERE merchant_fixed_code = ?");
    $stmt->execute([$merchant_code]);
    $marchand = $stmt->fetch();

    if (!$marchand) {
        echo json_encode(['success' => false, 'message' => 'Marchand introuvable.']);
        exit;
    }

    $final_name = $merchant_name ?: $marchand['nom_boutique'];
    $ref        = 'WAV-' . strtoupper(substr(uniqid(), -6));
    $dateStr    = date('d M H:i');

    // 3. Insertion (Le trigger MySQL gère la sécurité du solde)
    $stmt = $db->prepare("
        INSERT INTO transaction 
            (montant_transaction, type_transaction, transaction_ref, client_name, id_user, id_marchand) 
        VALUES (?, 'paiement', ?, ?, ?, ?)
    ");
    
    $stmt->execute([$montant, $ref, $client['nom_user'], $id_user, $marchand['id_marchand']]);

    // 4. Récupérer le solde mis à jour
    $stmt = $db->prepare("SELECT solde_user FROM client WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $nouveauSolde = (float)$stmt->fetchColumn();

    echo json_encode([
        'success'       => true,
        'ref'           => $ref,
        'nouveau_solde' => $nouveauSolde,
        'date'          => $dateStr,
        'merchant_name' => $final_name,
        'montant'       => $montant
    ]);

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Solde insuffisant') !== false) {
        echo json_encode(['success' => false, 'message' => 'Solde insuffisant.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
    }
}