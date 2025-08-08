-- Vérification de la structure de la table utilisateurs
DESCRIBE utilisateurs;

-- Vérification des valeurs uniques dans la colonne sexe
SELECT sexe, COUNT(*) as count 
FROM utilisateurs 
GROUP BY sexe;

-- Afficher quelques exemples d'utilisateurs
SELECT id, nom, prenom, sexe, role 
FROM utilisateurs 
LIMIT 5;

-- Vérifier les contraintes de la table
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'utilisateurs';

-- Vérifier les valeurs NULL dans la colonne sexe
SELECT id, nom, prenom, sexe, role 
FROM utilisateurs 
WHERE sexe IS NULL OR sexe = '';

-- Compter le nombre total d'utilisateurs
SELECT COUNT(*) as total_utilisateurs 
FROM utilisateurs;

-- Vérifier les rôles des utilisateurs
SELECT role, COUNT(*) as count 
FROM utilisateurs 
GROUP BY role;
