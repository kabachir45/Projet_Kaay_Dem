DROP DATABASE IF EXISTS Kaay_Dem;
CREATE DATABASE Kaay_Dem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Kaay_Dem;

CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    photo VARCHAR(255),
    est_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE profil_conducteur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,
    numero_permis VARCHAR(100) NOT NULL,
    statut ENUM("EN_ATTENTE","VALIDE","REFUSE") DEFAULT "EN_ATTENTE",
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE profil_passager (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

CREATE TABLE vehicules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conducteur_id INT NOT NULL,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    immatriculation VARCHAR(50) UNIQUE NOT NULL,
    nombre_places INT NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY(conducteur_id) REFERENCES profil_conducteur(id) ON DELETE CASCADE
);

CREATE TABLE trajets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conducteur_id INT NOT NULL,
    vehicule_id INT,
    ville_depart VARCHAR(100) NOT NULL,
    ville_arrivee VARCHAR(100) NOT NULL,
    points_arret VARCHAR(255) DEFAULT NULL,
    date_depart DATETIME NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    places_disponibles INT NOT NULL,
    description TEXT DEFAULT NULL,
    annule TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(conducteur_id) REFERENCES profil_conducteur(id),
    FOREIGN KEY(vehicule_id) REFERENCES vehicules(id)
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trajet_id INT NOT NULL,
    passager_id INT NOT NULL,
    statut ENUM("EN_ATTENTE","CONFIRMEE","ANNULEE","TERMINEE") DEFAULT "EN_ATTENTE",
    paiement_confirme TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(trajet_id) REFERENCES trajets(id) ON DELETE CASCADE,
    FOREIGN KEY(passager_id) REFERENCES profil_passager(id) ON DELETE CASCADE
);

CREATE TABLE evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNIQUE NOT NULL,
    note INT NOT NULL CHECK(note BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

CREATE TABLE signalements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rapporteur_id INT NOT NULL,
    signale_id INT NOT NULL,
    motif VARCHAR(255) NOT NULL,
    description TEXT,
    traite TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(rapporteur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY(signale_id) REFERENCES utilisateurs(id)
);

-- ===========================
-- DONNEES DE DEMONSTRATION
-- ===========================

-- id=1 : Admin
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, est_admin)
VALUES ("Admin", "Kaay Dem", "admin@kaaydem.sn", "$2b$10$x0j0lqdO7N4OBLg90JAT7uvrcUzpZP5pvUzmDRefD2Kc.m8TvStsW", "+221 77 000 00 00", 1);

-- id=2 : Conducteur Mamadou
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone)
VALUES ("SY", "Mamadou", "mamadou@test.sn", "$2b$10$FYJ.yT2RKH8YbBAz57AoaO2RlFZao87A2ctJDwS8P1Jg0tNE5q0Y.", "+221 77 111 11 11");

-- id=3 : Conductrice Fatoumata
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone)
VALUES ("OUEDRAOGO", "Fatoumata", "fatoumata@test.sn", "$2b$10$FYJ.yT2RKH8YbBAz57AoaO2RlFZao87A2ctJDwS8P1Jg0tNE5q0Y.", "+221 77 222 22 22");

-- id=4 : Passager Bachir
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone)
VALUES ("KA", "Bachir", "bachir@test.sn", "$2b$10$FYJ.yT2RKH8YbBAz57AoaO2RlFZao87A2ctJDwS8P1Jg0tNE5q0Y.", "+221 77 333 33 33");

-- Profils conducteur validés (Mamadou id=2, Fatoumata id=3)
INSERT INTO profil_conducteur (utilisateur_id, numero_permis, statut)
VALUES (2, "SN-2020-001234", "VALIDE"),
       (3, "SN-2019-005678", "VALIDE");

-- Profil passager (Bachir id=4 + Mamadou et Fatoumata peuvent aussi réserver)
INSERT INTO profil_passager (utilisateur_id) VALUES (4), (2), (3);

-- Véhicules (conducteur_id=1 = Mamadou, conducteur_id=2 = Fatoumata)
INSERT INTO vehicules (conducteur_id, marque, modele, immatriculation, nombre_places, actif)
VALUES (1, "Peugeot", "208",     "DK-1234-AB", 4, 1),
       (2, "Toyota",  "Corolla", "DK-5678-CD", 4, 1);

-- Trajets de démo
INSERT INTO trajets (conducteur_id, vehicule_id, ville_depart, ville_arrivee, date_depart, prix, places_disponibles, description)
VALUES
(1, 1, "Dakar Plateau", "Diamniadio",  DATE_ADD(NOW(), INTERVAL 1 DAY),  500.00, 3, "Départ depuis Place de l Indépendance. Ponctuel garanti."),
(1, 1, "Diamniadio",   "Dakar Plateau",DATE_ADD(NOW(), INTERVAL 1 DAY),  500.00, 2, "Retour après les cours, 17h30."),
(2, 2, "Rufisque",     "Diamniadio",   DATE_ADD(NOW(), INTERVAL 2 DAY),  300.00, 3, "Départ devant la mairie de Rufisque."),
(1, 1, "Dakar Plateau", "Thiès",       DATE_ADD(NOW(), INTERVAL 3 DAY), 1500.00, 2, "Trajet weekend, bagages acceptés.");

-- Réservation démo (passager_id=1 = Bachir dans profil_passager)
INSERT INTO reservations (trajet_id, passager_id, statut)
VALUES (1, 1, "EN_ATTENTE");
