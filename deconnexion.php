<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

if (isset($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("UPDATE administrateurs SET est_connecte = FALSE WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    
    // Désactiver sessions admin
    if (isset($_SESSION['admin_token'])) {
        $stmt = $pdo->prepare("UPDATE sessions_admin SET est_active = FALSE WHERE token = ?");
        $stmt->execute([$_SESSION['admin_token']]);
    }
    
    journaliser('Déconnexion admin', $_SESSION['admin_email']);
}

session_destroy();
rediriger('index.php');
?>