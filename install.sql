-- ============================================================
-- SPORTIFY — Complete Install SQL
-- Import this file via phpMyAdmin or MySQL CLI
-- After import: login at /login.php with credentials below
--
-- Admin:    admin@gmail.com   / admin@123
-- Seller:   seller@demo.com   / seller123
-- Customer: customer@demo.com / customer123
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Target database is selected automatically by the cloud provider

-- ============================================================
-- Drop existing tables (safe re-import)
-- ============================================================
DROP TABLE IF EXISTS `revenue_log`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `tournament_photos`;
DROP TABLE IF EXISTS `tournaments`;
DROP TABLE IF EXISTS `venue_photos`;
DROP TABLE IF EXISTS `venues`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE `users` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150)   NOT NULL,
  `email`         VARCHAR(200)   NOT NULL,
  `password`      VARCHAR(255)   NOT NULL,
  `role`          ENUM('customer','seller','admin') NOT NULL DEFAULT 'customer',
  `status`        ENUM('active','suspended')        NOT NULL DEFAULT 'active',
  `phone`         VARCHAR(20)    DEFAULT NULL,
  `city`          VARCHAR(100)   DEFAULT NULL,
  `profile_pic`   VARCHAR(300)   DEFAULT NULL,
  `business_name` VARCHAR(200)   DEFAULT NULL,
  `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: venues
-- ============================================================
CREATE TABLE `venues` (
  `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `seller_id`             INT UNSIGNED    NOT NULL,
  `name`                  VARCHAR(200)    NOT NULL,
  `sport_type`            ENUM('Cricket','Football','Badminton','Basketball','Tennis','Swimming','Others') NOT NULL,
  `location`              VARCHAR(300)    NOT NULL,
  `city`                  VARCHAR(100)    DEFAULT NULL,
  `state`                 VARCHAR(100)    DEFAULT NULL,
  `pincode`               VARCHAR(20)     DEFAULT NULL,
  `description`           TEXT            DEFAULT NULL,
  `price_per_slot`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `slot_duration`         ENUM('30','60','120') NOT NULL DEFAULT '60',
  `operating_hours_start` TIME            NOT NULL DEFAULT '06:00:00',
  `operating_hours_end`   TIME            NOT NULL DEFAULT '22:00:00',
  `rating_avg`            DECIMAL(3,2)    DEFAULT 0.00,
  `is_active`             TINYINT(1)      NOT NULL DEFAULT 1,
  `is_deleted`            TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seller` (`seller_id`),
  KEY `idx_rating` (`rating_avg`),
  KEY `idx_sport`  (`sport_type`),
  KEY `idx_active` (`is_active`,`is_deleted`),
  CONSTRAINT `fk_venue_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: venue_photos
-- ============================================================
CREATE TABLE `venue_photos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `venue_id`   INT UNSIGNED NOT NULL,
  `photo_url`  VARCHAR(500) NOT NULL,
  `sort_order` INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_venue_photo` (`venue_id`),
  CONSTRAINT `fk_photo_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: tournaments
-- ============================================================
CREATE TABLE `tournaments` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seller_id`             INT UNSIGNED NOT NULL,
  `name`                  VARCHAR(200) NOT NULL,
  `sport_type`            ENUM('Cricket','Football','Badminton','Basketball','Tennis','Swimming','Others') NOT NULL,
  `location`              VARCHAR(300) NOT NULL,
  `city`                  VARCHAR(100) DEFAULT NULL,
  `state`                 VARCHAR(100) DEFAULT NULL,
  `pincode`               VARCHAR(20)  DEFAULT NULL,
  `description`           TEXT         DEFAULT NULL,
  `start_date`            DATE         NOT NULL,
  `end_date`              DATE         NOT NULL,
  `registration_deadline` DATE         DEFAULT NULL,
  `is_active`             TINYINT(1)   NOT NULL DEFAULT 1,
  `is_deleted`            TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_t_seller` (`seller_id`),
  KEY `idx_t_start`  (`start_date`),
  CONSTRAINT `fk_tournament_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: tournament_photos
-- ============================================================
CREATE TABLE `tournament_photos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tournament_id` INT UNSIGNED NOT NULL,
  `photo_url`     VARCHAR(500) NOT NULL,
  `sort_order`    INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_t_photo` (`tournament_id`),
  CONSTRAINT `fk_tphoto_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: bookings
