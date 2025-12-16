<?php
/**
 * Migration: Adicionar suporte para máquinas e armazém no histórico de garantia
 * 
 * Adiciona as colunas machine_id e warehouse_id à tabela warranty_history
 */

require 'config.php';
requireLogin();

// Apenas admin pode executar
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['flash_message'] = 'Apenas administradores podem executar migrações';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$pdo = getConnection();

try {
    // Verificar se as colunas já existem
    $stmt = $pdo->query("SHOW COLUMNS FROM warranty_history LIKE 'machine_id'");
    $machine_id_exists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM warranty_history LIKE 'warehouse_id'");
    $warehouse_id_exists = $stmt->rowCount() > 0;
    
    $changes = [];
    
    // Adicionar machine_id se não existir
    if (!$machine_id_exists) {
        $pdo->exec("
            ALTER TABLE warranty_history 
            ADD COLUMN machine_id INT DEFAULT NULL COMMENT 'ID da máquina' AFTER product_id,
            ADD INDEX idx_machine_id (machine_id),
            ADD INDEX idx_machine_date (machine_id, created_at)
        ");
        $changes[] = '✓ Coluna machine_id adicionada';
    }
    
    // Adicionar warehouse_id se não existir
    if (!$warehouse_id_exists) {
        $pdo->exec("
            ALTER TABLE warranty_history 
            ADD COLUMN warehouse_id INT DEFAULT NULL COMMENT 'ID do item de armazém' AFTER machine_id,
            ADD INDEX idx_warehouse_id (warehouse_id),
            ADD INDEX idx_warehouse_date (warehouse_id, created_at)
        ");
        $changes[] = '✓ Coluna warehouse_id adicionada';
    }
    
    // Tornar product_id opcional (mudando de NOT NULL para NULL)
    if (!$machine_id_exists || !$warehouse_id_exists) {
        // Verificar se product_id é NOT NULL
        $stmt = $pdo->query("SHOW COLUMNS FROM warranty_history WHERE Field = 'product_id'");
        $column_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column_info && strpos($column_info['Null'], 'NO') !== false) {
            $pdo->exec("
                ALTER TABLE warranty_history 
                MODIFY COLUMN product_id INT DEFAULT NULL COMMENT 'ID do produto'
            ");
            $changes[] = '✓ Coluna product_id modificada para opcional';
        }
    }
    
    if (!empty($changes)) {
        $_SESSION['flash_message'] = 'Migração concluída com sucesso!<br>' . implode('<br>', $changes);
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'As colunas já existem. Nenhuma migração necessária.';
        $_SESSION['flash_type'] = 'info';
    }
    
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Erro na migração: ' . htmlspecialchars($e->getMessage());
    $_SESSION['flash_type'] = 'danger';
    error_log("Erro na migração de warranty_history: " . $e->getMessage());
}

header('Location: warranties.php');
exit;
?>
