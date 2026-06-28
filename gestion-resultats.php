<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

// Récupérer tous les résultats
$stmt = $pdo->query("
    SELECT r.*, e.nom, e.prenom, e.matricule, e.niveau_id,
           s.type_evaluation, m.nom as matiere_nom, n.nom as niveau_nom
    FROM resultats r
    JOIN etudiants e ON r.etudiant_id = e.id
    JOIN sujets s ON r.sujet_id = s.id
    JOIN matieres m ON s.matiere_id = m.id
    JOIN niveaux n ON e.niveau_id = n.id
    ORDER BY r.date_calcul DESC
");
$resultats = $stmt->fetchAll();

// Calculs automatiques
if (isset($_GET['calculer_moyennes'])) {
    $etudiant_id = intval($_GET['calculer_moyennes']);
    
    // Récupérer niveau
    $stmt = $pdo->prepare("SELECT n.type FROM etudiants e JOIN niveaux n ON e.niveau_id = n.id WHERE e.id = ?");
    $stmt->execute([$etudiant_id]);
    $type_niveau = $stmt->fetch()['type'];
    
    if ($type_niveau === 'secondaire') {
        $moyenne = calculerMoyenneLycee($etudiant_id, null);
    } else {
        $credits = calculerCreditsValides($etudiant_id);
        $moyenne = $credits;
    }
    
    $success = "Calcul effectué. Moyenne: $moyenne";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Résultats - Administration</title>
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
                <li><a href="gestion-resultats.php" class="active"><span class="icon">📈</span> Résultats</a></li>
                <li><a href="annonce-resultats.php"><span class="icon">📢</span> Annoncer Résultats</a></li>
                <li><a href="export-resultats.php"><span class="icon">📥</span> Export</a></li>
                <li><a href="deconnexion.php"><span class="icon">🚪</span> Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📈 Gestion des Résultats</h1>
                <div class="export-buttons">
                    <button onclick="exporterDonnees('csv', 'tableResultats')" class="btn-export csv">📋 CSV</button>
                    <button onclick="exporterDonnees('excel', 'tableResultats')" class="btn-export excel">📊 Excel</button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Tous les Résultats</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="tableResultats">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Niveau</th>
                                    <th>Matière</th>
                                    <th>Type</th>
                                    <th>Note</th>
                                    <th>Clé</th>
                                    <th>Publié</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultats as $res): ?>
                                    <tr>
                                        <td><?php echo $res['matricule']; ?></td>
                                        <td><?php echo $res['prenom'] . ' ' . $res['nom']; ?></td>
                                        <td><?php echo $res['niveau_nom']; ?></td>
                                        <td><?php echo $res['matiere_nom']; ?></td>
                                        <td><?php echo $res['type_evaluation']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $res['note'] >= 10 ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo number_format($res['note'], 2); ?>/20
                                            </span>
                                        </td>
                                        <td><code><?php echo $res['cle_unique']; ?></code></td>
                                        <td>
                                            <?php if ($res['est_publie']): ?>
                                                <span class="badge badge-success">✅ Oui</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">⏳ Non</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?calculer_moyennes=<?php echo $res['etudiant_id']; ?>" 
                                               class="btn btn-info btn-sm">📊 Calculer</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function exporterDonnees(format, tableId) {
            const table = document.getElementById(tableId);
            let data = '';
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowData = [];
                cells.forEach(cell => rowData.push('"' + cell.textContent.trim() + '"'));
                data += rowData.join(',') + '\n';
            });
            const blob = new Blob([data], { type: 'text/' + format + ';charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'resultats_' + new Date().toISOString().slice(0,10) + '.' + format;
            a.click();
        }
    </script>
</body>
</html>