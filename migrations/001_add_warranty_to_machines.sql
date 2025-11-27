-- =================================================================
-- SCRIPT DE MIGRAÇÃO: ADICIONAR SUPORTE A GARANTIA EM MÁQUINAS
-- =================================================================
-- Propósito: Adicionar colunas de garantia à tabela ready_machines
-- Banco de dados: it_inventory
-- Data: 2024
-- =================================================================

USE `it_inventory`;

-- ============================================
-- 1. Verificar e adicionar coluna has_warranty
-- ============================================
ALTER TABLE `ready_machines` ADD COLUMN IF NOT EXISTS `has_warranty` TINYINT(1) DEFAULT 0 AFTER `windows_11_compatible`;

-- ==========================================
-- 2. Adicionar coluna warranty_provider
-- ==========================================
ALTER TABLE `ready_machines` ADD COLUMN IF NOT EXISTS `warranty_provider` VARCHAR(255) DEFAULT NULL AFTER `has_warranty`;

-- ===========================================
-- 3. Adicionar coluna warranty_period_value
-- ===========================================
ALTER TABLE `ready_machines` ADD COLUMN IF NOT EXISTS `warranty_period_value` INT(11) DEFAULT NULL AFTER `warranty_provider`;

-- ==========================================
-- 4. Adicionar coluna warranty_period_unit
-- ==========================================
ALTER TABLE `ready_machines` ADD COLUMN IF NOT EXISTS `warranty_period_unit` VARCHAR(50) DEFAULT 'months' AFTER `warranty_period_value`;

-- ==========================================
-- 5. Verificação final
-- ==========================================
-- Descomente a linha abaixo para verificar:
-- SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ready_machines' AND (COLUMN_NAME LIKE 'warranty%' OR COLUMN_NAME = 'has_warranty') ORDER BY ORDINAL_POSITION;
