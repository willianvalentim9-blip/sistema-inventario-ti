-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/12/2025 às 20:34
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

DELIMITER $$
--
-- Procedimentos
--
DROP PROCEDURE IF EXISTS `GetWarrantyAlerts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetWarrantyAlerts` (IN `p_alert_level` INT)   BEGIN
    -- p_alert_level: 1=Ativas, 2=Atenção, 3=Crítica, 4=Expirada
    SELECT 
        p.id,
        p.name,
        p.category,
        p.warranty_end_date,
        DATEDIFF(p.warranty_end_date, CURDATE()) as days_remaining,
        p.warranty_provider,
        CASE 
            WHEN p.warranty_end_date < CURDATE() THEN 'Expirada'
            WHEN DATEDIFF(p.warranty_end_date, CURDATE()) <= 7 THEN 'Crítica (7 dias)'
            WHEN DATEDIFF(p.warranty_end_date, CURDATE()) <= 30 THEN 'Atenção (30 dias)'
            ELSE 'Ativa'
        END as status
    FROM products p
    WHERE p.has_warranty = 1
    AND (
        (p_alert_level = 1 AND DATEDIFF(p.warranty_end_date, CURDATE()) > 30) OR
        (p_alert_level = 2 AND DATEDIFF(p.warranty_end_date, CURDATE()) > 7 AND DATEDIFF(p.warranty_end_date, CURDATE()) <= 30) OR
        (p_alert_level = 3 AND DATEDIFF(p.warranty_end_date, CURDATE()) >= 0 AND DATEDIFF(p.warranty_end_date, CURDATE()) <= 7) OR
        (p_alert_level = 4 AND p.warranty_end_date < CURDATE())
    )
    ORDER BY p.warranty_end_date ASC;
END$$

DROP PROCEDURE IF EXISTS `RegisterWarrantyHistory`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegisterWarrantyHistory` (IN `p_product_id` INT, IN `p_user_id` INT, IN `p_action_type` VARCHAR(20), IN `p_old_values` JSON, IN `p_new_values` JSON, IN `p_description` VARCHAR(500))   BEGIN
    INSERT INTO warranty_history (
        product_id,
        user_id,
        action_type,
        old_values,
        new_values,
        change_description
    ) VALUES (
        p_product_id,
        p_user_id,
        p_action_type,
        p_old_values,
        p_new_values,
        p_description
    );
END$$

--
-- Funções
--
DROP FUNCTION IF EXISTS `CalculateWarrantyStatus`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `CalculateWarrantyStatus` (`warranty_end_date` DATE) RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    IF warranty_end_date IS NULL THEN
        RETURN 'Sem Garantia';
    ELSEIF warranty_end_date < CURDATE() THEN
        RETURN 'Expirada';
    ELSEIF DATEDIFF(warranty_end_date, CURDATE()) <= 7 THEN
        RETURN 'Crítica';
    ELSEIF DATEDIFF(warranty_end_date, CURDATE()) <= 30 THEN
        RETURN 'Atenção';
    ELSE
        RETURN 'Ativa';
    END IF;
END$$

DROP FUNCTION IF EXISTS `GenerateBarcodeCode`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `GenerateBarcodeCode` (`category` VARCHAR(50)) RETURNS VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
    DECLARE new_code VARCHAR(100);
    DECLARE code_exists INT;
    DECLARE random_suffix VARCHAR(10);
    
    SET random_suffix = LPAD(FLOOR(RAND() * 1000000), 7, '0');
    SET new_code = CONCAT('IT-', UPPER(SUBSTRING(category, 1, 3)), '-', UNIX_TIMESTAMP(), '-', random_suffix);
    
    SELECT COUNT(*) INTO code_exists 
    FROM products 
    WHERE barcode = new_code;
    
    WHILE code_exists > 0 DO
        SET random_suffix = LPAD(FLOOR(RAND() * 1000000), 7, '0');
        SET new_code = CONCAT('IT-', UPPER(SUBSTRING(category, 1, 3)), '-', UNIX_TIMESTAMP(), '-', random_suffix);
        SELECT COUNT(*) INTO code_exists 
        FROM products 
        WHERE barcode = new_code;
    END WHILE;
    
    RETURN new_code;
END$$

DROP FUNCTION IF EXISTS `GetWarrantyDaysRemaining`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `GetWarrantyDaysRemaining` (`warranty_end_date` DATE) RETURNS INT(11) DETERMINISTIC BEGIN
    IF warranty_end_date IS NULL THEN
        RETURN -1;
    ELSE
        RETURN DATEDIFF(warranty_end_date, CURDATE());
    END IF;
END$$

DROP FUNCTION IF EXISTS `ValidateStock`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ValidateStock` (`product_id` INT, `quantity_needed` INT) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE available_qty INT;
    
    SELECT quantity INTO available_qty 
    FROM products 
    WHERE id = product_id;
    
    IF available_qty IS NULL THEN
        RETURN FALSE;
    END IF;
    
    RETURN available_qty >= quantity_needed;
END$$

