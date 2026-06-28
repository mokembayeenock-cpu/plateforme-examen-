<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$success = '';
$erreur = '';

// Suppression sujet
if (isset($_GET['supprimer']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM sujets WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Sujet supprimé.";
}

// Activer/Désactiver sujet
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE sujets SET est_actif = NOT est_actif WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Statut du sujet modifié.";
}

// Récupérer tous les sujets
$sujets = $pdo->query("
    SELECT s.*, m.nom as matiere_nom, n.nom as niveau_nom,
           (SELECT COUNT(*) FROM questions WHERE sujet_id = s.id) as nb_questions,
           (SELECT COUNT(*) FROM compositions WHERE sujet_id = s.id) as nb_compositions
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
    <title>Liste Sujets - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🔐 Admin Panel</h2>
                <p><?php echo nettoyer($_SESSION['admin_email'] ?? ''); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Tableau de Bord</a></li>
                <li><a href="creation-sujet.php">📝 Créer Sujet</a></li>
                <li><a href="liste-sujets.php" class="active">📋 Liste Sujets</a></li>
                <li><a href="surveillance.php">👁️ Surveillance</a></li>
                <li><a href="gestion-resultats.php">📈 Résultats</a></li>
                <li><a href="annonce-resultats.php">📢 Annoncer Résultats</a></li>
                <li><a href="deconnexion.php">🚪 Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📋 Liste des Sujets</h1>
                <a href="creation-sujet.php" class="btn btn-primary">+ Nouveau Sujet</a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($sujets)): ?>
                        <p class="text-center">Aucun sujet créé.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th>Matière</th>
                                        <th>Type</th>
                                        <th>Questions</th>
                                        <th>Compositions</th>
                                        <th>Durée</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sujets as $sujet): ?>
                                        <tr>
                                            <td><?php echo nettoyer($sujet['niveau_nom']); ?></td>
                                            <td><?php echo nettoyer($sujet['matiere_nom']); ?></td>
                                            <td><span class="badge badge-info"><?php echo $sujet['type_evaluation']; ?></span></td>
                                            <td><?php echo $sujet['nb_questions']; ?></td>
                                            <td><?php echo $sujet['nb_compositions']; ?></td>
                                            <td><?php echo $sujet['duree_minutes']; ?> min</td>
                                            <td>
                                                <?php if ($sujet['est_actif']): ?>
                                                    <span class="badge badge-success">✅ Actif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">❌ Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($sujet['date_creation'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?toggle=1&id=<?php echo $sujet['id']; ?>" 
                                                       class="btn btn-sm <?php echo $sujet['est_actif'] ? 'btn-warning' : 'btn-success'; ?>">
                                                        <?php echo $sujet['est_actif'] ? 'Désactiver' : 'Activer'; ?>
                                                    </a>
                                                    <a href="?supprimer=1&id=<?php echo $sujet['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Supprimer ce sujet ?');">
                                                        🗑️
                                                    </a>
                                                </div>
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
</html><?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$success = '';
$erreur = '';

// Suppression sujet
if (isset($_GET['supprimer']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("DELETE FROM sujets WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Sujet supprimé.";
}

// Activer/Désactiver sujet
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE sujets SET est_actif = NOT est_actif WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Statut du sujet modifié.";
}

// Récupérer tous les sujets
$sujets = $pdo->query("
    SELECT s.*, m.nom as matiere_nom, n.nom as niveau_nom,
           (SELECT COUNT(*) FROM questions WHERE sujet_id = s.id) as nb_questions,
           (SELECT COUNT(*) FROM compositions WHERE sujet_id = s.id) as nb_compositions
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
    <title>Liste Sujets - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🔐 Admin Panel</h2>
                <p><?php echo nettoyer($_SESSION['admin_email'] ?? ''); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Tableau de Bord</a></li>
                <li><a href="creation-sujet.php">📝 Créer Sujet</a></li>
                <li><a href="liste-sujets.php" class="active">📋 Liste Sujets</a></li>
                <li><a href="surveillance.php">👁️ Surveillance</a></li>
                <li><a href="gestion-resultats.php">📈 Résultats</a></li>
                <li><a href="annonce-resultats.php">📢 Annoncer Résultats</a></li>
                <li><a href="deconnexion.php">🚪 Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📋 Liste des Sujets</h1>
                <a href="creation-sujet.php" class="btn btn-primary">+ Nouveau Sujet</a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($sujets)): ?>
                        <p class="text-center">Aucun sujet créé.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Niveau</th>
                                        <th>Matière</th>
                                        <th>Type</th>
                                        <th>Questions</th>
                                        <th>Compositions</th>
                                        <th>Durée</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sujets as $sujet): ?>
                                        <tr>
                                            <td><?php echo nettoyer($sujet['niveau_nom']); ?></td>
                                            <td><?php echo nettoyer($sujet['matiere_nom']); ?></td>
                                            <td><span class="badge badge-info"><?php echo $sujet['type_evaluation']; ?></span></td>
                                            <td><?php echo $sujet['nb_questions']; ?></td>
                                            <td><?php echo $sujet['nb_compositions']; ?></td>
                                            <td><?php echo $sujet['duree_minutes']; ?> min</td>
                                            <td>
                                                <?php if ($sujet['est_actif']): ?>
                                                    <span class="badge badge-success">✅ Actif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">❌ Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($sujet['date_creation'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?toggle=1&id=<?php echo $sujet['id']; ?>" 
                                                       class="btn btn-sm <?php echo $sujet['est_actif'] ? 'btn-warning' : 'btn-success'; ?>">
                                                        <?php echo $sujet['est_actif'] ? 'Désactiver' : 'Activer'; ?>
                                                    </a>
                                                    <a href="?supprimer=1&id=<?php echo $sujet['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Supprimer ce sujet ?');">
                                                        🗑️
                                                    </a>
                                                </div>
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