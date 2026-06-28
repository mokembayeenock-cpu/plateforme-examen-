<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$format = $_GET['format'] ?? '';
$niveau_id = $_GET['niveau_id'] ?? 0;

// Récupérer niveaux
$niveaux = $pdo->query("SELECT * FROM niveaux ORDER BY type, nom")->fetchAll();

// Si format demandé, exporter
if ($format && $niveau_id > 0) {
    $stmt = $pdo->prepare("
        SELECT e.matricule, e.nom, e.prenom, e.date_naissance, e.email, e.telephone,
               n.nom as niveau_nom, m.nom as matiere_nom, s.type_evaluation,
               r.note, r.cle_unique, r.date_calcul
        FROM resultats r
        JOIN etudiants e ON r.etudiant_id = e.id
        JOIN sujets s ON r.sujet_id = s.id
        JOIN matieres m ON s.matiere_id = m.id
        JOIN niveaux n ON e.niveau_id = n.id
        WHERE e.niveau_id = ? AND r.est_publie = TRUE
        ORDER BY e.nom, e.prenom, m.nom
    ");
    $stmt->execute([$niveau_id]);
    $resultats = $stmt->fetchAll();
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultats_niveau_' . $niveau_id . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Date Naissance', 'Email', 'Téléphone', 'Niveau', 'Matière', 'Type', 'Note', 'Clé', 'Date']);
        
        foreach ($resultats as $r) {
            fputcsv($output, [
                $r['matricule'], $r['nom'], $r['prenom'], $r['date_naissance'],
                $r['email'], $r['telephone'], $r['niveau_nom'], $r['matiere_nom'],
                $r['type_evaluation'], $r['note'] . '/20', $r['cle_unique'],
                date('d/m/Y H:i', strtotime($r['date_calcul']))
            ]);
        }
        fclose($output);
        exit;
    }
    
    if ($format === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultats_niveau_' . $niveau_id . '.xls"');
        
        echo '<table border="1">';
        echo '<tr><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Niveau</th><th>Matière</th><th>Type</th><th>Note</th><th>Date</th></tr>';
        foreach ($resultats as $r) {
            echo '<tr>';
            echo '<td>' . $r['matricule'] . '</td>';
            echo '<td>' . $r['nom'] . '</td>';
            echo '<td>' . $r['prenom'] . '</td>';
            echo '<td>' . $r['niveau_nom'] . '</td>';
            echo '<td>' . $r['matiere_nom'] . '</td>';
            echo '<td>' . $r['type_evaluation'] . '</td>';
            echo '<td>' . $r['note'] . '/20</td>';
            echo '<td>' . date('d/m/Y', strtotime($r['date_calcul'])) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Export Résultats - Administration</title>
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
                <li><a href="export-resultats.php" class="active"><span class="icon">📥</span> Export</a></li>
                <li><a href="deconnexion.php"><span class="icon">🚪</span> Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📥 Export des Résultats</h1>
            </div>
            
            <div class="card">
                <div class="card-header"><h3>Exporter par Niveau</h3></div>
                <div class="card-body">
                    <div class="dashboard-grid">
                        <?php foreach ($niveaux as $niveau): ?>
                            <div class="card text-center">
                                <h4><?php echo $niveau['nom']; ?></h4>
                                <p><small><?php echo $niveau['type']; ?></small></p>
                                <div class="export-buttons" style="justify-content:center;">
                                    <a href="?format=csv&niveau_id=<?php echo $niveau['id']; ?>" class="btn-export csv">CSV</a>
                                    <a href="?format=excel&niveau_id=<?php echo $niveau['id']; ?>" class="btn-export excel">Excel</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>