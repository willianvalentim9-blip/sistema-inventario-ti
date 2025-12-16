<?php
require 'config.php';

try {
    $pdo = getConnection();
    
    // Lê o arquivo de migração
    $sql = file_get_contents('migrations/add_warranty_to_machines.sql');
    
    // Executa cada comando SQL
    $queries = explode(';', $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && strpos($query, '--') !== 0) {
            try {
                $pdo->exec($query);
                echo "✓ Executado: " . substr($query, 0, 50) . "...\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "⚠ Coluna já existe (OK)\n";
                } else {
                    echo "✗ Erro: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Migração concluída!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
