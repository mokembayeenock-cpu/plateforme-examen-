<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

header('Content-Type: application/json');

$composition_id = $_GET['id'] ?? null;

if (!$composition_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT date_limite, est_termine, est_deconnecte_force, est_fraude, motif_fraude
        FROM compositions WHERE id = ?
    ");
    $stmt->execute([$composition_id]);
    $composition = $stmt->fetch();
    
    if (!$composition) {
        http_response_code(404);
        echo json_encode(['erreur' => 'Composition non trouvée']);
        exit;
    }
    
    $date_limite = strtotime($composition['date_limite']);
    $maintenant = time();
    $temps_restant = max(0, $date_limite - $maintenant);
    
    echo json_encode([
        'temps_restant' => $temps_restant,
        'est_termine' => $composition['est_termine'] || $temps_restant <= 0,
        'est_deconnecte_force' => $composition['est_deconnecte_force'],
        'est_fraude' => $composition['est_fraude'],
        'motif_fraude' => $composition['motif_fraude']
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur']);
}
?>