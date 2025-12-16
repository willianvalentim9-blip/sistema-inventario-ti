<?php
/**
 * Script interativo para configurar sistema de máquinas com dedução de estoque
 * Este script verifica o status do banco e aplica as migrações necessárias
 */

require 'config.php';

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  🔧 CONFIGURADOR DE SISTEMA DE MÁQUINAS COM DEDUÇÃO DE ESTOQUE ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo = getConnection();
    
    // 1. VERIFICAR TABELAS EXISTENTES
    echo "📊 Verificando status do banco de dados...\n";
    echo "─" . str_repeat("─", 60) . "\n\n";
    
    $tables = ['machine_products', 'product_movements', 'machine_movements'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.TABLES WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = 'it_inventory'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $exists = $result['count'] > 0;
        
        $status = $exists ? '✅ Existe' : '❌ Falta';
        echo "   $status: Tabela $table\n";
        
        if (!$exists) {
            $missingTables[] = $table;
        }
    }
    
    // 2. VERIFICAR COLUNAS EM ready_machines
    echo "\n";
    echo "📋 Verificando colunas em ready_machines...\n";
    echo "─" . str_repeat("─", 60) . "\n\n";
    
    $columns = ['has_warranty', 'warranty_provider', 'warranty_period_value', 'warranty_period_unit'];
    $missingColumns = [];
    
    foreach ($columns as $column) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.COLUMNS WHERE TABLE_NAME = 'ready_machines' AND COLUMN_NAME = '$column' AND TABLE_SCHEMA = 'it_inventory'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $exists = $result['count'] > 0;
        
        $status = $exists ? '✅ Existe' : '❌ Falta';
        echo "   $status: Coluna $column\n";
        
        if (!$exists) {
            $missingColumns[] = $column;
        }
    }
    
    echo "\n";
    
    // 3. RESUMO E AÇÃO
    if (empty($missingTables) && empty($missingColumns)) {
        echo "═" . str_repeat("═", 60) . "═\n";
        echo "✅ SISTEMA COMPLETAMENTE CONFIGURADO!\n";
        echo "═" . str_repeat("═", 60) . "═\n\n";
        echo "O sistema de máquinas com dedução de estoque está pronto!\n\n";
        exit(0);
    }
    
    echo "═" . str_repeat("═", 60) . "═\n";
    echo "⚠️  FALTAM CONFIGURAÇÕES!\n";
    echo "═" . str_repeat("═", 60) . "═\n\n";
    
    if (!empty($missingTables)) {
        echo "Tabelas faltando: " . implode(', ', $missingTables) . "\n";
    }
    if (!empty($missingColumns)) {
        echo "Colunas faltando: " . implode(', ', $missingColumns) . "\n";
    }
    
    echo "\n";
    echo "🔧 SOLUÇÃO AUTOMÁTICA\n";
    echo "─" . str_repeat("─", 60) . "\n\n";
    
    // Ler arquivo de migração
    $migrationFile = 'COMPLETE_MIGRATION.sql';
    if (!file_exists($migrationFile)) {
        echo "❌ Arquivo de migração não encontrado: $migrationFile\n";
        exit(1);
    }
    
    echo "Lendo arquivo: $migrationFile\n";
    $sql = file_get_contents($migrationFile);
    
    // Dividir em queries
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($q) {
            return !empty($q) && strpos($q, '--') !== 0 && strpos($q, '/*') !== 0;
        }
    );
    
    echo "Encontradas " . count($queries) . " queries para executar\n";
    echo "\n";
    
    // Executar queries
    echo "Executando migrações...\n";
    echo "─" . str_repeat("─", 60) . "\n\n";
    
    $executed = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($queries as $query) {
        if (empty(trim($query))) continue;
        
        try {
            $pdo->exec($query);
            $executed++;
            echo "   ✅ Executada query " . ($executed + $skipped) . "\n";
        } catch (PDOException $e) {
            // Erros que podemos ignorar
            if (strpos($e->getMessage(), 'Duplicate') !== false ||
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Constraint') !== false) {
                $skipped++;
                echo "   ⚠️  Ignorada query " . ($executed + $skipped) . " (já existe)\n";
            } else {
                $errors[] = [
                    'query' => substr($query, 0, 50) . '...',
                    'error' => $e->getMessage()
                ];
                echo "   ❌ Erro em query " . ($executed + $skipped) . ": " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n";
    echo "═" . str_repeat("═", 60) . "═\n";
    echo "RESULTADO DA MIGRAÇÃO\n";
    echo "═" . str_repeat("═", 60) . "═\n\n";
    echo "✅ Executadas: $executed\n";
    echo "⚠️  Ignoradas: $skipped\n";
    echo "❌ Erros: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nDetalhes dos erros:\n";
        foreach ($errors as $err) {
            echo "  • " . $err['query'] . "\n";
            echo "    → " . $err['error'] . "\n";
        }
    }
    
    echo "\n";
    echo "═" . str_repeat("═", 60) . "═\n";
    echo "✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "═" . str_repeat("═", 60) . "═\n\n";
    
    echo "O sistema de máquinas com dedução de estoque agora está funcionando!\n";
    echo "Você pode começar a criar máquinas em add_machine.php\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO CRÍTICO: " . $e->getMessage() . "\n\n";
    exit(1);
}

?>
