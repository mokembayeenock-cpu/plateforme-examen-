<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

$erreur = '';
$resultat = null;
$etudiant_info = null;
$moyenne_generale = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cle = nettoyer($_POST['cle_resultat'] ?? '');
    
    if (empty($cle)) {
        $erreur = "Veuillez entrer votre clé de résultat.";
    } elseif (strlen($cle) !== 8) {
        $erreur = "La clé doit comporter 8 caractères.";
    } else {
        // Rechercher le résultat
        $stmt = $pdo->prepare("
            SELECT r.*, s.type_evaluation, s.matiere_id, m.nom as matiere_nom, m.coefficient, m.credit,
                   e.nom, e.prenom, e.matricule, e.date_naissance, e.email, e.telephone, e.photo_path,
                   e.niveau_id, n.nom as niveau_nom, n.type as niveau_type
            FROM resultats r
            JOIN sujets s ON r.sujet_id = s.id
            JOIN matieres m ON s.matiere_id = m.id
            JOIN etudiants e ON r.etudiant_id = e.id
            JOIN niveaux n ON e.niveau_id = n.id
            WHERE r.cle_unique = ?
        ");
        $stmt->execute([$cle]);
        $resultat = $stmt->fetch();
        
        if (!$resultat) {
            $erreur = "Clé invalide. Aucun résultat trouvé.";
        } elseif (!$resultat['est_publie']) {
            $erreur = "Les résultats ne sont pas encore publiés. Veuillez patienter.";
        } else {
            $etudiant_info = [
                'nom' => $resultat['nom'],
                'prenom' => $resultat['prenom'],
                'matricule' => $resultat['matricule'],
                'date_naissance' => $resultat['date_naissance'],
                'email' => $resultat['email'],
                'telephone' => $resultat['telephone'],
                'photo' => $resultat['photo_path'],
                'niveau' => $resultat['niveau_nom']
            ];
            
            // Calculer la moyenne générale
            $etudiant_id = $resultat['etudiant_id'];
            
            $stmt = $pdo->prepare("
                SELECT r.note, s.type_evaluation, m.nom as matiere_nom, m.coefficient
                FROM resultats r
                JOIN sujets s ON r.sujet_id = s.id
                JOIN matieres m ON s.matiere_id = m.id
                WHERE r.etudiant_id = ? AND r.est_publie = TRUE
            ");
            $stmt->execute([$etudiant_id]);
            $tous_resultats = $stmt->fetchAll();
            
            // Calcul moyenne
            $somme_notes_coef = 0;
            $somme_coefficients = 0;
            $matieres_results = [];
            
            foreach ($tous_resultats as $res) {
                $somme_notes_coef += $res['note'] * $res['coefficient'];
                $somme_coefficients += $res['coefficient'];
                
                if (!isset($matieres_results[$res['matiere_nom']])) {
                    $matieres_results[$res['matiere_nom']] = ['notes' => [], 'coefficient' => $res['coefficient']];
                }
                $matieres_results[$res['matiere_nom']]['notes'][] = $res;
            }
            
            $moyenne_generale = $somme_coefficients > 0 ? round($somme_notes_coef / $somme_coefficients, 2) : 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Résultat - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/etudiant.css">
</head>
<body>
    <div class="formulaire-container">
        <?php if (!$resultat): ?>
            <div class="resultat-card">
                <h2>🔍 Consultation de Résultat</h2>
                <p>Entrez votre clé unique pour consulter votre résultat</p>
                
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="cle_resultat">Clé de résultat (8 caractères)</label>
                        <input type="text" id="cle_resultat" name="cle_resultat" class="form-control" 
                               placeholder="Ex: Ab3$kL9@" maxlength="8" required
                               style="text-align:center;font-size:1.2em;letter-spacing:3px;">
                    </div>
                    <button type="submit" class="btn btn-etudiant w-100 btn-lg">Consulter</button>
                </form>
                
                <a href="../index.php" class="btn btn-outline btn-sm mt-2">Retour</a>
            </div>
        <?php else: ?>
            <div class="resultat-card">
                <h2>📊 Résultat</h2>
                
                <!-- Infos étudiant -->
                <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px;flex-wrap:wrap;justify-content:center;">
                    <?php if ($etudiant_info['photo']): ?>
                        <img src="../<?php echo $etudiant_info['photo']; ?>" alt="Photo" 
                             style="width:100px;height:133px;object-fit:cover;border-radius:5px;border:2px solid #ddd;">
                    <?php endif; ?>
                    <div style="text-align:left;">
                        <p><strong>Nom :</strong> <?php echo $etudiant_info['prenom'] . ' ' . $etudiant_info['nom']; ?></p>
                        <p><strong>Matricule :</strong> <?php echo $etudiant_info['matricule']; ?></p>
                        <p><strong>Niveau :</strong> <?php echo $etudiant_info['niveau']; ?></p>
                        <p><strong>Né le :</strong> <?php echo date('d/m/Y', strtotime($etudiant_info['date_naissance'])); ?></p>
                        <p><strong>Email :</strong> <?php echo $etudiant_info['email']; ?></p>
                        <p><strong>Tél :</strong> <?php echo $etudiant_info['telephone']; ?></p>
                    </div>
                </div>
                
                <hr>
                
                <!-- Note -->
                <div style="margin:20px 0;">
                    <p><strong>Matière :</strong> <?php echo $resultat['matiere_nom']; ?></p>
                    <p><strong>Type :</strong> <?php echo $resultat['type_evaluation']; ?></p>
                    
                    <div class="note-circle <?php echo $resultat['note'] >= 10 ? 'reussi' : 'echec'; ?>">
                        <?php echo number_format($resultat['note'], 2); ?>/20
                    </div>
                    
                    <p style="font-size:1.2em;font-weight:600;color:<?php echo $resultat['note'] >= 10 ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo $resultat['note'] >= 10 ? '✅ Admis' : '❌ Non admis'; ?>
                    </p>
                </div>
                
                <?php if ($moyenne_generale !== null): ?>
                    <div class="alert alert-info">
                        <strong>Moyenne générale :</strong> <?php echo $moyenne_generale; ?>/20
                    </div>
                <?php endif; ?>
                
                <!-- Téléchargement -->
                <div class="export-buttons" style="justify-content:center;">
                    <a href="telecharger.php?cle=<?php echo $cle; ?>&format=pdf" class="btn-export pdf">📄 PDF</a>
                    <a href="telecharger.php?cle=<?php echo $cle; ?>&format=excel" class="btn-export excel">📊 Excel</a>
                    <a href="telecharger.php?cle=<?php echo $cle; ?>&format=csv" class="btn-export csv">📋 CSV</a>
                    <a href="telecharger.php?cle=<?php echo $cle; ?>&format=doc" class="btn-export doc">📝 DOC</a>
                </div>
                
                <a href="../index.php" class="btn btn-outline btn-sm mt-2">Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>