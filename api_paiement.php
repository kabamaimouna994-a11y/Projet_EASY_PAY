<?php
// api_paiement.php - Traitement d'un paiement
// Note : le solde est déduit automatiquement par le trigger MySQL (tg_maj_auto_solde_apres_paiement)
// et la sécurité solde est gérée par le trigger (tg_securite_solde_avant_paiement)
require_once 'db.php';

$data          = getJsonBody();
$id_user       = (int)($data['id_user'] ?? 0);
$pin           = trim($data['pin'] ?? '');
$merchant_code = strtoupper(trim($data['merchant_code'] ?? ''));
$merchant_name = trim($data['merchant_name'] ?? '');
$montant       = (float)($data['montant'] ?? 0);

// Validations de base
if (!$id_user || strlen($pin) !== 4 || empty($merchant_code) || $montant <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données de paiement invalides.']);
    exit;
}

$db = getDB();

// 1. Vérifier le client et son PIN (PIN en clair)
$stmt = $db->prepare("SELECT id_user, nom_user, solde_user FROM client WHERE id_user = ? AND pin_user = ?");
$stmt->execute([$id_user, $pin]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(['success' => false, 'message' => 'PIN incorrect.']);
    exit;
}

// 2. Récupérer l'id_marchand depuis le code marchand
$stmt = $db->prepare("SELECT id_marchand FROM marchand WHERE code_marchand = ? OR merchant_fixed_code = ?");
$stmt->execute([$merchant_code, $merchant_code]);
$marchand = $stmt->fetch();

if (!$marchand) {
    echo json_encode(['success' => false, 'message' => 'Marchand introuvable.']);
    exit;
}

// 3. Générer la référence unique
$ref     = 'WAV-' . strtoupper(substr(uniqid(), -6));
$dateStr = date('d M H:i');

// 4. Insérer la transaction
// Le trigger MySQL va automatiquement :
//   - Vérifier que le solde est suffisant (tg_securite_solde_avant_paiement)
//   - Déduire le montant du solde client (tg_maj_auto_solde_apres_paiement)
try {
    $stmt = $db->prepare("
        INSERT INTO transaction 
            (montant_transaction, type_transaction, transaction_ref, client_name, id_user, id_marchand) 
        VALUES 
            (?, 'paiement', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $montant,
        $ref,
        $client['nom_user'],
        $id_user,
        $marchand['id_marchand']
    ]);

    // 5. Récupérer le nouveau solde après déduction par le trigger
    $stmt = $db->prepare("SELECT solde_user FROM client WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $nouveauSolde = (float)$stmt->fetchColumn();

    echo json_encode([
        'success'        => true,
        'ref'            => $ref,
        'nouveau_solde'  => $nouveauSolde,
        'date'           => $dateStr,
        'client_name'    => $client['nom_user'],
        'merchant_name'  => $merchant_name,
        'montant'        => $montant
    ]);

} catch (PDOException $e) {
    // Le trigger renvoie une erreur SQLSTATE 45000 si solde insuffisant
    if (strpos($e->getMessage(), 'Solde insuffisant') !== false) {
        echo json_encode(['success' => false, 'message' => 'Solde insuffisant.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du paiement : ' . $e->getMessage()]);
    }
}
