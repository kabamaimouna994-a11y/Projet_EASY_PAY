<?php
require_once 'db.php';

// Récupération des données JSON envoyées par le JavaScript
$data = getJsonBody(); 
$action = $data['action'] ?? '';
$db = getDB();



// --- ACTION : VÉRIFICATION DU MARCHAND ---
if ($action === 'check_merchant') {
    $code = strtoupper(trim($data['merchant_code'] ?? ''));
    
    // Vérification dans la table 'marchand' (minuscule pour Hostinger)
    $stmt = $db->prepare("SELECT nom_boutique FROM marchand WHERE merchant_fixed_code = ?");
    $stmt->execute([$code]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => !!$m, 'nom_boutique' => $m['nom_boutique'] ?? '']);
    exit;
}

// --- ACTION : TRAITEMENT DU PAIEMENT ---
$id_user = (int)($data['id_user'] ?? 0);
$pin = trim($data['pin'] ?? '');
$merchant_code = strtoupper(trim($data['merchant_code'] ?? ''));
$montant = (float)($data['montant'] ?? 0);

try {
    // 1. Vérification du client et de son PIN
    $stmt = $db->prepare("SELECT nom_user, solde_user FROM client WHERE id_user = ? AND pin_user = ?");
    $stmt->execute([$id_user, $pin]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo json_encode(['success' => false, 'message' => 'Code PIN incorrect.']);
        exit;
    }

    if ($client['solde_user'] < $montant) {
        echo json_encode(['success' => false, 'message' => 'Solde insuffisant.']);
        exit;
    }

    // 2. Vérification du marchand (on récupère l'ID et le nom vus en base de données)
    $stmtM = $db->prepare("SELECT id_marchand, nom_boutique FROM marchand WHERE merchant_fixed_code = ?");
    $stmtM->execute([$merchant_code]);
    $marchand = $stmtM->fetch(PDO::FETCH_ASSOC);

    if (!$marchand) {
        echo json_encode(['success' => false, 'message' => 'Marchand introuvable.']);
        exit;
    }

    // 3. Début de la transaction SQL
    $db->beginTransaction();

    // Mise à jour du solde Client
    $db->prepare("UPDATE client SET solde_user = solde_user - ? WHERE id_user = ?")
       ->execute([$montant, $id_user]);

    // Mise à jour du solde Marchand (Correction de l'erreur Column not found)
    // Réécrivez cette ligne manuellement dans votre fichier :
    $db->prepare("UPDATE `marchand` SET `solde_marchand` = `solde_marchand` + ? WHERE `id_marchand` = ?")
    ->execute([$montant, $marchand['id_marchand']]);

    // Insertion dans l'historique 'transaction' (Vérifié sur votre capture d'écran)
    $ref = 'PAY-' . strtoupper(substr(uniqid(), -6)); 
    $sql = "INSERT INTO transaction (id_user, id_marchand, montant_transaction, transaction_ref, type_transaction, client_name, date_transaction) 
            VALUES (?, ?, ?, ?, 'paiement', ?, NOW())";
    $db->prepare($sql)->execute([
        $id_user, 
        $marchand['id_marchand'], 
        $montant, 
        $ref, 
        $client['nom_user']
    ]);

    $db->commit();

    // 4. Réponse au client
    echo json_encode([
        'success' => true,
        'ref' => $ref,
        'nouveau_solde' => ($client['solde_user'] - $montant),
        'date' => date('d/m H:i'),
        'merchant_name' => $marchand['nom_boutique']
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur technique : ' . $e->getMessage()]);
}
?>