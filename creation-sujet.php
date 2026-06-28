<?php
require_once '../includes/config.php';
require_once '../includes/fonctions.php';
require_once '../includes/securite.php';

if (!estAdminConnecte()) {
    rediriger('admin/connexion.php');
}

$erreur = '';
$success = '';

// Récupérer niveaux
$niveaux = $pdo->query("SELECT * FROM niveaux ORDER BY type, nom")->fetchAll();

// Récupérer matières
$matieres = $pdo->query("SELECT * FROM matieres ORDER BY nom")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $niveau_id = intval($_POST['niveau_id']);
    $matiere_id = intval($_POST['matiere_id']);
    $type_evaluation = nettoyer($_POST['type_evaluation']);
    $type_questions = nettoyer($_POST['type_questions']);
    $duree_minutes = intval($_POST['duree_minutes']);
    $admin_id = $_SESSION['admin_id'];
    
    // Récupérer les questions
    $questions_data = $_POST['questions'] ?? [];
    $points_data = $_POST['points'] ?? [];
    $reponses_data = $_POST['reponses_correctes'] ?? [];
    $options_data = $_POST['options'] ?? [];
    
    if (empty($questions_data) || count($questions_data) > 20) {
        $erreur = "Le sujet doit contenir entre 1 et 20 questions.";
    } elseif (empty($niveau_id) || empty($matiere_id)) {
        $erreur = "Veuillez sélectionner un niveau et une matière.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Créer le sujet
            $stmt = $pdo->prepare("
                INSERT INTO sujets (admin_id, niveau_id, matiere_id, type_evaluation, type_questions, duree_minutes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$admin_id, $niveau_id, $matiere_id, $type_evaluation, $type_questions, $duree_minutes]);
            $sujet_id = $pdo->lastInsertId();
            
            // Ajouter les questions
            foreach ($questions_data as $index => $enonce) {
                if (empty(trim($enonce))) continue;
                
                $points = floatval($points_data[$index] ?? 1);
                $reponse_correcte = $reponses_data[$index] ?? null;
                
                $stmt = $pdo->prepare("
                    INSERT INTO questions (sujet_id, numero, enonce, type, points, reponse_correcte, ordre)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $sujet_id,
                    $index + 1,
                    $enonce,
                    $type_questions,
                    $points,
                    $reponse_correcte,
                    $index
                ]);
                
                // Ajouter options quiz
                if ($type_questions === 'quiz' && isset($options_data[$index])) {
                    $question_id = $pdo->lastInsertId();
                    
                    foreach ($options_data[$index] as $optIndex => $option_texte) {
                        if (empty(trim($option_texte))) continue;
                        
                        $est_correcte = (trim($option_texte) === trim($reponse_correcte));
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO quiz_options (question_id, option_texte, est_correcte)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$question_id, $option_texte, $est_correcte]);
                    }
                }
            }
            
            $pdo->commit();
            $success = "Sujet créé avec succès !";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = "Erreur: " . $e->getMessage();
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
    <title>Créer un Sujet - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                <li><a href="creation-sujet.php" class="active"><span class="icon">📝</span> Créer Sujet</a></li>
                <li><a href="liste-sujets.php"><span class="icon">📋</span> Liste Sujets</a></li>
                <li><a href="surveillance.php"><span class="icon">👁️</span> Surveillance</a></li>
                <li><a href="gestion-resultats.php"><span class="icon">📈</span> Résultats</a></li>
                <li><a href="deconnexion.php"><span class="icon">🚪</span> Déconnexion</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="admin-header">
                <h1>📝 Créer un Sujet</h1>
            </div>
            
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?php echo $erreur; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="sujetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="card">
                    <div class="card-header"><h3>Configuration du sujet</h3></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Niveau *</label>
                                <select name="niveau_id" class="form-control" required>
                                    <option value="">Sélectionner</option>
                                    <?php foreach ($niveaux as $niveau): ?>
                                        <option value="<?php echo $niveau['id']; ?>">
                                            <?php echo $niveau['nom'] . ' (' . $niveau['type'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Matière *</label>
                                <select name="matiere_id" class="form-control" required>
                                    <option value="">Sélectionner</option>
                                    <?php foreach ($matieres as $matiere): ?>
                                        <option value="<?php echo $matiere['id']; ?>">
                                            <?php echo $matiere['nom'] . ' (Coef: ' . $matiere['coefficient'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Type d'évaluation *</label>
                                <select name="type_evaluation" class="form-control" required>
                                    <option value="controle">Contrôle Continu / Devoir</option>
                                    <option value="examen">Examen</option>
                                    <option value="rattrapage">Rattrapage</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Type de questions *</label>
                                <select name="type_questions" class="form-control" id="typeQuestions" required>
                                    <option value="question">Questions ouvertes</option>
                                    <option value="quiz">Quiz (QCM)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Durée (minutes) *</label>
                            <input type="number" name="duree_minutes" class="form-control" 
                                   value="60" min="5" max="480" required>
                        </div>
                    </div>
                </div>
                
                <!-- Questions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Questions (max 20)</h3>
                        <button type="button" class="btn btn-primary btn-sm" onclick="ajouterQuestion()">+ Ajouter</button>
                    </div>
                    <div class="card-body" id="questionsContainer">
                        <!-- Question 1 par défaut -->
                        <div class="question-item" data-index="0" style="border:1px solid #ddd;padding:15px;margin-bottom:15px;border-radius:8px;">
                            <h4>Question <span class="q-numero">1</span></h4>
                            <div class="form-group">
                                <label>Énoncé</label>
                                <textarea name="questions[]" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Points</label>
                                    <input type="number" name="points[]" class="form-control" 
                                           value="1" min="0.5" max="20" step="0.5" required>
                                </div>
                                <div class="form-group reponse-correcte-group" style="display:none;">
                                    <label>Réponse correcte (pour quiz)</label>
                                    <input type="text" name="reponses_correctes[]" class="form-control">
                                </div>
                            </div>
                            <div class="options-container" style="display:none;">
                                <label>Options (A-D)</label>
                                <div class="options-list">
                                    <?php foreach (range(0, 3) as $i): ?>
                                        <input type="text" name="options[0][]" class="form-control" 
                                               placeholder="Option <?php echo chr(65+$i); ?>" style="margin-bottom:5px;">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="supprimerQuestion(this)" 
                                    style="display:none;">Supprimer</button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg w-100">💾 Enregistrer le Sujet</button>
            </form>
        </main>
    </div>
    
    <script>
        let questionCount = 1;
        
        function ajouterQuestion() {
            if (questionCount >= 20) {
                alert('Maximum 20 questions autorisées.');
                return;
            }
            
            questionCount++;
            const container = document.getElementById('questionsContainer');
            const template = container.querySelector('.question-item').cloneNode(true);
            
            template.dataset.index = questionCount - 1;
            template.querySelector('.q-numero').textContent = questionCount;
            template.querySelector('textarea').value = '';
            template.querySelector('input[name="points[]"]').value = '1';
            
            // Mettre à jour les noms des inputs options
            const optionsInputs = template.querySelectorAll('.options-container input');
            optionsInputs.forEach(input => {
                input.name = 'options[' + (questionCount - 1) + '][]';
                input.value = '';
            });
            
            // Afficher bouton supprimer
            template.querySelector('.btn-danger').style.display = 'inline-block';
            
            container.appendChild(template);
            updateQuestionType();
        }
        
        function supprimerQuestion(btn) {
            const container = document.getElementById('questionsContainer');
            if (container.children.length > 1) {
                btn.closest('.question-item').remove();
                questionCount--;
                renumeroterQuestions();
            }
        }
        
        function renumeroterQuestions() {
            const items = document.querySelectorAll('.question-item');
            items.forEach((item, index) => {
                item.dataset.index = index;
                item.querySelector('.q-numero').textContent = index + 1;
            });
            questionCount = items.length;
        }
        
        document.getElementById('typeQuestions').addEventListener('change', updateQuestionType);
        
        function updateQuestionType() {
            const type = document.getElementById('typeQuestions').value;
            const isQuiz = type === 'quiz';
            
            document.querySelectorAll('.reponse-correcte-group').forEach(el => {
                el.style.display = isQuiz ? 'block' : 'none';
            });
            
            document.querySelectorAll('.options-container').forEach(el => {
                el.style.display = isQuiz ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>