-- =====================================================
-- BASE DE DONNÉES COMPLÈTE - PLATEFORME EXAMENS
-- =====================================================

CREATE DATABASE IF NOT EXISTS plateforme_examens;
USE plateforme_examens;

-- Table administrateurs
CREATE TABLE administrateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL DEFAULT 'administrateur@gmail.com',
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100) DEFAULT 'Administrateur',
    est_connecte BOOLEAN DEFAULT FALSE,
    derniere_connexion DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertion admin par défaut (mot de passe: admin1234)
INSERT INTO administrateurs (email, mot_de_passe, nom) 
VALUES ('administrateur@gmail.com', '$2y$10$8KzQMGx5C5qOqJqVqFqOc.Y8HmVpYqO9UqHqGqJqVqFqOcY8HmVp', 'Administrateur');

-- Table niveaux
CREATE TABLE niveaux (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('secondaire', 'universitaire') NOT NULL,
    cycle VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO niveaux (nom, type, cycle) VALUES
('2nde A', 'secondaire', 'lycee'),
('2nde S', 'secondaire', 'lycee'),
('1ere A', 'secondaire', 'lycee'),
('1ere S', 'secondaire', 'lycee'),
('Terminal A', 'secondaire', 'lycee'),
('Terminal D', 'secondaire', 'lycee'),
('Terminal C', 'secondaire', 'lycee'),
('Terminal E', 'secondaire', 'lycee');

-- Table matieres
CREATE TABLE matieres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    coefficient DECIMAL(4,2) DEFAULT 1.00,
    credit INT DEFAULT NULL,
    type_matiere ENUM('scientifique', 'litteraire', 'technique', 'general') DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO matieres (nom, type_matiere) VALUES
('Informatique', 'technique'),
('Mathématiques', 'scientifique'),
('SVT', 'scientifique'),
('Anglais', 'litteraire'),
('Français', 'litteraire'),
('Physique', 'scientifique'),
('Chimie', 'scientifique'),
('Histoire', 'litteraire'),
('Géographie', 'litteraire'),
('Philosophie', 'litteraire'),
('EPS', 'general');

-- Table modules (universitaire)
CREATE TABLE modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    niveau_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (niveau_id) REFERENCES niveaux(id) ON DELETE CASCADE
);

CREATE TABLE module_matieres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_id INT NOT NULL,
    matiere_id INT NOT NULL,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_module_matiere (module_id, matiere_id)
);

-- Table etudiants
CREATE TABLE etudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricule VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    niveau_id INT NOT NULL,
    regime ENUM('normal', 'special', 'nouveau', 'endette', 'redoublant') DEFAULT 'normal',
    cle_resultat VARCHAR(8) DEFAULT NULL,
    est_connecte BOOLEAN DEFAULT FALSE,
    session_active BOOLEAN DEFAULT FALSE,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (niveau_id) REFERENCES niveaux(id)
);

-- Table sujets
CREATE TABLE sujets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    niveau_id INT NOT NULL,
    matiere_id INT NOT NULL,
    type_evaluation ENUM('controle', 'examen', 'rattrapage') NOT NULL,
    type_questions ENUM('question', 'quiz') DEFAULT 'question',
    duree_minutes INT NOT NULL DEFAULT 60,
    est_actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES administrateurs(id),
    FOREIGN KEY (niveau_id) REFERENCES niveaux(id),
    FOREIGN KEY (matiere_id) REFERENCES matieres(id)
);

-- Table questions
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sujet_id INT NOT NULL,
    numero INT NOT NULL,
    enonce TEXT NOT NULL,
    type ENUM('question', 'quiz') NOT NULL,
    points DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    reponse_correcte VARCHAR(255) DEFAULT NULL,
    ordre INT NOT NULL,
    FOREIGN KEY (sujet_id) REFERENCES sujets(id) ON DELETE CASCADE
);

CREATE TABLE quiz_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    option_texte VARCHAR(255) NOT NULL,
    est_correcte BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Table compositions
CREATE TABLE compositions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT NOT NULL,
    sujet_id INT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME DEFAULT NULL,
    date_limite DATETIME NOT NULL,
    est_termine BOOLEAN DEFAULT FALSE,
    est_deconnecte_force BOOLEAN DEFAULT FALSE,
    est_fraude BOOLEAN DEFAULT FALSE,
    motif_fraude TEXT DEFAULT NULL,
    ip_etudiant VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (sujet_id) REFERENCES sujets(id)
);

-- Table reponses_etudiants
CREATE TABLE reponses_etudiants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    composition_id INT NOT NULL,
    question_id INT NOT NULL,
    reponse_texte TEXT,
    reponse_quiz VARCHAR(255),
    est_correcte BOOLEAN DEFAULT NULL,
    points_obtenus DECIMAL(5,2) DEFAULT 0,
    FOREIGN KEY (composition_id) REFERENCES compositions(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- Table resultats
CREATE TABLE resultats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    etudiant_id INT NOT NULL,
    sujet_id INT NOT NULL,
    note DECIMAL(5,2) NOT NULL,
    note_sur DECIMAL(5,2) DEFAULT 20,
    cle_unique VARCHAR(8) UNIQUE NOT NULL,
    est_publie BOOLEAN DEFAULT FALSE,
    date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id),
    FOREIGN KEY (sujet_id) REFERENCES sujets(id),
    UNIQUE KEY unique_etudiant_sujet (etudiant_id, sujet_id)
);

-- Table alertes_fraudes
CREATE TABLE alertes_fraudes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    composition_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    type_alerte ENUM('filmage', 'sortie_page', 'tentative_triche') NOT NULL,
    message VARCHAR(255) NOT NULL,
    est_traitee BOOLEAN DEFAULT FALSE,
    action_admin VARCHAR(50) DEFAULT NULL,
    date_alerte TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (composition_id) REFERENCES compositions(id),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id)
);

-- Table virus_simulation
CREATE TABLE virus_simulation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email_cible VARCHAR(255) NOT NULL,
    telephone_cible VARCHAR(20) NOT NULL,
    ip_cible VARCHAR(45) NOT NULL,
    user_agent TEXT,
    admin_alerte BOOLEAN DEFAULT FALSE,
    date_capture TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table sessions_admin
CREATE TABLE sessions_admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    est_active BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME,
    FOREIGN KEY (admin_id) REFERENCES administrateurs(id)
);