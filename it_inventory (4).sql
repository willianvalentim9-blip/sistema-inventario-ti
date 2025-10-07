-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 03/10/2025 às 21:01
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `it_inventory`
--
CREATE DATABASE IF NOT EXISTS `it_inventory` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `it_inventory`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `ip_address`, `user_agent`, `old_values`, `new_values`, `details`, `timestamp`) VALUES
(355, 1, 'LOGOUT', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '\"::1\"', '\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/135.0.0.0 Safari\\/537.36 OPR\\/120.0.0.0\"', NULL, '2025-10-02 12:05:45'),
(356, 1, 'LOGIN', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '\"::1\"', '\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/135.0.0.0 Safari\\/537.36 OPR\\/120.0.0.0\"', NULL, '2025-10-02 12:06:57'),
(357, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', NULL, NULL, NULL, '2025-10-02 12:07:14'),
(358, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', NULL, NULL, NULL, '2025-10-02 13:22:40'),
(359, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', NULL, NULL, NULL, '2025-10-02 13:22:43'),
(360, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', NULL, NULL, NULL, '2025-10-02 13:22:47'),
(361, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', NULL, NULL, NULL, '2025-10-02 13:22:51'),
(362, 1, 'LOGIN', 'users', 1, '192.168.25.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.25.100\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-02 13:30:07'),
(363, 1, 'CREATE', 'products', 27, '192.168.25.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', NULL, NULL, NULL, '2025-10-02 13:32:04'),
(364, 1, 'LOGIN', 'users', 1, '192.168.25.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.25.100\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-02 13:33:45'),
(365, 1, 'LOGOUT', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '\"::1\"', '\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/135.0.0.0 Safari\\/537.36 OPR\\/120.0.0.0\"', NULL, '2025-10-02 13:40:03'),
(366, 1, 'LOGIN', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '\"::1\"', '\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/135.0.0.0 Safari\\/537.36 OPR\\/120.0.0.0\"', NULL, '2025-10-02 13:40:08'),
(367, 1, 'LOGIN', 'users', 1, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1', '\"::1\"', '\"Mozilla\\/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit\\/605.1.15 (KHTML, like Gecko) Version\\/13.0.3 Mobile\\/15E148 Safari\\/604.1\"', NULL, '2025-10-02 16:03:37'),
(368, 1, 'LOGIN', 'users', 1, '192.168.25.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.25.100\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 12:01:42'),
(369, 1, 'CREATE', 'products', 28, '192.168.25.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', NULL, NULL, NULL, '2025-10-03 12:04:26'),
(370, 1, 'LOGIN', 'users', 1, '192.168.25.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.25.100\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 12:52:28'),
(371, 1, 'LOGIN', 'users', 1, '192.168.0.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.0.104\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 16:57:15'),
(372, 1, 'LOGOUT', 'users', 1, '192.168.0.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.0.104\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 18:00:54'),
(373, 1, 'LOGIN', 'users', 1, '192.168.0.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.0.104\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 18:01:19'),
(374, 1, 'LOGOUT', 'users', 1, '192.168.0.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.0.104\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 18:03:56'),
(375, 1, 'LOGIN', 'users', 1, '192.168.0.104', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36', '\"192.168.0.104\"', '\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Mobile Safari\\/537.36\"', NULL, '2025-10-03 18:13:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('product','machine') NOT NULL COMMENT 'Define se a categoria é para produtos ou máquinas',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_type_unique` (`name`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `machine_inputs`
--

DROP TABLE IF EXISTS `machine_inputs`;
CREATE TABLE IF NOT EXISTS `machine_inputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_added` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `input_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `machine_name` varchar(255) DEFAULT NULL,
  `machine_serial_number` varchar(100) DEFAULT NULL,
  `machine_cost_price` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `machine_inputs_ibfk_1` (`machine_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `machine_movements`
--

DROP TABLE IF EXISTS `machine_movements`;
CREATE TABLE IF NOT EXISTS `machine_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_type` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `machine_outputs`
--

DROP TABLE IF EXISTS `machine_outputs`;
CREATE TABLE IF NOT EXISTS `machine_outputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_removed` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `output_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `machine_name` varchar(255) DEFAULT NULL,
  `machine_serial_number` varchar(100) DEFAULT NULL,
  `final_sale_price` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_quantity` int(11) DEFAULT 0,
  `max_quantity` int(11) DEFAULT 0,
  `price` decimal(12,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'available',
  `created_by` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `barcode` (`barcode`),
  UNIQUE KEY `qr_code` (`qr_code`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `idx_created_by` (`created_by`),
  KEY `fk_product_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category_id`, `category`, `manufacturer`, `model`, `sku`, `barcode`, `qr_code`, `serial_number`, `quantity`, `min_quantity`, `max_quantity`, `price`, `location`, `status`, `created_by`, `image`, `notes`, `created_at`, `updated_at`) VALUES
(27, 'SSD WD 500GB', 'Backup alana cuidado!', NULL, 'SSD', 'WD', 'WD5000LPCX', NULL, 'IT-SSD-1759411924-9678', NULL, NULL, 1, 5, 100, 100.00, 'Prateira 1', 'available', NULL, 'img_68de7e9748df18.34388998.jpg', NULL, '2025-10-02 13:32:04', '2025-10-03 11:34:23'),
(28, 'HD 500GB', '', NULL, 'HDD', 'Samsung', 'ST500LM012', NULL, 'IT-HDD-1759493066-6607', NULL, NULL, 1, 5, 100, NULL, NULL, 'available', NULL, 'img_68dfbbb4e4d929.37907428.jpg', NULL, '2025-10-03 12:04:26', '2025-10-03 12:04:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_inputs`
--

DROP TABLE IF EXISTS `product_inputs`;
CREATE TABLE IF NOT EXISTS `product_inputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_added` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `input_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_name` varchar(255) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `product_serial_number` varchar(100) DEFAULT NULL,
  `product_barcode` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_inputs`
--

INSERT INTO `product_inputs` (`id`, `product_id`, `user_id`, `quantity_added`, `reason`, `details`, `unit_price`, `input_date`, `product_name`, `product_category`, `product_serial_number`, `product_barcode`) VALUES
(37, 27, 1, 1, 'Compra de Fornecedor', '', 100.00, '2025-10-03 11:34:23', 'SSD WD 500GB', 'SSD', NULL, 'IT-SSD-1759411924-9678');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_movements`
--

DROP TABLE IF EXISTS `product_movements`;
CREATE TABLE IF NOT EXISTS `product_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_type` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_movements`
--

INSERT INTO `product_movements` (`id`, `product_id`, `user_id`, `movement_type`, `quantity`, `previous_quantity`, `new_quantity`, `reason`, `movement_date`) VALUES
(92, 27, 1, 'entrada', 1, 0, 1, 'Produto criado: SSD WD 500GB', '2025-10-02 13:32:04'),
(93, 27, 1, 'saida', 1, 1, 0, 'Venda - ', '2025-10-03 11:33:54'),
(94, 27, 1, 'entrada', 1, 0, 1, 'Compra de Fornecedor', '2025-10-03 11:34:23'),
(95, 28, 1, 'entrada', 1, 0, 1, 'Produto criado: HD 500GB', '2025-10-03 12:04:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_outputs`
--

DROP TABLE IF EXISTS `product_outputs`;
CREATE TABLE IF NOT EXISTS `product_outputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_removed` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `output_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_name` varchar(255) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `product_serial_number` varchar(100) DEFAULT NULL,
  `product_barcode` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_outputs`
--

INSERT INTO `product_outputs` (`id`, `product_id`, `user_id`, `quantity_removed`, `reason`, `details`, `unit_price`, `output_date`, `product_name`, `product_category`, `product_serial_number`, `product_barcode`) VALUES
(25, 27, 1, 1, 'Venda', '', 100.00, '2025-10-03 11:33:54', 'SSD WD 500GB', 'SSD', NULL, 'IT-SSD-1759411924-9678');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ready_machines`
--

DROP TABLE IF EXISTS `ready_machines`;
CREATE TABLE IF NOT EXISTS `ready_machines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `memory` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `graphics` varchar(255) DEFAULT NULL,
  `motherboard` varchar(255) DEFAULT NULL,
  `power_supply` varchar(255) DEFAULT NULL,
  `case_type` varchar(255) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `qr_code` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` varchar(50) DEFAULT 'available',
  `location` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `windows_10_compatible` tinyint(1) DEFAULT 0,
  `windows_11_compatible` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  UNIQUE KEY `barcode` (`barcode`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `fk_machine_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=354 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(343, 'company_logo', 'assets/img/logo_1759411371.png', '2025-10-02 12:07:14', '2025-10-02 13:22:51'),
(344, 'user_theme_1', 'light', '2025-10-02 13:19:44', '2025-10-02 13:25:39');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `full_name` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `theme` varchar(50) DEFAULT 'blue',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `avatar`, `last_login`, `reset_token`, `reset_token_expires_at`, `created_at`, `updated_at`, `theme`) VALUES
(1, 'admin', 'admin123', 'admin@sistema.com', 'admin', 'Administrador do Sistema', NULL, '2025-10-03 18:13:12', NULL, NULL, '2025-09-24 18:57:20', '2025-10-03 18:13:12', 'blue'),
(2, 'user', 'user123', 'user@sistema.com', 'user', 'Usuário Padrão', 'avatar_2_1758833318.png', '2025-09-30 19:38:10', NULL, NULL, '2025-09-24 18:57:20', '2025-09-30 19:38:10', 'blue'),
(3, 'felipe', 'abc,123', 'suporte02@supportti.net', 'admin', 'felipe', NULL, '2025-09-25 14:46:25', NULL, NULL, '2025-09-24 20:54:48', '2025-09-29 20:26:57', 'blue'),
(4, 'Ryan', '@#8520@#', 'suporte03@supportti.net', 'admin', '', 'avatar_4_1759154926.png', '2025-10-01 20:57:47', NULL, NULL, '2025-09-25 20:35:20', '2025-10-01 20:57:47', 'blue'),
(5, 'Jux', 'Jux123', 'suporte04@supportti.net', 'admin', '', NULL, '2025-09-26 12:22:03', NULL, NULL, '2025-09-26 12:03:54', '2025-09-26 12:22:03', 'blue'),
(6, 'William', '@#8520@#', 'willianvalentim9@gmail.com', 'user', '', NULL, NULL, '75a7d1aff32d182f5d22e7bcb203b3a74c0178c50d5b8f4235e9e2f2d782a96e', '2025-09-26 16:02:47', '2025-09-26 12:39:37', '2025-09-26 13:02:47', 'blue'),
(7, 'Cleber', '@#8520@#', 'direcao@supportti.net', 'admin', '', NULL, NULL, NULL, NULL, '2025-09-29 13:03:03', '2025-09-29 13:03:03', 'blue');

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `machine_inputs`
--
ALTER TABLE `machine_inputs`
  ADD CONSTRAINT `machine_inputs_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `ready_machines` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `machine_inputs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `machine_movements`
--
ALTER TABLE `machine_movements`
  ADD CONSTRAINT `machine_movements_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `ready_machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `machine_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `machine_outputs`
--
ALTER TABLE `machine_outputs`
  ADD CONSTRAINT `machine_outputs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `product_inputs`
--
ALTER TABLE `product_inputs`
  ADD CONSTRAINT `product_inputs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `product_movements`
--
ALTER TABLE `product_movements`
  ADD CONSTRAINT `product_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `product_outputs`
--
ALTER TABLE `product_outputs`
  ADD CONSTRAINT `product_outputs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `ready_machines`
--
ALTER TABLE `ready_machines`
  ADD CONSTRAINT `fk_machine_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
