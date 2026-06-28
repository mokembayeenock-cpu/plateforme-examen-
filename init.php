<?php
// =====================================================
// INITIALISATION BASE DE DONNÉES SQLite
// =====================================================

require_once __DIR__ . '/../includes/config.php';

try {
    // Création des tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS administrateurs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL DEFAULT 'administrateur@gmail.com',
            mot_de_passe TEXT NOT NULL,
            nom TEXT DEFAULT 'Administrateur',
            est_connecte INTEGER DEFAULT 0,
            derniere_connexion DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS niveaux (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT UNIQUE NOT NULL,
            type TEXT NOT NULL,
            cycle TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS matieres (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            coefficient REAL DEFAULT 1.0,
            credit INTEGER DEFAULT NULL,
            type_matiere TEXT DEFAULT 'general',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS modules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            niveau_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (niveau_id) REFERENCES niveaux(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS module_matieres (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            module_id INTEGER NOT NULL,
            matiere_id INTEGER NOT NULL,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
            UNIQUE(module_id, matiere_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS etudiants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            matricule TEXT UNIQUE NOT NULL,
            nom TEXT NOT NULL,
            prenom TEXT NOT NULL,
            date_naissance DATE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            telephone TEXT NOT NULL,
            mot_de_passe TEXT NOT NULL,
            photo_path TEXT DEFAULT NULL,
            niveau_id INTEGER NOT NULL,
            regime TEXT DEFAULT 'normal',
            cle_resultat TEXT DEFAULT NULL,
            est_connecte INTEGER DEFAULT 0,
            session_active INTEGER DEFAULT 0,
            date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (niveau_id) REFERENCES niveaux(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sujets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            niveau_id INTEGER NOT NULL,
            matiere_id INTEGER NOT NULL,
            type_evaluation TEXT NOT NULL,
            type_questions TEXT DEFAULT 'question',
            duree_minutes INTEGER NOT NULL DEFAULT 60,
            est_actif INTEGER DEFAULT 1,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES administrateurs(id),
            FOREIGN KEY (niveau_id) REFERENCES niveaux(id),
            FOREIGN KEY (matiere_id) REFERENCES matieres(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sujet_id INTEGER NOT NULL,
            numero INTEGER NOT NULL,
            enonce TEXT NOT NULL,
            type TEXT NOT NULL,
            points REAL NOT NULL DEFAULT 1.0,
            reponse_correcte TEXT DEFAULT NULL,
            ordre INTEGER NOT NULL,
            FOREIGN KEY (sujet_id) REFERENCES sujets(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_options (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question_id INTEGER NOT NULL,
            option_texte TEXT NOT NULL,
            est_correcte INTEGER DEFAULT 0,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS compositions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            etudiant_id INTEGER NOT NULL,
            sujet_id INTEGER NOT NULL,
            date_debut DATETIME NOT NULL,
            date_fin DATETIME DEFAULT NULL,
            date_limite DATETIME NOT NULL,
            est_termine INTEGER DEFAULT 0,
            est_deconnecte_force INTEGER DEFAULT 0,
            est_fraude INTEGER DEFAULT 0,
            motif_fraude TEXT DEFAULT NULL,
            ip_etudiant TEXT DEFAULT NULL,
            FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
            FOREIGN KEY (sujet_id) REFERENCES sujets(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reponses_etudiants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            composition_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            reponse_texte TEXT,
            reponse_quiz TEXT,
            est_correcte INTEGER DEFAULT NULL,
            points_obtenus REAL DEFAULT 0,
            FOREIGN KEY (composition_id) REFERENCES compositions(id),
            FOREIGN KEY (question_id) REFERENCES questions(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS resultats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            etudiant_id INTEGER NOT NULL,
            sujet_id INTEGER NOT NULL,
            note REAL NOT NULL,
            note_sur REAL DEFAULT 20,
            cle_unique TEXT UNIQUE NOT NULL,
            est_publie INTEGER DEFAULT 0,
            date_calcul DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
            FOREIGN KEY (sujet_id) REFERENCES sujets(id),
            UNIQUE(etudiant_id, sujet_id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alertes_fraudes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            composition_id INTEGER NOT NULL,
            etudiant_id INTEGER NOT NULL,
            type_alerte TEXT NOT NULL,
            message TEXT NOT NULL,
            est_traitee INTEGER DEFAULT 0,
            action_admin TEXT DEFAULT NULL,
            date_alerte DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (composition_id) REFERENCES compositions(id),
            FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions_admin (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            token TEXT UNIQUE NOT NULL,
            est_active INTEGER DEFAULT 1,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            date_expiration DATETIME,
            FOREIGN KEY (admin_id) REFERENCES administrateurs(id)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS annonces (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            message TEXT NOT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insérer les données par défaut
    $admin_password = password_hash('admin1234', PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO administrateurs (email, mot_de_passe, nom) VALUES (?, ?, ?)");
    $stmt->execute(['administrateur@gmail.com', $admin_password, 'Administrateur']);

    // Niveaux par défaut
    $niveaux = [
        ['2nde A', 'secondaire', 'lycee'],
        ['2nde S', 'secondaire', 'lycee'],
        ['1ere A', 'secondaire', 'lycee'],
        ['1ere S', 'secondaire', 'lycee'],
        ['Terminal A', 'secondaire', 'lycee'],
        ['Terminal D', 'secondaire', 'lycee'],
        ['Terminal C', 'secondaire', 'lycee'],
        ['Terminal E', 'secondaire', 'lycee'],
    ];
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO niveaux (nom, type, cycle) VALUES (?, ?, ?)");
    foreach ($niveaux as $niveau) {
        $stmt->execute($niveau);
    }

    // Matières par défaut
    $matieres = [
        'Informatique', 'Mathématiques', 'SVT', 'Anglais', 'Français',
        'Physique', 'Chimie', 'Histoire', 'Géographie', 'Philosophie', 'EPS'
    ];
    
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO matieres (nom) VALUES (?)");
    foreach ($matieres as $matiere) {
        $stmt->execute([$matiere]);
    }

    // Annonce par défaut
    $stmt = $pdo->prepare("INSERT INTO annonces (message) VALUES (?)");
    $stmt->execute(['Bienvenue sur la plateforme d\'examens en ligne.']);

    echo "<h2 style='color:green;text-align:center;'>✅ Base de données initialisée avec succès !</h2>";
    echo "<p style='text-align:center;'><a href='../index.php'>Accéder au site</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>❌ Erreur : " . $e->getMessage() . "</h2>";
}
?>