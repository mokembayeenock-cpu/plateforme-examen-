<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

// Récupérer les compositions en cours
$stmt = $pdo->query("
    SELECT c.*, e.nom, e.prenom, e.matricule, e.email, e.telephone,
           s.type_evaluation, m.nom as matiere_nom, n.nom as niveau_nom,
           (SELECT COUNT(*) FROM alertes_fraudes af WHERE af.composition_id = c.id AND af.est_traitee = FALSE) as nb_alertes
    FROM compositions c
    JOIN etudiants e ON c.etudiant_id = e.id
    JOIN sujets s ON c.sujet_id = s.id
    JOIN matieres m ON s.matiere_id = m.id
    JOIN niveaux n ON s.niveau_id = n.id
    WHERE c.est_termine = FALSE
    ORDER BY c.date_debut DESC
");
$compositions = $stmt->fetchAll();

// Traitement action admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'deconnecter') {
        $composition_id = intval($_POST['composition_id']);
        $etudiant_id = intval($_POST['etudiant_id']);
        $motif = nettoyer($_POST['motif'] ?? 'Déconnexion par administrateur');
        
        $stmt = $pdo->prepare("
            UPDATE compositions 
            SET est_deconnecte_force = TRUE, est_fraude = TRUE, motif_fraude = ?, date_fin = NOW(), est_termine = TRUE
            WHERE id = ?
        ");
        $stmt->execute([$motif, $composition_id]);
        
        $stmt = $pdo->prepare("UPDATE etudiants SET est_connecte = FALSE, session_active = FALSE WHERE id = ?");
        $stmt->execute([$etudiant_id]);
        
        // Ajouter alerte
        $stmt = $pdo->prepare("
            INSERT INTO alertes_fraudes (composition_id, etudiant_id, type_alerte, message, est_traitee, action_admin)
            VALUES (?, ?, 'tentative_triche', ?, TRUE, 'deconnexion')
        ");
        $stmt->execute([$composition_id, $etudiant_id, $motif]);
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'avertir') {
        $composition_id = intval($_POST['composition_id']);
        $etudiant_id = intval($_POST['etudiant_id']);
        $message = nettoyer($_POST['message_fraude'] ?? 'Fraude détectée');
        
        $stmt = $pdo->prepare("
            INSERT INTO alertes_fraudes (composition_id, etudiant_id, type_alerte, message, est_traitee, action_admin)
            VALUES (?, ?, 'filmage', ?, TRUE, 'avertissement')
        ");
        $stmt->execute([$composition_id, $etudiant_id, $message]);
    }
    
    rediriger('admin/surveillance.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surveillance - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <meta http-equiv="refresh" content="30">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🔐 Admin Panel</h2>
                <p><?php echo nettoyer($_SESSION['admin_email']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span> Tableau de Bord</a></li>
                <li><a href="creation-sujet.php"><span class="icon">📝</span> Créer Sujet</a></li>
                <li><a href="surveillance.php" class="active"><span class="icon">👁️</span> Surveillance</a></li>
                <li><a href="deconnexion.php"><span class="icon">🚪</span> Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>👁️ Surveillance en temps réel</h1>
                <span class="badge badge-info">Auto-rafraîchissement 30s</span>
            </div>
            
            <?php if (empty($compositions)): ?>
                <div class="alert alert-info">Aucune composition en cours actuellement.</div>
            <?php else: ?>
                <div class="surveillance-grid">
                    <?php foreach ($compositions as $comp): ?>
                        <div class="student-card <?php echo $comp['nb_alertes'] > 0 ? 'fraude' : ''; ?>">
                            <span class="status-dot <?php echo $comp['nb_alertes'] > 0 ? 'fraude' : 'active'; ?>"></span>
                            
                            <h4><?php echo nettoyer($comp['prenom'] . ' ' . $comp['nom']); ?></h4>
                            <p><strong>Matricule:</strong> <?php echo $comp['matricule']; ?></p>
                            <p><strong>Niveau:</strong> <?php echo $comp['niveau_nom']; ?></p>
                            <p><strong>Matière:</strong> <?php echo $comp['matiere_nom']; ?></p>
                            <p><strong>Type:</strong> <?php echo $comp['type_evaluation']; ?></p>
                            <p><strong>Début:</strong> <?php echo date('H:i:s', strtotime($comp['date_debut'])); ?></p>
                            <p><strong>Limite:</strong> <?php echo date('H:i:s', strtotime($comp['date_limite'])); ?></p>
                            <p><strong>IP:</strong> <?php echo $comp['ip_etudiant']; ?></p>
                            
                            <?php if ($comp['nb_alertes'] > 0): ?>
                                <div class="alert alert-danger">
                                    🚨 <?php echo $comp['nb_alertes']; ?> alerte(s) de fraude
                                </div>
                            <?php endif; ?>
                            
                            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
                                <button onclick="ouvrirModalAvertir(<?php echo $comp['id']; ?>, <?php echo $comp['etudiant_id']; ?>)" 
                                        class="btn btn-warning btn-sm">⚠️ Écrire Fraude</button>
                                
                                <form method="POST" action="" style="display:inline;" 
                                      onsubmit="return confirm('Déconnecter cet étudiant ?');">
                                    <input type="hidden" name="action" value="deconnecter">
                                    <input type="hidden" name="composition_id" value="<?php echo $comp['id']; ?>">
                                    <input type="hidden" name="etudiant_id" value="<?php echo $comp['etudiant_id']; ?>">
                                    <input type="hidden" name="motif" value="Fraude détectée par administrateur">
                                    <button type="submit" class="btn btn-danger btn-sm">🚫 Déconnecter</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal avertir -->
    <div class="modal" id="modalAvertir">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚠️ Envoyer un avertissement</h3>
                <button class="modal-close" onclick="fermerModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="avertir">
                <input type="hidden" name="composition_id" id="modalCompId">
                <input type="hidden" name="etudiant_id" id="modalEtuId">
                <div class="form-group">
                    <label>Message de fraude</label>
                    <textarea name="message_fraude" class="form-control" rows="3" required>Fraude détectée ! Arrêtez immédiatement toute tentative de triche.</textarea>
                </div>
                <button type="submit" class="btn btn-warning w-100">Envoyer l'avertissement</button>
            </form>
        </div>
    </div>
    
    <script>
        function ouvrirModalAvertir(compId, etuId) {
            document.getElementById('modalCompId').value = compId;
            document.getElementById('modalEtuId').value = etuId;
            document.getElementById('modalAvertir').classList.add('show');
        }
        
        function fermerModal() {
            document.getElementById('modalAvertir').classList.remove('show');
        }
        
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
            }
        });
    </script>
</body>
</html>