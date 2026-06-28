<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

// Vérifier connexion
if (!estEtudiantConnecte()) {
    rediriger('etudiant/connexion.php');
}

$etudiant_id = $_SESSION['etudiant_id'];
$erreur = '';
$success = '';

// Récupérer les infos de l'étudiant
$stmt = $pdo->prepare("SELECT e.*, n.nom as niveau_nom, n.type as niveau_type FROM etudiants e JOIN niveaux n ON e.niveau_id = n.id WHERE e.id = ?");
$stmt->execute([$etudiant_id]);
$etudiant = $stmt->fetch();

// Récupérer les niveaux disponibles
$niveaux = $pdo->query("SELECT * FROM niveaux ORDER BY type, nom")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = nettoyer($_POST['nom']);
    $prenom = nettoyer($_POST['prenom']);
    $date_naissance = $_POST['date_naissance'];
    $email = nettoyer($_POST['email']);
    $telephone = nettoyer($_POST['telephone']);
    $niveau_id = intval($_POST['niveau_id']);
    $regime = isset($_POST['regime']) ? nettoyer($_POST['regime']) : 'normal';
    
    // Validations
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($email) || empty($telephone)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!validerEmail($email)) {
        $erreur = "Format d'email invalide.";
    } elseif (!validerTelephone($telephone)) {
        $erreur = "Format de téléphone invalide.";
    } else {
        // Gestion photo
        $photo_path = $etudiant['photo_path'];
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['photo'];
            
            // Vérifier taille (max 2KB = 2048 octets)
            if ($file['size'] > MAX_PHOTO_SIZE) {
                $erreur = "La photo ne doit pas dépasser 2KB.";
            } else {
                // Vérifier type
                $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!in_array($file['type'], $allowed)) {
                    $erreur = "Format photo invalide. Utilisez JPG ou PNG.";
                } else {
                    // Créer dossier si nécessaire
                    if (!is_dir(PHOTO_DIR)) {
                        mkdir(PHOTO_DIR, 0755, true);
                    }
                    
                    // Générer nom unique
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $photo_name = 'etu_' . $etudiant_id . '_' . time() . '.' . $extension;
                    $destination = PHOTO_DIR . $photo_name;
                    
                    // Redimensionner et traiter fond vert
                    $image = imagecreatefromstring(file_get_contents($file['tmp_name']));
                    
                    // Créer image 4x4 proportion (120x160 pixels)
                    $new_image = imagecreatetruecolor(120, 160);
                    
                    // Fond vert
                    $vert = imagecolorallocate($new_image, 0, 255, 0);
                    imagefill($new_image, 0, 0, $vert);
                    
                    // Redimensionner
                    $src_width = imagesx($image);
                    $src_height = imagesy($image);
                    
                    imagecopyresampled($new_image, $image, 10, 10, 0, 0, 100, 140, $src_width, $src_height);
                    
                    // Sauvegarder
                    if ($extension === 'png') {
                        imagepng($new_image, $destination, 9);
                    } else {
                        imagejpeg($new_image, $destination, 60);
                    }
                    
                    imagedestroy($image);
                    imagedestroy($new_image);
                    
                    // Optimiser à 2KB
                    $quality = 40;
                    while (filesize($destination) > MAX_PHOTO_SIZE && $quality > 5) {
                        if ($extension === 'png') {
                            imagepng(imagecreatefromstring(file_get_contents($destination)), $destination, 9);
                        } else {
                            imagejpeg(imagecreatefromstring(file_get_contents($destination)), $destination, $quality);
                        }
                        $quality -= 5;
                    }
                    
                    $photo_path = 'assets/uploads/photos/' . $photo_name;
                }
            }
        }
        
        if (empty($erreur)) {
            // Générer matricule si nouveau
            $matricule = $etudiant['matricule'] ?? genererMatricule();
            
            // Mettre à jour
            $stmt = $pdo->prepare("
                UPDATE etudiants 
                SET nom = ?, prenom = ?, date_naissance = ?, email = ?, telephone = ?, 
                    niveau_id = ?, regime = ?, photo_path = ?, matricule = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $prenom, $date_naissance, $email, $telephone, $niveau_id, $regime, $photo_path, $matricule, $etudiant_id]);
            
            $_SESSION['etudiant_nom'] = $nom . ' ' . $prenom;
            $_SESSION['etudiant_niveau'] = $niveau_id;
            
            $success = "Informations enregistrées avec succès. Votre matricule est : " . $matricule;
            
            // Rediriger vers composition
            rediriger('etudiant/composition.php');
        }
    }
}

