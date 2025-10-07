-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 25/09/2025 às 15:20
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `machine_inputs`
--

CREATE TABLE `machine_inputs` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_added` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `input_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `machine_name` varchar(255) DEFAULT NULL,
  `machine_serial_number` varchar(100) DEFAULT NULL,
  `machine_cost_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `machine_inputs`
--

INSERT INTO `machine_inputs` (`id`, `machine_id`, `user_id`, `quantity_added`, `reason`, `details`, `input_date`, `machine_name`, `machine_serial_number`, `machine_cost_price`) VALUES
(5, 6, 1, 10, 'Compra de Fornecedor', 'Compra de Fornecedor', '2025-09-25 12:44:40', 'we', NULL, 100.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `machine_movements`
--

CREATE TABLE `machine_movements` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_type` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `machine_movements`
--

INSERT INTO `machine_movements` (`id`, `machine_id`, `user_id`, `movement_type`, `old_status`, `new_status`, `details`, `movement_date`) VALUES
(11, 6, 1, 'entrada', 'available', 'available', 'Compra de Fornecedor', '2025-09-25 12:44:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `machine_outputs`
--

CREATE TABLE `machine_outputs` (
  `id` int(11) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_removed` int(11) NOT NULL DEFAULT 1,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `output_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `machine_name` varchar(255) DEFAULT NULL,
  `machine_serial_number` varchar(100) DEFAULT NULL,
  `final_sale_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `machine_outputs`
--

INSERT INTO `machine_outputs` (`id`, `machine_id`, `user_id`, `quantity_removed`, `reason`, `details`, `output_date`, `machine_name`, `machine_serial_number`, `final_sale_price`) VALUES
(1, 4, 1, 1, 'Descarte', '', '2025-09-24 20:49:41', 'ewqrqwer', 'rweqrqw', NULL),
(2, 4, 1, 1, 'Venda', '', '2025-09-24 20:49:44', 'ewqrqwer', 'rweqrqw', 100.00),
(3, 4, 1, 1, 'Descarte', '', '2025-09-24 20:51:25', 'ewqrqwer', 'rweqrqw', NULL),
(4, 4, 1, 1, 'Descarte', '', '2025-09-24 20:51:57', 'ewqrqwer', 'rweqrqw', NULL),
(5, 5, 1, 1, 'Uso Interno', 'fgsgdf', '2025-09-25 11:48:12', 'TESTE', 'qweqwe', NULL),
(6, 5, 1, 1, 'Venda', '', '2025-09-25 12:32:39', 'TESTE', 'qweqwe', 10.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
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
  `price` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `category`, `manufacturer`, `model`, `sku`, `barcode`, `qr_code`, `serial_number`, `quantity`, `min_quantity`, `max_quantity`, `price`, `location`, `status`, `image`, `notes`, `created_at`, `updated_at`) VALUES
(9, 'qwerqwer', 'qrwe', 'Case', 'erqwreqw', 'qerqwer', NULL, 'IT-CASE-1758805254-1988', NULL, NULL, 12, 5, 100, 1000.00, NULL, 'available', NULL, NULL, '2025-09-25 13:00:54', '2025-09-25 13:18:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_inputs`
--

CREATE TABLE `product_inputs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_added` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `input_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_name` varchar(255) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `product_serial_number` varchar(100) DEFAULT NULL,
  `product_barcode` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_inputs`
--

INSERT INTO `product_inputs` (`id`, `product_id`, `user_id`, `quantity_added`, `reason`, `details`, `unit_price`, `input_date`, `product_name`, `product_category`, `product_serial_number`, `product_barcode`) VALUES
(1, 5, 1, 1, 'Ajuste de Inventário', '', NULL, '2025-09-24 20:23:52', 'weqqw', 'SSD', NULL, 'IT-SSD-1758742030-2051'),
(2, 5, 1, 1, 'Compra de Fornecedor', '', NULL, '2025-09-24 20:23:57', 'weqqw', 'SSD', NULL, 'IT-SSD-1758742030-2051'),
(3, 6, 1, 20, 'Compra de Fornecedor', '', NULL, '2025-09-24 20:50:45', '12313', 'GPU', NULL, 'IT-GPU-1758745897-6019'),
(4, 6, 1, 2, 'Compra de Fornecedor', '', NULL, '2025-09-24 20:53:41', '12313', 'GPU', NULL, 'IT-GPU-1758745897-6019'),
(5, 6, 1, 3, 'Ajuste de Inventário', '', NULL, '2025-09-25 11:46:42', '12313', 'GPU', 'AA2131', 'IT-GPU-1758745897-6019'),
(6, 9, 1, 1, 'Compra de Fornecedor', '', NULL, '2025-09-25 13:01:05', 'qwerqwer', 'Case', NULL, 'IT-CASE-1758805254-1988'),
(7, 9, 1, 1, 'Compra de Fornecedor', '', NULL, '2025-09-25 13:13:42', 'qwerqwer', 'Case', NULL, 'IT-CASE-1758805254-1988'),
(8, 9, 1, 1, 'Compra de Fornecedor', '', NULL, '2025-09-25 13:18:26', 'qwerqwer', 'Case', NULL, 'IT-CASE-1758805254-1988');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_movements`
--

CREATE TABLE `product_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `movement_type` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_movements`
--

INSERT INTO `product_movements` (`id`, `product_id`, `user_id`, `movement_type`, `quantity`, `previous_quantity`, `new_quantity`, `reason`, `movement_date`) VALUES
(25, 9, 1, 'entrada', 10, 0, 10, 'Produto criado: qwerqwer', '2025-09-25 13:00:54'),
(26, 9, 1, 'saida', 1, 10, 9, 'Venda - ', '2025-09-25 13:00:59'),
(27, 9, 1, 'entrada', 1, 9, 10, 'Compra de Fornecedor', '2025-09-25 13:01:05'),
(28, 9, 1, 'entrada', 1, 10, 11, 'Compra de Fornecedor', '2025-09-25 13:13:42'),
(29, 9, 1, 'entrada', 1, 11, 12, 'Compra de Fornecedor', '2025-09-25 13:18:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `product_outputs`
--

CREATE TABLE `product_outputs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantity_removed` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `output_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_name` varchar(255) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `product_serial_number` varchar(100) DEFAULT NULL,
  `product_barcode` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `product_outputs`
--

INSERT INTO `product_outputs` (`id`, `product_id`, `user_id`, `quantity_removed`, `reason`, `details`, `unit_price`, `output_date`, `product_name`, `product_category`, `product_serial_number`, `product_barcode`) VALUES
(1, 2, 1, 2, 'Uso Interno', '', NULL, '2025-09-24 19:06:28', 'dsFAASDF', 'PSU', 'SADFAS', '1758740313263'),
(2, 5, 1, 1, 'Venda', '', NULL, '2025-09-24 20:13:17', 'weqqw', 'SSD', NULL, 'IT-SSD-1758742030-2051'),
(3, 6, 1, 1, 'Venda', '', NULL, '2025-09-24 20:50:50', '12313', 'GPU', NULL, 'IT-GPU-1758745897-6019'),
(4, 6, 1, 20, 'Perda ou Roubo', '', NULL, '2025-09-24 20:53:23', '12313', 'GPU', NULL, 'IT-GPU-1758745897-6019'),
(5, 6, 1, 1, 'Venda', '', NULL, '2025-09-25 11:46:34', '12313', 'GPU', 'AA2131', 'IT-GPU-1758745897-6019'),
(6, 7, 1, 1, 'Venda', 'Cliente/Depto: falo de tal', 100.00, '2025-09-25 12:33:16', 'twqr4w', 'GPU', 'wrtw', '1758803583918'),
(7, 9, 1, 1, 'Venda', '', 1000.00, '2025-09-25 13:00:59', 'qwerqwer', 'Case', NULL, 'IT-CASE-1758805254-1988');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ready_machines`
--

CREATE TABLE `ready_machines` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `processor` varchar(255) DEFAULT NULL,
  `memory` varchar(255) DEFAULT NULL,
  `storage` varchar(255) DEFAULT NULL,
  `graphics` varchar(255) DEFAULT NULL,
  `motherboard` varchar(255) DEFAULT NULL,
  `power_supply` varchar(255) DEFAULT NULL,
  `case_type` varchar(255) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ready_machines`
--

INSERT INTO `ready_machines` (`id`, `name`, `description`, `processor`, `memory`, `storage`, `graphics`, `motherboard`, `power_supply`, `case_type`, `specifications`, `sale_price`, `cost_price`, `serial_number`, `barcode`, `qr_code`, `quantity`, `status`, `location`, `image`, `notes`, `windows_10_compatible`, `windows_11_compatible`, `created_at`, `updated_at`) VALUES
(6, 'we', 'wqe', '', '', '', '', '', '', '', '', 100.00, 100.00, NULL, NULL, NULL, 20, 'available', NULL, NULL, NULL, 0, 0, '2025-09-25 12:44:15', '2025-09-25 12:44:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `timestamp`) VALUES
(1, 1, 'login', 'Usuário \'admin\' fez login no sistema', '::1', '2025-09-24 18:57:44'),
(2, 1, 'login', 'Usuário \'admin\' fez login no sistema', '::1', '2025-09-24 20:30:50'),
(3, 1, 'login', 'Usuário \'admin\' fez login no sistema', '::1', '2025-09-24 20:35:47');

-- --------------------------------------------------------

--
-- Estrutura para tabela `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Sistema de Estoque TI', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(2, 'company_name', 'Sua Empresa', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(3, 'company_slogan', 'Sistema completo de controle de estoque de TI', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(4, 'company_logo', '', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(5, 'theme', 'light', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(6, 'backup_frequency', 'daily', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(7, 'low_stock_alert', '5', '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(8, 'system_version', '1.0', '2025-09-24 18:57:20', '2025-09-24 18:57:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'user',
  `full_name` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin123', 'admin@sistema.com', 'admin', 'Administrador do Sistema', NULL, '2025-09-24 18:57:20', '2025-09-24 20:35:47'),
(2, 'user', 'user123', 'user@sistema.com', 'user', 'Usuário Padrão', NULL, '2025-09-24 18:57:20', '2025-09-24 18:57:20'),
(3, 'felipe', 'abc,123', 'suporte02@supportti.net', 'user', 'felipe', NULL, '2025-09-24 20:54:48', '2025-09-24 20:55:02');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `machine_inputs`
--
ALTER TABLE `machine_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine_id` (`machine_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `machine_movements`
--
ALTER TABLE `machine_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `machine_id` (`machine_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `machine_outputs`
--
ALTER TABLE `machine_outputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD UNIQUE KEY `serial_number` (`serial_number`);

--
-- Índices de tabela `product_inputs`
--
ALTER TABLE `product_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `product_movements`
--
ALTER TABLE `product_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `product_outputs`
--
ALTER TABLE `product_outputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `ready_machines`
--
ALTER TABLE `ready_machines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD UNIQUE KEY `qr_code` (`qr_code`);

--
-- Índices de tabela `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `machine_inputs`
--
ALTER TABLE `machine_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `machine_movements`
--
ALTER TABLE `machine_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `machine_outputs`
--
ALTER TABLE `machine_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `product_inputs`
--
ALTER TABLE `product_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `product_movements`
--
ALTER TABLE `product_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `product_outputs`
--
ALTER TABLE `product_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `ready_machines`
--
ALTER TABLE `ready_machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `machine_inputs_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `ready_machines` (`id`) ON DELETE CASCADE,
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
-- Restrições para tabelas `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
