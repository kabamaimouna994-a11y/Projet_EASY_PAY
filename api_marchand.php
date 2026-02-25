<?php
// api_marchand.php
require_once 'db.php';

$data   = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'login') {
    $nom = strtoupper(trim($data['nom_boutique'] ?? ''));
    $pin = trim($data['pin'] ?? '');

    $db = getDB();
    // Le compte doit déjà exister dans la BDD (créé par l'admin)
    $stmt = $db->prepare("SELECT id_marchand, nom_boutique, merchant_fixed_name, merchant_fixed_code FROM marchand WHERE nom_boutique = ? AND pin_marchand = ?");
    $stmt->execute([$nom, $pin]);
    $marchand = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($marchand) {
        echo json_encode(['success' => true, 'marchand' => $marchand]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Identifiants invalides ou compte non activé.']);
    }
    exit;
}

if ($action === 'save_config') {
    $id_marchand = (int)($data['id_marchand'] ?? 0);
    $fixed_name  = trim($data['fixed_name'] ?? '');
    $fixed_code  = strtoupper(trim($data['fixed_code'] ?? ''));

    $db = getDB();
    // Vérification unicité du code
    $check = $db->prepare("SELECT id_marchand FROM marchand WHERE merchant_fixed_code = ? AND id_marchand != ?");
    $check->execute([$fixed_code, $id_marchand]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet ID de paiement est déjà utilisé par un autre terminal.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE marchand SET merchant_fixed_name = ?, merchant_fixed_code = ? WHERE id_marchand = ?");
    $stmt->execute([$fixed_name, $fixed_code, $id_marchand]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'historique') {
    $code = strtoupper(trim($data['merchant_code'] ?? ''));
    $db = getDB();
    $stmt = $db->prepare("
        SELECT t.client_name, t.montant_transaction, t.date_transaction 
        FROM `transaction` t 
        JOIN marchand m ON t.id_marchand = m.id_marchand 
        WHERE m.merchant_fixed_code = ? 
        ORDER BY t.date_transaction DESC LIMIT 50
    ");
    $stmt->execute([$code]);
    $trans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function($t) {
        return [
            'client_name' => $t['client_name'] ?? 'Client Inconnu',
            'amount'      => number_format((float)$t['montant_transaction'], 0, ',', ' '),
            'date'        => date('d/m H:i', strtotime($t['date_transaction']))
        ];
    }, $trans);

    echo json_encode(['success' => true, 'transactions' => $formatted]);
    exit;
}