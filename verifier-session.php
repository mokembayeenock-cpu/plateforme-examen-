<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

header('Content-Type: application/json');

$etudiant_id = $_SESSION['etudiant_id'] ?? null;

if (!$etudiant_id) {
    echo json_encode(['connecte' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT session_active FROM etudiants WHERE id = ?");
    $stmt->execute([$etudiant_id]);
    $etudiant = $stmt->fetch();
    
    echo json_encode([
        'connecte' => true,
        'session_active' => $etudiant['session_active'] ?? false
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>