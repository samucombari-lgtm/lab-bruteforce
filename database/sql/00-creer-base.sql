-- =====================================================================
--  Création de la base et de l'utilisateur MariaDB de la cible
-- =====================================================================
--  À exécuter UNE SEULE FOIS, en tant qu'administrateur MariaDB :
--
--      sudo mariadb < database/sql/00-creer-base.sql
--
--  Ces identifiants doivent correspondre à ceux du fichier .env
--  (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS lab_bruteforce
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'lab_user'@'localhost'
    IDENTIFIED BY 'LabBrute2026!';

GRANT ALL PRIVILEGES ON lab_bruteforce.* TO 'lab_user'@'localhost';

FLUSH PRIVILEGES;
