<?php
// =====================================================
// CONFIGURATION - EXEMPLE
// Copier ce fichier en config.php et remplir vos infos
// =====================================================

// Base de données
define('DB_HOST', 'localhost');       // ← Changer pour InfinityFree
define('DB_NAME', 'plateforme_examens');  // ← Votre nom de base
define('DB_USER', 'root');           // ← Votre utilisateur
define('DB_PASS', '');              // ← Votre mot de passe

// Site
define('SITE_URL', 'http://localhost/plateforme_examens/');
define('SITE_NAME', 'Plateforme Examens');
define('ADMIN_EMAIL', 'administrateur@gmail.com');

// Uploads
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('PHOTO_DIR', UPLOAD_DIR . 'photos/');
define('MAX_PHOTO_SIZE', 2048);

// Sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 1 en production

// Timezone
date_default_timezone_set('Africa/Porto-Novo');

// Connexion BDD
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>