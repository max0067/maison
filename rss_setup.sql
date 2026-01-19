-- Structure de base de données pour l'application RSS Reader
-- Créée pour être simple et efficace

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des dossiers (organisés par utilisateur)
CREATE TABLE IF NOT EXISTS folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT '📁',
    color VARCHAR(7) DEFAULT '#3498db',
    ordre INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des flux RSS
CREATE TABLE IF NOT EXISTS rss_feeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    refresh_interval INT DEFAULT 3600,
    last_fetch TIMESTAMP NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table de liaison flux-dossiers
CREATE TABLE IF NOT EXISTS folder_feeds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder_id INT NOT NULL,
    feed_id INT NOT NULL,
    ordre INT DEFAULT 0,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE,
    FOREIGN KEY (feed_id) REFERENCES rss_feeds(id) ON DELETE CASCADE,
    UNIQUE KEY unique_folder_feed (folder_id, feed_id)
);

-- Table des articles RSS
CREATE TABLE IF NOT EXISTS rss_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feed_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    link VARCHAR(1000) NOT NULL,
    description TEXT,
    content TEXT,
    author VARCHAR(255),
    pub_date TIMESTAMP NULL,
    guid VARCHAR(500),
    image_url VARCHAR(1000),
    is_read TINYINT(1) DEFAULT 0,
    is_favorite TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (feed_id) REFERENCES rss_feeds(id) ON DELETE CASCADE,
    INDEX idx_pub_date (pub_date),
    INDEX idx_feed_date (feed_id, pub_date)
);

-- Table des lectures par utilisateur
CREATE TABLE IF NOT EXISTS user_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES rss_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_item (user_id, item_id)
);

-- Table des favoris par utilisateur
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES rss_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_favorite (user_id, item_id)
);

-- Création d'un admin par défaut (mot de passe: admin123)
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@rss-reader.local', 'admin');

-- Quelques flux RSS par défaut
INSERT INTO rss_feeds (title, url, description) VALUES
('Le Monde - Actualités', 'https://www.lemonde.fr/rss/une.xml', 'Les dernières actualités du journal Le Monde'),
('TechCrunch', 'https://techcrunch.com/feed/', 'Actualités technologiques'),
('BBC News', 'https://feeds.bbci.co.uk/news/rss.xml', 'Actualités internationales BBC');

-- Créer un dossier par défaut pour l'admin
INSERT INTO folders (user_id, name, icon, color) VALUES
(1, 'Actualités', '📰', '#e74c3c'),
(1, 'Technologie', '💻', '#3498db'),
(1, 'Personnel', '⭐', '#f39c12');