DELIMITER ;

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
) ENGINE=InnoDB AUTO_INCREMENT=1469 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `ip_address`, `user_agent`, `old_values`, `new_values`, `details`, `timestamp`) VALUES
(1442, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:09'),
(1443, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:19'),
(1444, 1, 'BACKUP_DATABASE_SUCCESS', 'backup: backup_2025-12-17_17-46-22.sql', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:22'),
(1445, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:28'),
(1446, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:34'),
(1447, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:42'),
(1448, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:48'),
(1449, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:46:50'),
(1450, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:47:46'),
(1451, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:52:44'),
(1452, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:52:49'),
(1453, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:52:55'),
(1454, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:53:00'),
(1455, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:53:38'),
(1456, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:53:43'),
(1457, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:53:49'),
(1458, 1, 'RESTORE_USER', 'users', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '\"username: felipe, email: suporte02@supportti.net\"', '\"is_deleted = FALSE\"', NULL, '2025-12-17 16:54:24'),
(1459, 1, 'RESTORE_USER', 'users', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '\"username: William, email: willianvalentim9@gmail.com\"', '\"is_deleted = FALSE\"', NULL, '2025-12-17 16:54:26'),
(1460, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 16:55:18'),
(1461, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 17:01:31'),
(1462, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 17:08:46'),
(1463, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 17:09:52'),
(1464, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 17:12:32'),
(1465, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 17:12:36'),
(1466, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 17:12:40'),
(1467, 1, 'UPDATE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 19:18:14'),
(1468, 1, 'REMOVE_LOGO', 'system_settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', NULL, NULL, NULL, '2025-12-17 19:18:18');

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
-- Estrutura para tabela `low_stock_logs`
--

DROP TABLE IF EXISTS `low_stock_logs`;
CREATE TABLE IF NOT EXISTS `low_stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `min_quantity` int(11) NOT NULL,
  `alert_type` varchar(50) NOT NULL COMMENT 'warning ou critical',
  `status` varchar(50) DEFAULT 'active' COMMENT 'active ou resolved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by_user_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Estrutura para tabela `machine_products`
--

DROP TABLE IF EXISTS `machine_products`;
CREATE TABLE IF NOT EXISTS `machine_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` int(11) NOT NULL COMMENT 'ID da máquina pronta',
  `product_id` int(11) NOT NULL COMMENT 'ID do produto/componente',
  `quantity` int(11) DEFAULT 1 COMMENT 'Quantidade do produto usada',
  `component_type` varchar(100) DEFAULT NULL COMMENT 'Tipo de componente (Processador, RAM, Storage, etc)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_machine_product` (`machine_id`,`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=333 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `has_warranty` tinyint(1) NOT NULL DEFAULT 0,
  `warranty_template_id` int(11) DEFAULT NULL COMMENT 'ID do template de garantia usado',
  `warranty_provider` varchar(255) DEFAULT NULL,
  `warranty_period_value` int(11) DEFAULT NULL,
  `warranty_period_unit` varchar(20) DEFAULT NULL,
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `warranty_notes` text DEFAULT NULL,
  `warranty_ticket_number` varchar(100) DEFAULT NULL COMMENT 'N??mero do ticket/protocolo da garantia',
  `warranty_label` varchar(100) DEFAULT NULL COMMENT 'Etiqueta/Identificador da garantia',
  `warranty_client_name` varchar(255) DEFAULT NULL COMMENT 'Nome do cliente propriet??rio do produto com garantia',
  `warranty_supplier_id` int(11) DEFAULT NULL COMMENT 'Refer??ncia para o fornecedor de garantia',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `barcode` (`barcode`),
  UNIQUE KEY `qr_code` (`qr_code`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `idx_created_by` (`created_by`),
  KEY `fk_product_category` (`category_id`),
  KEY `idx_warranty_template` (`warranty_template_id`),
  KEY `idx_warranty_dates` (`warranty_start_date`,`warranty_end_date`),
  KEY `idx_has_warranty` (`has_warranty`),
  KEY `idx_warranty_ticket` (`warranty_ticket_number`),
  KEY `idx_warranty_label` (`warranty_label`),
  KEY `idx_warranty_client` (`warranty_client_name`),
  KEY `idx_warranty_supplier` (`warranty_supplier_id`),
  KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `products`
--
DROP TRIGGER IF EXISTS `trg_product_delete`;
DELIMITER $$
CREATE TRIGGER `trg_product_delete` BEFORE DELETE ON `products` FOR EACH ROW BEGIN
    INSERT INTO admin_logs (
        action,
        table_name,
        record_id,
        old_values,
        new_values,
        details,
        timestamp
    ) VALUES (
        'DELETE',
        'products',
        OLD.id,
        JSON_OBJECT(
            'id', OLD.id,
            'name', OLD.name,
            'category', OLD.category,
            'barcode', OLD.barcode,
            'quantity', OLD.quantity,
            'price', OLD.price,
            'warranty_end_date', OLD.warranty_end_date
        ),
        NULL,
        CONCAT('Produto deletado: ', OLD.name, ' (ID: ', OLD.id, ')'),
        NOW()
    );
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_product_warranty_update`;
DELIMITER $$
CREATE TRIGGER `trg_product_warranty_update` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    IF (
        OLD.warranty_provider != NEW.warranty_provider OR
        OLD.warranty_start_date != NEW.warranty_start_date OR
        OLD.warranty_end_date != NEW.warranty_end_date OR
        OLD.warranty_notes != NEW.warranty_notes OR
        OLD.warranty_period_value != NEW.warranty_period_value OR
        OLD.warranty_period_unit != NEW.warranty_period_unit
    ) THEN
        INSERT INTO warranty_history (
            product_id,
            user_id,
            action_type,
            old_values,
            new_values,
            change_description
        ) VALUES (
            NEW.id,
            IF(@current_user_id IS NULL, 1, @current_user_id),
            'UPDATE',
            JSON_OBJECT(
                'warranty_provider', OLD.warranty_provider,
                'warranty_start_date', OLD.warranty_start_date,
                'warranty_end_date', OLD.warranty_end_date,
                'warranty_notes', OLD.warranty_notes,
                'warranty_period_value', OLD.warranty_period_value,
                'warranty_period_unit', OLD.warranty_period_unit
            ),
            JSON_OBJECT(
                'warranty_provider', NEW.warranty_provider,
                'warranty_start_date', NEW.warranty_start_date,
                'warranty_end_date', NEW.warranty_end_date,
                'warranty_notes', NEW.warranty_notes,
                'warranty_period_value', NEW.warranty_period_value,
                'warranty_period_unit', NEW.warranty_period_unit
            ),
            'Garantia atualizada automaticamente'
        );
    END IF;
END
$$
DELIMITER ;

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
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=625 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `has_warranty` tinyint(1) DEFAULT 0,
  `warranty_provider` varchar(255) DEFAULT NULL,
  `warranty_period_value` int(11) DEFAULT NULL,
  `warranty_period_unit` varchar(50) DEFAULT 'months',
  `warranty_start_date` date DEFAULT NULL,
  `warranty_end_date` date DEFAULT NULL,
  `warranty_notes` text DEFAULT NULL,
  `warranty_template_id` int(11) DEFAULT NULL,
  `warranty_client_name` varchar(255) DEFAULT NULL,
  `warranty_ticket_number` varchar(100) DEFAULT NULL,
  `warranty_label` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `warranty_supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  UNIQUE KEY `barcode` (`barcode`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `fk_machine_category` (`category_id`),
  KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=439 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(411, 'user_theme_1', 'dark_blue', '2025-12-11 13:38:48', '2025-12-12 12:10:12'),
(414, 'system_name', 'Sistema de Estoque TI', '2025-12-16 19:31:00', '2025-12-16 19:31:00'),
(415, 'company_name', 'Supportti', '2025-12-16 19:31:00', '2025-12-16 19:31:00'),
(416, 'company_slogan', 'abre chamado', '2025-12-16 19:31:00', '2025-12-16 19:31:00'),
(417, 'company_logo', '', '2025-12-16 19:41:22', '2025-12-17 19:18:18');

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
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT 'Marca usuário como deletado (soft delete)',
  `deleted_at` datetime DEFAULT NULL COMMENT 'Data e hora da deleção',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `avatar`, `last_login`, `reset_token`, `reset_token_expires_at`, `created_at`, `updated_at`, `theme`, `is_deleted`, `deleted_at`) VALUES
(1, 'admin', 'admin123', 'admin@sistema.com', 'admin', 'Administrador do Sistema', 'avatar_1_1765980774.jpg', '2025-12-17 11:20:55', NULL, NULL, '2025-09-24 18:57:20', '2025-12-17 14:12:54', '', 0, NULL),
(2, 'user', 'user123', 'user@sistema.com', 'user', 'Usuário Padrão', 'avatar_2_1758833318.png', '2025-12-16 18:55:40', NULL, NULL, '2025-09-24 18:57:20', '2025-12-16 18:55:40', 'blue', 0, NULL),
(3, 'felipe', 'abc,123', 'suporte02@supportti.net', 'admin', 'felipe', NULL, '2025-09-25 14:46:25', NULL, NULL, '2025-09-24 20:54:48', '2025-12-17 16:54:24', 'blue', 0, NULL),
(6, 'William', 'abc,123', 'willianvalentim9@gmail.com', 'administrativo', '', NULL, '2025-12-16 18:56:10', '75a7d1aff32d182f5d22e7bcb203b3a74c0178c50d5b8f4235e9e2f2d782a96e', '2025-09-26 16:02:47', '2025-09-26 12:39:37', '2025-12-17 16:54:26', 'blue', 0, NULL),
(7, 'Cleber', '@#8520@#', 'direcao@supportti.net', 'admin', '', NULL, NULL, NULL, NULL, '2025-09-29 13:03:03', '2025-09-29 13:03:03', 'blue', 0, NULL),
(8, 'Cris', 'cris123', 'TESTE@valentim.com.br', 'administrativo', '', NULL, '2025-12-11 13:27:44', NULL, NULL, '2025-12-11 13:27:18', '2025-12-11 13:27:44', 'blue', 0, NULL);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_active_warranties`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `vw_active_warranties`;
CREATE TABLE IF NOT EXISTS `vw_active_warranties` (
`id` int(11)
,`name` varchar(255)
,`category` varchar(100)
,`barcode` varchar(100)
,`warranty_provider` varchar(255)
,`warranty_start_date` date
,`warranty_end_date` date
,`warranty_period_value` int(11)
,`warranty_period_unit` varchar(20)
,`has_warranty` tinyint(1)
,`days_remaining` int(7)
,`status` varchar(20)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_inventory_value`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `vw_inventory_value`;
CREATE TABLE IF NOT EXISTS `vw_inventory_value` (
`category` varchar(100)
,`total_items` bigint(21)
,`total_quantity` decimal(32,0)
,`average_price` decimal(16,6)
,`total_value` decimal(44,2)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_low_stock`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `vw_low_stock`;
CREATE TABLE IF NOT EXISTS `vw_low_stock` (
`id` int(11)
,`name` varchar(255)
,`category` varchar(100)
,`barcode` varchar(100)
,`quantity` int(11)
,`min_quantity` int(11)
,`max_quantity` int(11)
,`price` decimal(12,2)
,`total_value` decimal(22,2)
,`stock_status` varchar(7)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_warranty_alerts`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `vw_warranty_alerts`;
CREATE TABLE IF NOT EXISTS `vw_warranty_alerts` (
`id` int(11)
,`name` varchar(255)
,`category` varchar(100)
,`barcode` varchar(100)
,`warranty_provider` varchar(255)
,`warranty_end_date` date
,`days_remaining` int(7)
,`alert_level` varchar(8)
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_warranty_status_summary`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `vw_warranty_status_summary`;
CREATE TABLE IF NOT EXISTS `vw_warranty_status_summary` (
`id` int(11)
,`product_name` varchar(255)
,`warranty_start_date` date
,`warranty_end_date` date
,`template_name` varchar(100)
,`warranty_status` varchar(17)
,`days_remaining` int(7)
,`alert_level` int(1)
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `warehouse`
--

DROP TABLE IF EXISTS `warehouse`;
CREATE TABLE IF NOT EXISTS `warehouse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome do item no armazém',
  `description` text DEFAULT NULL COMMENT 'Descrição detalhada',
  `category_id` int(11) DEFAULT NULL COMMENT 'ID da categoria (relacionado com categories)',
  `category` varchar(100) NOT NULL COMMENT 'Categoria do item',
  `manufacturer` varchar(100) DEFAULT NULL COMMENT 'Fabricante',
  `model` varchar(100) DEFAULT NULL COMMENT 'Modelo',
  `sku` varchar(100) DEFAULT NULL COMMENT 'Código SKU',
  `barcode` varchar(100) DEFAULT NULL COMMENT 'Código de barras',
  `qr_code` varchar(100) DEFAULT NULL COMMENT 'Código QR',
  `serial_number` varchar(100) DEFAULT NULL COMMENT 'Número de série',
  `quantity` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantidade em estoque',
  `min_quantity` int(11) DEFAULT 0 COMMENT 'Estoque mínimo',
  `max_quantity` int(11) DEFAULT 0 COMMENT 'Estoque máximo',
  `price` decimal(12,2) DEFAULT NULL COMMENT 'Preço unitário',
  `location` varchar(100) DEFAULT NULL COMMENT 'Localização física no armazém',
  `status` varchar(50) DEFAULT 'available' COMMENT 'Status (available, unavailable, reserved)',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que criou',
  `image` varchar(255) DEFAULT NULL COMMENT 'Caminho da imagem',
  `notes` text DEFAULT NULL COMMENT 'Observações',
  `has_warranty` tinyint(1) DEFAULT 0 COMMENT 'Possui garantia',
  `warranty_provider` varchar(255) DEFAULT NULL COMMENT 'Fornecedor da garantia',
  `warranty_period_value` int(11) DEFAULT NULL COMMENT 'Valor do período de garantia',
  `warranty_period_unit` varchar(50) DEFAULT 'months' COMMENT 'Unidade do período (days, months, years)',
  `warranty_notes` longtext DEFAULT NULL,
  `warranty_start_date` date DEFAULT NULL COMMENT 'Data de início da garantia',
  `warranty_end_date` date DEFAULT NULL COMMENT 'Data de fim da garantia',
  `warranty_client_name` varchar(255) DEFAULT NULL COMMENT 'Nome do cliente da garantia',
  `warranty_ticket_number` varchar(100) DEFAULT NULL COMMENT 'Número do ticket da garantia',
  `warranty_label` varchar(100) DEFAULT NULL COMMENT 'Etiqueta/código da garantia',
  `warranty_supplier_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL COMMENT 'Número da nota fiscal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de criação',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Data de atualização',
  `is_deleted` tinyint(1) DEFAULT 0 COMMENT 'Soft delete flag',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Data de exclusão',
  `deleted_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que excluiu',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_qr_code` (`qr_code`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_status` (`status`),
  KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Armazém - Produtos exclusivos para usuários administrativos';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warehouse_inputs`
--

DROP TABLE IF EXISTS `warehouse_inputs`;
CREATE TABLE IF NOT EXISTS `warehouse_inputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL COMMENT 'ID do item no armazém',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID do usuário responsável',
  `quantity_added` int(11) NOT NULL COMMENT 'Quantidade adicionada',
  `reason` varchar(255) NOT NULL COMMENT 'Motivo da entrada',
  `details` text DEFAULT NULL COMMENT 'Detalhes adicionais',
  `unit_price` decimal(12,2) DEFAULT NULL COMMENT 'Preço unitário',
  `input_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da entrada',
  `warehouse_name` varchar(255) DEFAULT NULL COMMENT 'Nome do item (cache)',
  `warehouse_category` varchar(100) DEFAULT NULL COMMENT 'Categoria (cache)',
  `warehouse_serial_number` varchar(100) DEFAULT NULL COMMENT 'Número de série (cache)',
  `warehouse_barcode` varchar(100) DEFAULT NULL COMMENT 'Código de barras (cache)',
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Entradas de itens no armazém';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warehouse_movements`
--

DROP TABLE IF EXISTS `warehouse_movements`;
CREATE TABLE IF NOT EXISTS `warehouse_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL COMMENT 'ID do item no armazém',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID do usuário que fez a movimentação',
  `movement_type` varchar(50) NOT NULL COMMENT 'Tipo (entrada, saida, ajuste)',
  `quantity` int(11) NOT NULL COMMENT 'Quantidade movimentada',
  `previous_quantity` int(11) NOT NULL COMMENT 'Quantidade anterior',
  `new_quantity` int(11) NOT NULL COMMENT 'Nova quantidade',
  `reason` text DEFAULT NULL COMMENT 'Motivo da movimentação',
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da movimentação',
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_movement_type` (`movement_type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Movimentações de estoque do armazém';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warehouse_outputs`
--

DROP TABLE IF EXISTS `warehouse_outputs`;
CREATE TABLE IF NOT EXISTS `warehouse_outputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL COMMENT 'ID do item no armazém',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID do usuário responsável',
  `quantity_removed` int(11) NOT NULL COMMENT 'Quantidade removida',
  `reason` varchar(255) NOT NULL COMMENT 'Motivo da saída',
  `details` text DEFAULT NULL COMMENT 'Detalhes adicionais',
  `unit_price` decimal(12,2) DEFAULT NULL COMMENT 'Preço unitário',
  `output_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da saída',
  `warehouse_name` varchar(255) DEFAULT NULL COMMENT 'Nome do item (cache)',
  `warehouse_category` varchar(100) DEFAULT NULL COMMENT 'Categoria (cache)',
  `warehouse_serial_number` varchar(100) DEFAULT NULL COMMENT 'Número de série (cache)',
  `warehouse_barcode` varchar(100) DEFAULT NULL COMMENT 'Código de barras (cache)',
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Saídas de itens do armazém';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warehouse_warranty_history`
--

DROP TABLE IF EXISTS `warehouse_warranty_history`;
CREATE TABLE IF NOT EXISTS `warehouse_warranty_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL COMMENT 'ID do item no armazém',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID do usuário que fez a alteração',
  `action_type` varchar(50) NOT NULL COMMENT 'Tipo de ação (create, update, expire)',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Valores antigos (JSON)' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Valores novos (JSON)' CHECK (json_valid(`new_values`)),
  `change_description` varchar(500) DEFAULT NULL COMMENT 'Descrição da mudança',
  `change_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data da mudança',
  PRIMARY KEY (`id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Histórico de garantias dos itens do armazém';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warranty_claims`
--

DROP TABLE IF EXISTS `warranty_claims`;
CREATE TABLE IF NOT EXISTS `warranty_claims` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT 'ID do produto com problema',
  `user_id` int(11) NOT NULL COMMENT 'ID do usuário que abriu o chamado',
  `claim_date` date NOT NULL COMMENT 'Data do acionamento da garantia',
  `problem_type` varchar(100) DEFAULT NULL COMMENT 'Tipo do problema (ex: Defeito Eletrônico, Dano Físico)',
  `description` text NOT NULL COMMENT 'Descrição detalhada do problema',
  `status` enum('open','in_analysis','in_repair','resolved','denied') DEFAULT 'open' COMMENT 'Status do chamado',
  `resolution` varchar(100) DEFAULT NULL COMMENT 'Como foi resolvido (ex: Reparado, Substituído)',
  `resolution_date` date DEFAULT NULL COMMENT 'Data da resolução',
  `notes` text DEFAULT NULL COMMENT 'Notas adicionais do técnico/suporte',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de criação do chamado',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Última atualização',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_claim_date` (`claim_date`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_status` (`product_id`,`status`),
  KEY `idx_status_date` (`status`,`claim_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de acionamentos/reclamações de garantia';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warranty_history`
--

DROP TABLE IF EXISTS `warranty_history`;
CREATE TABLE IF NOT EXISTS `warranty_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL COMMENT 'ID do produto',
  `machine_id` int(11) DEFAULT NULL COMMENT 'ID da máquina',
  `warehouse_id` int(11) DEFAULT NULL COMMENT 'ID do item de armazém',
  `user_id` int(11) NOT NULL COMMENT 'ID do usuário que fez a alteração',
  `action_type` enum('CREATE','UPDATE','DELETE','CLAIM') NOT NULL DEFAULT 'UPDATE' COMMENT 'Tipo de ação realizada',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Valores anteriores em formato JSON' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Novos valores em formato JSON' CHECK (json_valid(`new_values`)),
  `change_description` varchar(500) DEFAULT NULL COMMENT 'Descrição da alteração (motivo)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data e hora da alteração',
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_date` (`product_id`,`created_at`),
  KEY `idx_action_type_date` (`action_type`,`created_at`),
  KEY `idx_machine_id` (`machine_id`),
  KEY `idx_machine_date` (`machine_id`,`created_at`),
  KEY `idx_warehouse_id` (`warehouse_id`),
  KEY `idx_warehouse_date` (`warehouse_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico completo de alterações em garantias';

-- --------------------------------------------------------

--
-- Estrutura para tabela `warranty_suppliers`
--

DROP TABLE IF EXISTS `warranty_suppliers`;
CREATE TABLE IF NOT EXISTS `warranty_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome da empresa fornecedora',
  `cnpj` varchar(18) DEFAULT NULL COMMENT 'CNPJ da empresa',
  `contact_person` varchar(255) DEFAULT NULL COMMENT 'Pessoa de contato',
  `email` varchar(100) DEFAULT NULL COMMENT 'Email para contato',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Telefone para contato',
  `mobile` varchar(20) DEFAULT NULL COMMENT 'Celular para contato',
  `website` varchar(255) DEFAULT NULL COMMENT 'Website da empresa',
  `address_street` varchar(255) DEFAULT NULL COMMENT 'Rua/Avenida',
  `address_number` varchar(10) DEFAULT NULL COMMENT 'Numero',
  `address_complement` varchar(255) DEFAULT NULL COMMENT 'Complemento',
  `address_neighborhood` varchar(100) DEFAULT NULL COMMENT 'Bairro',
  `address_city` varchar(100) DEFAULT NULL COMMENT 'Cidade',
  `address_state` varchar(2) DEFAULT NULL COMMENT 'Estado (UF)',
  `address_postal_code` varchar(10) DEFAULT NULL COMMENT 'CEP',
  `warranty_policy` text DEFAULT NULL COMMENT 'Politica de garantia',
  `payment_terms` varchar(100) DEFAULT NULL COMMENT 'Termos de pagamento',
  `notes` text DEFAULT NULL COMMENT 'Observações gerais',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Status ativo/inativo',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de cria├º├úo',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Data da ├║ltima atualiza├º├úo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`),
  KEY `idx_name` (`name`),
  KEY `idx_cnpj` (`cnpj`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_warranty_suppliers_is_deleted` (`is_deleted`),
  KEY `idx_warranty_suppliers_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `warranty_suppliers_active`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `warranty_suppliers_active`;
CREATE TABLE IF NOT EXISTS `warranty_suppliers_active` (
`id` int(11)
,`name` varchar(255)
,`cnpj` varchar(18)
,`contact_person` varchar(255)
,`email` varchar(100)
,`phone` varchar(20)
,`mobile` varchar(20)
,`website` varchar(255)
,`address_street` varchar(255)
,`address_number` varchar(10)
,`address_complement` varchar(255)
,`address_neighborhood` varchar(100)
,`address_city` varchar(100)
,`address_state` varchar(2)
,`address_postal_code` varchar(10)
,`warranty_policy` text
,`payment_terms` varchar(100)
,`notes` text
,`is_active` tinyint(1)
,`is_deleted` tinyint(1)
,`deleted_at` timestamp
,`deleted_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `warranty_suppliers_deleted`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `warranty_suppliers_deleted`;
CREATE TABLE IF NOT EXISTS `warranty_suppliers_deleted` (
`id` int(11)
,`name` varchar(255)
,`cnpj` varchar(18)
,`contact_person` varchar(255)
,`email` varchar(100)
,`phone` varchar(20)
,`mobile` varchar(20)
,`website` varchar(255)
,`address_street` varchar(255)
,`address_number` varchar(10)
,`address_complement` varchar(255)
,`address_neighborhood` varchar(100)
,`address_city` varchar(100)
,`address_state` varchar(2)
,`address_postal_code` varchar(10)
,`warranty_policy` text
,`payment_terms` varchar(100)
,`notes` text
,`is_active` tinyint(1)
,`is_deleted` tinyint(1)
,`deleted_at` timestamp
,`deleted_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para tabela `warranty_templates`
--

DROP TABLE IF EXISTS `warranty_templates`;
CREATE TABLE IF NOT EXISTS `warranty_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nome do template (ex: Eletrônicos Padrão)',
  `description` text DEFAULT NULL COMMENT 'Descrição detalhada do template',
  `period_value` int(11) NOT NULL COMMENT 'Valor do período (ex: 12)',
  `period_unit` enum('days','months','years') NOT NULL DEFAULT 'months' COMMENT 'Unidade do período',
  `warranty_provider` varchar(255) DEFAULT NULL COMMENT 'Fornecedor padrão da garantia',
  `warranty_notes` text DEFAULT NULL COMMENT 'Notas padrão que aparecem em todas as garantias deste template',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1 = Ativo, 0 = Inativo',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data de criação',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Última atualização',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_name` (`name`),
  KEY `idx_warranty_templates_is_deleted` (`is_deleted`),
  KEY `idx_warranty_templates_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Templates de garantia pré-configurados';

--
-- Despejando dados para a tabela `warranty_templates`
--

INSERT INTO `warranty_templates` (`id`, `name`, `description`, `period_value`, `period_unit`, `warranty_provider`, `warranty_notes`, `is_active`, `is_deleted`, `deleted_at`, `deleted_by`, `created_at`, `updated_at`) VALUES
(2, 'Componentes PC', 'Garantia estendida para componentes de computador (CPU, RAM, SSD, HDD, Motherboard)', 24, 'months', 'Distribuidor', 'Inclui frete de ida e volta. Garantia contra defeitos de fábrica. Cobre reparos ou reposição.', 1, 0, NULL, NULL, '2025-11-12 13:54:39', '2025-12-04 11:52:44'),
(3, 'Periféricos', 'Garantia para periféricos como mouse, teclado, webcam, headset', 6, 'months', 'Fornecedor', 'Defeitos de fábrica apenas. Não cobre: danos físicos, desgaste natural, uso indevido.', 1, 0, NULL, NULL, '2025-11-12 13:54:39', '2025-12-04 11:48:02'),
(4, 'Impressoras', 'Garantia completa para impressoras com suporte técnico', 12, 'months', 'Fabricante', 'Suporte técnico incluído. Manutenção preventiva coberta. Peças e mão de obra incluídas.', 1, 0, NULL, NULL, '2025-11-12 13:54:39', '2025-11-12 13:54:39');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `warranty_templates_active`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `warranty_templates_active`;
CREATE TABLE IF NOT EXISTS `warranty_templates_active` (
`id` int(11)
,`name` varchar(100)
,`description` text
,`period_value` int(11)
,`period_unit` enum('days','months','years')
,`warranty_provider` varchar(255)
,`warranty_notes` text
,`is_active` tinyint(1)
,`is_deleted` tinyint(1)
,`deleted_at` timestamp
,`deleted_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `warranty_templates_deleted`
-- (Veja abaixo para a visão atual)
--
DROP VIEW IF EXISTS `warranty_templates_deleted`;
CREATE TABLE IF NOT EXISTS `warranty_templates_deleted` (
`id` int(11)
,`name` varchar(100)
,`description` text
,`period_value` int(11)
,`period_unit` enum('days','months','years')
,`warranty_provider` varchar(255)
,`warranty_notes` text
,`is_active` tinyint(1)
,`is_deleted` tinyint(1)
,`deleted_at` timestamp
,`deleted_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_active_warranties`
--
DROP TABLE IF EXISTS `vw_active_warranties`;

DROP VIEW IF EXISTS `vw_active_warranties`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_active_warranties`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`category` AS `category`, `p`.`barcode` AS `barcode`, `p`.`warranty_provider` AS `warranty_provider`, `p`.`warranty_start_date` AS `warranty_start_date`, `p`.`warranty_end_date` AS `warranty_end_date`, `p`.`warranty_period_value` AS `warranty_period_value`, `p`.`warranty_period_unit` AS `warranty_period_unit`, `p`.`has_warranty` AS `has_warranty`, to_days(`p`.`warranty_end_date`) - to_days(curdate()) AS `days_remaining`, `CalculateWarrantyStatus`(`p`.`warranty_end_date`) AS `status` FROM `products` AS `p` WHERE `p`.`has_warranty` = 1 AND `p`.`warranty_end_date` >= curdate() ORDER BY `p`.`warranty_end_date` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_inventory_value`
--
DROP TABLE IF EXISTS `vw_inventory_value`;

DROP VIEW IF EXISTS `vw_inventory_value`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_inventory_value`  AS SELECT `products`.`category` AS `category`, count(0) AS `total_items`, sum(`products`.`quantity`) AS `total_quantity`, avg(`products`.`price`) AS `average_price`, sum(`products`.`quantity` * `products`.`price`) AS `total_value` FROM `products` GROUP BY `products`.`category` ORDER BY sum(`products`.`quantity` * `products`.`price`) DESC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_low_stock`
--
DROP TABLE IF EXISTS `vw_low_stock`;

DROP VIEW IF EXISTS `vw_low_stock`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_low_stock`  AS SELECT `products`.`id` AS `id`, `products`.`name` AS `name`, `products`.`category` AS `category`, `products`.`barcode` AS `barcode`, `products`.`quantity` AS `quantity`, `products`.`min_quantity` AS `min_quantity`, `products`.`max_quantity` AS `max_quantity`, `products`.`price` AS `price`, `products`.`quantity`* `products`.`price` AS `total_value`, CASE WHEN `products`.`quantity` < `products`.`min_quantity` THEN 'Crítico' WHEN `products`.`quantity` < `products`.`min_quantity` * 1.5 THEN 'Baixo' ELSE 'Normal' END AS `stock_status` FROM `products` WHERE `products`.`quantity` <= `products`.`min_quantity` ORDER BY `products`.`quantity` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_warranty_alerts`
--
DROP TABLE IF EXISTS `vw_warranty_alerts`;

DROP VIEW IF EXISTS `vw_warranty_alerts`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_warranty_alerts`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `name`, `p`.`category` AS `category`, `p`.`barcode` AS `barcode`, `p`.`warranty_provider` AS `warranty_provider`, `p`.`warranty_end_date` AS `warranty_end_date`, to_days(`p`.`warranty_end_date`) - to_days(curdate()) AS `days_remaining`, CASE WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) < 0 THEN 'Expirada' WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) <= 7 THEN 'Crítica' WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) <= 30 THEN 'Aviso' ELSE 'OK' END AS `alert_level`, `p`.`updated_at` AS `updated_at` FROM `products` AS `p` WHERE `p`.`has_warranty` = 1 AND `p`.`warranty_end_date` <= curdate() + interval 30 day ORDER BY `p`.`warranty_end_date` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_warranty_status_summary`
--
DROP TABLE IF EXISTS `vw_warranty_status_summary`;

DROP VIEW IF EXISTS `vw_warranty_status_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_warranty_status_summary`  AS SELECT `p`.`id` AS `id`, `p`.`name` AS `product_name`, `p`.`warranty_start_date` AS `warranty_start_date`, `p`.`warranty_end_date` AS `warranty_end_date`, `wt`.`name` AS `template_name`, CASE WHEN `p`.`warranty_end_date` is null THEN 'Sem Garantia' WHEN `p`.`warranty_end_date` < curdate() THEN 'Expirada' WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) <= 7 THEN 'Crítica (7 dias)' WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) <= 30 THEN 'Atenção (30 dias)' ELSE 'Ativa' END AS `warranty_status`, to_days(`p`.`warranty_end_date`) - to_days(curdate()) AS `days_remaining`, CASE WHEN `p`.`warranty_end_date` is null THEN 0 WHEN `p`.`warranty_end_date` < curdate() THEN 4 WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) <= 7 THEN 3 WHEN to_days(`p`.`warranty_end_date`) - to_days(curdate()) <= 30 THEN 2 ELSE 1 END AS `alert_level` FROM (`products` `p` left join `warranty_templates` `wt` on(`p`.`warranty_template_id` = `wt`.`id`)) WHERE `p`.`has_warranty` = 1 ;

-- --------------------------------------------------------

--
-- Estrutura para view `warranty_suppliers_active`
--
DROP TABLE IF EXISTS `warranty_suppliers_active`;

DROP VIEW IF EXISTS `warranty_suppliers_active`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `warranty_suppliers_active`  AS SELECT `warranty_suppliers`.`id` AS `id`, `warranty_suppliers`.`name` AS `name`, `warranty_suppliers`.`cnpj` AS `cnpj`, `warranty_suppliers`.`contact_person` AS `contact_person`, `warranty_suppliers`.`email` AS `email`, `warranty_suppliers`.`phone` AS `phone`, `warranty_suppliers`.`mobile` AS `mobile`, `warranty_suppliers`.`website` AS `website`, `warranty_suppliers`.`address_street` AS `address_street`, `warranty_suppliers`.`address_number` AS `address_number`, `warranty_suppliers`.`address_complement` AS `address_complement`, `warranty_suppliers`.`address_neighborhood` AS `address_neighborhood`, `warranty_suppliers`.`address_city` AS `address_city`, `warranty_suppliers`.`address_state` AS `address_state`, `warranty_suppliers`.`address_postal_code` AS `address_postal_code`, `warranty_suppliers`.`warranty_policy` AS `warranty_policy`, `warranty_suppliers`.`payment_terms` AS `payment_terms`, `warranty_suppliers`.`notes` AS `notes`, `warranty_suppliers`.`is_active` AS `is_active`, `warranty_suppliers`.`is_deleted` AS `is_deleted`, `warranty_suppliers`.`deleted_at` AS `deleted_at`, `warranty_suppliers`.`deleted_by` AS `deleted_by`, `warranty_suppliers`.`created_at` AS `created_at`, `warranty_suppliers`.`updated_at` AS `updated_at` FROM `warranty_suppliers` WHERE `warranty_suppliers`.`is_deleted` = 0 OR `warranty_suppliers`.`is_deleted` is null ;

-- --------------------------------------------------------

--
-- Estrutura para view `warranty_suppliers_deleted`
--
DROP TABLE IF EXISTS `warranty_suppliers_deleted`;

DROP VIEW IF EXISTS `warranty_suppliers_deleted`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `warranty_suppliers_deleted`  AS SELECT `warranty_suppliers`.`id` AS `id`, `warranty_suppliers`.`name` AS `name`, `warranty_suppliers`.`cnpj` AS `cnpj`, `warranty_suppliers`.`contact_person` AS `contact_person`, `warranty_suppliers`.`email` AS `email`, `warranty_suppliers`.`phone` AS `phone`, `warranty_suppliers`.`mobile` AS `mobile`, `warranty_suppliers`.`website` AS `website`, `warranty_suppliers`.`address_street` AS `address_street`, `warranty_suppliers`.`address_number` AS `address_number`, `warranty_suppliers`.`address_complement` AS `address_complement`, `warranty_suppliers`.`address_neighborhood` AS `address_neighborhood`, `warranty_suppliers`.`address_city` AS `address_city`, `warranty_suppliers`.`address_state` AS `address_state`, `warranty_suppliers`.`address_postal_code` AS `address_postal_code`, `warranty_suppliers`.`warranty_policy` AS `warranty_policy`, `warranty_suppliers`.`payment_terms` AS `payment_terms`, `warranty_suppliers`.`notes` AS `notes`, `warranty_suppliers`.`is_active` AS `is_active`, `warranty_suppliers`.`is_deleted` AS `is_deleted`, `warranty_suppliers`.`deleted_at` AS `deleted_at`, `warranty_suppliers`.`deleted_by` AS `deleted_by`, `warranty_suppliers`.`created_at` AS `created_at`, `warranty_suppliers`.`updated_at` AS `updated_at` FROM `warranty_suppliers` WHERE `warranty_suppliers`.`is_deleted` = 1 ;

-- --------------------------------------------------------

--
-- Estrutura para view `warranty_templates_active`
--
DROP TABLE IF EXISTS `warranty_templates_active`;

DROP VIEW IF EXISTS `warranty_templates_active`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `warranty_templates_active`  AS SELECT `warranty_templates`.`id` AS `id`, `warranty_templates`.`name` AS `name`, `warranty_templates`.`description` AS `description`, `warranty_templates`.`period_value` AS `period_value`, `warranty_templates`.`period_unit` AS `period_unit`, `warranty_templates`.`warranty_provider` AS `warranty_provider`, `warranty_templates`.`warranty_notes` AS `warranty_notes`, `warranty_templates`.`is_active` AS `is_active`, `warranty_templates`.`is_deleted` AS `is_deleted`, `warranty_templates`.`deleted_at` AS `deleted_at`, `warranty_templates`.`deleted_by` AS `deleted_by`, `warranty_templates`.`created_at` AS `created_at`, `warranty_templates`.`updated_at` AS `updated_at` FROM `warranty_templates` WHERE `warranty_templates`.`is_deleted` = 0 OR `warranty_templates`.`is_deleted` is null ;

-- --------------------------------------------------------

--
-- Estrutura para view `warranty_templates_deleted`
--
DROP TABLE IF EXISTS `warranty_templates_deleted`;

DROP VIEW IF EXISTS `warranty_templates_deleted`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `warranty_templates_deleted`  AS SELECT `warranty_templates`.`id` AS `id`, `warranty_templates`.`name` AS `name`, `warranty_templates`.`description` AS `description`, `warranty_templates`.`period_value` AS `period_value`, `warranty_templates`.`period_unit` AS `period_unit`, `warranty_templates`.`warranty_provider` AS `warranty_provider`, `warranty_templates`.`warranty_notes` AS `warranty_notes`, `warranty_templates`.`is_active` AS `is_active`, `warranty_templates`.`is_deleted` AS `is_deleted`, `warranty_templates`.`deleted_at` AS `deleted_at`, `warranty_templates`.`deleted_by` AS `deleted_by`, `warranty_templates`.`created_at` AS `created_at`, `warranty_templates`.`updated_at` AS `updated_at` FROM `warranty_templates` WHERE `warranty_templates`.`is_deleted` = 1 ;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `machine_products`
--
ALTER TABLE `machine_products`
  ADD CONSTRAINT `fk_machine_products_machine` FOREIGN KEY (`machine_id`) REFERENCES `ready_machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_machine_products_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`warranty_template_id`) REFERENCES `warranty_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_warranty_supplier_fk` FOREIGN KEY (`warranty_supplier_id`) REFERENCES `warranty_suppliers` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `warehouse_inputs`
--
ALTER TABLE `warehouse_inputs`
  ADD CONSTRAINT `fk_warehouse_inputs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_warehouse_inputs_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `warehouse_movements`
--
ALTER TABLE `warehouse_movements`
  ADD CONSTRAINT `fk_warehouse_movements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_warehouse_movements_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `warehouse_outputs`
--
ALTER TABLE `warehouse_outputs`
  ADD CONSTRAINT `fk_warehouse_outputs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_warehouse_outputs_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `warehouse_warranty_history`
--
ALTER TABLE `warehouse_warranty_history`
  ADD CONSTRAINT `fk_warehouse_warranty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_warehouse_warranty_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `warranty_claims`
--
ALTER TABLE `warranty_claims`
  ADD CONSTRAINT `warranty_claims_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_claims_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `warranty_history`
--
ALTER TABLE `warranty_history`
  ADD CONSTRAINT `warranty_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_history_ibfk_3` FOREIGN KEY (`machine_id`) REFERENCES `ready_machines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warranty_history_ibfk_4` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouse` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
