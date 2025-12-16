<?php
/**
 * Script para executar todas as migrações pendentes
 * Cria as tabelas necessárias para funcionalidades de máquinas e garantias
 */

require 'config.php';

try {
    $pdo = getConnection();
    
    echo "🔧 Iniciando execução de migrações...\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    // Lista de migrações na ordem correta
    $migrations = [
        'migrations/add_warranty_to_machines.sql' => 'Adicionar campos de garantia a ready_machines',
        'migrations/2025-12-02-machine-products-table.sql' => 'Criar tabela machine_products (relacionamento máquina-produtos)',
        'migrations/2025-11-28-soft-delete-and-low-stock.sql' => 'Adicionar soft delete e alertas de estoque baixo',
        'migrations/2025-12-01-warranty-history-table.sql' => 'Criar tabela warranty_history',
        'migrations/2025-11-01-warranty-machines.sql' => 'Adicionar campos de garantia a ready_machines (backup)',
    ];
    
    foreach ($migrations as $filePath => $description) {
        // Verifica se arquivo existe
        if (!file_exists($filePath)) {
            echo "⏭️  SKIPPED: $description\n";
            echo "   ⚠️  Arquivo não encontrado: $filePath\n\n";
            continue;
        }
        
        echo "📋 Executando: $description\n";
        echo "   📁 Arquivo: $filePath\n";
        
        $sql = file_get_contents($filePath);
        
        // Remove comentários e divide por ;
        $queries = array_filter(
            array_map('trim', explode(';', $sql)),
            function($q) {
                return !empty($q) && strpos($q, '--') !== 0 && strpos($q, '/*') !== 0;
            }
        );
        
        $executedCount = 0;
        $skippedCount = 0;
        
        foreach ($queries as $query) {
            if (empty(trim($query))) continue;
            
            try {
                $pdo->exec($query);
                $executedCount++;
            } catch (PDOException $e) {
                // Ignora erros de "column already exists" e "table already exists"
                if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false ||
                    strpos($e->getMessage(), 'Constraint') !== false) {
                    $skippedCount++;
                } else {
                    throw $e;
                }
            }
        }
        
        echo "   ✅ Executadas: $executedCount comando(s)\n";
        if ($skippedCount > 0) {
            echo "   ⚠️  Puladas: $skippedCount comando(s) (já existem)\n";
        }
        echo "\n";
    }
    
    echo "=" . str_repeat("=", 60) . "\n";
    echo "✅ MIGRAÇÕES CONCLUÍDAS COM SUCESSO!\n\n";
    
    // Verifica tabelas criadas
    echo "📊 Verificando tabelas:\n";
    $tables = ['machine_products', 'warranty_history', 'ready_machines', 'products'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = 'it_inventory'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $exists = $result['count'] > 0 ? '✅' : '❌';
        echo "   $exists Tabela: $table\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

?>
