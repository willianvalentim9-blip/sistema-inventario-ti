<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config.php';

try {
    $pdo = getConnection();
    
    // Verificar se a coluna warranty_notes existe
    $result = $pdo->query("DESCRIBE warehouse");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<pre>";
    echo "Colunas atuais na tabela warehouse:\n";
    print_r($columns);
    echo "\n\n";
    
    // Adicionar warranty_supplier_id se não existir
    if (!in_array('warranty_supplier_id', $columns)) {
        echo "Adicionando coluna warranty_supplier_id...\n";
        $pdo->exec("ALTER TABLE warehouse ADD COLUMN warranty_supplier_id INT NULL AFTER warranty_end_date");
        echo "✅ Coluna warranty_supplier_id adicionada\n\n";
    } else {
        echo "✓ Coluna warranty_supplier_id já existe\n\n";
    }
    
    // Adicionar warranty_notes se não existir
    if (!in_array('warranty_notes', $columns)) {
        echo "Adicionando coluna warranty_notes...\n";
        $pdo->exec("ALTER TABLE warehouse ADD COLUMN warranty_notes LONGTEXT NULL AFTER warranty_supplier_id");
        echo "✅ Coluna warranty_notes adicionada\n\n";
    } else {
        echo "✓ Coluna warranty_notes já existe\n\n";
    }
    
    // Adicionar warranty_start_date e warranty_end_date se não existirem
    if (!in_array('warranty_start_date', $columns)) {
        echo "Adicionando coluna warranty_start_date...\n";
        $pdo->exec("ALTER TABLE warehouse ADD COLUMN warranty_start_date DATE NULL AFTER warranty_period_years");
        echo "✅ Coluna warranty_start_date adicionada\n\n";
    } else {
        echo "✓ Coluna warranty_start_date já existe\n\n";
    }
    
    if (!in_array('warranty_end_date', $columns)) {
        echo "Adicionando coluna warranty_end_date...\n";
        $pdo->exec("ALTER TABLE warehouse ADD COLUMN warranty_end_date DATE NULL AFTER warranty_start_date");
        echo "✅ Coluna warranty_end_date adicionada\n\n";
    } else {
        echo "✓ Coluna warranty_end_date já existe\n\n";
    }
    
    // Verificar novamente
    $result = $pdo->query("DESCRIBE warehouse");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "Colunas finais na tabela warehouse:\n";
    print_r($columns);
    echo "\n\n";
    
    echo "✅ Migração concluída com sucesso!\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre>";
    echo "❌ Erro: " . $e->getMessage();
    echo "</pre>";
}
?>
