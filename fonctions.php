<?php
require_once 'config.php';

// =====================================================
// FONCTIONS UTILITAIRES
// =====================================================

/**
 * Génère une clé unique aléatoire
 */
function genererCleUnique($longueur = 8) {
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $cle = '';
    $max = strlen($caracteres) - 1;
    for ($i = 0; $i < $longueur; $i++) {
        $cle .= $caracteres[random_int(0, $max)];
    }
    return $cle;
}

/**
 * Génère une matricule unique
 */
function genererMatricule() {
    global $pdo;
    $annee = date('Y');
    $prefix = 'ETU' . $annee;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM etudiants WHERE matricule LIKE '$prefix%'");
    $result = $stmt->fetch();
    $numero = str_pad($result['total'] + 1, 4, '0', STR_PAD_LEFT);
    
    return $prefix . $numero;
}

/**
 * Vérifie si l'étudiant est connecté
 */
function estEtudiantConnecte() {
    return isset($_SESSION['etudiant_id']) && isset($_SESSION['etudiant_email']);
}

/**
 * Vérifie si l'admin est connecté
 */
function estAdminConnecte() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
}

/**
 * Redirige vers une URL
 */
function rediriger($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

/**
 * Nettoie une chaîne de caractères
 */
function nettoyer($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Envoie un email (simulation)
 */
function envoyerEmail($destinataire, $sujet, $message) {
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // En production, utilisez mail() ou PHPMailer
    return mail($destinataire, $sujet, $message, $headers);
}

/**
 * Envoie un SMS (simulation)
 */
function envoyerSMS($telephone, $message) {
    // Simulation - En production, utilisez une API SMS
    $log = date('Y-m-d H:i:s') . " - SMS à $telephone : $message\n";
    file_put_contents(UPLOAD_DIR . 'sms_log.txt', $log, FILE_APPEND);
    return true;
}

/**
 * Calcule la moyenne du lycée
 */
function calculerMoyenneLycee($etudiant_id, $matiere_id) {
    global $pdo;
    
    // Récupérer les notes de contrôle
    $stmt = $pdo->prepare("
        SELECT r.note, s.type_evaluation 
        FROM resultats r
        JOIN sujets s ON r.sujet_id = s.id
        WHERE r.etudiant_id = ? AND s.matiere_id = ?
        AND s.type_evaluation IN ('controle', 'examen')
        ORDER BY s.type_evaluation, s.date_creation
    ");
    $stmt->execute([$etudiant_id, $matiere_id]);
    $notes = $stmt->fetchAll();
    
    $controles = [];
    $examen = 0;
    
    foreach ($notes as $note) {
        if ($note['type_evaluation'] === 'controle') {
            $controles[] = $note['note'];
        } else {
            $examen = $note['note'];
        }
    }
    
    $moyenne_controles = count($controles) > 0 ? array_sum($controles) / count($controles) : 0;
    $moyenne_generale = ($moyenne_controles + $examen) / 2;
    
    return $moyenne_generale;
}

/**
 * Calcule la moyenne universitaire
 */
function calculerMoyenneUniversitaire($etudiant_id, $matiere_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT r.note, s.type_evaluation 
        FROM resultats r
        JOIN sujets s ON r.sujet_id = s.id
        WHERE r.etudiant_id = ? AND s.matiere_id = ?
    ");
    $stmt->execute([$etudiant_id, $matiere_id]);
    $notes = $stmt->fetchAll();
    
    $controle = 0;
    $examen = 0;
    
    foreach ($notes as $note) {
        if ($note['type_evaluation'] === 'controle') {
            $controle = $note['note'];
        } elseif ($note['type_evaluation'] === 'examen') {
            $examen = $note['note'];
        }
    }
    
    // Contrôle 30% + Examen 70%
    return ($controle * 0.30) + ($examen * 0.70);
}

/**
 * Vérifie si l'étudiant doit passer le rattrapage
 */
function doitPasserRattrapage($etudiant_id, $matiere_id, $type_niveau) {
    if ($type_niveau === 'secondaire') {
        $moyenne = calculerMoyenneLycee($etudiant_id, $matiere_id);
    } else {
        $moyenne = calculerMoyenneUniversitaire($etudiant_id, $matiere_id);
    }
    
    return $moyenne < 10;
}

/**
 * Calcule les crédits totaux validés (universitaire)
 */
function calculerCreditsValides($etudiant_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT m.credit, 
               (r.note * 0.70 + COALESCE(rc.note, 0) * 0.30) as moyenne
        FROM resultats r
        JOIN sujets s ON r.sujet_id = s.id
        JOIN matieres m ON s.matiere_id = m.id
        LEFT JOIN (
            SELECT r2.etudiant_id, s2.matiere_id, r2.note
            FROM resultats r2
            JOIN sujets s2 ON r2.sujet_id = s2.id
            WHERE s2.type_evaluation = 'controle'
        ) rc ON rc.etudiant_id = r.etudiant_id AND rc.matiere_id = s.matiere_id
        WHERE r.etudiant_id = ? AND s.type_evaluation = 'examen'
    ");
    $stmt->execute([$etudiant_id]);
    $matieres = $stmt->fetchAll();
    
    $total_credits = 0;
    foreach ($matieres as $matiere) {
        if ($matiere['moyenne'] >= 10) {
            $total_credits += $matiere['credit'];
        }
    }
    
    return $total_credits;
}

/**
 * Génère un token CSRF
 */
function genererCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF
 */
function verifierCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité CSRF");
    }
    return true;
}

/**
 * Journalise une action
 */
function journaliser($action, $details = '') {
    $log = date('Y-m-d H:i:s') . " - $action - $details - IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    file_put_contents(UPLOAD_DIR . 'journal.txt', $log, FILE_APPEND);
}
?>