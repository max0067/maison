-- Script de création de la base de données pour le système de gestion de gîte

-- Table des utilisateurs admin
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des chambres/locations
CREATE TABLE IF NOT EXISTS chambres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    capacite INT DEFAULT 2,
    photo VARCHAR(255),
    ordre INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    chambre_id INT NOT NULL,
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL,
    nombre_personnes INT DEFAULT 1,
    prix_total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'annulee') DEFAULT 'en_attente',
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chambre_id) REFERENCES chambres(id)
);

-- Table des contenus du site
CREATE TABLE IF NOT EXISTS contenus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(50) NOT NULL UNIQUE,
    valeur TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des contenus par défaut
INSERT INTO contenus (cle, valeur, type) VALUES
('site_titre', 'La Maison du Soleil', 'text'),
('site_sous_titre', 'Maison d\'hôtes de charme – Réservez votre séjour', 'text'),
('maison_titre', 'Notre Maison d\'Hôtes', 'text'),
('maison_description', 'Située au cœur de la nature, notre maison d\'hôtes vous accueille pour un séjour reposant dans un cadre authentique et chaleureux.', 'textarea'),
('chambres_titre', 'Nos Chambres', 'text'),
('reservation_titre', 'Réserver votre séjour', 'text'),
('footer_text', '© 2026 – La Maison du Soleil | Maison d\'Hôtes', 'text'),
('hero_image', 'hero.jpg', 'image');

-- Insertion de chambres par défaut
INSERT INTO chambres (nom, description, prix, capacite, ordre) VALUES
('Chambre Classique', 'Lit double – Salle de bain privée', 80.00, 2, 1),
('Chambre Confort', 'Lit queen size – Vue jardin', 110.00, 2, 2),
('Suite', 'Salon privé – Terrasse', 150.00, 4, 3);

-- Création d'un admin par défaut (mot de passe: admin123)
INSERT INTO admin_users (username, password, email) VALUES
('admin', '$2y$12$SG1MvsiWG9PfNcwr0vKuieCneVHrCW9KsagV1bLe098Y12OaMDWAm', 'admin@maison-soleil.com');
