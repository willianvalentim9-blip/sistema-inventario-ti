<?php
/**
 * Script para adicionar campos de garantia à tabela warehouse
 * Execute uma vez para atualizar a estrutura do banco
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'config.php';

try {
    $pdo = getConnection();
    
    echo "🔧 Iniciando migração de campos de garantia no armazém...\n\n";
    
    // SQL para adicionar colunas
    $sql = "
        ALTER TABLE warehouse 
        ADD COLUMN IF NOT EXISTS warranty_client_name VARCHAR(255) NULL COMMENT 'Nome do cliente da garantia' AFTER warranty_end_date,
        ADD COLUMN IF NOT EXISTS warranty_ticket_number VARCHAR(100) NULL COMMENT 'Número do ticket da garantia' AFTER warranty_client_name,
        ADD COLUMN IF NOT EXISTS warranty_label VARCHAR(100) NULL COMMENT 'Etiqueta/código da garantia' AFTER warranty_ticket_number,
        ADD COLUMN IF NOT EXISTS warranty_supplier_id INT(11) NULL COMMENT 'ID do fornecedor de garantia' AFTER warranty_label,
        ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) NULL COMMENT 'Número da nota fiscal' AFTER warranty_supplier_id;
    ";
    
    echo "▶ Executando migração...\n";
    $pdo->exec($sql);
    echo "✅ Migração executada com sucesso!\n\n";
    
    // Verificar colunas adicionadas
    echo "🔍 Verificando estrutura atualizada da tabela warehouse...\n";
    $stmt = $pdo->query("DESCRIBE warehouse");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $warranty_cols = array_filter($columns, function($col) {
        return strpos($col['Field'], 'warranty') !== false || $col['Field'] === 'invoice_number';
    });
    
    echo "\n📋 Colunas relacionadas a garantia:\n";
    foreach ($warranty_cols as $col) {
        echo "   - {$col['Field']}: {$col['Type']} (NULL: {$col['Null']})\n";
    }
    
    echo "\n✅ Migração concluída com sucesso!\n";
    echo "A tabela warehouse agora possui todos os campos necessários para edição de garantias.\n";
    
} catch (PDOException $e) {
    echo "❌ Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
