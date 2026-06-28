<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'plateforme_examens');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_URL', 'http://localhost/plateforme_examens/');
define('SITE_NAME', 'Plateforme Examens');
define('ADMIN_EMAIL', 'administrateur@gmail.com');

// Configuration des uploads
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('PHOTO_DIR', UPLOAD_DIR . 'photos/');
define('MAX_PHOTO_SIZE', 2048); // 2KB

// Configuration des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mettre 1 en production avec HTTPS

// Timezone
date_default_timezone_set('Africa/Porto-Novo'); // Ajustez selon votre fuseau

// Connexion à la base de données
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
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>