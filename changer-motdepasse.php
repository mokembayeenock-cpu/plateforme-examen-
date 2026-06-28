<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancien = $_POST['ancien_mot_de_passe'];
    $nouveau = $_POST['nouveau_mot_de_passe'];
    $confirmer = $_POST['confirmer_mot_de_passe'];
    
    if (empty($ancien) || empty($nouveau) || empty($confirmer)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif ($nouveau !== $confirmer) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($nouveau) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM administrateurs WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (password_verify($ancien, $admin['mot_de_passe'])) {
            $hash = password_hash($nouveau, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE administrateurs SET mot_de_passe = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['admin_id']]);
            $success = "Mot de passe changé avec succès.";
        } else {
            $erreur = "Ancien mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer Mot de passe - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="main-content" style="margin-left:0;max-width:500px;margin:50px auto;">
        <div class="card">
            <div class="card-header"><h3>🔑 Changer le mot de passe</h3></div>
            <div class="card-body">
                <?php if ($erreur): ?><div class="alert alert-danger"><?php echo $erreur; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Ancien mot de passe</label>
                        <input type="password" name="ancien_mot_de_passe" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="nouveau_mot_de_passe" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Confirmer</label>
                        <input type="password" name="confirmer_mot_de_passe" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Changer</button>
                </form>
                <a href="dashboard.php" class="btn btn-outline w-100 mt-2">Retour</a>
            </div>
        </div>
    </div>
</body>
</html>