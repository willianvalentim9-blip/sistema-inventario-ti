<?php
/**
 * PÁGINA DE SETUP: ADICIONAR CAMPOS DE GARANTIA À TABELA ready_machines
 * 
 * Acesso: Abrir em navegador ou executar via curl
 * URL: http://localhost/sistema4/setup_warranty_machines.php
 */

require_once 'config.php';

// Verificar autenticação (apenas para segurança)
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acesso negado. Por favor, faça login primeiro.');
}

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    // 1. Verificar se as colunas já existem
    $stmt = $pdo->query("SHOW COLUMNS FROM ready_machines");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    $warranty_columns = ['has_warranty', 'warranty_provider', 'warranty_period_value', 'warranty_period_unit'];
    $missing_columns = array_diff($warranty_columns, $existing_columns);
    
    if (empty($missing_columns)) {
        echo json_encode([
            'success' => true,
            'status' => 'complete',
            'message' => 'As colunas de garantia já existem na tabela ready_machines',
            'columns_verified' => $warranty_columns
        ]);
        exit;
    }
    
    // 2. Adicionar colunas faltantes
    $migration_log = [];
    
    if (in_array('has_warranty', $missing_columns)) {
        $pdo->exec("ALTER TABLE `ready_machines` ADD COLUMN `has_warranty` TINYINT(1) DEFAULT 0 AFTER `windows_11_compatible`");
        $migration_log[] = '✓ Coluna has_warranty adicionada';
    }
    
    if (in_array('warranty_provider', $missing_columns)) {
        $pdo->exec("ALTER TABLE `ready_machines` ADD COLUMN `warranty_provider` VARCHAR(255) DEFAULT NULL AFTER `has_warranty`");
        $migration_log[] = '✓ Coluna warranty_provider adicionada';
    }
    
    if (in_array('warranty_period_value', $missing_columns)) {
        $pdo->exec("ALTER TABLE `ready_machines` ADD COLUMN `warranty_period_value` INT(11) DEFAULT NULL AFTER `warranty_provider`");
        $migration_log[] = '✓ Coluna warranty_period_value adicionada';
    }
    
    if (in_array('warranty_period_unit', $missing_columns)) {
        $pdo->exec("ALTER TABLE `ready_machines` ADD COLUMN `warranty_period_unit` VARCHAR(50) DEFAULT 'months' AFTER `warranty_period_value`");
        $migration_log[] = '✓ Coluna warranty_period_unit adicionada';
    }
    
    // 3. Verificar se as colunas foram criadas com sucesso
    $stmt = $pdo->query("SHOW COLUMNS FROM ready_machines WHERE Field IN ('has_warranty', 'warranty_provider', 'warranty_period_value', 'warranty_period_unit')");
    $verified_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $verified_columns[] = $row['Field'];
    }
    
    if (count($verified_columns) === 4) {
        // Log no banco de dados
        if (logSystemAction('SETUP', 'Colunas de garantia adicionadas à tabela ready_machines')) {
            $migration_log[] = '✓ Ação registrada no log do sistema';
        }
        
        echo json_encode([
            'success' => true,
            'status' => 'success',
            'message' => 'Colunas de garantia adicionadas com sucesso!',
            'migration_log' => $migration_log,
            'columns_added' => $verified_columns
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'status' => 'partial',
            'message' => 'Algumas colunas não foram verificadas após criação',
            'migration_log' => $migration_log,
            'verified_columns' => $verified_columns
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Erro ao adicionar colunas de garantia: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Erro inesperado: ' . $e->getMessage()
    ]);
}
?>
