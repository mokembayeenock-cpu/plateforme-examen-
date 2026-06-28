<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$composition_id = $data['composition_id'] ?? null;
$reponses = $data['reponses'] ?? [];

if (!$composition_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Composition ID manquant']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    foreach ($reponses as $question_id => $reponse) {
        // Récupérer la question
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $question = $stmt->fetch();
        
        $est_correcte = null;
        $points_obtenus = 0;
        
        if ($question['type'] === 'quiz' && $question['reponse_correcte']) {
            $est_correcte = (strtolower(trim($reponse)) === strtolower(trim($question['reponse_correcte'])));
            $points_obtenus = $est_correcte ? $question['points'] : 0;
        }
        
        // Insérer ou mettre à jour la réponse
        $stmt = $pdo->prepare("
            INSERT INTO reponses_etudiants (composition_id, question_id, reponse_texte, reponse_quiz, est_correcte, points_obtenus)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reponse_texte = VALUES(reponse_texte), reponse_quiz = VALUES(reponse_quiz)
        ");
        $stmt->execute([
            $composition_id,
            $question_id,
            $question['type'] === 'question' ? $reponse : null,
            $question['type'] === 'quiz' ? $reponse : null,
            $est_correcte,
            $points_obtenus
        ]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>