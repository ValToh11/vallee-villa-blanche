-- =========================================================
-- Vallée Villa Blanche — schéma de la base de données
-- =========================================================

CREATE DATABASE IF NOT EXISTS vallee_vb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE vallee_vb;

-- --- Table des villas ---
CREATE TABLE villas (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nom           VARCHAR(150) NOT NULL,
  description   TEXT,
  localisation  VARCHAR(120),
  capacite      INT,
  couchage      VARCHAR(255),
  prix_nuit     DECIMAL(8,2),                 -- tarif public ; NULL pour les villas privées
  image         VARCHAR(255),
  statut        ENUM('publie','prive') NOT NULL DEFAULT 'prive',
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- --- Table des clients ---
CREATE TABLE clients (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  prenom        VARCHAR(80) NOT NULL,
  nom           VARCHAR(80) NOT NULL,
  email         VARCHAR(180) NOT NULL UNIQUE,
  mot_de_passe  VARCHAR(255) NOT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --- Villas de départ (les 4 lieux de la famille) ---
INSERT INTO villas (nom, description, localisation, capacite, couchage, prix_nuit, statut) VALUES
('Chamrousse — résidence Montagne, Alpes & Golf',
 'Résidence à la montagne, proche des pistes et d''un golf.',
 'Alpes', 4, 'studio + 1 petite chambre (lit superposé)', 90.00, 'publie'),

('La Palmyre — résidence Océan & Golf',
 'Résidence en bord d''océan avec grande terrasse.',
 'Océan', 6, '2 chambres + grande terrasse', NULL, 'prive'),

('Autun — résidence Bourgogne-Morvan & Golf',
 'Résidence au cœur de la Bourgogne, proche du Morvan.',
 'Bourgogne-Morvan', 4, '1 chambre + 1 canapé-lit', NULL, 'prive'),

('Vittel — résidence Thermes & Golf',
 'Grande résidence avec salle de réception et piscine.',
 'Vittel', 8, 'salle de réception, piscine · 3 chambres + canapé-lit', NULL, 'prive');
