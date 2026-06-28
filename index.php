<?php
require_once __DIR__ . '/database/init.php';
?>
<?php
require_once 'includes/config.php';
require_once 'includes/fonctions.php';
require_once 'includes/securite.php';

// Vérifier les connexions pour l'affichage conditionnel
$admin_connecte = estAdminConnecte();
$etudiant_connecte = estEtudiantConnecte();

// Récupérer les annonces
$annonces = [];
try {
    $stmt = $pdo->query("SELECT message, date_creation FROM annonces ORDER BY date_creation DESC LIMIT 5");
    $annonces = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table pas encore créée
}

// Si l'admin est connecté, son lien ne s'affiche pas
// Si un étudiant est connecté, seul le lien admin reste
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Plateforme d'examens en ligne - Gérez vos évaluations en toute sécurité">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo SITE_NAME; ?> - Accueil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
</head>
<body class="bg-ecole">
    <div class="overlay">
        <div class="container">
            <!-- En-tête -->
            <header class="header-accueil">
                <h1>🎓 Plateforme d'Évaluation en Ligne</h1>
                <p class="slogan">Excellence académique et innovation numérique</p>
            </header>

            <!-- Annonces -->
            <div class="annonces-box">
                <h3>📢 Annonces</h3>
                <?php if (empty($annonces)): ?>
                    <div class="annonce-item">Bienvenue sur la plateforme. Les examens sont ouverts selon le calendrier académique.</div>
                <?php else: ?>
                    <?php foreach ($annonces as $annonce): ?>
                        <div class="annonce-item">
                            <?php echo nettoyer($annonce['message']); ?>
                            <small>(<?php echo date('d/m/Y', strtotime($annonce['date_creation'])); ?>)</small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Boîte de connexion -->
            <div class="connexion-box">
                <h2>Accès à la Plateforme</h2>
                <div class="btn-container">
                    <?php if (!$admin_connecte): ?>
                        <a href="admin/connexion.php" class="btn btn-admin btn-lg">
                            🔐 Connexion Administrateur
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!$etudiant_connecte): ?>
                        <a href="etudiant/connexion.php" class="btn btn-etudiant btn-lg">
                            🎓 Connexion Étudiant
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($etudiant_connecte): ?>
                        <div class="alert alert-info mt-2">
                            Vous êtes connecté en tant qu'étudiant. 
                            <a href="etudiant/composition.php">Accéder à la composition</a> | 
                            <a href="etudiant/deconnexion.php">Se déconnecter</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($admin_connecte): ?>
                        <div class="alert alert-success mt-2">
                            Vous êtes connecté en tant qu'administrateur. 
                            <a href="admin/dashboard.php">Accéder au tableau de bord</a> | 
                            <a href="admin/deconnexion.php">Se déconnecter</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pied de page -->
            <footer style="text-align: center; color: white; margin-top: 30px; opacity: 0.8;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
            </footer>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
