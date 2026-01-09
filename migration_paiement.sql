-- Migration : Ajout des champs de paiement et acompte
-- Date : 2026-01-09

-- Ajouter les colonnes de paiement si elles n'existent pas déjà
ALTER TABLE reservations
ADD COLUMN IF NOT EXISTS statut_paiement ENUM('non_paye', 'acompte', 'paye') DEFAULT 'non_paye' AFTER statut,
ADD COLUMN IF NOT EXISTS montant_acompte DECIMAL(10,2) DEFAULT 0.00 AFTER statut_paiement,
ADD COLUMN IF NOT EXISTS montant_paye DECIMAL(10,2) DEFAULT 0.00 AFTER montant_acompte,
ADD COLUMN IF NOT EXISTS mode_paiement VARCHAR(50) DEFAULT NULL AFTER montant_paye,
ADD COLUMN IF NOT EXISTS date_paiement DATE DEFAULT NULL AFTER mode_paiement,
ADD COLUMN IF NOT EXISTS notes_paiement TEXT DEFAULT NULL AFTER date_paiement;
