<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

// Vérifier connexion admin
if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

// Statistiques
$stats = [];

// Nombre total d'étudiants
$stmt = $pdo->query("SELECT COUNT(*) as total FROM etudiants");
$stats['total_etudiants'] = $stmt->fetch()['total'];

// Nombre de sujets actifs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM sujets WHERE est_actif = TRUE");
$stats['sujets_actifs'] = $stmt->fetch()['total'];

// Nombre de compositions en cours
$stmt = $pdo->query("SELECT COUNT(*) as total FROM compositions WHERE est_termine = FALSE");
$stats['compositions_en_cours'] = $stmt->fetch()['total'];

// Nombre d'alertes de fraude non traitées
$stmt = $pdo->query("SELECT COUNT(*) as total FROM alertes_fraudes WHERE est_traitee = FALSE");
$stats['alertes_fraudes'] = $stmt->fetch()['total'];

// Étudiants connectés actuellement
$stmt = $pdo->query("SELECT COUNT(*) as total FROM etudiants WHERE est_connecte = TRUE");
$stats['etudiants_connectes'] = $stmt->fetch()['total'];

// Récupérer les alertes récentes
$stmt = $pdo->query("
    SELECT af.*, e.nom, e.prenom, e.matricule 
    FROM alertes_fraudes af
    JOIN etudiants e ON af.etudiant_id = e.id
    WHERE af.est_traitee = FALSE
    ORDER BY af.date_alerte DESC
    LIMIT 10
");
$alertes_recentes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>🔐 Admin Panel</h2>
                <p><?php echo nettoyer($_SESSION['admin_email']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><span class="icon">📊</span> Tableau de Bord</a></li>
                <li><a href="gestion-niveaux.php"><span class="icon">🏫</span> Niveaux</a></li>
                <li><a href="gestion-matieres.php"><span class="icon">📚</span> Matières</a></li>
                <li><a href="gestion-modules.php"><span class="icon">📦</span> Modules</a></li>
                <li><a href="gestion-etudiants.php"><span class="icon">👨‍🎓</span> Étudiants</a></li>
                <li><a href="creation-sujet.php"><span class="icon">📝</span> Créer Sujet</a></li>
                <li><a href="liste-sujets.php"><span class="icon">📋</span> Liste Sujets</a></li>
                <li><a href="surveillance.php"><span class="icon">👁️</span> Surveillance</a></li>
                <li><a href="gestion-resultats.php"><span class="icon">📈</span> Résultats</a></li>
                <li><a href="annonce-resultats.php"><span class="icon">📢</span> Annoncer Résultats</a></li>
                <li><a href="alertes-fraudes.php"><span class="icon">🚨</span> Alertes Fraudes
                    <?php if ($stats['alertes_fraudes'] > 0): ?>
                        <span class="badge badge-danger"><?php echo $stats['alertes_fraudes']; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="changer-motdepasse.php"><span class="icon">🔑</span> Changer Mot de passe</a></li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="deconnexion.php" class="btn btn-danger w-100">🚪 Déconnexion</a>
            </div>
        </aside>
        
        <!-- Contenu principal -->
        <main class="main-content">
            <div class="admin-header">
                <h1>📊 Tableau de Bord</h1>
                <div class="admin-user">
                    <div class="avatar">A</div>
                    <span><?php echo nettoyer($_SESSION['admin_nom'] ?? 'Admin'); ?></span>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">👨‍🎓</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_etudiants']; ?></h3>
                        <p>Étudiants inscrits</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">📝</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['sujets_actifs']; ?></h3>
                        <p>Sujets actifs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['compositions_en_cours']; ?></h3>
                        <p>Compositions en cours</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon purple">🟢</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['etudiants_connectes']; ?></h3>
                        <p>Étudiants connectés</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">🚨</div>
                    <div class="stat-info">
                        <h3><?php echo $stats['alertes_fraudes']; ?></h3>
                        <p>Alertes fraude</p>
                    </div>
                </div>
            </div>
            
            <!-- Alertes récentes -->
            <div class="card">
                <div class="card-header">
                    <h3>🚨 Alertes Fraude Récentes</h3>
                    <a href="alertes-fraudes.php" class="btn btn-outline btn-sm">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($alertes_recentes)): ?>
                        <p class="text-center">✅ Aucune alerte de fraude en attente.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Étudiant</th>
                                        <th>Matricule</th>
                                        <th>Type</th>
                                        <th>Message</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alertes_recentes as $alerte): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($alerte['date_alerte'])); ?></td>
                                            <td><?php echo nettoyer($alerte['nom'] . ' ' . $alerte['prenom']); ?></td>
                                            <td><?php echo $alerte['matricule']; ?></td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <?php echo $alerte['type_alerte']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo nettoyer($alerte['message']); ?></td>
                                            <td>
                                                <a href="alertes-fraudes.php?action=traiter&id=<?php echo $alerte['id']; ?>" 
                                                   class="btn btn-warning btn-sm">Traiter</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="card">
                <div class="card-header">
                    <h3>⚡ Actions Rapides</h3>
                </div>
                <div class="card-body">
                    <div class="btn-group">
                        <a href="creation-sujet.php" class="btn btn-primary">📝 Créer un Sujet</a>
                        <a href="surveillance.php" class="btn btn-warning">👁️ Surveiller</a>
                        <a href="annonce-resultats.php" class="btn btn-success">📢 Annoncer Résultats</a>
                        <a href="gestion-etudiants.php" class="btn btn-info">👨‍🎓 Gérer Étudiants</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>