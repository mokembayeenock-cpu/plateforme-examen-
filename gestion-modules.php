<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$erreur = '';
$success = '';

// Récupérer niveaux universitaires
$niveaux = $pdo->query("SELECT * FROM niveaux WHERE type = 'universitaire' ORDER BY nom")->fetchAll();

// Récupérer modules existants
$modules = $pdo->query("
    SELECT modu.*, n.nom as niveau_nom 
    FROM modules modu 
    JOIN niveaux n ON modu.niveau_id = n.id 
    ORDER BY n.nom, modu.nom
")->fetchAll();

// Récupérer matières
$matieres = $pdo->query("SELECT * FROM matieres ORDER BY nom")->fetchAll();

// Ajouter un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_module'])) {
    $nom = nettoyer($_POST['nom_module']);
    $niveau_id = intval($_POST['niveau_id']);
    
    if (empty($nom) || empty($niveau_id)) {
        $erreur = "Tous les champs sont obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO modules (nom, niveau_id) VALUES (?, ?)");
        $stmt->execute([$nom, $niveau_id]);
        $success = "Module ajouté avec succès.";
    }
}

// Ajouter matière à un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_matiere'])) {
    $module_id = intval($_POST['module_id']);
    $matiere_id = intval($_POST['matiere_id']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO module_matieres (module_id, matiere_id) VALUES (?, ?)");
        $stmt->execute([$module_id, $matiere_id]);
        $success = "Matière ajoutée au module.";
    } catch (PDOException $e) {
        $erreur = "Cette matière est déjà dans ce module.";
    }
}

// Supprimer module
if (isset($_GET['supprimer_module'])) {
    $id = intval($_GET['supprimer_module']);
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Module supprimé.";
    rediriger('admin/gestion-modules.php');
}

// Supprimer matière d'un module
if (isset($_GET['supprimer_matiere'])) {
    $id = intval($_GET['supprimer_matiere']);
    $stmt = $pdo->prepare("DELETE FROM module_matieres WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Matière retirée du module.";
    rediriger('admin/gestion-modules.php');
}

// Ajouter niveau universitaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_niveau'])) {
    $nom_niveau = nettoyer($_POST['nom_niveau']);
    $cycle = nettoyer($_POST['cycle']);
    
    if (!empty($nom_niveau)) {
        $stmt = $pdo->prepare("INSERT INTO niveaux (nom, type, cycle) VALUES (?, 'universitaire', ?)");
        $stmt->execute([$nom_niveau, $cycle]);
        $success = "Niveau ajouté.";
    }
}

