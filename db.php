<?php
// ============================================
// db.php - Connexion à la base de données EASY-PAY (Version Hostinger)
// ============================================

// Identifiants exacts de ton interface Hostinger
define('DB_HOST', 'localhost'); 
define('DB_USER', 'u241547498_usr_BHeKNNRN'); // Ton utilisateur actuel
define('DB_NAME', 'u241547498_db_BHeKNNRN');  // Ta base de données actuelle
define('DB_PASS', '!$@a=YbQ1e');               // Ton mot de passe actuel

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
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion BDD: ' . $e->getMessage()]);
            exit();
        }
    }
    return $pdo;
}

// Headers indispensables pour le fonctionnement de ton application mobile
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Helper pour lire les données JSON envoyées par le JavaScript
function getJsonBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}