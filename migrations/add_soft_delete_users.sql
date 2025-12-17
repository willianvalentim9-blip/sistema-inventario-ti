-- ========================================
-- MIGRATION: Add Soft Delete Support to Users Table
-- ========================================
-- Adiciona colunas is_deleted e deleted_at à tabela users
-- para implementar soft delete (exclusão lógica)
-- Data: 2025-12-16

-- Verifica se as colunas já existem antes de adicionar
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN DEFAULT FALSE COMMENT 'Marca usuário como deletado (soft delete)',
ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL COMMENT 'Data e hora da deleção';

-- Cria índice para queries mais rápidas
ALTER TABLE users ADD INDEX idx_is_deleted (is_deleted);
ALTER TABLE users ADD INDEX idx_deleted_at (deleted_at);

-- ========================================
-- ROLLBACK (se necessário)
-- ========================================
-- ALTER TABLE users DROP COLUMN is_deleted;
-- ALTER TABLE users DROP COLUMN deleted_at;
-- ALTER TABLE users DROP INDEX idx_is_deleted;
-- ALTER TABLE users DROP INDEX idx_deleted_at;
