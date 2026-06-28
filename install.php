<?php
// =====================================================
// INSTALLATEUR AUTOMATIQUE - PLATEFORME EXAMENS
// =====================================================

$etape = $_GET['etape'] ?? 1;
$erreurs = [];
$success = '';

// Vérification prérequis
function verifierPHP() {
    return version_compare(PHP_VERSION, '7.4.0', '>=');
}

function verifierExtension($ext) {
    return extension_loaded($ext);
}

function verifierDossier($dossier) {
    return is_writable($dossier);
}

$extensions_requises = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json', 'session', 'fileinfo'];
$dossiers_requis = [
    'assets/uploads/',
    'assets/uploads/photos/',
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Plateforme Examens</title>
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --dark: #343a40;
            --light: #f8f9fa;
            --white: #ffffff;
            --border: #dee2e6;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .install-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: var(--white);
            padding: 40px;
            text-align: center;
        }
        
        .install-header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .install-header p {
            opacity: 0.8;
        }
        
        .install-body {
            padding: 40px;
        }
        
        .install-footer {
            background: var(--light);
            padding: 20px 40px;
            text-align: center;
            border-top: 1px solid var(--border);
        }
        
        .steps {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: var(--border);
            color: #666;
        }
        
        .step.active {
            background: var(--primary);
            color: var(--white);
        }
        
        .step.done {
            background: var(--success);
            color: var(--white);
        }
        
        .step.error {
            background: var(--danger);
            color: var(--white);
        }
        
        .step-connector {
            width: 50px;
            height: 2px;
            background: var(--border);
            align-self: center;
        }
        
        .step-connector.done {
            background: var(--success);
        }
        
        .check-list {
            list-style: none;
            margin: 20px 0;
        }
        
        .check-list li {
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .check-list li.pass {
            background: #d4edda;
            color: #155724;
        }
        
        .check-list li.fail {
            background: #f8d7da;
            color: #721c24;
        }
        
        .check-list li.warn {
            background: #fff3cd;
            color: #856404;
        }
        
        .check-icon {
            font-size: 1.2em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }
        
        .btn-success {
            background: var(--success);
            color: var(--white);
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 20px;
            height: 8px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, var(--primary), var(--success));
            height: 100%;
            border-radius: 20px;
            transition: width 0.5s ease;
        }
        
        .code-block {
            background: #1a1a2e;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin: 15px 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .summary-table th,
        .summary-table td {
            padding: 10px;
            border: 1px solid var(--border);
            text-align: left;
        }
        
        .summary-table th {
            background: var(--dark);
            color: var(--white);
        }
        
        .text-center { text-align: center; }
        .mt-1 { margin-top: 10px; }
        .mt-2 { margin-top: 20px; }
        .mt-3 { margin-top: 30px; }
        .mb-2 { margin-bottom: 20px; }
        .w-100 { width: 100%; }
        
        @media (max-width: 600px) {
            .install-body { padding: 20px; }
            .install-header { padding: 25px; }
            .install-header h1 { font-size: 1.5em; }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1>🎓 Installation - Plateforme Examens</h1>
            <p>Assistant d'installation automatique</p>
        </div>
        
        <div class="install-body">
            <!-- Étapes -->
            <div class="steps">
                <?php 
                $steps = [
                    1 => 'Prérequis',
                    2 => 'Base de données',
                    3 => 'Configuration',
                    4 => 'Installation',
                    5 => 'Terminé'
                ];
                
                foreach ($steps as $num => $label):
                    $class = '';
                    if ($num < $etape) $class = 'done';
                    if ($num == $etape) $class = 'active';
                ?>
                    <span class="step <?php echo $class; ?>"><?php echo $num; ?></span>
                    <?php if ($num < count($steps)): ?>
                        <span class="step-connector <?php echo $num < $etape ? 'done' : ''; ?>"></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Barre progression -->
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo ($etape / count($steps)) * 100; ?>%;"></div>
            </div>
            
            <?php if ($etape == 1): ?>
                <!-- ÉTAPE 1 : Vérification prérequis -->
                <h2>📋 Étape 1 : Vérification des Prérequis</h2>
                <p>Vérification de votre environnement serveur.</p>
                
                <ul class="check-list">
                    <?php
                    $all_ok = true;
                    
                    // Vérification PHP
                    $php_ok = verifierPHP();
                    $all_ok = $all_ok && $php_ok;
                    ?>
                    <li class="<?php echo $php_ok ? 'pass' : 'fail'; ?>">
                        <span class="check-icon"><?php echo $php_ok ? '✅' : '❌'; ?></span>
                        Version PHP >= 7.4 (Actuel: <?php echo PHP_VERSION; ?>)
                    </li>
                    
                    <?php foreach ($extensions_requises as $ext): 
                        $ext_ok = verifierExtension($ext);
                        $all_ok = $all_ok && $ext_ok;
                    ?>
                        <li class="<?php echo $ext_ok ? 'pass' : 'fail'; ?>">
                            <span class="check-icon"><?php echo $ext_ok ? '✅' : '❌'; ?></span>
                            Extension PHP : <?php echo $ext; ?>
                        </li>
                    <?php endforeach; ?>
                    
                    <?php
                    // Vérification dossiers
                    foreach ($dossiers_requis as $dossier):
                        if (!is_dir($dossier)) {
                            @mkdir($dossier, 0755, true);
                        }
                        $dir_ok = verifierDossier($dossier);
                        $all_ok = $all_ok && $dir_ok;
                    ?>
                        <li class="<?php echo $dir_ok ? 'pass' : 'fail'; ?>">
                            <span class="check-icon"><?php echo $dir_ok ? '✅' : '❌'; ?></span>
                            Dossier accessible en écriture : <?php echo $dossier; ?>
                        </li>
                    <?php endforeach; ?>
                    
                    <?php
                    // Vérification fichier .htaccess
                    $htaccess_ok = file_exists('.htaccess');
                    ?>
                    <li class="<?php echo $htaccess_ok ? 'pass' : 'warn'; ?>">
                        <span class="check-icon"><?php echo $htaccess_ok ? '✅' : '⚠️'; ?></span>
                        Fichier .htaccess présent
                    </li>
                </ul>
                
                <?php if ($all_ok): ?>
                    <div class="alert alert-success">✅ Tous les prérequis sont satisfaits !</div>
                    <a href="?etape=2" class="btn btn-primary w-100">Continuer →</a>
                <?php else: ?>
                    <div class="alert alert-danger">❌ Certains prérequis ne sont pas satisfaits. Veuillez les corriger avant de continuer.</div>
                    <button class="btn btn-primary w-100" disabled>Continuer →</button>
                <?php endif; ?>
                
            <?php elseif ($etape == 2): ?>
                <!-- ÉTAPE 2 : Configuration Base de données -->
                <h2>🗄️ Étape 2 : Base de Données</h2>
                <p>Configurez votre connexion MySQL.</p>
                
                <form method="POST" action="?etape=3">
                    <div class="form-group">
                        <label>Hôte de la base de données</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nom de la base de données</label>
                        <input type="text" name="db_name" class="form-control" value="plateforme_examens" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Utilisateur</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="db_pass" class="form-control">
                    </div>
                    
                    <div class="alert alert-info">
                        💡 La base de données sera créée automatiquement si elle n'existe pas.
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Tester et Continuer →</button>
                </form>
                
            <?php elseif ($etape == 3): ?>
                <!-- ÉTAPE 3 : Test connexion -->
                <h2>🔌 Étape 3 : Test de Connexion</h2>
                
                <?php
                $db_host = $_POST['db_host'] ?? 'localhost';
                $db_name = $_POST['db_name'] ?? 'plateforme_examens';
                $db_user = $_POST['db_user'] ?? 'root';
                $db_pass = $_POST['db_pass'] ?? '';
                
                // Sauvegarder config
                $config_data = "<?php\n";
                $config_data .= "define('DB_HOST', '$db_host');\n";
                $config_data .= "define('DB_NAME', '$db_name');\n";
                $config_data .= "define('DB_USER', '$db_user');\n";
                $config_data .= "define('DB_PASS', '$db_pass');\n";
                $config_data .= "define('SITE_URL', 'http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/');\n";
                $config_data .= "define('SITE_NAME', 'Plateforme Examens');\n";
                $config_data .= "define('ADMIN_EMAIL', 'administrateur@gmail.com');\n";
                $config_data .= "define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');\n";
                $config_data .= "define('PHOTO_DIR', UPLOAD_DIR . 'photos/');\n";
                $config_data .= "define('MAX_PHOTO_SIZE', 2048);\n";
                $config_data .= "ini_set('session.cookie_httponly', 1);\n";
                $config_data .= "ini_set('session.use_only_cookies', 1);\n";
                $config_data .= "ini_set('session.cookie_secure', 0);\n";
                $config_data .= "date_default_timezone_set('Africa/Porto-Novo');\n\n";
                $config_data .= "try {\n";
                $config_data .= "    \$pdo = new PDO(\n";
                $config_data .= "        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\",\n";
                $config_data .= "        DB_USER,\n";
                $config_data .= "        DB_PASS,\n";
                $config_data .= "        [\n";
                $config_data .= "            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
                $config_data .= "            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
                $config_data .= "            PDO::ATTR_EMULATE_PREPARES => false,\n";
                $config_data .= "        ]\n";
                $config_data .= "    );\n";
                $config_data .= "} catch (PDOException \$e) {\n";
                $config_data .= "    die(\"Erreur de connexion : \" . \$e->getMessage());\n";
                $config_data .= "}\n\n";
                $config_data .= "if (session_status() === PHP_SESSION_NONE) {\n";
                $config_data .= "    session_start();\n";
                $config_data .= "}\n";
                
                try {
                    // Connexion sans base pour créer
                    $pdo_test = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    
                    // Créer base si nécessaire
                    $pdo_test->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Connexion à la base
                    $pdo_test = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    
                    // Sauvegarder config temporaire
                    file_put_contents('includes/config_temp.php', $config_data);
                    
                    echo '<div class="alert alert-success">✅ Connexion à la base de données réussie !</div>';
                    
                    // Afficher résumé
                    echo '<table class="summary-table">';
                    echo "<tr><th>Paramètre</th><th>Valeur</th></tr>";
                    echo "<tr><td>Hôte</td><td>$db_host</td></tr>";
                    echo "<tr><td>Base de données</td><td>$db_name</td></tr>";
                    echo "<tr><td>Utilisateur</td><td>$db_user</td></tr>";
                    echo "<tr><td>URL du site</td><td>http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/</td></tr>";
                    echo '</table>';
                    
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">❌ Erreur de connexion : ' . $e->getMessage() . '</div>';
                    echo '<a href="?etape=2" class="btn btn-danger w-100">↩ Réessayer</a>';
                    $etape = 2;
                }
                ?>
                
                <?php if ($etape == 3): ?>
                    <a href="?etape=4" class="btn btn-primary w-100">Installer la Base de Données →</a>
                <?php endif; ?>
                
            <?php elseif ($etape == 4): ?>
                <!-- ÉTAPE 4 : Installation -->
                <h2>⚙️ Étape 4 : Installation</h2>
                
                <?php
                // Charger config temporaire
                if (file_exists('includes/config_temp.php')) {
                    require_once 'includes/config_temp.php';
                    
                    // Lire et exécuter le schéma SQL
                    $sql_file = 'database/schema.sql';
                    
                    if (file_exists($sql_file)) {
                        $sql_content = file_get_contents($sql_file);
                        
                        // Supprimer les lignes CREATE DATABASE et USE
                        $sql_content = preg_replace('/CREATE DATABASE.*?;/i', '', $sql_content);
                        $sql_content = preg_replace('/USE.*?;/i', '', $sql_content);
                        
                        // Séparer les requêtes
                        $queries = explode(';', $sql_content);
                        $success_count = 0;
                        $error_count = 0;
                        $errors_list = [];
                        
                        foreach ($queries as $query) {
                            $query = trim($query);
                            if (!empty($query)) {
                                try {
                                    $pdo->exec($query);
                                    $success_count++;
                                } catch (PDOException $e) {
                                    // Ignorer erreurs de table existante
                                    if (strpos($e->getMessage(), 'already exists') === false) {
                                        $error_count++;
                                        $errors_list[] = $e->getMessage();
                                    } else {
                                        $success_count++;
                                    }
                                }
                            }
                        }
                        
                        echo '<div class="alert alert-success">✅ ' . $success_count . ' requêtes exécutées avec succès.</div>';
                        
                        if ($error_count > 0) {
                            echo '<div class="alert alert-warning">⚠️ ' . $error_count . ' erreur(s) rencontrée(s).</div>';
                            foreach ($errors_list as $err) {
                                echo '<div class="alert alert-danger" style="font-size:0.85em;">' . htmlspecialchars($err) . '</div>';
                            }
                        }
                        
                        // Créer compte admin par défaut
                        $admin_password = password_hash('admin1234', PASSWORD_BCRYPT);
                        
                        try {
                            $stmt = $pdo->prepare("SELECT id FROM administrateurs WHERE email = ?");
                            $stmt->execute(['administrateur@gmail.com']);
                            
                            if (!$stmt->fetch()) {
                                $stmt = $pdo->prepare("INSERT INTO administrateurs (email, mot_de_passe, nom) VALUES (?, ?, ?)");
                                $stmt->execute(['administrateur@gmail.com', $admin_password, 'Administrateur']);
                                echo '<div class="alert alert-success">✅ Compte administrateur créé.</div>';
                            }
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-warning">⚠️ Compte admin existe déjà ou erreur.</div>';
                        }
                        
                        // Créer annonces par défaut
                        try {
                            $pdo->exec("CREATE TABLE IF NOT EXISTS annonces (
                                id INT PRIMARY KEY AUTO_INCREMENT,
                                message TEXT NOT NULL,
                                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            )");
                            
                            $stmt = $pdo->prepare("INSERT INTO annonces (message) VALUES (?)");
                            $stmt->execute(['Bienvenue sur la plateforme d\'examens en ligne. Les examens sont ouverts selon le calendrier académique.']);
                        } catch (PDOException $e) {}
                        
                        // Remplacer config temporaire par définitive
                        if (file_exists('includes/config_temp.php')) {
                            $config_content = file_get_contents('includes/config_temp.php');
                            // Remplacer config_temp par config
                            $config_content = str_replace("require_once 'config_temp.php'", "require_once 'config.php'", $config_content);
                            file_put_contents('includes/config.php', $config_content);
                            unlink('includes/config_temp.php');
                        }
                        
                        echo '<div class="alert alert-success">✅ Installation terminée avec succès !</div>';
                        
                    } else {
                        echo '<div class="alert alert-danger">❌ Fichier schema.sql introuvable.</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">❌ Fichier de configuration temporaire introuvable. Veuillez reprendre à l\'étape 2.</div>';
                }
                ?>
                
                <a href="?etape=5" class="btn btn-success w-100">Finaliser l'Installation →</a>
                
            <?php elseif ($etape == 5): ?>
                <!-- ÉTAPE 5 : Terminé -->
                <h2>🎉 Installation Terminée !</h2>
                
                <div class="alert alert-success">
                    <h3>✅ Félicitations !</h3>
                    <p>Votre plateforme d'examens est installée et prête à l'emploi.</p>
                </div>
                
                <div class="code-block">
📋 Informations de connexion :

🔐 Administrateur :
   Email : administrateur@gmail.com
   Mot de passe : admin1234
   ⚠️ CHANGEZ CE MOT DE PASSE IMMEDIATEMENT !

🎓 Étudiants :
   Les étudiants doivent créer leur compte via la page de connexion.

🌐 Accès :
   <a href="index.php" style="color:#00ff00;">Accéder à la Plateforme</a>
                </div>
                
                <div class="alert alert-warning">
                    <strong>⚠️ Important :</strong> Pour des raisons de sécurité, veuillez :
                    <ul style="margin-top:10px;padding-left:20px;">
                        <li>Supprimer le fichier <code>install.php</code></li>
                        <li>Changer le mot de passe administrateur</li>
                        <li>Vérifier les permissions des dossiers</li>
                    </ul>
                </div>
                
                <div style="display:flex;gap:15px;flex-wrap:wrap;">
                    <a href="index.php" class="btn btn-success flex-grow-1">🏠 Accéder au Site</a>
                    <a href="admin/connexion.php" class="btn btn-primary flex-grow-1">🔐 Administration</a>
                </div>
                
                <form method="POST" action="" onsubmit="return confirm('SUPPRIMER le fichier install.php ?');" class="mt-2">
                    <button type="submit" name="delete_install" class="btn btn-danger w-100">🗑️ Supprimer install.php</button>
                </form>
                
                <?php
                if (isset($_POST['delete_install'])) {
                    if (unlink(__FILE__)) {
                        echo '<div class="alert alert-success mt-2">✅ Fichier install.php supprimé.</div>';
                        echo '<script>setTimeout(function(){ window.location.href = "index.php"; }, 2000);</script>';
                    } else {
                        echo '<div class="alert alert-danger mt-2">❌ Impossible de supprimer. Faites-le manuellement.</div>';
                    }
                }
                ?>
            <?php endif; ?>
        </div>
        
        <div class="install-footer">
            <p>© <?php echo date('Y'); ?> Plateforme Examens - Tous droits réservés</p>
        </div>
    </div>
</body>
</html>