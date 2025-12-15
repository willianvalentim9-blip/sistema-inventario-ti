<?php
/**
 * Script para adicionar coluna warranty_template_id à tabela warehouse
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once '../../config.php';

try {
    $pdo = getConnection();
    
    echo "🔧 Iniciando migração: adicionar warranty_template_id ao armazém...\n\n";
    
    // SQL para adicionar coluna
    $sql = "
        ALTER TABLE warehouse 
        ADD COLUMN IF NOT EXISTS warranty_template_id INT(11) NULL COMMENT 'ID do template de garantia' AFTER warranty_supplier_id;
    ";
    
    echo "▶ Executando migração...\n";
    $pdo->exec($sql);
    echo "✅ Coluna warranty_template_id adicionada com sucesso!\n\n";
    
    // Verificar coluna adicionada
    echo "🔍 Verificando estrutura atualizada da tabela warehouse...\n";
    $stmt = $pdo->query("DESCRIBE warehouse");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $found = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'warranty_template_id') {
            echo "   ✅ warranty_template_id: {$col['Type']} (NULL: {$col['Null']})\n";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "   ⚠️ Aviso: Coluna não foi encontrada após criação\n";
    }
    
    echo "\n✅ Migração concluída com sucesso!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
