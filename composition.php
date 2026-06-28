<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

// Vérifier connexion
if (!estEtudiantConnecte()) {
    rediriger('etudiant/connexion.php');
}

$etudiant_id = $_SESSION['etudiant_id'];

// Récupérer infos étudiant
$stmt = $pdo->prepare("SELECT e.*, n.nom as niveau_nom, n.type as niveau_type FROM etudiants e JOIN niveaux n ON e.niveau_id = n.id WHERE e.id = ?");
$stmt->execute([$etudiant_id]);
$etudiant = $stmt->fetch();

// Vérifier si des sujets sont disponibles
$stmt = $pdo->prepare("
    SELECT s.*, m.nom as matiere_nom, m.coefficient, m.credit
    FROM sujets s
    JOIN matieres m ON s.matiere_id = m.id
    WHERE s.niveau_id = ? AND s.est_actif = TRUE
    ORDER BY s.date_creation DESC
");
$stmt->execute([$etudiant['niveau_id']]);
$sujets = $stmt->fetchAll();

// Vérifier si l'étudiant a déjà une composition en cours
$stmt = $pdo->prepare("
    SELECT c.*, s.duree_minutes, s.type_evaluation, m.nom as matiere_nom
    FROM compositions c
    JOIN sujets s ON c.sujet_id = s.id
    JOIN matieres m ON s.matiere_id = m.id
    WHERE c.etudiant_id = ? AND c.est_termine = FALSE
    ORDER BY c.date_debut DESC
    LIMIT 1
");
$stmt->execute([$etudiant_id]);
$composition_en_cours = $stmt->fetch();

$sujet_actif = null;
$questions = [];

if ($composition_en_cours) {
    $stmt = $pdo->prepare("SELECT * FROM sujets WHERE id = ?");
    $stmt->execute([$composition_en_cours['sujet_id']]);
    $sujet_actif = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE sujet_id = ? ORDER BY ordre ASC");
    $stmt->execute([$sujet_actif['id']]);
    $questions = $stmt->fetchAll();
    
    // Récupérer options quiz
    foreach ($questions as &$question) {
        if ($question['type'] === 'quiz') {
            $stmt = $pdo->prepare("SELECT * FROM quiz_options WHERE question_id = ?");
            $stmt->execute([$question['id']]);
            $question['options'] = $stmt->fetchAll();
        }
    }
}

// Traitement lancement composition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sujet_id'])) {
    $sujet_id = intval($_POST['sujet_id']);
    
    // Vérifier si pas déjà en cours
    $stmt = $pdo->prepare("SELECT id FROM compositions WHERE etudiant_id = ? AND sujet_id = ? AND est_termine = FALSE");
    $stmt->execute([$etudiant_id, $sujet_id]);
    
    if ($stmt->fetch()) {
        $erreur = "Vous avez déjà une composition en cours pour ce sujet.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM sujets WHERE id = ? AND est_actif = TRUE");
        $stmt->execute([$sujet_id]);
        $sujet = $stmt->fetch();
        
        if ($sujet) {
            $date_debut = date('Y-m-d H:i:s');
            $date_limite = date('Y-m-d H:i:s', strtotime("+{$sujet['duree_minutes']} minutes"));
            
            $stmt = $pdo->prepare("
                INSERT INTO compositions (etudiant_id, sujet_id, date_debut, date_limite, ip_etudiant) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$etudiant_id, $sujet_id, $date_debut, $date_limite, $_SERVER['REMOTE_ADDR']]);
            
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
    <title>Composition - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/etudiant.css">
</head>
<body>
    <?php if ($composition_en_cours && $sujet_actif): ?>
        <!-- PAGE DE COMPOSITION ACTIVE -->
        <div class="composition-container">
            <div class="composition-header">
                <div class="info">
                    <span class="matiere">📚 <?php echo nettoyer($composition_en_cours['matiere_nom']); ?></span>
                    <span class="badge badge-info"><?php echo $composition_en_cours['type_evaluation']; ?></span>
                </div>
                
                <div class="timer-box" id="timer">
                    <span class="timer-icon">⏱️</span>
                    <span id="timerDisplay">--:--</span>
                </div>
                
                <div>
                    <span>👨‍🎓 <?php echo nettoyer($_SESSION['etudiant_nom']); ?></span>
                </div>
            </div>
            
            <!-- Barre progression -->
            <div class="progress-text">
                Questions répondues : <span id="answeredCount">0</span> / <?php echo count($questions); ?>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" id="progressBar" style="width: 0%;"></div>
            </div>
            
            <!-- Navigation questions -->
            <div class="questions-nav" id="questionsNav">
                <?php foreach ($questions as $index => $q): ?>
                    <button class="question-nav-btn" data-question="<?php echo $index; ?>" 
                            onclick="scrollToQuestion(<?php echo $index; ?>)">
                        <?php echo $index + 1; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <!-- Formulaire questions -->
            <form id="compositionForm" method="POST" action="soumettre.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="composition_id" value="<?php echo $composition_en_cours['id']; ?>">
                <input type="hidden" name="sujet_id" value="<?php echo $sujet_actif['id']; ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-block" id="question-<?php echo $index; ?>">
                        <div class="question-header">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="question-numero"><?php echo $question['numero']; ?></span>
                                <span class="question-points"><?php echo $question['points']; ?> pt(s)</span>
                            </div>
                        </div>
                        
                        <div class="question-enonce">
                            <?php echo nl2br(nettoyer($question['enonce'])); ?>
                        </div>
                        
                        <?php if ($question['type'] === 'quiz' && isset($question['options'])): ?>
                            <ul class="options-list">
                                <?php foreach ($question['options'] as $optIndex => $option): ?>
                                    <li class="option-item" onclick="selectOption(this, <?php echo $index; ?>)">
                                        <input type="radio" name="reponse_<?php echo $question['id']; ?>" 
                                               value="<?php echo nettoyer($option['option_texte']); ?>"
                                               id="opt_<?php echo $question['id']; ?>_<?php echo $optIndex; ?>">
                                        <span class="option-lettre"><?php echo chr(65 + $optIndex); ?></span>
                                        <span><?php echo nettoyer($option['option_texte']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <textarea name="reponse_<?php echo $question['id']; ?>" 
                                      class="reponse-textarea" 
                                      placeholder="Votre réponse..."
                                      onchange="updateProgress()"></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Soumission -->
                <div class="submit-section">
                    <p class="warning-text">⚠️ Une fois soumis, vous ne pourrez plus modifier vos réponses.</p>
                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirmerSoumission()">
                        📤 Soumettre la composition
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Overlay fraude -->
        <div class="anti-fraude-overlay" id="fraudeOverlay">
            <div class="anti-fraude-content">
                <div class="icon">🚨</div>
                <h2>ALERTE FRAUDE</h2>
                <div class="message-fraude" id="fraudeMessage">
                    Une tentative de fraude a été détectée.
                </div>
                <p>L'administrateur a été informé.</p>
            </div>
        </div>
        
        <script>
            // Timer
            const dateLimite = new Date('<?php echo $composition_en_cours['date_limite']; ?>').getTime();
            
            function updateTimer() {
                const now = new Date().getTime();
                const distance = dateLimite - now;
                
                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById('timerDisplay').textContent = 
                    String(hours).padStart(2, '0') + ':' + 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
                
                const timerBox = document.getElementById('timer');
                
                if (distance < 300000) { // 5 minutes
                    timerBox.classList.add('warning');
                }
                
                if (distance < 0) {
                    clearInterval(timerInterval);
                    document.getElementById('compositionForm').submit();
                }
            }
            
            const timerInterval = setInterval(updateTimer, 1000);
            updateTimer();
            
            // Progression
            function updateProgress() {
                const total = <?php echo count($questions); ?>;
                let answered = 0;
                
                <?php foreach ($questions as $index => $q): ?>
                    <?php if ($q['type'] === 'quiz'): ?>
                        if (document.querySelector('input[name="reponse_<?php echo $q['id']; ?>"]:checked')) {
                            answered++;
                            document.querySelector('[data-question="<?php echo $index; ?>"]')?.classList.add('repondu');
                        }
                    <?php else: ?>
                        const textarea<?php echo $index; ?> = document.querySelector('textarea[name="reponse_<?php echo $q['id']; ?>"]');
                        if (textarea<?php echo $index; ?> && textarea<?php echo $index; ?>.value.trim() !== '') {
                            answered++;
                            document.querySelector('[data-question="<?php echo $index; ?>"]')?.classList.add('repondu');
                        }
                    <?php endif; ?>
                <?php endforeach; ?>
                
                document.getElementById('answeredCount').textContent = answered;
                document.getElementById('progressBar').style.width = (answered / total * 100) + '%';
            }
            
            function selectOption(element, questionIndex) {
                const radio = element.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
                updateProgress();
            }
            
            function scrollToQuestion(index) {
                document.getElementById('question-' + index).scrollIntoView({ behavior: 'smooth' });
            }
            
            function confirmerSoumission() {
                return confirm('Êtes-vous sûr de vouloir soumettre votre composition ? Cette action est irréversible.');
            }
            
            // Anti-fraude
            let fraudWarnings = 0;
            
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    fraudWarnings++;
                    sendFraudAlert('sortie_page');
                }
            });
            
            // Détection capture écran
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 's' || e.key === 'PrintScreen')) {
                    e.preventDefault();
                    fraudWarnings++;
                    sendFraudAlert('tentative_triche');
                    return false;
                }
            });
            
            async function sendFraudAlert(type) {
                try {
                    await fetch('../api/alerte-fraude.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            composition_id: <?php echo $composition_en_cours['id']; ?>,
                            etudiant_id: <?php echo $etudiant_id; ?>,
                            type: type,
                            message: 'Tentative détectée - Avertissement ' + fraudWarnings
                        })
                    });
                    
                    if (fraudWarnings >= 3) {
                        document.getElementById('fraudeMessage').textContent = 
                            'Fraude détectée ! Vous allez être déconnecté.';
                        document.getElementById('fraudeOverlay').style.display = 'flex';
                        
                        setTimeout(() => {
                            window.location.href = 'deconnexion.php?fraude=1';
                        }, 3000);
                    }
                } catch (error) {
                    console.error('Erreur alerte:', error);
                }
            }
            
            // Mise à jour initiale progression
            updateProgress();
        </script>
        
    <?php else: ?>
        <!-- SÉLECTION DU SUJET -->
        <div class="formulaire-container">
            <div class="formulaire-card">
                <h2>📝 Sujets Disponibles</h2>
                <p class="sous-titre">Sélectionnez un sujet pour commencer la composition</p>
                
                <?php if (empty($sujets)): ?>
                    <div class="alert alert-info">
                        Aucun sujet disponible pour le moment. Veuillez attendre que l'administrateur active un sujet.
                    </div>
                <?php else: ?>
                    <div class="sujets-list">
                        <?php foreach ($sujets as $sujet): ?>
                            <div class="card" style="margin-bottom:15px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                                    <div>
                                        <strong><?php echo nettoyer($sujet['matiere_nom']); ?></strong>
                                        <br>
                                        <small>
                                            Type: <?php echo $sujet['type_evaluation']; ?> | 
                                            Durée: <?php echo $sujet['duree_minutes']; ?> min |
                                            Coef: <?php echo $sujet['coefficient']; ?>
                                        </small>
                                    </div>
                                    <form method="POST" action="">
                                        <input type="hidden" name="sujet_id" value="<?php echo $sujet['id']; ?>">
                                        <button type="submit" class="btn btn-etudiant">Commencer</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align:center;margin-top:15px;">
                    <a href="deconnexion.php" class="btn btn-outline btn-sm">Se déconnecter</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>