// Ajouter matière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_matiere_libre'])) {
    $nom_matiere = nettoyer($_POST['nom_matiere']);
    $credit = intval($_POST['credit']);
    $coefficient = floatval($_POST['coefficient']);
    
    if (!empty($nom_matiere)) {
        $stmt = $pdo->prepare("INSERT INTO matieres (nom, coefficient, credit) VALUES (?, ?, ?)");
        $stmt->execute([$nom_matiere, $coefficient, $credit]);
        $success = "Matière ajoutée.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Modules - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🔐 Admin Panel</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span> Tableau de Bord</a></li>
                <li><a href="gestion-niveaux.php"><span class="icon">🏫</span> Niveaux</a></li>
                <li><a href="gestion-matieres.php"><span class="icon">📚</span> Matières</a></li>
                <li><a href="gestion-modules.php" class="active"><span class="icon">📦</span> Modules</a></li>
                <li><a href="deconnexion.php"><span class="icon">🚪</span> Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📦 Gestion des Modules (Universitaire)</h1>
            </div>
            
            <?php if ($erreur): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            
            <div class="dashboard-grid">
                <!-- Ajouter niveau universitaire -->
                <div class="card">
                    <div class="card-header"><h3>🏫 Ajouter Niveau Universitaire</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="ajouter_niveau" value="1">
                            <div class="form-group">
                                <label>Nom du niveau</label>
                                <input type="text" name="nom_niveau" class="form-control" placeholder="Ex: L1 Informatique" required>
                            </div>
                            <div class="form-group">
                                <label>Cycle</label>
                                <select name="cycle" class="form-control" required>
                                    <option value="licence">Licence</option>
                                    <option value="master">Master</option>
                                    <option value="doctorat">Doctorat</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ajouter Niveau</button>
                        </form>
                    </div>
                </div>
                
                <!-- Ajouter matière -->
                <div class="card">
                    <div class="card-header"><h3>📚 Ajouter Matière</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="ajouter_matiere_libre" value="1">
                            <div class="form-group">
                                <label>Nom matière</label>
                                <input type="text" name="nom_matiere" class="form-control" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Coefficient</label>
                                    <input type="number" name="coefficient" class="form-control" value="1" step="0.5" min="0.5">
                                </div>
                                <div class="form-group">
                                    <label>Crédit</label>
                                    <input type="number" name="credit" class="form-control" value="3" min="1">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ajouter Matière</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Ajouter module -->
            <div class="card">
                <div class="card-header"><h3>📦 Créer un Module</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="ajouter_module" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom du module</label>
                                <input type="text" name="nom_module" class="form-control" placeholder="Ex: Algorithmique" required>
                            </div>
                            <div class="form-group">
                                <label>Niveau</label>
                                <select name="niveau_id" class="form-control" required>
                                    <option value="">Sélectionner</option>
                                    <?php foreach ($niveaux as $niveau): ?>
                                        <option value="<?php echo $niveau['id']; ?>"><?php echo $niveau['nom']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Créer Module</button>
                    </form>
                </div>
            </div>
            
            <!-- Liste modules -->
            <div class="card">
                <div class="card-header"><h3>📋 Modules Existants</h3></div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <p class="text-center">Aucun module créé.</p>
                    <?php else: ?>
                        <?php 
                        $current_niveau = '';
                        foreach ($modules as $module): 
                            if ($current_niveau != $module['niveau_nom']):
                                if ($current_niveau != '') echo '</div></div>';
                                $current_niveau = $module['niveau_nom'];
                        ?>
                            <div class="card mb-2" style="border:2px solid #007bff;">
                                <div class="card-header" style="background:#e3f2fd;">
                                    <h4>🏫 <?php echo $current_niveau; ?></h4>
                                </div>
                                <div class="card-body">
                        <?php endif; ?>
                        
                        <div style="border:1px solid #ddd;padding:15px;margin-bottom:10px;border-radius:8px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                                <strong>📦 <?php echo nettoyer($module['nom']); ?></strong>
                                <div>
                                    <a href="?supprimer_module=<?php echo $module['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Supprimer ce module ?')">🗑️</a>
                                </div>
                            </div>
                            
                            <!-- Matières du module -->
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT mm.id as mm_id, m.nom as matiere_nom, m.credit, m.coefficient
                                FROM module_matieres mm
                                JOIN matieres m ON mm.matiere_id = m.id
                                WHERE mm.module_id = ?
                            ");
                            $stmt->execute([$module['id']]);
                            $matieres_module = $stmt->fetchAll();
                            ?>
                            
                            <div style="margin-top:10px;padding-left:20px;">
                                <strong>Matières :</strong>
                                <?php if (empty($matieres_module)): ?>
                                    <span style="color:#6c757d;">Aucune matière</span>
                                <?php else: ?>
                                    <ul style="list-style:none;padding:0;">
                                        <?php foreach ($matieres_module as $mm): ?>
                                            <li style="padding:5px 0;display:flex;justify-content:space-between;align-items:center;">
                                                📚 <?php echo $mm['matiere_nom']; ?> 
                                                (Crédit: <?php echo $mm['credit']; ?>, Coef: <?php echo $mm['coefficient']; ?>)
                                                <a href="?supprimer_matiere=<?php echo $mm['mm_id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Retirer cette matière ?')">✕</a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Ajouter matière au module -->
                            <form method="POST" style="margin-top:10px;display:flex;gap:10px;align-items:end;">
                                <input type="hidden" name="ajouter_matiere" value="1">
                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                <div class="form-group" style="flex:1;margin:0;">
                                    <select name="matiere_id" class="form-control" required>
                                        <option value="">+ Ajouter matière</option>
                                        <?php foreach ($matieres as $mat): ?>
                                            <option value="<?php echo $mat['id']; ?>"><?php echo $mat['nom']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">+</button>
                            </form>
                        </div>
                        
                        <?php endforeach; ?>
                        <?php if ($current_niveau != '') echo '</div></div>'; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/admin.js"></script>
</body>
</html>