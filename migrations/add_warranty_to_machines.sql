-- ============================================
-- MIGRAÇÃO: ADICIONAR CAMPOS DE GARANTIA A ready_machines
-- ============================================
-- Este script adiciona suporte a garantia para máquinas
-- executando: ALTER TABLE ready_machines

ALTER TABLE `ready_machines` ADD COLUMN `has_warranty` TINYINT(1) DEFAULT 0 AFTER `windows_11_compatible`;
ALTER TABLE `ready_machines` ADD COLUMN `warranty_provider` VARCHAR(255) DEFAULT NULL AFTER `has_warranty`;
ALTER TABLE `ready_machines` ADD COLUMN `warranty_period_value` INT(11) DEFAULT NULL AFTER `warranty_provider`;
ALTER TABLE `ready_machines` ADD COLUMN `warranty_period_unit` VARCHAR(50) DEFAULT 'months' AFTER `warranty_period_value`;

-- Verificação (listar as novas colunas)
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ready_machines' AND COLUMN_NAME LIKE 'warranty%' OR COLUMN_NAME = 'has_warranty';
