<?php
// api_marchand.php - Gestion des marchands
require_once 'db.php';

$data   = getJsonBody();
$action = $data['action'] ?? ($_GET['action'] ?? '');

// ---- ACTION : Connexion marchand ----
if ($action === 'login') {
    $nom = strtoupper(trim($data['nom_boutique'] ?? ''));
    $pin = trim($data['pin'] ?? '');

    if (empty($nom) || strlen($pin) !== 4) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id_marchand, nom_boutique, merchant_fixed_name, merchant_fixed_code FROM marchand WHERE nom_boutique = ? AND pin_marchand = ?");
    $stmt->execute([$nom, $pin]);
    $marchand = $stmt->fetch();

    if (!$marchand) {
        // Créer le compte marchand automatiquement avec un code unique
        $code_marchand = 'MCH-' . strtoupper(substr(uniqid(), -6));
        $stmt = $db->prepare("INSERT INTO marchand (code_marchand, nom_boutique, pin_marchand) VALUES (?, ?, ?)");
        $stmt->execute([$code_marchand, $nom, $pin]);
        $id = $db->lastInsertId();

        echo json_encode([
            'success'  => true,
            'marchand' => [
                'id_marchand'          => $id,
                'nom_boutique'         => $nom,
                'merchant_fixed_name'  => null,
                'merchant_fixed_code'  => null
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'marchand' => $marchand]);
    }
    exit;
}

// ---- ACTION : Sauvegarder config QR ----
if ($action === 'save_config') {
    $id_marchand = (int)($data['id_marchand'] ?? 0);
    $fixed_name  = trim($data['fixed_name'] ?? '');
    $fixed_code  = strtoupper(trim($data['fixed_code'] ?? ''));

    if (!$id_marchand || empty($fixed_name) || empty($fixed_code)) {
        echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
        exit;
    }

    $db = getDB();

    // Vérifier que le code n'est pas déjà utilisé par un autre marchand
    $stmt = $db->prepare("SELECT id_marchand FROM marchand WHERE merchant_fixed_code = ? AND id_marchand != ?");
    $stmt->execute([$fixed_code, $id_marchand]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet ID de paiement est déjà utilisé.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE marchand SET merchant_fixed_name = ?, merchant_fixed_code = ? WHERE id_marchand = ?");
    $stmt->execute([$fixed_name, $fixed_code, $id_marchand]);

    echo json_encode(['success' => true, 'message' => 'Configuration sauvegardée.']);
    exit;
}

// ---- ACTION : Historique paiements reçus ----
if ($action === 'historique') {
    $merchant_code = strtoupper(trim($data['merchant_code'] ?? ''));

    if (empty($merchant_code)) {
        echo json_encode(['success' => false, 'message' => 'Code marchand manquant.']);
        exit;
    }

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT 
            t.client_name,
            t.montant_transaction,
            t.date_transaction
        FROM `transaction` t
        JOIN marchand m ON t.id_marchand = m.id_marchand
        WHERE m.merchant_fixed_code = ? OR m.code_marchand = ?
        ORDER BY t.date_transaction DESC
        LIMIT 100
    ");
    $stmt->execute([$merchant_code, $merchant_code]);
    $trans = $stmt->fetchAll();

    $formatted = array_map(function($t) {
        return [
            'client_name' => $t['client_name'] ?? 'Client inconnu',
            'amount'      => number_format((float)$t['montant_transaction'], 0, ',', ' '),
            'date'        => date('d M Y H:i', strtotime($t['date_transaction']))
        ];
    }, $trans);

    echo json_encode(['success' => true, 'transactions' => $formatted]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
