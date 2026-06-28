<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

// Vérifier connexion
if (!estEtudiantConnecte()) {
    rediriger('etudiant/connexion.php');
}

$etudiant_id = $_SESSION['etudiant_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('etudiant/composition.php');
}

// Vérifier CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Erreur de sécurité.");
}

$composition_id = intval($_POST['composition_id']);
$sujet_id = intval($_POST['sujet_id']);

// Vérifier que la composition appartient bien à l'étudiant
$stmt = $pdo->prepare("SELECT * FROM compositions WHERE id = ? AND etudiant_id = ? AND est_termine = FALSE");
$stmt->execute([$composition_id, $etudiant_id]);
$composition = $stmt->fetch();

if (!$composition) {
    die("Composition invalide.");
}

// Récupérer toutes les questions du sujet
$stmt = $pdo->prepare("SELECT * FROM questions WHERE sujet_id = ? ORDER BY ordre ASC");
$stmt->execute([$sujet_id]);
$questions = $stmt->fetchAll();

$total_points = 0;
$points_obtenus = 0;

$pdo->beginTransaction();

try {
    foreach ($questions as $question) {
        $total_points += $question['points'];
        $reponse = '';
        $est_correcte = null;
        $points = 0;
        
        if ($question['type'] === 'quiz') {
            $reponse = $_POST['reponse_' . $question['id']] ?? '';
            
            if ($reponse && $question['reponse_correcte']) {
                $est_correcte = (strtolower(trim($reponse)) === strtolower(trim($question['reponse_correcte'])));
                $points = $est_correcte ? $question['points'] : 0;
            }
        } else {
            $reponse = $_POST['reponse_' . $question['id']] ?? '';
            // Pour les questions ouvertes, l'admin devra corriger manuellement
            $est_correcte = null;
            $points = 0;
        }
        
        $points_obtenus += $points;
        
        // Insérer la réponse
        $stmt = $pdo->prepare("
            INSERT INTO reponses_etudiants (composition_id, question_id, reponse_texte, reponse_quiz, est_correcte, points_obtenus)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $composition_id,
            $question['id'],
            $question['type'] === 'question' ? $reponse : null,
            $question['type'] === 'quiz' ? $reponse : null,
            $est_correcte,
            $points
        ]);
    }
    
    // Calculer la note sur 20
    $note_sur_20 = $total_points > 0 ? ($points_obtenus / $total_points) * 20 : 0;
    $note_sur_20 = round($note_sur_20, 2);
    
    // Générer clé unique
    $cle_unique = genererCleUnique(8);
    
    // Vérifier unicité
    $stmt = $pdo->prepare("SELECT id FROM resultats WHERE cle_unique = ?");
    $stmt->execute([$cle_unique]);
    while ($stmt->fetch()) {
        $cle_unique = genererCleUnique(8);
        $stmt->execute([$cle_unique]);
    }
    
    // Enregistrer le résultat
    $stmt = $pdo->prepare("
        INSERT INTO resultats (etudiant_id, sujet_id, note, note_sur, cle_unique, est_publie)
        VALUES (?, ?, ?, 20, ?, FALSE)
    ");
    $stmt->execute([$etudiant_id, $sujet_id, $note_sur_20, $cle_unique]);
    
    // Marquer la composition comme terminée
    $stmt = $pdo->prepare("
        UPDATE compositions 
        SET est_termine = TRUE, date_fin = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$composition_id]);
    
    // Mettre à jour l'étudiant
    $stmt = $pdo->prepare("
        UPDATE etudiants 
        SET cle_resultat = ?, est_connecte = FALSE, session_active = FALSE 
        WHERE id = ?
    ");
    $stmt->execute([$cle_unique, $etudiant_id]);
    
    $pdo->commit();
    
    // Envoyer la clé par email et SMS
    $etudiant_email = $_SESSION['etudiant_email'];
    
    // Récupérer téléphone
    $stmt = $pdo->prepare("SELECT telephone, nom, prenom FROM etudiants WHERE id = ?");
    $stmt->execute([$etudiant_id]);
    $info = $stmt->fetch();
    
    $message_email = "
        <h2>Résultat de votre composition</h2>
        <p>Bonjour {$info['prenom']} {$info['nom']},</p>
        <p>Votre composition a été soumise avec succès.</p>
        <p>Votre clé unique pour consulter votre résultat : <strong>$cle_unique</strong></p>
        <p>Conservez précieusement cette clé.</p>
    ";
    
    envoyerEmail($etudiant_email, "Clé de résultat - " . SITE_NAME, $message_email);
    envoyerSMS($info['telephone'], "Votre clé résultat: $cle_unique - " . SITE_NAME);
    
    // Détruire session
    session_destroy();
    
    // Rediriger vers page de confirmation
    header("Location: ../resultat-confirmation.php?cle=$cle_unique");
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erreur lors de la soumission: " . $e->getMessage());
}
?>