DROP DATABASE IF EXISTS Kaay_Dem;
CREATE DATABASE Kaay_Dem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Kaay_Dem;

-- ===========================
-- UTILISATEURS
-- ===========================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===========================
-- ADMINISTRATEURS
-- ===========================
CREATE TABLE administrateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,
    FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
);

-- ===========================
-- PROFIL CONDUCTEUR
-- ===========================
CREATE TABLE profil_conducteur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,
    numero_permis VARCHAR(100) NOT NULL,
    statut ENUM('EN_ATTENTE','VALIDE','REFUSE') DEFAULT 'EN_ATTENTE',

    FOREIGN KEY(utilisateur_id)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
);

-- ===========================
-- PROFIL PASSAGER
-- ===========================
CREATE TABLE profil_passager (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,

    FOREIGN KEY(utilisateur_id)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
);

-- ===========================
-- VEHICULES
-- ===========================
CREATE TABLE vehicules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conducteur_id INT NOT NULL,

    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(50) UNIQUE NOT NULL,
    nombre_places INT NOT NULL,

    FOREIGN KEY(conducteur_id)
        REFERENCES profil_conducteur(id)
        ON DELETE CASCADE
);

-- ===========================
-- TRAJETS
-- ===========================
CREATE TABLE trajets (
    id INT AUTO_INCREMENT PRIMARY KEY,

    conducteur_id INT NOT NULL,
    vehicule_id INT NOT NULL,

    ville_depart VARCHAR(100) NOT NULL,
    ville_arrivee VARCHAR(100) NOT NULL,

    date_depart DATETIME NOT NULL,

    prix DECIMAL(10,2) NOT NULL,

    places_disponibles INT NOT NULL,

    FOREIGN KEY(conducteur_id)
        REFERENCES profil_conducteur(id),

    FOREIGN KEY(vehicule_id)
        REFERENCES vehicules(id)
);

-- ===========================
-- RESERVATIONS
-- ===========================
CREATE TABLE reservations (

    id INT AUTO_INCREMENT PRIMARY KEY,

    trajet_id INT NOT NULL,

    passager_id INT NOT NULL,

    statut ENUM('EN_ATTENTE','CONFIRMEE','ANNULEE','TERMINEE')
    DEFAULT 'EN_ATTENTE',

    FOREIGN KEY(trajet_id)
        REFERENCES trajets(id)
        ON DELETE CASCADE,

    FOREIGN KEY(passager_id)
        REFERENCES profil_passager(id)
        ON DELETE CASCADE
);

-- ===========================
-- EVALUATIONS
-- ===========================
CREATE TABLE evaluations (

    id INT AUTO_INCREMENT PRIMARY KEY,

    reservation_id INT UNIQUE,

    note INT NOT NULL,

    commentaire TEXT,

    FOREIGN KEY(reservation_id)
        REFERENCES reservations(id)
        ON DELETE CASCADE
);

-- ===========================
-- SIGNALEMENTS
-- ===========================
CREATE TABLE signalements (

    id INT AUTO_INCREMENT PRIMARY KEY,

    rapporteur_id INT NOT NULL,

    signale_id INT NOT NULL,

    motif VARCHAR(255) NOT NULL,

    description TEXT,

    FOREIGN KEY(rapporteur_id)
        REFERENCES utilisateurs(id),

    FOREIGN KEY(signale_id)
        REFERENCES utilisateurs(id)
);