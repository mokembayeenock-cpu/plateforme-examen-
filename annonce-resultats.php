<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$erreur = '';
$success = '';

// Publier/Dépublier résultats
if (isset($_GET['action']) && isset($_GET['sujet_id'])) {
    $sujet_id = intval($_GET['sujet_id']);
    $action = $_GET['action'];
    
    if ($action === 'publier') {
        $stmt = $pdo->prepare("UPDATE resultats SET est_publie = TRUE WHERE sujet_id = ?");
        $stmt->execute([$sujet_id]);
        
        // Activer les résultats
        $stmt = $pdo->prepare("SELECT etudiant_id, cle_unique FROM resultats WHERE sujet_id = ?");
        $stmt->execute([$sujet_id]);
        $resultats = $stmt->fetchAll();
        
        foreach ($resultats as $res) {
            // Envoyer notification
            $stmt = $pdo->prepare("SELECT email, telephone FROM etudiants WHERE id = ?");
            $stmt->execute([$res['etudiant_id']]);
            $etu = $stmt->fetch();
            
            if ($etu) {
                envoyerEmail($etu['email'], "Résultats disponibles", "Vos résultats sont disponibles. Clé: " . $res['cle_unique']);
                envoyerSMS($etu['telephone'], "Resultats disponibles. Cle: " . $res['cle_unique']);
            }
        }
        
        $success = "Résultats publiés et notifications envoyées.";
    } elseif ($action === 'depublier') {
        $stmt = $pdo->prepare("UPDATE resultats SET est_publie = FALSE WHERE sujet_id = ?");
        $stmt->execute([$sujet_id]);
        $success = "Résultats dépubliés.";
    }
}

// Récupérer sujets avec résultats
$sujets = $pdo->query("
    SELECT s.*, m.nom as matiere_nom, n.nom as niveau_nom,
           (SELECT COUNT(*) FROM resultats r WHERE r.sujet_id = s.id) as nb_resultats,
           (SELECT COUNT(*) FROM resultats r WHERE r.sujet_id = s.id AND r.est_publie = TRUE) as nb_publies
    FROM sujets s
    JOIN matieres m ON s.matiere_id = m.id
    JOIN niveaux n ON s.niveau_id = n.id
    ORDER BY s.date_creation DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annoncer Résultats - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header"><h2>🔐 Admin Panel</h2></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span> Tableau de Bord</a></li>
                <li><a href="gestion-resultats.php"><span class="icon">📈</span> Résultats</a></li>
                <li><a href="annonce-resultats.php" class="active"><span class="icon">📢</span> Annoncer Résultats</a></li>
                <li><a href="export-resultats.php"><span class="icon">📥</span> Export</a></li>
                <li><a href="deconnexion.php"><span class="icon">🚪</span> Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📢 Publier les Résultats</h1>
            </div>
            
            <?php if ($erreur): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Sujets avec Résultats</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($sujets)): ?>
                        <p class="text-center">Aucun sujet avec résultats.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th>Matière</th>
                                        <th>Type</th>
                                        <th>Résultats</th>
                                        <th>Publiés</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sujets as $sujet): ?>
                                        <tr>
                                            <td><?php echo $sujet['niveau_nom']; ?></td>
                                            <td><?php echo $sujet['matiere_nom']; ?></td>
                                            <td><span class="badge badge-info"><?php echo $sujet['type_evaluation']; ?></span></td>
                                            <td><?php echo $sujet['nb_resultats']; ?></td>
                                            <td><?php echo $sujet['nb_publies']; ?></td>
                                            <td>
                                                <?php if ($sujet['nb_publies'] > 0): ?>
                                                    <span class="badge badge-success">✅ Publié</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">⏳ En attente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($sujet['nb_resultats'] > 0): ?>
                                                    <?php if ($sujet['nb_publies'] == 0): ?>
                                                        <a href="?action=publier&sujet_id=<?php echo $sujet['id']; ?>" 
                                                           class="btn btn-success btn-sm"
                                                           onclick="return confirm('Publier tous les résultats de ce sujet ? Les étudiants recevront une notification.');">
                                                            📢 Publier
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=depublier&sujet_id=<?php echo $sujet['id']; ?>" 
                                                           class="btn btn-warning btn-sm"
                                                           onclick="return confirm('Dépublier ces résultats ?');">
                                                            🔒 Dépublier
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Aucun résultat</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/admin.js"></script>
</body>
</html>