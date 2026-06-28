<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

header('Content-Type: application/json');

// Vérifier méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée']);
    exit;
}

// Récupérer données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Données invalides']);
    exit;
}

$composition_id = $data['composition_id'] ?? null;
$etudiant_id = $data['etudiant_id'] ?? null;
$type_alerte = $data['type'] ?? 'sortie_page';
$message = $data['message'] ?? 'Alerte fraude détectée';

if (!$composition_id || !$etudiant_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Paramètres manquants']);
    exit;
}

try {
    // Insérer l'alerte
    $stmt = $pdo->prepare("
        INSERT INTO alertes_fraudes (composition_id, etudiant_id, type_alerte, message) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$composition_id, $etudiant_id, $type_alerte, $message]);
    
    $alerte_id = $pdo->lastInsertId();
    
    // Vérifier si 3 alertes ou plus
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM alertes_fraudes 
        WHERE composition_id = ? AND est_traitee = FALSE
    ");
    $stmt->execute([$composition_id]);
    $count = $stmt->fetch()['total'];
    
    $deconnexion_force = false;
    
    if ($count >= 3) {
        // Déconnecter l'étudiant
        $stmt = $pdo->prepare("
            UPDATE compositions 
            SET est_deconnecte_force = TRUE, est_fraude = TRUE, motif_fraude = ? 
            WHERE id = ?
        ");
        $stmt->execute(["Fraude détectée ($count alertes)", $composition_id]);
        
        $stmt = $pdo->prepare("UPDATE etudiants SET est_connecte = FALSE, session_active = FALSE WHERE id = ?");
        $stmt->execute([$etudiant_id]);
        
        $deconnexion_force = true;
    }
    
    echo json_encode([
        'success' => true,
        'alerte_id' => $alerte_id,
        'count' => $count,
        'deconnexion_force' => $deconnexion_force
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>