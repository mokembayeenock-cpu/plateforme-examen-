<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

// Si déjà connecté, rediriger vers le dashboard
if (estAdminConnecte()) {
    rediriger('admin/dashboard.php');
}

$erreur = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erreur = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $email = nettoyer($_POST['email']);
        $mot_de_passe = $_POST['mot_de_passe'];
        
        if (empty($email) || empty($mot_de_passe)) {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!validerEmail($email)) {
            $erreur = "Format d'email invalide.";
        } else {
            // Vérifier les identifiants
            $stmt = $pdo->prepare("SELECT * FROM administrateurs WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($mot_de_passe, $admin['mot_de_passe'])) {
                // Connexion réussie
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_nom'] = $admin['nom'];
                
                // Mettre à jour la base de données
                $stmt = $pdo->prepare("UPDATE administrateurs SET est_connecte = TRUE, derniere_connexion = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
                // Créer une session admin
                $token = bin2hex(random_bytes(32));
                $date_expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                $stmt = $pdo->prepare("INSERT INTO sessions_admin (admin_id, token, date_expiration) VALUES (?, ?, ?)");
                $stmt->execute([$admin['id'], $token, $date_expiration]);
                
                $_SESSION['admin_token'] = $token;
                
                journaliser('Connexion admin', "Admin: {$admin['email']}");
                
                rediriger('admin/dashboard.php');
            } else {
                $erreur = "Email ou mot de passe incorrect.";
                journaliser('Tentative connexion admin échouée', "Email: $email");
            }
        }
    }
}

// Générer token CSRF
$csrf_token = genererCSRF();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-admin-login">
    <div class="overlay">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h1>🔐 Administration</h1>
                    <p>Connexion sécurisée</p>
                </div>
                
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?php echo $erreur; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="email">📧 Adresse Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="administrateur@gmail.com" required 
                               value="<?php echo isset($_POST['email']) ? nettoyer($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe">🔒 Mot de passe</label>
                        <div class="password-input">
                            <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" 
                                   placeholder="Votre mot de passe" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-admin w-100 btn-lg">
                            Se Connecter
                        </button>
                    </div>
                </form>
                
                <div class="login-footer">
                    <a href="../index.php">← Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('mot_de_passe');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
        }
        
        // Redirection automatique après inactivité (15 minutes)
        let inactivityTimeout;
        
        function resetInactivityTimer() {
            clearTimeout(inactivityTimeout);
            inactivityTimeout = setTimeout(() => {
                window.location.href = '../admin/deconnexion.php?inactivite=1';
            }, 15 * 60 * 1000); // 15 minutes
        }
        
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        document.addEventListener('click', resetInactivityTimer);
        document.addEventListener('scroll', resetInactivityTimer);
        
        resetInactivityTimer();
    </script>
</body>
</html>