$csrf_token = genererCSRF();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire Étudiant - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/etudiant.css">
</head>
<body>
    <div class="formulaire-container">
        <div class="formulaire-card">
            <h2>📋 Informations Étudiant</h2>
            <p class="sous-titre">Veuillez compléter vos informations pour l'examen</p>
            
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo $erreur; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <!-- Photo -->
                <div class="photo-upload">
                    <div class="photo-preview" id="photoPreview">
                        <?php if ($etudiant['photo_path']): ?>
                            <img src="../<?php echo $etudiant['photo_path']; ?>" alt="Photo">
                        <?php else: ?>
                            <span class="placeholder">Photo 4x4<br>Fond vert</span>
                        <?php endif; ?>
                    </div>
                    <label for="photo" class="btn btn-outline btn-sm">📷 Choisir une photo</label>
                    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png">
                    <small style="display:block;margin-top:5px;color:#6c757d;">Format 4x4, fond vert, max 2KB</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" class="form-control" 
                               value="<?php echo nettoyer($etudiant['nom'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" 
                               value="<?php echo nettoyer($etudiant['prenom'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance *</label>
                        <input type="date" id="date_naissance" name="date_naissance" class="form-control" 
                               value="<?php echo $etudiant['date_naissance'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau_id">Niveau *</label>
                        <select id="niveau_id" name="niveau_id" class="form-control" required>
                            <option value="">Sélectionner un niveau</option>
                            <?php foreach ($niveaux as $niveau): ?>
                                <option value="<?php echo $niveau['id']; ?>" 
                                    <?php echo ($etudiant['niveau_id'] ?? '') == $niveau['id'] ? 'selected' : ''; ?>>
                                    <?php echo $niveau['nom'] . ' (' . $niveau['type'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo nettoyer($etudiant['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" 
                               value="<?php echo nettoyer($etudiant['telephone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <?php if (($etudiant['niveau_type'] ?? '') === 'universitaire'): ?>
                <div class="form-group">
                    <label for="regime">Régime *</label>
                    <select id="regime" name="regime" class="form-control" required>
                        <option value="normal" <?php echo ($etudiant['regime'] ?? '') === 'normal' ? 'selected' : ''; ?>>Régime Normal</option>
                        <option value="special" <?php echo ($etudiant['regime'] ?? '') === 'special' ? 'selected' : ''; ?>>Régime Spécial</option>
                        <option value="nouveau" <?php echo ($etudiant['regime'] ?? '') === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                        <option value="endette" <?php echo ($etudiant['regime'] ?? '') === 'endette' ? 'selected' : ''; ?>>Endetté</option>
                        <option value="redoublant" <?php echo ($etudiant['regime'] ?? '') === 'redoublant' ? 'selected' : ''; ?>>Redoublant</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group mt-2">
                    <button type="submit" class="btn btn-etudiant w-100 btn-lg" name="composer">
                        📝 Composer
                    </button>
                </div>
            </form>
            
            <div style="text-align:center;margin-top:15px;">
                <a href="deconnexion.php" class="btn btn-outline btn-sm">Se déconnecter</a>
            </div>
        </div>
    </div>
    
    <script>
        // Prévisualisation photo
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2048) {
                    alert('La photo ne doit pas dépasser 2KB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('photoPreview');
                    preview.innerHTML = '<img src="' + event.target.result + '" alt="Aperçu">';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>