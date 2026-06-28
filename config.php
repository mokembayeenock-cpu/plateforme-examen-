<?php
// =====================================================
// CONFIGURATION POUR REPLIT (SQLite)
// =====================================================

// Désactiver les erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chemin de la base SQLite
define('DB_PATH', __DIR__ . '/../database/plateforme_examens.db');

// URL du site (sera mise à jour automatiquement)
$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . '://' . $host . '/');
define('SITE_NAME', 'Plateforme Examens');
define('ADMIN_EMAIL', 'administrateur@gmail.com');

// Chemins
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('PHOTO_DIR', UPLOAD_DIR . 'photos/');
define('MAX_PHOTO_SIZE', 2048);

// Créer les dossiers si nécessaire
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(PHOTO_DIR)) mkdir(PHOTO_DIR, 0755, true);

// Timezone
date_default_timezone_set('Africa/Porto-Novo');

// Connexion SQLite
try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Activer les clés étrangères
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
