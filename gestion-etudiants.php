<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$success = '';
$erreur = '';
$recherche = $_GET['recherche'] ?? '';

// Supprimer étudiant
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $pdo->prepare("DELETE FROM etudiants WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Étudiant supprimé.";
}

// Réinitialiser mot de passe
if (isset($_GET['reset_mdp'])) {
    $id = intval($_GET['reset_mdp']);
    $nouveau_mdp = 'etu12345';
    $hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE etudiants SET mot_de_passe = ? WHERE id = ?");
    $stmt->execute([$hash, $id]);
    $success = "Mot de passe réinitialisé à : etu12345";
}

// Récupérer étudiants
$sql = "
    SELECT e.*, n.nom as niveau_nom 
    FROM etudiants e 
    JOIN niveaux n ON e.niveau_id = n.id 
";
if (!empty($recherche)) {
    $sql .= " WHERE e.matricule LIKE ? OR e.nom LIKE ? OR e.prenom LIKE ? OR e.email LIKE ?";
    $stmt = $pdo->prepare($sql . " ORDER BY e.nom, e.prenom");
    $stmt->execute(["%$recherche%", "%$recherche%", "%$recherche%", "%$recherche%"]);
} else {
    $stmt = $pdo->query($sql . " ORDER BY e.date_inscription DESC");
}
$etudiants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Étudiants - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header"><h2>🔐 Admin Panel</h2></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Tableau de Bord</a></li>
                <li><a href="gestion-niveaux.php">🏫 Niveaux</a></li>
                <li><a href="gestion-etudiants.php" class="active">👨‍🎓 Étudiants</a></li>
                <li><a href="deconnexion.php">🚪 Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>👨‍🎓 Gestion des Étudiants</h1>
                <span class="badge badge-info">Total: <?php echo count($etudiants); ?></span>
            </div>
            
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <!-- Recherche -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="d-flex gap-1">
                        <input type="text" name="recherche" class="form-control" 
                               placeholder="Rechercher par matricule, nom, prénom, email..." 
                               value="<?php echo nettoyer($recherche); ?>">
                        <button type="submit" class="btn btn-primary">🔍</button>
                        <?php if ($recherche): ?>
                            <a href="gestion-etudiants.php" class="btn btn-outline">✕</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Liste -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($etudiants)): ?>
                        <p class="text-center">Aucun étudiant trouvé.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Matricule</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Niveau</th>
                                        <th>Régime</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etudiants as $e): ?>
                                        <tr>
                                            <td>
                                                <?php if ($e['photo_path']): ?>
                                                    <img src="../<?php echo $e['photo_path']; ?>" alt="Photo" 
                                                         style="width:40px;height:53px;object-fit:cover;border-radius:3px;">
                                                <?php else: ?>
                                                    <span style="color:#ccc;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?php echo $e['matricule']; ?></code></td>
                                            <td><?php echo nettoyer($e['nom']); ?></td>
                                            <td><?php echo nettoyer($e['prenom']); ?></td>
                                            <td><small><?php echo nettoyer($e['email']); ?></small></td>
                                            <td><?php echo $e['telephone']; ?></td>
                                            <td><?php echo $e['niveau_nom']; ?></td>
                                            <td><span class="badge badge-info"><?php echo $e['regime']; ?></span></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="?reset_mdp=<?php echo $e['id']; ?>" 
                                                       class="btn btn-warning btn-sm"
                                                       onclick="return confirm('Réinitialiser le mot de passe ?');">
                                                        🔑
                                                    </a>
                                                    <a href="?supprimer=<?php echo $e['id']; ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Supprimer définitivement ?');">
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
</body>
</html>