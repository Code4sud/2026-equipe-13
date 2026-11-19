-- FindMyWay2Work — schéma de base de données
-- À importer dans phpMyAdmin avant la première visite du site.
-- Les métiers sont ensuite générés automatiquement par l'IA (table vide).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `findmyway2work`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `findmyway2work`;

-- Comptes utilisateurs
CREATE TABLE `utilisateurs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prenom` VARCHAR(80) NOT NULL,
  `nom` VARCHAR(80) NOT NULL,
  `age` TINYINT UNSIGNED NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `sociabilite` ENUM('timide', 'normal', 'avenant') NOT NULL DEFAULT 'normal',
  `attente` ENUM('financier', 'entre_deux', 'passion') NOT NULL DEFAULT 'entre_deux',
  `cree_le` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_utilisateurs_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Centres d'intérêt choisis par l'utilisateur (clés de la liste fixe du code)
CREATE TABLE `utilisateur_interets` (
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `interet` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`utilisateur_id`, `interet`),
  CONSTRAINT `fk_utilisateur_interets_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Les 15 métiers (générés une seule fois par l'IA)
CREATE TABLE `metiers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_fr` VARCHAR(120) NOT NULL,
  `nom_en` VARCHAR(120) NOT NULL,
  `corps_fr` VARCHAR(120) NOT NULL,
  `corps_en` VARCHAR(120) NOT NULL,
  `description_fr` TEXT NOT NULL,
  `description_en` TEXT NOT NULL,
  `revenu` ENUM('faible', 'moyen', 'eleve') NOT NULL,
  `contact_social` ENUM('faible', 'moyen', 'eleve') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_metiers_nom_fr` (`nom_fr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Centres d'intérêt associés à chaque métier (1 à 3)
CREATE TABLE `metier_interets` (
  `metier_id` INT UNSIGNED NOT NULL,
  `interet` VARCHAR(32) NOT NULL,
  PRIMARY KEY (`metier_id`, `interet`),
  CONSTRAINT `fk_metier_interets_metier`
    FOREIGN KEY (`metier_id`) REFERENCES `metiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partie en cours (une seule à la fois par utilisateur, supprimée à la fin ou à l'abandon)
CREATE TABLE `parties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `metier_id` INT UNSIGNED NOT NULL,
  `note_reussite` DECIMAL(4,1) DEFAULT NULL,
  `creee_le` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parties_utilisateur` (`utilisateur_id`),
  KEY `idx_parties_metier` (`metier_id`),
  CONSTRAINT `fk_parties_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parties_metier`
    FOREIGN KEY (`metier_id`) REFERENCES `metiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications d'une partie : textes bilingues, ordre attendu et ordre d'affichage mélangé
CREATE TABLE `partie_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `partie_id` INT UNSIGNED NOT NULL,
  `source_fr` VARCHAR(140) NOT NULL,
  `source_en` VARCHAR(140) NOT NULL,
  `texte_fr` TEXT NOT NULL,
  `texte_en` TEXT NOT NULL,
  `position_attendue` TINYINT UNSIGNED NOT NULL,
  `ordre_affichage` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partie_notifications_partie` (`partie_id`),
  CONSTRAINT `fk_partie_notifications_partie`
    FOREIGN KEY (`partie_id`) REFERENCES `parties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes finales par métier joué (une ligne par utilisateur et par métier, mise à jour à chaque partie terminée)
CREATE TABLE `resultats` (
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `metier_id` INT UNSIGNED NOT NULL,
  `note_reussite` DECIMAL(4,1) NOT NULL,
  `note_appreciation` DECIMAL(4,1) NOT NULL,
  `mis_a_jour_le` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`utilisateur_id`, `metier_id`),
  CONSTRAINT `fk_resultats_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resultats_metier`
    FOREIGN KEY (`metier_id`) REFERENCES `metiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
