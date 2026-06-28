<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';

$cle = $_GET['cle'] ?? '';
$format = $_GET['format'] ?? 'pdf';

if (empty($cle)) {
    die("Clé manquante.");
}

// Récupérer le résultat
$stmt = $pdo->prepare("
    SELECT r.*, e.nom, e.prenom, e.matricule, e.date_naissance, e.email, e.telephone,
           s.type_evaluation, m.nom as matiere_nom, n.nom as niveau_nom
    FROM resultats r
    JOIN etudiants e ON r.etudiant_id = e.id
    JOIN sujets s ON r.sujet_id = s.id
    JOIN matieres m ON s.matiere_id = m.id
    JOIN niveaux n ON e.niveau_id = n.id
    WHERE r.cle_unique = ? AND r.est_publie = TRUE
");
$stmt->execute([$cle]);
$resultat = $stmt->fetch();

if (!$resultat) {
    die("Résultat non trouvé ou non publié.");
}

// Générer le contenu selon le format
switch ($format) {
    case 'csv':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultat_' . $resultat['matricule'] . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
        
        fputcsv($output, ['Matricule', 'Nom', 'Prénom', 'Niveau', 'Matière', 'Type', 'Note', 'Date']);
        fputcsv($output, [
            $resultat['matricule'],
            $resultat['nom'],
            $resultat['prenom'],
            $resultat['niveau_nom'],
            $resultat['matiere_nom'],
            $resultat['type_evaluation'],
            $resultat['note'] . '/20',
            date('d/m/Y', strtotime($resultat['date_calcul']))
        ]);
        fclose($output);
        break;
        
    case 'excel':
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultat_' . $resultat['matricule'] . '.xls"');
        
        echo '<table border="1">';
        echo '<tr><th>Matricule</th><td>' . $resultat['matricule'] . '</td></tr>';
        echo '<tr><th>Nom</th><td>' . $resultat['prenom'] . ' ' . $resultat['nom'] . '</td></tr>';
        echo '<tr><th>Niveau</th><td>' . $resultat['niveau_nom'] . '</td></tr>';
        echo '<tr><th>Matière</th><td>' . $resultat['matiere_nom'] . '</td></tr>';
        echo '<tr><th>Type</th><td>' . $resultat['type_evaluation'] . '</td></tr>';
        echo '<tr><th>Note</th><td>' . $resultat['note'] . '/20</td></tr>';
        echo '<tr><th>Date</th><td>' . date('d/m/Y', strtotime($resultat['date_calcul'])) . '</td></tr>';
        echo '</table>';
        break;
        
    case 'doc':
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultat_' . $resultat['matricule'] . '.doc"');
        
        echo "<html><body>";
        echo "<h2>Résultat</h2>";
        echo "<p><strong>Matricule :</strong> {$resultat['matricule']}</p>";
        echo "<p><strong>Nom :</strong> {$resultat['prenom']} {$resultat['nom']}</p>";
        echo "<p><strong>Niveau :</strong> {$resultat['niveau_nom']}</p>";
        echo "<p><strong>Matière :</strong> {$resultat['matiere_nom']}</p>";
        echo "<p><strong>Type :</strong> {$resultat['type_evaluation']}</p>";
        echo "<p><strong>Note :</strong> {$resultat['note']}/20</p>";
        echo "<p><strong>Date :</strong> " . date('d/m/Y', strtotime($resultat['date_calcul'])) . "</p>";
        echo "</body></html>";
        break;
        
    case 'pdf':
    default:
        // Simulation PDF (en production utiliser TCPDF/FPDF)
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><title>Résultat</title>";
        echo "<style>
            body { font-family: Arial; padding: 40px; }
            h2 { color: #007bff; text-align: center; }
            .header { text-align: center; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
            th { background: #007bff; color: white; }
            .note { font-size: 2em; text-align: center; font-weight: bold; }
        </style></head><body>";
        echo "<h2>Relevé de Notes</h2>";
        echo "<div class='header'><p>" . SITE_NAME . "</p></div>";
        echo "<table>";
        echo "<tr><th>Matricule</th><td>{$resultat['matricule']}</td></tr>";
        echo "<tr><th>Nom et Prénom</th><td>{$resultat['prenom']} {$resultat['nom']}</td></tr>";
        echo "<tr><th>Date de naissance</th><td>" . date('d/m/Y', strtotime($resultat['date_naissance'])) . "</td></tr>";
        echo "<tr><th>Niveau</th><td>{$resultat['niveau_nom']}</td></tr>";
        echo "<tr><th>Email</th><td>{$resultat['email']}</td></tr>";
        echo "<tr><th>Téléphone</th><td>{$resultat['telephone']}</td></tr>";
        echo "<tr><th>Matière</th><td>{$resultat['matiere_nom']}</td></tr>";
        echo "<tr><th>Type d'évaluation</th><td>{$resultat['type_evaluation']}</td></tr>";
        echo "</table>";
        echo "<p class='note' style='color:" . ($resultat['note'] >= 10 ? 'green' : 'red') . ";'>";
        echo "Note : {$resultat['note']}/20</p>";
        echo "<p style='text-align:center;'>Date : " . date('d/m/Y', strtotime($resultat['date_calcul'])) . "</p>";
        echo "<script>window.print();</script>";
        echo "</body></html>";
        break;
}
?>