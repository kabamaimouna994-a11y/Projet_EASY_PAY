<?php
// api_transactions_client.php - Historique des transactions d'un client
require_once 'db.php';

$data    = getJsonBody();
$id_user = (int)($data['id_user'] ?? 0);

if (!$id_user) {
    echo json_encode(['success' => false, 'message' => 'ID client manquant.']);
    exit;
}

$db = getDB();

// Jointure pour récupérer le nom du marchand depuis la table marchand
$stmt = $db->prepare("
    SELECT 
        m.nom_boutique,
        m.merchant_fixed_name,
        t.montant_transaction,
        t.date_transaction,
        t.transaction_ref
    FROM `transaction` t
    LEFT JOIN marchand m ON t.id_marchand = m.id_marchand
    WHERE t.id_user = ?
    ORDER BY t.date_transaction DESC
    LIMIT 50
");
$stmt->execute([$id_user]);
$transactions = $stmt->fetchAll();

// Formatter pour l'affichage dans accueil.html
$formatted = array_map(function($t) {
    // On affiche merchant_fixed_name si dispo, sinon nom_boutique
    $nomAffiche = !empty($t['merchant_fixed_name']) ? $t['merchant_fixed_name'] : $t['nom_boutique'];
    return [
        'dest'    => $nomAffiche ?? 'Marchand inconnu',
        'montant' => number_format((float)$t['montant_transaction'], 0, ',', ' '),
        'date'    => date('d M H:i', strtotime($t['date_transaction'])),
        'ref'     => $t['transaction_ref']
    ];
}, $transactions);

echo json_encode(['success' => true, 'transactions' => $formatted]);
