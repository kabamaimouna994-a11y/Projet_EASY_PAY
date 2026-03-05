<?php
// api_marchand.php - Adapté pour Hostinger
require_once 'db.php';

// On utilise la méthode universelle pour lire le JSON envoyé par le JS
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? '';
$db = getDB();

// --- ACTION 1: CONNEXION ---
if ($action === 'login') {
    $nom = strtoupper(trim($data['nom_boutique'] ?? ''));
    $pin = trim($data['pin'] ?? '');

    // Note: 'marchand' en minuscule pour Hostinger
    $stmt = $db->prepare("SELECT id_marchand, nom_boutique, merchant_fixed_name, merchant_fixed_code FROM marchand WHERE nom_boutique = ? AND pin_marchand = ?");
    $stmt->execute([$nom, $pin]);
    $marchand = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($marchand) {
        echo json_encode(['success' => true, 'marchand' => $marchand]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Identifiants invalides.']);
    }
    exit;
}

// --- ACTION 2: VÉRIFIER SI DÉJÀ CONFIGURÉ (Essentiel pour ton HTML) ---
if ($action === 'check_config') {
    $id = (int)($data['id_marchand'] ?? 0);
    $stmt = $db->prepare("SELECT merchant_fixed_name, merchant_fixed_code FROM marchand WHERE id_marchand = ?");
    $stmt->execute([$id]);
    $m = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'fixed_name' => $m['merchant_fixed_name'] ?? null, 
        'fixed_code' => $m['merchant_fixed_code'] ?? null
    ]);
    exit;
}

// --- ACTION 3: ENREGISTRER LE NOM DE BOUTIQUE ---
if ($action === 'save_config') {
    $id_marchand = (int)($data['id_marchand'] ?? 0);
    $fixed_name  = trim($data['fixed_name'] ?? '');
    $fixed_code  = strtoupper(trim($data['fixed_code'] ?? ''));

    // Vérifier si le code existe déjà ailleurs
    $check = $db->prepare("SELECT id_marchand FROM marchand WHERE merchant_fixed_code = ? AND id_marchand != ?");
    $check->execute([$fixed_code, $id_marchand]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet ID est déjà utilisé.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE marchand SET merchant_fixed_name = ?, merchant_fixed_code = ? WHERE id_marchand = ?");
    $stmt->execute([$fixed_name, $fixed_code, $id_marchand]);

    echo json_encode(['success' => true]);
    exit;
}

// --- ACTION 4: HISTORIQUE (Pour les stats et la liste) ---
if ($action === 'historique') {
    $code = strtoupper(trim($data['merchant_code'] ?? ''));
    
    // Note: `transaction` et `marchand` en minuscules
    $stmt = $db->prepare("
        SELECT t.client_name, t.montant_transaction, t.date_transaction 
        FROM `transaction` t 
        JOIN marchand m ON t.id_marchand = m.id_marchand 
        WHERE m.merchant_fixed_code = ? 
        ORDER BY t.date_transaction DESC LIMIT 50
    ");
    $stmt->execute([$code]);
    $trans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // On retourne le montant en FLOAT (chiffre) pour que Chart.js et le total JS fonctionnent
    $formatted = array_map(function($t) {
        return [
            'client_name' => $t['client_name'] ?? 'Client Inconnu',
            'amount'      => (float)$t['montant_transaction'], 
            'date'        => date('d/m H:i', strtotime($t['date_transaction']))
        ];
    }, $trans);

    echo json_encode(['success' => true, 'transactions' => $formatted]);
    exit;
}