-- ============================================================
CREATE TABLE `bookings` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`   VARCHAR(20)  NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `venue_id`    INT UNSIGNED NOT NULL,
  `slot_date`   DATE         NOT NULL,
  `slot_start`  TIME         NOT NULL,
  `slot_end`    TIME         NOT NULL,
  `status`      ENUM('confirmed','dismissed') NOT NULL DEFAULT 'confirmed',
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reference` (`reference`),
  UNIQUE KEY `uq_slot` (`venue_id`,`slot_date`,`slot_start`),
  KEY `idx_customer`  (`customer_id`),
  KEY `idx_slot_date` (`slot_date`),
  CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `users`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_venue`    FOREIGN KEY (`venue_id`)    REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: reviews
-- ============================================================
CREATE TABLE `reviews` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `venue_id`    INT UNSIGNED NOT NULL,
  `booking_id`  INT UNSIGNED NOT NULL,
  `rating`      TINYINT      NOT NULL,
  `comment`     VARCHAR(500) DEFAULT NULL,
  `is_deleted`  TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_booking` (`booking_id`),
  KEY `idx_venue_review` (`venue_id`),
  CONSTRAINT `fk_review_customer` FOREIGN KEY (`customer_id`) REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_venue`    FOREIGN KEY (`venue_id`)    REFERENCES `venues`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_booking`  FOREIGN KEY (`booking_id`)  REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: revenue_log
-- ============================================================
CREATE TABLE `revenue_log` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `seller_id`   INT UNSIGNED  NOT NULL,
  `booking_id`  INT UNSIGNED  NOT NULL,
  `amount`      DECIMAL(10,2) NOT NULL,
  `recorded_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rev_seller` (`seller_id`),
  CONSTRAINT `fk_rev_seller`  FOREIGN KEY (`seller_id`)  REFERENCES `users`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed: Users
-- Passwords (bcrypt): admin@123 | seller123 | customer123
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `phone`, `city`, `business_name`) VALUES
('Admin',                'admin@gmail.com',    '$2y$10$bcbQUmWnKWvgOxkjr/upQO.wsdNlhKZhAnD2whOhi.Y5k10l0f0MW', 'admin',    'active', NULL,         NULL,     NULL),
('Priya Sports Complex', 'seller@demo.com',    '$2y$10$t3z4Wl9d8Rf6h5.SE.JEdevEeKu0dQ8ERgPJVx4nON9pFqKfJC0dq', 'seller',   'active', '9876543210', 'Mumbai', 'Priya Multi-Sport Complex'),
('Arjun Sharma',         'customer@demo.com',  '$2y$10$rSsHge2HzsJQyWj1XW792eJmxeFEeUJUpsAStK.dl1tmNZNwxSgL6', 'customer', 'active', '9123456789', 'Mumbai', NULL);

-- ============================================================
-- Seed: Sample Venues (linked to seller id=2)
-- ============================================================
INSERT INTO `venues` (`seller_id`, `name`, `sport_type`, `location`, `description`, `price_per_slot`, `slot_duration`, `operating_hours_start`, `operating_hours_end`, `rating_avg`, `is_active`) VALUES
(2, 'Green Turf Cricket Ground',  'Cricket',    'Andheri West, Mumbai',  'Premium synthetic turf with floodlights. Ideal for tape-ball and leather cricket.',      800.00,  '60', '06:00:00', '22:00:00', 4.50, 1),
(2, 'Priya Football Arena',       'Football',   'Bandra East, Mumbai',   'Full-size football ground with artificial grass. Changing rooms available.',               1200.00, '60', '07:00:00', '21:00:00', 4.20, 1),
(2, 'Ace Badminton Court',        'Badminton',  'Santacruz, Mumbai',     '3 professional badminton courts with wooden flooring and proper lighting.',                  300.00,  '60', '05:30:00', '23:00:00', 3.80, 1),
(2, 'Slam Dunk Basketball Court', 'Basketball', 'Goregaon West, Mumbai', 'Full-size NBA standard court. Ideal for 5v5 matches and practice sessions.',                600.00,  '60', '06:00:00', '22:00:00', 4.70, 1),
(2, 'Tennis Hub Worli',           'Tennis',     'Worli, Mumbai',         'Synthetic clay court. Perfect for singles and doubles. Coaching available on request.',     500.00,  '60', '06:00:00', '20:00:00', 4.00, 1),
(2, 'AquaLife Swimming Pool',     'Swimming',   'Powai, Mumbai',         'Olympic-size pool with 8 lanes. Timing systems and trained lifeguards available.',          400.00,  '60', '05:00:00', '22:00:00', 4.60, 1);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ============================================================
-- DONE!  Visit /login.php to get started.
-- ============================================================
