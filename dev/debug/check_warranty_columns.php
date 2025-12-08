<?php
require_once 'config.php';

try {
    $pdo = getConnection();
    $stmt = $pdo->query('DESCRIBE warehouse');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas da tabela warehouse:\n";
    echo "=====================================\n";
    foreach($columns as $col) {
        if(strpos($col['Field'], 'warranty') !== false || $col['Field'] === 'invoice_number') {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ") NULL:" . ($col['Null'] === 'YES' ? 'SIM' : 'NÃO') . "\n";
        }
    }
    echo "\n\nTodas as colunas:\n";
    echo "=====================================\n";
    foreach($columns as $col) {
        echo "- " . $col['Field'] . "\n";
    }
} catch(Exception $e) {
    echo 'Erro: ' . $e->getMessage();
}
?>
