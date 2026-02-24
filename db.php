<?php
// ============================================
// db.php - Connexion à la base de données easy_pay
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Utilisateur WAMP par défaut
define('DB_PASS', '');           // Mot de passe WAMP (vide par défaut)
define('DB_NAME', 'easy_pay');   // Nom de ta BDD existante

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Erreur de connexion BDD: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Headers pour les requêtes fetch depuis le HTML
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper : lire le body JSON
function getJsonBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
