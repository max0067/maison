-- Setup MySQL pour SaaS Rédaction Immobilière
-- Base de données : u482325361_immo

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    hashed_password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    plan ENUM('free', 'pro') DEFAULT 'free',
    stripe_customer_id VARCHAR(255),
    stripe_subscription_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_stripe_customer (stripe_customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des annonces
CREATE TABLE IF NOT EXISTS annonces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    texte_original TEXT NOT NULL,
    texte_professionnel TEXT,
    texte_court TEXT,
    texte_premium TEXT,
    type_bien ENUM('appartement', 'maison', 'terrain') NOT NULL,
    type_transaction ENUM('vente', 'location') NOT NULL,
    gamme ENUM('standard', 'haut_de_gamme') NOT NULL,
    ton ENUM('professionnel', 'court', 'premium'),
    score_clarte FLOAT,
    score_attractivite FLOAT,
    score_lisibilite FLOAT,
    score_global FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de suivi d'utilisation mensuelle
CREATE TABLE IF NOT EXISTS usage_monthly (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    count INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, year, month),
    INDEX idx_user_month (user_id, year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Utilisateur admin de test (mot de passe: admin123)
INSERT INTO users (email, hashed_password, is_active, is_verified, plan)
VALUES ('admin@redacimmo.fr', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4/X4.5K5.5K5.5K', TRUE, TRUE, 'pro')
ON DUPLICATE KEY UPDATE email=email;
