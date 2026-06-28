<?php
require_once 'config.php';

// =====================================================
// SÉCURITÉ - Anti-Scraping et Protection
// =====================================================

/**
 * Protection anti-scraping
 */
function protectionAntiScraping() {
    // Vérifier le User-Agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $bots = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'java'];
    
    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            // Journaliser la tentative
            journaliser('Tentative scraping', "User-Agent: $user_agent");
            
            // Bloquer l'accès
            header('HTTP/1.0 403 Forbidden');
            die("Accès refusé");
        }
    }
    
    // Limiter le taux de requêtes (rate limiting simple)
    if (!isset($_SESSION['request_count'])) {
        $_SESSION['request_count'] = 1;
        $_SESSION['request_time'] = time();
    } else {
        $_SESSION['request_count']++;
        
        if ($_SESSION['request_count'] > 100 && (time() - $_SESSION['request_time']) < 60) {
            header('HTTP/1.0 429 Too Many Requests');
            die("Trop de requêtes. Veuillez patienter.");
        }
        
        if ((time() - $_SESSION['request_time']) > 60) {
            $_SESSION['request_count'] = 1;
            $_SESSION['request_time'] = time();
        }
    }
}

/**
 * Protection XSS
 */
function xssProtection($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validation email
 */
function validerEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validation téléphone
 */
function validerTelephone($telephone) {
    return preg_match('/^[0-9]{8,15}$/', $telephone);
}

// Appliquer la protection anti-scraping sur toutes les pages
protectionAntiScraping();
?>