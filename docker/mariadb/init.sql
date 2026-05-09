-- Script d'initialisation MariaDB
-- Exécuté automatiquement au premier démarrage du conteneur

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- S'assurer que la base utilise le bon charset
ALTER DATABASE aksam_assurance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
