<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$success = '';
$erreur = '';

// Ajouter niveau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = nettoyer($_POST['nom']);
    $type = nettoyer($_POST['type']);
    $cycle = nettoyer($_POST['cycle']);
    
    if (empty($nom)) {
        $erreur = "Le nom est obligatoire.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO niveaux (nom, type, cycle) VALUES (?, ?, ?)");
            $stmt->execute([$nom, $type, $cycle]);
            $success = "Niveau ajouté.";
        } catch (PDOException $e) {
            $erreur = "Ce niveau existe déjà.";
        }
    }
}

// Supprimer
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $pdo->prepare("DELETE FROM niveaux WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Niveau supprimé.";
}

// Récupérer niveaux
$niveaux = $pdo->query("SELECT * FROM niveaux ORDER BY type, nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Niveaux - Administration</title>
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
                <li><a href="gestion-niveaux.php" class="active">🏫 Niveaux</a></li>
                <li><a href="gestion-matieres.php">📚 Matières</a></li>
                <li><a href="gestion-modules.php">📦 Modules</a></li>
                <li><a href="deconnexion.php">🚪 Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header"><h1>🏫 Gestion des Niveaux</h1></div>
            
            <?php if ($erreur): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <!-- Formulaire ajout -->
            <div class="card">
                <div class="card-header"><h3>Ajouter un Niveau</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="ajouter" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="nom" class="form-control" placeholder="Ex: L1 Info" required>
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control" required>
                                    <option value="secondaire">Secondaire</option>
                                    <option value="universitaire">Universitaire</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Cycle</label>
                                <select name="cycle" class="form-control">
                                    <option value="college">Collège</option>
                                    <option value="lycee">Lycée</option>
                                    <option value="licence">Licence</option>
                                    <option value="master">Master</option>
                                    <option value="doctorat">Doctorat</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>
            </div>
            
            <!-- Liste -->
            <div class="card">
                <div class="card-header"><h3>Niveaux Existants</h3></div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr><th>Nom</th><th>Type</th><th>Cycle</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($niveaux as $n): ?>
                                <tr>
                                    <td><?php echo $n['nom']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $n['type']; ?></span></td>
                                    <td><?php echo $n['cycle']; ?></td>
                                    <td>
                                        <a href="?supprimer=<?php echo $n['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Supprimer ?');">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>