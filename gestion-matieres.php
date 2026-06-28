<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$success = '';
$erreur = '';

// Ajouter matière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom = nettoyer($_POST['nom']);
    $coefficient = floatval($_POST['coefficient']);
    $credit = intval($_POST['credit']);
    $type = nettoyer($_POST['type_matiere']);
    
    if (empty($nom)) {
        $erreur = "Le nom est obligatoire.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO matieres (nom, coefficient, credit, type_matiere) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $coefficient, $credit, $type]);
        $success = "Matière ajoutée.";
    }
}

// Modifier coefficient
if (isset($_POST['modifier_coef'])) {
    $id = intval($_POST['matiere_id']);
    $coef = floatval($_POST['coefficient']);
    $stmt = $pdo->prepare("UPDATE matieres SET coefficient = ? WHERE id = ?");
    $stmt->execute([$coef, $id]);
    $success = "Coefficient mis à jour.";
}

// Supprimer
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $pdo->prepare("DELETE FROM matieres WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Matière supprimée.";
}

// Récupérer matières
$matieres = $pdo->query("SELECT * FROM matieres ORDER BY type_matiere, nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Matières - Administration</title>
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
                <li><a href="gestion-matieres.php" class="active">📚 Matières</a></li>
                <li><a href="gestion-modules.php">📦 Modules</a></li>
                <li><a href="deconnexion.php">🚪 Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header"><h1>📚 Gestion des Matières</h1></div>
            
            <?php if ($erreur): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <!-- Ajout -->
            <div class="card">
                <div class="card-header"><h3>Ajouter une Matière</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="ajouter" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="nom" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Coefficient</label>
                                <input type="number" name="coefficient" class="form-control" value="1" step="0.5" min="0.5">
                            </div>
                            <div class="form-group">
                                <label>Crédit</label>
                                <input type="number" name="credit" class="form-control" value="3" min="1">
                            </div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type_matiere" class="form-control">
                                    <option value="general">Général</option>
                                    <option value="scientifique">Scientifique</option>
                                    <option value="litteraire">Littéraire</option>
                                    <option value="technique">Technique</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>
            </div>
            
            <!-- Liste -->
            <div class="card">
                <div class="card-header"><h3>Matières Existantes</h3></div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr><th>Nom</th><th>Type</th><th>Coefficient</th><th>Crédit</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matieres as $m): ?>
                                <tr>
                                    <td><?php echo $m['nom']; ?></td>
                                    <td><span class="badge badge-info"><?php echo $m['type_matiere']; ?></span></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="modifier_coef" value="1">
                                            <input type="hidden" name="matiere_id" value="<?php echo $m['id']; ?>">
                                            <input type="number" name="coefficient" value="<?php echo $m['coefficient']; ?>" 
                                                   step="0.5" min="0.5" style="width:70px;padding:5px;">
                                            <button type="submit" class="btn btn-sm btn-primary">✓</button>
                                        </form>
                                    </td>
                                    <td><?php echo $m['credit'] ?? '-'; ?></td>
                                    <td>
                                        <a href="?supprimer=<?php echo $m['id']; ?>" 
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