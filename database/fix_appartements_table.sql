-- Script de correction de la table appartements
-- Supprime la colonne en double et nettoie les contraintes

-- Désactiver temporairement les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Vérifier s'il y a des données dans la colonne proprietaire_id
SELECT 'Vérification des données dans proprietaire_id' AS etape;
SELECT COUNT(*) AS nb_appartements_avec_proprietaire_id 
FROM appartements 
WHERE proprietaire_id IS NOT NULL;

-- 2. Si des données existent dans proprietaire_id mais pas dans id_proprietaire, les copier
UPDATE appartements 
SET id_proprietaire = proprietaire_id 
WHERE proprietaire_id IS NOT NULL 
AND id_proprietaire IS NULL;

-- 3. Supprimer la contrainte de clé étrangère sur proprietaire_id
ALTER TABLE appartements 
DROP FOREIGN KEY appartements_ibfk_1;

-- 4. Supprimer la colonne proprietaire_id
ALTER TABLE appartements 
DROP COLUMN proprietaire_id;

-- 5. Supprimer l'ancienne contrainte de clé étrangère sur id_proprietaire
ALTER TABLE appartements 
DROP FOREIGN KEY fk_proprietaire;

-- 6. Recréer la contrainte de clé étrangère avec ON DELETE SET NULL
ALTER TABLE appartements 
ADD CONSTRAINT fk_appartement_proprietaire 
FOREIGN KEY (id_proprietaire) 
REFERENCES proprietaires(id) 
ON DELETE SET NULL;

-- 7. Réactiver les vérifications de clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- 8. Vérifier la structure finale
SHOW CREATE TABLE appartements;
