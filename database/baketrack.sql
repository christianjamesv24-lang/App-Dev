-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 03, 2026 at 03:37 PM
-- Server version: 8.4.3
-- PHP Version: 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


--
-- Current schema (includes active-account tracking, order idempotency and indexes)
--


CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `idempotency_key` char(64) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `notes` text,
  `status` varchar(30) NOT NULL DEFAULT 'waiting',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  KEY `user_id` (`user_id`),
  KEY `status_order_date` (`status`,`order_date`,`id`),
  KEY `user_status_order_date` (`user_id`,`status`,`order_date`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int NOT NULL DEFAULT '0',
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'available',
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `order_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `darkmode` tinyint(1) NOT NULL DEFAULT '0',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `site_content` (
  `content_key` varchar(100) NOT NULL,
  `content_value` text NOT NULL,
  PRIMARY KEY (`content_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `type` varchar(30) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `receipt` text,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `transaction_date_id` (`transaction_date`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `password_reset_tokens` (
  `token_hash` char(64) NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`token_hash`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `php_sessions` (
  `id` varchar(128) NOT NULL,
  `data` mediumblob NOT NULL,
  `last_activity` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `birthdate` date DEFAULT NULL,
  `darkmode` tinyint(1) NOT NULL DEFAULT '0',
  `anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


--
-- Data
--


INSERT INTO `admins` (`id`, `user_id`, `username`, `email`, `password`, `is_active`, `created_at`) VALUES
(1, 3, 'admin', 'admin@baketrack.local', '$2y$10$UK6PvJ9iI3jwjgOYJavzeuk84a0Mbxy1BMXup.mgoUGZoIHRfTvtW', 1, '2026-09-06 09:58:00');

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 1, 'fghfgh', 1, '2026-09-06 11:09:02');

INSERT INTO `orders` (`id`, `user_id`, `total`, `payment`, `change_amount`, `notes`, `status`, `order_date`) VALUES
(1, 1, 30.00, NULL, NULL, NULL, 'completed', '2026-09-06 12:57:59'),
(2, 1, 30.00, NULL, NULL, NULL, 'completed', '2026-09-06 13:25:24'),
(3, 9, 60.00, NULL, NULL, NULL, 'ready', '2026-09-07 00:42:30'),
(4, 1, 60.00, NULL, NULL, NULL, 'waiting', '2026-10-01 00:35:56'),
(6, 6, 30.00, NULL, NULL, NULL, 'completed', '2026-10-31 11:11:41'),
(7, 5, 60.00, 80.00, 20.00, NULL, 'completed', '2026-10-31 11:12:18'),
(8, 5, 60.00, 80.00, 20.00, NULL, 'completed', '2026-10-31 11:12:22');

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 30.00),
(2, 2, 1, 1, 30.00),
(3, 3, 1, 2, 30.00),
(4, 4, 1, 1, 30.00),
(5, 4, 1, 1, 30.00),
(7, 6, 1, 1, 30.00),
(8, 7, 1, 2, 30.00),
(9, 8, 1, 2, 30.00);

INSERT INTO `products` (`id`, `name`, `price`, `quantity`, `description`, `image`, `status`, `date_added`) VALUES
(1, 'Custard', 30.00, 20, '', '1788695845_images.jpg', 'available', '2026-09-06 11:57:25');

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `order_id`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 0, 1, 5, 'ansarap', '2026-09-06 13:24:52'),
(2, 1, 1, 2, 5, 'sarap', '2026-09-06 13:27:21');

INSERT INTO `transactions` (`id`, `user_id`, `order_id`, `amount`, `type`, `status`, `receipt`, `transaction_date`) VALUES
(2, 6, 6, 30.00, 'sale', 'completed', '', '2026-10-31 11:11:53'),
(3, 5, 7, 60.00, 'sale', 'completed', '', '2026-10-31 11:12:18'),
(4, 5, 8, 60.00, 'sale', 'completed', '', '2026-10-31 11:12:22');

INSERT INTO `users` (`id`, `username`, `password`, `email`, `fullname`, `role`, `birthdate`, `darkmode`, `anonymous`, `created_at`) VALUES
(1, 'christian.james', '$2y$12$OKZk460Mrk.ahCIjTCpihOCatnviUho633XgxmK.4mf.kQDOeEcWu', 'christianjamesv24@gmail.com', 'Christian James', 'customer', '2005-05-24', 0, 0, '2026-09-06 05:13:14'),
(2, 'myrtle anne v..cassanova', '$2y$12$lQ5H7dz7nWxIaM/Dd6QHXujPlU3PRW3KdLg45XojjkDNFRRnzGKy6', 'mavcassanova@gmail.com', 'Myrtle Anne V. Cassanova', 'customer', NULL, 0, 0, '2026-09-06 09:48:33'),
(3, 'admin', '$2y$10$UK6PvJ9iI3jwjgOYJavzeuk84a0Mbxy1BMXup.mgoUGZoIHRfTvtW', 'admin@baketrack.local', 'Vencent Mariano', 'admin', '2006-01-02', 0, 0, '2026-09-06 09:58:00'),
(4, 'myrtle.cassonava', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mavcassonava@gmail.com', 'Myrtle Anne V. Cassonava', 'admin', NULL, 0, 0, '2026-09-06 10:10:08'),
(5, 'mark.ceghar', '$2y$10$8beg7YH7p/0DZXSpL7Z/h.wY9vwgsiW6RIVz7TmCpBrv5koSasY7q', 'assistant.new@baketrack.local', 'Mark Anthony V. Ceghar', 'assistant', NULL, 0, 0, '2026-09-06 10:10:08'),
(6, 'melissa.cupa', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mavcupa@gmail.com', 'Melissa Anne V. Cupa', 'customer', '2003-03-15', 1, 1, '2026-09-06 10:10:08'),
(9, 'mishfa.anilao', '$2y$12$BIxNW1IyC6LBbTxpW1ewLOEy6j3xfXpVhvs9giQyTwrvO/ewz/rBm', 'onats@gmail.com', 'mishfa anilao', 'customer', NULL, 0, 0, '2026-09-07 00:41:18');


--
-- AUTO_INCREMENT values
--


ALTER TABLE `admins` AUTO_INCREMENT=2;

ALTER TABLE `messages` AUTO_INCREMENT=2;

ALTER TABLE `orders` AUTO_INCREMENT=9;

ALTER TABLE `order_items` AUTO_INCREMENT=10;

ALTER TABLE `products` AUTO_INCREMENT=3;

ALTER TABLE `reviews` AUTO_INCREMENT=3;

ALTER TABLE `transactions` AUTO_INCREMENT=5;

ALTER TABLE `users` AUTO_INCREMENT=10;

COMMIT;
