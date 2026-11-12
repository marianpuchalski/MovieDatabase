-- ============================================================================
-- Projekt: Baza filmów
-- Plik: sql/schema.sql
-- Silnik: MySQL 8.0+ / MariaDB 10.2+ (XAMPP)
-- Kodowanie: utf8mb4 / utf8mb4_unicode_ci
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `baza_filmow`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `baza_filmow`;

SET NAMES utf8mb4;

-- Usunięcie tabel w kolejności odwrotnej do zależności kluczy obcych
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `movie_cast`;
DROP TABLE IF EXISTS `movie_genres`;
DROP TABLE IF EXISTS `actors`;
DROP TABLE IF EXISTS `genres`;
DROP TABLE IF EXISTS `movies`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------------------------
-- Tabela: users (użytkownicy i administratorzy)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_username` (`username`),
    UNIQUE KEY `uq_users_email` (`email`),
    CONSTRAINT `chk_users_is_blocked` CHECK (`is_blocked` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabela: movies (katalog filmów)
-- ----------------------------------------------------------------------------
CREATE TABLE `movies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `release_year` SMALLINT UNSIGNED NOT NULL,
    `director` VARCHAR(150) NOT NULL,
    `duration_minutes` SMALLINT UNSIGNED NOT NULL,
    `description` TEXT NOT NULL,
    `poster_url` VARCHAR(500) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_movies_title` (`title`),
    INDEX `idx_movies_release_year` (`release_year`),
    INDEX `idx_movies_created_at` (`created_at`),
    CONSTRAINT `chk_movies_release_year` CHECK (`release_year` BETWEEN 1888 AND 2100),
    CONSTRAINT `chk_movies_duration` CHECK (`duration_minutes` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabela: genres (słownik gatunków filmowych)
-- ----------------------------------------------------------------------------
CREATE TABLE `genres` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_genres_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabela: movie_genres (relacja N:M między filmami a gatunkami)
-- ----------------------------------------------------------------------------
CREATE TABLE `movie_genres` (
    `movie_id` INT UNSIGNED NOT NULL,
    `genre_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`movie_id`, `genre_id`),
    INDEX `idx_movie_genres_genre_id` (`genre_id`),
    CONSTRAINT `fk_movie_genres_movie`
        FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_movie_genres_genre`
        FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabela: actors (katalog aktorów)
-- ----------------------------------------------------------------------------
CREATE TABLE `actors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `birth_date` DATE NULL DEFAULT NULL,
    `country` VARCHAR(100) NULL DEFAULT NULL,
    `biography` TEXT NULL DEFAULT NULL,
    `photo_url` VARCHAR(500) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_actors_last_name` (`last_name`),
    INDEX `idx_actors_name` (`last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabela: movie_cast (relacja N:M między filmami a aktorami z nazwą postaci)
-- ----------------------------------------------------------------------------
CREATE TABLE `movie_cast` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movie_id` INT UNSIGNED NOT NULL,
    `actor_id` INT UNSIGNED NOT NULL,
    `character_name` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_movie_cast_role` (`movie_id`, `actor_id`, `character_name`),
    INDEX `idx_movie_cast_movie_id` (`movie_id`),
    INDEX `idx_movie_cast_actor_id` (`actor_id`),
    CONSTRAINT `fk_movie_cast_movie`
        FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_movie_cast_actor`
        FOREIGN KEY (`actor_id`) REFERENCES `actors` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabela: reviews (oceny 1-5 i opcjonalne recenzje użytkowników)
-- ----------------------------------------------------------------------------
CREATE TABLE `reviews` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `movie_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL,
    `comment` TEXT NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_reviews_movie_user` (`movie_id`, `user_id`),
    INDEX `idx_reviews_movie_id` (`movie_id`),
    INDEX `idx_reviews_user_id` (`user_id`),
    CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5),
    CONSTRAINT `fk_reviews_movie`
        FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;