<?php
require_once 'includes/config.php';
require_once 'includes/fonctions.php';

$cle = $_GET['cle'] ?? '';

if (empty($cle)) {
    rediriger('index.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Composition Soumise - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/etudiant.css">
</head>
<body>
    <div class="formulaire-container">
        <div class="resultat-card">
            <div style="font-size:5em;margin-bottom:20px;">✅</div>
            <h2>Composition Soumise avec Succès !</h2>
            <p>Votre travail a été enregistré.</p>
            
            <div class="cle-unique">
                Votre clé de résultat : <strong><?php echo nettoyer($cle); ?></strong>
            </div>
            
            <div class="alert alert-warning">
                ⚠️ Conservez précieusement cette clé ! Elle vous sera demandée pour consulter votre résultat.
                Cette clé a également été envoyée à votre email et par SMS.
            </div>
            
            <p>Vous pourrez consulter votre résultat lorsque l'administrateur les aura publiés.</p>
            
            <a href="index.php" class="btn btn-etudiant mt-2">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>