<?php
/**
 * SCRIPT DE MIGRAÇÃO: ADICIONAR CAMPOS DE GARANTIA À TABELA ready_machines
 * 
 * Este script adiciona as colunas necessárias para suportar garantia em máquinas
 * Cols adicionadas: has_warranty, warranty_provider, warranty_period_value, warranty_period_unit
 */

require_once '../../config.php';

header('Content-Type: application/json');

try {
    $pdo = getConnection();
    
    // Verificar se as colunas já existem
    $stmt = $pdo->query("SHOW COLUMNS FROM ready_machines WHERE Field IN ('has_warranty', 'warranty_provider', 'warranty_period_value', 'warranty_period_unit')");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($existing_columns) === 4) {
        echo json_encode([
            'success' => true,
            'message' => 'As colunas de garantia já existem na tabela ready_machines'
        ]);
        exit;
    }
    
    // Executar ALTER TABLE para adicionar as colunas
    $sql = "ALTER TABLE ready_machines ADD COLUMN (
        `has_warranty` tinyint(1) DEFAULT 0,
        `warranty_provider` varchar(255) DEFAULT NULL,
        `warranty_period_value` int(11) DEFAULT NULL,
        `warranty_period_unit` varchar(50) DEFAULT 'months'
    )";
    
    $pdo->exec($sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Colunas de garantia adicionadas com sucesso à tabela ready_machines!',
        'columns_added' => [
            'has_warranty',
            'warranty_provider',
            'warranty_period_value',
            'warranty_period_unit'
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao adicionar colunas: ' . $e->getMessage()
    ]);
}
?>
